<?php

namespace App\Services;

use App\Exceptions\PortfolioImageProcessingException;
use App\Models\PortfolioImage;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Generates optimized portfolio image variants using native PHP GD only.
 * No external image libraries required — works on cPanel PHP 8.2 with ext-gd.
 */
class PortfolioImageService
{
    public const MAX_DIMENSION = 6000;

    private const DISK = 'public';

    private const BASE_DIR = 'portfolio';

    private const VARIANT_NAMES = ['original', 'large', 'medium', 'thumb'];

    private int $originalMaxDimension = 2400;

    private int $originalQuality = 85;

    /** Target max file size for thumb variant (bytes). */
    private int $thumbMaxBytes = 102400;

    /**
     * @var array<string, array{width:int, height:?int, crop:bool, quality:int}>
     */
    private array $variants = [
        'large' => ['width' => 1600, 'height' => null, 'crop' => false, 'quality' => 80],
        'medium' => ['width' => 900, 'height' => null, 'crop' => false, 'quality' => 75],
        'thumb' => ['width' => 500, 'height' => null, 'crop' => false, 'quality' => 68],
    ];

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
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function regenerateInFolder(string $folderRelative, string $sourceAbsolutePath, bool $force = false): array
    {
        $this->prepareRuntime();
        $this->assertGdAvailable();

        $disk = Storage::disk(self::DISK);

        if (! is_file($sourceAbsolutePath)) {
            throw new PortfolioImageProcessingException("Source file not found: {$sourceAbsolutePath}");
        }

        if (! $disk->exists($folderRelative)) {
            $disk->makeDirectory($folderRelative);
        }

        // Copy source to temp before --force deletes variant files in the folder.
        $tempSource = tempnam(sys_get_temp_dir(), 'pf_').'.'.strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION) ?: 'jpg');
        if (! @copy($sourceAbsolutePath, $tempSource)) {
            throw new PortfolioImageProcessingException('Could not read source image for processing.');
        }

        if ($force) {
            $this->deleteGeneratedVariants($folderRelative);
        }

        try {
            $paths = $this->buildOptimizedVariants($tempSource, $folderRelative);
            $this->assertVariantsExist($paths);
            $this->cleanupRawSources($folderRelative);

            return $paths;
        } finally {
            @unlink($tempSource);
        }
    }

    /**
     * Expected variant paths for the current server output format (webp or jpg).
     *
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function expectedVariantPaths(string $folderRelative): array
    {
        $ext = $this->outputFormat();

        return [
            'image_path' => "{$folderRelative}/original.{$ext}",
            'image_original' => "{$folderRelative}/original.{$ext}",
            'image_large' => "{$folderRelative}/large.{$ext}",
            'image_medium' => "{$folderRelative}/medium.{$ext}",
            'image_thumb' => "{$folderRelative}/thumb.{$ext}",
        ];
    }

    public function outputFormat(): string
    {
        return $this->supportsWebp() ? 'webp' : 'jpg';
    }

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

            if ($disk->size($path) < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * File sizes in bytes for each stored variant (null if missing).
     *
     * @return array{thumb:?int, medium:?int, large:?int, original:?int}
     */
    public static function variantByteSizes(PortfolioImage $image): array
    {
        $disk = Storage::disk('public');
        $sizes = [];

        foreach (['thumb', 'medium', 'large', 'original'] as $name) {
            $path = $image->getAttribute('image_'.$name) ?: ($name === 'original' ? $image->image_path : null);
            $sizes[$name] = ($path && $disk->exists($path)) ? $disk->size($path) : null;
        }

        return $sizes;
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
        $this->assertGdAvailable();

        $mime = $this->detectMime($sourcePath);

        if (! $this->isSupportedMime($mime)) {
            throw new PortfolioImageProcessingException(
                'Unsupported image format. Please upload a JPG, JPEG, PNG, or WEBP file.',
            );
        }

        $format = $this->outputFormat();

        $source = $this->createImageResource($sourcePath, $mime);

        if ($this->shouldFlattenTransparency($mime)) {
            $source = $this->flattenOnWhite($source);
        }

        $work = $this->scaleToMax($source, $this->originalMaxDimension);
        imagedestroy($source);

        $originalRelative = $dir.'/original.'.$format;
        $this->writeImage($work, Storage::disk(self::DISK)->path($originalRelative), $format, $this->originalQuality);

        $paths = ['image_original' => $originalRelative];

        foreach ($this->variants as $name => $cfg) {
            $variant = $cfg['crop']
                ? $this->cropCover($work, $cfg['width'], $cfg['height'] ?? $cfg['width'])
                : $this->scaleToWidth($work, $cfg['width']);

            $relative = $dir.'/'.$name.'.'.$format;
            $absolute = Storage::disk(self::DISK)->path($relative);
            $this->writeImage($variant, $absolute, $format, $cfg['quality']);

            if ($name === 'thumb' && is_file($absolute) && filesize($absolute) > $this->thumbMaxBytes) {
                foreach ([65, 60, 55] as $lowerQuality) {
                    $this->writeImage($variant, $absolute, $format, $lowerQuality);
                    if (filesize($absolute) <= $this->thumbMaxBytes) {
                        break;
                    }
                }
            }

            imagedestroy($variant);

            $paths['image_'.$name] = $relative;
        }

        imagedestroy($work);

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
            $paths = $this->buildOptimizedVariants($sourcePath, $dir);
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

    private function assertGdAvailable(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            throw new PortfolioImageProcessingException(
                'The PHP GD extension is required for image processing. Enable ext-gd in cPanel MultiPHP INI Editor.',
            );
        }
    }

    private function supportsWebp(): bool
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromwebp')) {
            return false;
        }

        if (! extension_loaded('gd')) {
            return false;
        }

        $info = gd_info();

        return ! empty($info['WebP Support']);
    }

    private function writeImage(GdImage $image, string $absolutePath, string $format, int $quality): void
    {
        $dir = dirname($absolutePath);

        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new PortfolioImageProcessingException("Image directory is not writable: {$dir}");
        }

        $ok = $format === 'webp'
            ? imagewebp($image, $absolutePath, $quality)
            : imagejpeg($image, $absolutePath, $quality);

        if (! $ok || ! is_file($absolutePath) || filesize($absolutePath) < 1) {
            throw new PortfolioImageProcessingException(
                "Failed to write {$format} image: {$absolutePath}",
            );
        }
    }

    private function createImageResource(string $path, ?string $mime = null): GdImage
    {
        if (! is_file($path)) {
            throw new PortfolioImageProcessingException("Source image not found: {$path}");
        }

        $mime ??= $this->detectMime($path);

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new PortfolioImageProcessingException(
                'Unsupported or corrupt image: '.($mime ?: 'unknown type'),
            );
        }

        if ($mime === 'image/jpeg') {
            $image = $this->fixJpegOrientation($image, $path);
        }

        return $image;
    }

    private function fixJpegOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? null;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated instanceof GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    /** Flatten PNG/WebP transparency onto white (fashion photos). */
    private function flattenOnWhite(GdImage $src): GdImage
    {
        $width = imagesx($src);
        $height = imagesy($src);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $src, 0, 0, 0, 0, $width, $height);
        imagedestroy($src);

        return $canvas;
    }

    private function scaleToMax(GdImage $src, int $max): GdImage
    {
        $width = imagesx($src);
        $height = imagesy($src);
        $longest = max($width, $height);

        if ($longest <= $max) {
            return $this->copyResampled($src, 0, 0, $width, $height, $width, $height);
        }

        $scale = $max / $longest;
        $targetWidth = (int) max(1, round($width * $scale));
        $targetHeight = (int) max(1, round($height * $scale));

        return $this->copyResampled($src, 0, 0, $width, $height, $targetWidth, $targetHeight);
    }

    private function scaleToWidth(GdImage $src, int $maxWidth): GdImage
    {
        $width = imagesx($src);
        $height = imagesy($src);

        if ($width <= $maxWidth) {
            return $this->copyResampled($src, 0, 0, $width, $height, $width, $height);
        }

        $targetWidth = $maxWidth;
        $targetHeight = (int) max(1, round($height * ($maxWidth / $width)));

        return $this->copyResampled($src, 0, 0, $width, $height, $targetWidth, $targetHeight);
    }

    private function cropCover(GdImage $src, int $targetWidth, int $targetHeight): GdImage
    {
        $width = imagesx($src);
        $height = imagesy($src);

        $ratio = max($targetWidth / $width, $targetHeight / $height);
        $cropWidth = (int) round($targetWidth / $ratio);
        $cropHeight = (int) round($targetHeight / $ratio);
        $srcX = (int) max(0, round(($width - $cropWidth) / 2));
        $srcY = (int) max(0, round(($height - $cropHeight) / 2));

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $dst, $src, 0, 0, $srcX, $srcY,
            $targetWidth, $targetHeight, $cropWidth, $cropHeight,
        );

        return $dst;
    }

    private function copyResampled(
        GdImage $src,
        int $srcX,
        int $srcY,
        int $srcW,
        int $srcH,
        int $dstW,
        int $dstH,
    ): GdImage {
        $dst = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        return $dst;
    }

    private function detectMime(string $path): ?string
    {
        $info = @getimagesize($path);

        if ($info['mime'] ?? null) {
            return $info['mime'];
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    }

    private function isSupportedMime(?string $mime): bool
    {
        if ($mime === 'image/webp' && ! function_exists('imagecreatefromwebp')) {
            return false;
        }

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
