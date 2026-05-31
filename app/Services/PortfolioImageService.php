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
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Throwable;

/**
 * Generates optimized portfolio image variants using Intervention Image.
 *
 * Flow:
 *  1. Store the raw upload to disk (no decode).
 *  2. Read once, orient, flatten PNG transparency, scale down to a working size.
 *  3. Save an optimized "original" and generate large / medium / thumb from it.
 *  4. If variant generation fails, fall back to the stored upload for all paths.
 */
class PortfolioImageService
{
    public const MAX_DIMENSION = 6000;

    private const DISK = 'public';

    private const BASE_DIR = 'portfolio';

    /** Longest side for the stored "original" variant (never the raw multi‑MB upload). */
    private int $originalMaxDimension = 2400;

    private int $originalQuality = 88;

    /**
     * @var array<string, array{width:int, height:?int, crop:bool, quality:int}>
     */
    private array $variants = [
        'large' => ['width' => 1600, 'height' => null, 'crop' => false, 'quality' => 82],
        'medium' => ['width' => 900, 'height' => null, 'crop' => false, 'quality' => 80],
        'thumb' => ['width' => 400, 'height' => 400, 'crop' => true, 'quality' => 78],
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
     * Remove every stored variant for an image (deletes its UUID folder).
     */
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

        // --- Pass 1: decode once, normalize, downscale to working size, save original ---
        $image = $this->manager()->read($sourcePath);
        $image->orient();

        if ($this->shouldFlattenTransparency($mime)) {
            $image->blendTransparency('ffffff');
        }

        $image->scaleDown($this->originalMaxDimension, $this->originalMaxDimension);

        $originalRelative = $dir.'/original.'.$format;
        $this->encodeToDisk($image, Storage::disk(self::DISK)->path($originalRelative), $format, $this->originalQuality);

        unset($image);

        // --- Pass 2: generate every variant from the smaller saved original ---
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
        if ($this->manager === null) {
            $this->manager = extension_loaded('imagick')
                ? ImageManager::imagick()
                : ImageManager::gd();
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

        if (extension_loaded('imagick')) {
            return true;
        }

        return function_exists('imagecreatefromwebp');
    }

    private function encodeToDisk(ImageInterface $image, string $absolutePath, string $format, int $quality): void
    {
        $encoded = $format === 'webp'
            ? $image->toWebp(quality: $quality)
            : $image->toJpeg(quality: $quality);

        $encoded->save($absolutePath);
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
