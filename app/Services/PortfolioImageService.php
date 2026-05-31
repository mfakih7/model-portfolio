<?php

namespace App\Services;

use App\Exceptions\PortfolioImageProcessingException;
use App\Models\PortfolioImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

/**
 * Generates optimized portfolio image variants using Intervention Image.
 */
class PortfolioImageService
{
    public const MAX_DIMENSION = 6000;

    private const DISK = 'public';

    private const BASE_DIR = 'portfolio';

    private const VARIANT_NAMES = ['original', 'large', 'medium', 'thumb'];

    private int $originalMaxDimension = 2400;

    private int $originalQuality = 88;

    /**
     * @var array<string, array{width:int, height:?int, crop:bool, quality:int}>
     */
    private array $variants = [
        'large' => ['width' => 1600, 'height' => null, 'crop' => false, 'quality' => 82],
        'medium' => ['width' => 900, 'height' => null, 'crop' => false, 'quality' => 80],
        'thumb' => ['width' => 500, 'height' => null, 'crop' => false, 'quality' => 78],
    ];

    private ?ImageManager $manager = null;

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function generate(UploadedFile $file): array
    {
        $this->prepareRuntime();

        $dir = self::BASE_DIR.'/'.Str::uuid()->toString();
        Storage::disk(self::DISK)->makeDirectory($dir);

        $storedRelative = $this->storeUpload($file, $dir);

        return $this->processStoredFile(
            Storage::disk(self::DISK)->path($storedRelative),
            $storedRelative,
            $dir,
            $file,
        );
    }

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function generateFromPath(string $sourcePath): array
    {
        $this->prepareRuntime();

        if (! is_file($sourcePath)) {
            throw new PortfolioImageProcessingException('Source image file was not found.');
        }

        $dir = self::BASE_DIR.'/'.Str::uuid()->toString();
        Storage::disk(self::DISK)->makeDirectory($dir);

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');
        $storedRelative = $dir.'/source.'.$ext;
        Storage::disk(self::DISK)->put($storedRelative, file_get_contents($sourcePath));

        return $this->processStoredFile(
            Storage::disk(self::DISK)->path($storedRelative),
            $storedRelative,
            $dir,
            null,
        );
    }

