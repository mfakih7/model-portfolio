<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AboutContent extends Model
{
    protected $fillable = [
        'title',
        'short_bio',
        'full_story',
        'main_image',
        'additional_image',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public function getMainImageUrlAttribute(): ?string
    {
        return $this->main_image
            ? Storage::disk('public')->url($this->main_image)
            : null;
    }

    public function getAdditionalImageUrlAttribute(): ?string
    {
        return $this->additional_image
            ? Storage::disk('public')->url($this->additional_image)
            : null;
    }
}
