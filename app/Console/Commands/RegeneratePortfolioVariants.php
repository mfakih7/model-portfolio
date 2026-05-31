<?php

namespace App\Console\Commands;

use App\Models\PortfolioImage;
use App\Services\PortfolioImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegeneratePortfolioVariants extends Command
{
    protected $signature = 'portfolio:regenerate-variants
        {--all : Regenerate variants for every image}
        {--force : Overwrite existing variant files in each folder}';

    protected $description = 'Generate WebP variants (original/large/medium/thumb) in each portfolio folder';

    public function handle(PortfolioImageService $service): int
    {
        $query = PortfolioImage::query();

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('image_medium')
                    ->orWhere('image_large', 'like', '%/upload.%')
                    ->orWhere('image_thumb', 'like', '%/upload.%')
                    ->orWhere('image_medium', 'like', '%/upload.%')
                    ->orWhere('image_large', 'like', '%/source.%')
                    ->orWhere('image_thumb', 'like', '%/source.%')
                    ->orWhere('image_medium', 'like', '%/source.%');
            });
        }

        $images = $query->get();

        if ($images->isEmpty()) {
            $this->info('No portfolio images need processing.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $verbose = $this->output->isVerbose();

        $this->info("Processing {$images->count()} image(s)...".($force ? ' (force overwrite)' : ''));

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($images as $image) {
            $label = "#{$image->id} \"{$image->title}\"";

            $folder = $service->folderForImage($image);

            if (! $folder) {
                $this->warn("  - {$label}: could not detect portfolio folder, skipped.");
                $skipped++;

                continue;
            }

            if ($verbose) {
                $this->line("  > {$label}: folder={$folder}");
            }

            $source = $service->findSourceInFolder($folder);

            if (! $source) {
                $this->warn("  - {$label}: no source/upload file found in {$folder}, skipped.");
                $skipped++;

                continue;
            }

            if ($verbose) {
                $this->line("    source: {$source}");
            }

            if (! $force && $service->variantsExist([
                'image_original' => "{$folder}/original.webp",
                'image_large' => "{$folder}/large.webp",
                'image_medium' => "{$folder}/medium.webp",
                'image_thumb' => "{$folder}/thumb.webp",
            ])) {
                $paths = [
                    'image_path' => "{$folder}/original.webp",
                    'image_original' => "{$folder}/original.webp",
                    'image_large' => "{$folder}/large.webp",
                    'image_medium' => "{$folder}/medium.webp",
                    'image_thumb' => "{$folder}/thumb.webp",
                ];
                $image->update($paths);
                $this->line("  - {$label}: variants already exist, DB synced.");
                $processed++;

                continue;
            }

            try {
                $paths = $service->regenerateInFolder($folder, $source, $force);
                $image->update($paths);

                if (! $service->variantsExist($paths)) {
                    throw new \RuntimeException('Variant files missing after generation.');
                }

                $this->info("  - {$label}: done → {$paths['image_thumb']}");

                if ($verbose) {
                    $disk = Storage::disk('public');
                    foreach (['image_original', 'image_large', 'image_medium', 'image_thumb'] as $key) {
                        $path = $paths[$key];
                        $size = round($disk->size($path) / 1024, 1);
                        $this->line("      {$key}: {$path} ({$size} KB)");
                    }
                }

                $processed++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("  - {$label}: FAILED — {$e->getMessage()}");

                if ($verbose) {
                    $this->line($e->getTraceAsString());
                }
            }
        }

        $missingThumb = PortfolioImage::all()->filter(function (PortfolioImage $image) {
            $path = $image->image_thumb;

            return ! $path
                || ! Storage::disk('public')->exists($path)
                || preg_match('/\/(source|upload)\./', $path);
        })->count();

        $this->newLine();
        $this->info("Processed: {$processed}");
        $this->info("Failed: {$failed}");
        $this->info("Skipped: {$skipped}");
        $this->info("Missing thumb (DB): {$missingThumb}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
