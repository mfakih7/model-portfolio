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
     * Resolve a public URL for a given variant, falling back gracefully:
     * requested variant -> legacy original (image_path) -> placeholder.
     */
    public function variantUrl(string $variant): string
    {
        $column = 'image_'.$variant;
        $path = $this->getAttribute($column);

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // Pre-migration / legacy rows only have the flat original file.
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        return asset('images/placeholder.svg');
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->variantUrl('thumb');
    }

    public function getMediumUrlAttribute(): string
    {
        return $this->variantUrl('medium');
    }

    public function getLargeUrlAttribute(): string
    {
        return $this->variantUrl('large');
    }

    public function getOriginalUrlAttribute(): string
    {
        return $this->variantUrl('original');
    }

    /**
     * Backward-compatible accessor. Defaults to the medium variant so no caller
     * accidentally serves the full-resolution original.
     */
    public function getImageUrlAttribute(): string
    {
        return $this->variantUrl('medium');
    }
}
