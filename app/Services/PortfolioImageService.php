<?php

namespace App\Services;

use App\Models\PortfolioImage;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates a set of optimized WebP variants for portfolio images.
 *
 * Each source image produces four files inside a dedicated UUID folder:
 *   portfolio/{uuid}/original.webp  full resolution (download / full preview only)
 *   portfolio/{uuid}/large.webp     max width 1200px (lightbox / detail)
 *   portfolio/{uuid}/medium.webp    max width 600px  (grids)
 *   portfolio/{uuid}/thumb.webp     250x250 cover crop (admin listing)
 */
class PortfolioImageService
{
    private const DISK = 'public';

    private const BASE_DIR = 'portfolio';

    /**
     * Resize variants. "crop" performs a centered cover crop to exact dimensions,
     * otherwise the image is scaled to the target width preserving aspect ratio.
     *
     * @var array<string, array{width:int, height:?int, crop:bool, quality:int}>
     */
    private array $variants = [
        'large' => ['width' => 1200, 'height' => null, 'crop' => false, 'quality' => 82],
        'medium' => ['width' => 600, 'height' => null, 'crop' => false, 'quality' => 80],
        'thumb' => ['width' => 250, 'height' => 250, 'crop' => true, 'quality' => 78],
    ];

    private int $originalQuality = 90;

    /**
     * Generate every variant from an uploaded file.
     *
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function generate(UploadedFile $file): array
    {
        return $this->generateFromPath($file->getRealPath());
    }

    /**
     * Generate every variant from an absolute source image path.
     *
     * @return array{image_path:string, image_original:string, image_large:string, image_medium:string, image_thumb:string}
     */
    public function generateFromPath(string $sourcePath): array
    {
        $source = $this->createImageResource($sourcePath);

        $dir = self::BASE_DIR.'/'.Str::uuid()->toString();
        Storage::disk(self::DISK)->makeDirectory($dir);

        $paths = [];

        try {
            $originalRel = $dir.'/original.webp';
            $this->writeWebp($source, $originalRel, $this->originalQuality);
            $paths['image_original'] = $originalRel;

            foreach ($this->variants as $name => $cfg) {
                $variant = $cfg['crop']
                    ? $this->cropCover($source, $cfg['width'], $cfg['height'])
                    : $this->scaleToWidth($source, $cfg['width']);

                $rel = $dir.'/'.$name.'.webp';
                $this->writeWebp($variant, $rel, $cfg['quality']);
                imagedestroy($variant);

                $paths['image_'.$name] = $rel;
            }
        } finally {
            imagedestroy($source);
        }

        // Keep the legacy column populated for backward compatibility.
        $paths['image_path'] = $paths['image_original'];

        return $paths;
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

        // Only delete dedicated per-image folders (portfolio/{uuid}), never the
        // shared portfolio root that legacy flat files used to live in.
        if ($dir && $dir !== '.' && $dir !== self::BASE_DIR && Storage::disk(self::DISK)->exists($dir)) {
            Storage::disk(self::DISK)->deleteDirectory($dir);

            return;
        }

        // Legacy flat file fallback.
        if ($image->image_path && Storage::disk(self::DISK)->exists($image->image_path)) {
            Storage::disk(self::DISK)->delete($image->image_path);
        }
    }

    private function writeWebp(GdImage $image, string $relativePath, int $quality): void
    {
        $absolute = Storage::disk(self::DISK)->path($relativePath);

        if (! imagewebp($image, $absolute, $quality)) {
            throw new RuntimeException("Failed to write WebP variant: {$relativePath}");
        }
    }

    private function createImageResource(string $path): GdImage
    {
        if (! is_file($path)) {
            throw new RuntimeException("Source image not found: {$path}");
        }

        $info = @getimagesize($path);
        $mime = $info['mime'] ?? null;

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif' => imagecreatefromgif($path),
            default => throw new RuntimeException('Unsupported image type: '.($mime ?: 'unknown')),
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException("Failed to decode image: {$path}");
        }

        if ($mime === 'image/jpeg') {
            $image = $this->fixJpegOrientation($image, $path);
        }

        return $image;
    }

    /**
     * Correct rotation based on EXIF orientation so phone photos aren't sideways.
     */
    private function fixJpegOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? null;

        $rotated = match ($orientation) {
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

    private function scaleToWidth(GdImage $src, int $maxWidth): GdImage
    {
        $width = imagesx($src);
        $height = imagesy($src);

        // Never upscale: smaller sources are re-encoded at their native size.
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

        $dst = $this->createCanvas($targetWidth, $targetHeight);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $dst;
    }

    private function copyResampled(GdImage $src, int $srcX, int $srcY, int $srcW, int $srcH, int $dstW, int $dstH): GdImage
    {
        $dst = $this->createCanvas($dstW, $dstH);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        return $dst;
    }

    private function createCanvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);

        return $canvas;
    }
}
