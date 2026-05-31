<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PortfolioImage extends Model
{
    protected $fillable = [
        'portfolio_category_id',
        'title',
        'description',
        'image_path',
        'image_original',
        'image_large',
        'image_medium',
        'image_thumb',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PortfolioCategory::class, 'portfolio_category_id');
    }

    /**
     * Public grid / listing thumbnail (max ~500px wide). Never serves raw uploads.
     */
    public function getThumbUrlAttribute(): string
    {
        return $this->publicVariantUrl('thumb', ['large']);
    }

    /**
     * Web / lightbox size (max 1600px wide). Never serves raw uploads or originals.
     */
    public function getLargeUrlAttribute(): string
    {
        return $this->publicVariantUrl('large', ['medium', 'thumb']);
    }

    /** Alias for large — the web-optimized display size. */
    public function getWebUrlAttribute(): string
    {
        return $this->large_url;
    }

    /**
     * Mid-size variant. Falls back to other public variants, never raw uploads.
     */
    public function getMediumUrlAttribute(): string
    {
        return $this->publicVariantUrl('medium', ['large', 'thumb']);
    }

    /**
     * Full-quality preserved copy — admin download only. Not for public pages.
     */
    public function getOriginalUrlAttribute(): string
    {
        $path = $this->image_original ?: $this->image_path;

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return $this->large_url;
    }

    /**
     * Backward-compatible accessor. Defaults to thumb so callers never pull originals.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->thumb_url;
    }

    /**
     * Whether this record still points at an unprocessed raw upload for public variants.
     */
    public function needsVariantRegeneration(): bool
    {
        foreach (['image_thumb', 'image_large', 'image_medium'] as $column) {
            $path = $this->getAttribute($column);

            if (! $path || $this->isRawUploadPath($path)) {
                return true;
            }

            if (! Storage::disk('public')->exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a visitor-safe variant URL, cascading through fallbacks.
     *
     * @param  list<string>  $fallbacks
     */
    private function publicVariantUrl(string $variant, array $fallbacks = []): string
    {
        foreach ([$variant, ...$fallbacks] as $name) {
            $path = $this->getAttribute('image_'.$name);

            if ($this->isPublicVariantPath($path) && Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return asset('images/placeholder.svg');
    }

    /**
     * Paths safe to expose on the public website (generated variants only).
     */
    private function isPublicVariantPath(?string $path): bool
    {
        if (! $path || $this->isRawUploadPath($path)) {
            return false;
        }

        return (bool) preg_match('/\/(large|medium|thumb|web)\.(webp|jpe?g|png)$/i', $path);
    }

    private function isRawUploadPath(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return (bool) preg_match('/\/(upload|source)\.[a-z0-9]+$/i', $path);
    }
}