    /**
     * Regenerate all variants inside an existing portfolio/{uuid} folder.
     * Throws on failure — never silently falls back to source.* paths.
     *
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function regenerateInFolder(string $folderRelative, string $sourceAbsolutePath, bool $force = false): array
    {
        $this->prepareRuntime();

        $disk = Storage::disk(self::DISK);

        if (! is_file($sourceAbsolutePath)) {
            throw new PortfolioImageProcessingException("Source file not found: {$sourceAbsolutePath}");
        }

        if (! $disk->exists($folderRelative)) {
            $disk->makeDirectory($folderRelative);
        }

        if ($force) {
            $this->deleteGeneratedVariants($folderRelative);
        }

        $paths = $this->buildOptimizedVariants($sourceAbsolutePath, $folderRelative);
        $this->assertVariantsExist($paths);
        $this->cleanupRawSources($folderRelative);

        return $paths;
    }

    /**
     * Locate the best source file inside a portfolio/{uuid} folder.
     */
    public function findSourceInFolder(string $folderRelative): ?string
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($folderRelative)) {
            return null;
        }

        $candidates = [];

        foreach ($disk->files($folderRelative) as $file) {
            $base = basename($file);

            if (preg_match('/^(source|upload)\.(jpe?g|png|webp|gif)$/i', $base)) {
                $candidates[] = ['priority' => 1, 'path' => $file];
            } elseif (preg_match('/^original\.(webp|jpe?g|png)$/i', $base)) {
                $candidates[] = ['priority' => 2, 'path' => $file];
            } elseif (preg_match('/^large\.(webp|jpe?g|png)$/i', $base)) {
                $candidates[] = ['priority' => 3, 'path' => $file];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $disk->path($candidates[0]['path']);
    }

    /**
     * Resolve the portfolio/{uuid} folder from a model's stored paths.
     */
    public function folderForImage(PortfolioImage $image): ?string
    {
        foreach (['image_original', 'image_path', 'image_large', 'image_medium', 'image_thumb'] as $column) {
            $path = $image->getAttribute($column);

            if (! $path) {
                continue;
            }

            $dir = dirname($path);

            if ($dir && $dir !== '.' && $dir !== self::BASE_DIR) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * @param  array{image_path?:string, image_original?:string, image_large?:string, image_medium?:string, image_thumb?:string}  $paths
     */
    public function variantsExist(array $paths): bool
    {
        $disk = Storage::disk(self::DISK);

        foreach (['image_original', 'image_large', 'image_medium', 'image_thumb'] as $key) {
            $path = $paths[$key] ?? null;

            if (! $path || ! $disk->exists($path)) {
                return false;
            }

            if ($this->isRawSourcePath($path)) {
                return false;
            }
        }

        return true;
    }

    public function deleteVariants(PortfolioImage $image): void
    {
        $reference = $image->image_original
            ?: $image->image_path
            ?: $image->image_large
            ?: $image->image_medium
            ?: $image->image_thumb;

        if (! $reference) {
            return;
        }

        $dir = dirname($reference);

        if ($dir && $dir !== '.' && $dir !== self::BASE_DIR && Storage::disk(self::DISK)->exists($dir)) {
            Storage::disk(self::DISK)->deleteDirectory($dir);

            return;
        }

        foreach (['image_original', 'image_large', 'image_medium', 'image_thumb', 'image_path'] as $column) {
            $path = $image->getAttribute($column);
            if ($path && Storage::disk(self::DISK)->exists($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    private function processStoredFile(
        string $absolutePath,
        string $storedRelative,
        string $dir,
        ?UploadedFile $file,
    ): array {
        try {
            $paths = $this->buildOptimizedVariants($absolutePath, $dir);
            $this->removeRawUploadIfReplaced($storedRelative, $paths['image_original']);

            return $paths;
        } catch (PortfolioImageProcessingException $e) {
            Log::error('Portfolio image processing failed', $this->logContext($file, $absolutePath, $e));

            throw $e;
        } catch (Throwable $e) {
            Log::error('Portfolio image processing failed', $this->logContext($file, $absolutePath, $e));

            $emergency = $this->attemptEmergencyVariants($absolutePath, $dir, $storedRelative);

            if ($emergency !== null) {
                Log::warning('Portfolio image saved with emergency variants only.', [
                    'stored' => $storedRelative,
                ]);

                return $emergency;
            }

            Log::critical('Portfolio image has no optimized variants; raw upload used temporarily.', [
                'stored' => $storedRelative,
            ]);

            return $this->fallbackPaths($storedRelative);
        }
    }

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    private function buildOptimizedVariants(string $sourcePath, string $dir): array
    {
        $mime = $this->detectMime($sourcePath);

        if (! $this->isSupportedMime($mime)) {
            throw new PortfolioImageProcessingException(
                'Unsupported image format. Please upload a JPG, JPEG, PNG, or WEBP file.',
            );
        }

        $format = $this->outputFormat();

        $image = $this->manager()->read($sourcePath);
        $image->orient();

        if ($this->shouldFlattenTransparency($mime)) {
            $image->blendTransparency('ffffff');
        }

        $image->scaleDown($this->originalMaxDimension, $this->originalMaxDimension);

        $originalRelative = $dir.'/original.'.$format;
        $this->encodeToDisk($image, Storage::disk(self::DISK)->path($originalRelative), $format, $this->originalQuality);

        unset($image);

        $originalAbsolute = Storage::disk(self::DISK)->path($originalRelative);
        $paths = ['image_original' => $originalRelative];

        foreach ($this->variants as $name => $cfg) {
            $variant = $this->manager()->read($originalAbsolute);

            if ($cfg['crop']) {
                $variant->coverDown($cfg['width'], $cfg['height']);
            } else {
                $variant->scaleDown($cfg['width'], $cfg['height']);
            }

            $relative = $dir.'/'.$name.'.'.$format;
            $this->encodeToDisk($variant, Storage::disk(self::DISK)->path($relative), $format, $cfg['quality']);
            $paths['image_'.$name] = $relative;

            unset($variant);
        }

        $paths['image_path'] = $paths['image_original'];

        return $paths;
    }

    /**
     * @param  array{image_path?:string, image_original?:string, image_large?:string, image_medium?:string, image_thumb?:string}  $paths
     */
    private function assertVariantsExist(array $paths): void
    {
        $disk = Storage::disk(self::DISK);

        foreach (['image_original', 'image_large', 'image_medium', 'image_thumb'] as $key) {
            $path = $paths[$key] ?? null;

            if (! $path || ! $disk->exists($path)) {
                throw new PortfolioImageProcessingException(
                    'Variant file was not created: '.($path ?: $key),
                );
            }

            if ($disk->size($path) < 1) {
                throw new PortfolioImageProcessingException("Variant file is empty: {$path}");
            }
        }
    }

    private function deleteGeneratedVariants(string $folderRelative): void
    {
        $disk = Storage::disk(self::DISK);

        foreach (self::VARIANT_NAMES as $name) {
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                $path = $folderRelative.'/'.$name.'.'.$ext;

                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }
    }

    private function cleanupRawSources(string $folderRelative): void
    {
        $disk = Storage::disk(self::DISK);

        foreach ($disk->files($folderRelative) as $file) {
            if ($this->isRawSourcePath($file)) {
                $disk->delete($file);
            }
        }
    }

    private function isRawSourcePath(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return (bool) preg_match('/\/(source|upload)\.[a-z0-9]+$/i', $path);
    }

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}|null
     */
    private function attemptEmergencyVariants(string $sourcePath, string $dir, string $storedRelative): ?array
    {
        try {
            $format = $this->outputFormat();
            $image = $this->manager()->read($sourcePath);
            $image->orient();

            if ($this->shouldFlattenTransparency($this->detectMime($sourcePath))) {
                $image->blendTransparency('ffffff');
            }

            $image->scaleDown($this->originalMaxDimension, $this->originalMaxDimension);

            $originalRelative = $dir.'/original.'.$format;
            $this->encodeToDisk($image, Storage::disk(self::DISK)->path($originalRelative), $format, $this->originalQuality);
            unset($image);

            $originalAbsolute = Storage::disk(self::DISK)->path($originalRelative);
            $paths = ['image_original' => $originalRelative];

            foreach ($this->variants as $name => $cfg) {
                $variant = $this->manager()->read($originalAbsolute);

                if ($cfg['crop']) {
                    $variant->coverDown($cfg['width'], $cfg['height']);
                } else {
                    $variant->scaleDown($cfg['width'], $cfg['height']);
                }

                $relative = $dir.'/'.$name.'.'.$format;
                $this->encodeToDisk($variant, Storage::disk(self::DISK)->path($relative), $format, $cfg['quality']);
                $paths['image_'.$name] = $relative;
                unset($variant);
            }

            $paths['image_path'] = $paths['image_original'];
            $this->removeRawUploadIfReplaced($storedRelative, $paths['image_original']);

            return $paths;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    private function fallbackPaths(string $storedRelative): array
    {
        return [
            'image_path' => $storedRelative,
            'image_original' => $storedRelative,
            'image_large' => $storedRelative,
            'image_medium' => $storedRelative,
            'image_thumb' => $storedRelative,
        ];
    }

    private function storeUpload(UploadedFile $file, string $dir): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $relative = $file->storeAs($dir, 'upload.'.$ext, self::DISK);

        if (! $relative || ! Storage::disk(self::DISK)->exists($relative)) {
            throw new PortfolioImageProcessingException(
                'The server could not save the uploaded image. Please check storage folder permissions.',
            );
        }

        return $relative;
    }

    private function removeRawUploadIfReplaced(string $storedRelative, string $optimizedRelative): void
    {
        if ($storedRelative !== $optimizedRelative && Storage::disk(self::DISK)->exists($storedRelative)) {
            Storage::disk(self::DISK)->delete($storedRelative);
        }
    }

    private function prepareRuntime(): void
    {
        @ini_set('memory_limit', '512M');

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
    }

    private function manager(): ImageManager
    {
        if ($this->manager !== null) {
            return $this->manager;
        }

        // GD is more reliable on shared cPanel hosts; Imagick often lacks WEBP support.
        if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
            $this->manager = ImageManager::gd();
        } elseif (extension_loaded('imagick')) {
            $this->manager = ImageManager::imagick();
        } else {
            throw new PortfolioImageProcessingException(
                'No image processing driver available. Enable the PHP GD or Imagick extension.',
            );
        }

        return $this->manager;
    }

    private function outputFormat(): string
    {
        return $this->supportsWebp() ? 'webp' : 'jpg';
    }

    private function supportsWebp(): bool
    {
        if (! function_exists('imagewebp')) {
            return false;
        }

        if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
            return true;
        }

        if (extension_loaded('imagick')) {
            $formats = \Imagick::queryFormats('WEBP');

            return ! empty($formats);
        }

        return function_exists('imagecreatefromwebp');
    }

    private function encodeToDisk(ImageInterface $image, string $absolutePath, string $format, int $quality): void
    {
        try {
            $encoded = $format === 'webp'
                ? $image->toWebp(quality: $quality)
                : $image->toJpeg(quality: $quality);

            $encoded->save($absolutePath);
        } catch (Throwable $e) {
            if ($format === 'webp') {
                throw new PortfolioImageProcessingException(
                    'WebP encoding failed (check that PHP GD has WebP support): '.$e->getMessage(),
                    $e,
                );
            }

            throw new PortfolioImageProcessingException(
                'Image encoding failed: '.$e->getMessage(),
                $e,
            );
        }

        if (! is_file($absolutePath) || filesize($absolutePath) < 1) {
            throw new PortfolioImageProcessingException("Failed to write image file: {$absolutePath}");
        }
    }

    private function detectMime(string $path): ?string
    {
        $info = @getimagesize($path);

        return $info['mime'] ?? null;
    }

    private function isSupportedMime(?string $mime): bool
    {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }

    private function shouldFlattenTransparency(?string $mime): bool
    {
        return in_array($mime, ['image/png', 'image/webp'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function logContext(?UploadedFile $file, string $path, Throwable $e): array
    {
        $dimensions = @getimagesize($path);

        return [
            'size' => $file?->getSize() ?? (is_file($path) ? filesize($path) : null),
            'mime' => $file?->getMimeType() ?? $this->detectMime($path),
            'dimensions' => $dimensions ?: null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];
    }
}
