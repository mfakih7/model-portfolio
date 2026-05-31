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
        {--all : Regenerate variants for every image, even those that already have them}';

    protected $description = 'Generate WebP image variants (original/large/medium/thumb) for portfolio images';

    public function handle(PortfolioImageService $service): int
    {
        $query = PortfolioImage::query();

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('image_medium')
                    ->orWhere('image_large', 'like', '%/upload.%')
                    ->orWhere('image_thumb', 'like', '%/upload.%')
                    ->orWhere('image_medium', 'like', '%/upload.%');
            });
        }

        $images = $query->get();

        if ($images->isEmpty()) {
            $this->info('No portfolio images need processing.');

            return self::SUCCESS;
        }

        $this->info("Processing {$images->count()} image(s)...");
        $processed = 0;
        $skipped = 0;

        foreach ($images as $image) {
            $source = $this->resolveSource($image);

            if (! $source) {
                $this->warn("  - #{$image->id} \"{$image->title}\": no source file found, skipped.");
                $skipped++;

                continue;
            }

            try {
                $legacyPath = $image->image_path;
                $legacyIsFlatFile = $legacyPath && dirname($legacyPath) === 'portfolio';
                $oldDir = $this->variantDirectory($image);

                $variants = $service->generateFromPath($source);
                $image->update($variants);

                if ($oldDir && $oldDir !== dirname($variants['image_original'] ?? '')) {
                    Storage::disk('public')->deleteDirectory($oldDir);
                }

                // Remove the old flat (pre-refactor) source file now that WebP
                // variants exist in a dedicated folder.
                if ($legacyIsFlatFile && Storage::disk('public')->exists($legacyPath)) {
                    Storage::disk('public')->delete($legacyPath);
                }

                $this->line("  - #{$image->id} \"{$image->title}\": done.");
                $processed++;
            } catch (Throwable $e) {
                $this->error("  - #{$image->id} \"{$image->title}\": {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Completed. Processed: {$processed}, skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveSource(PortfolioImage $image): ?string
    {
        foreach ([$image->image_original, $image->image_path, $image->image_large, $image->image_medium] as $candidate) {
            if ($candidate && Storage::disk('public')->exists($candidate)) {
                return Storage::disk('public')->path($candidate);
            }
        }

        return null;
    }

    private function variantDirectory(PortfolioImage $image): ?string
    {
        $reference = $image->image_original ?: $image->image_path ?: $image->image_large;

        if (! $reference) {
            return null;
        }

        $dir = dirname($reference);

        return ($dir && $dir !== '.' && $dir !== 'portfolio') ? $dir : null;
    }
}
