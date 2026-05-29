<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = [
        'model_name',
        'model_title',
        'intro_text',
        'hero_image',
        'meta_title',
        'meta_description',
        'instagram_url',
        'tiktok_url',
        'facebook_url',
        'youtube_url',
        'twitter_url',
        'linkedin_url',
        'whatsapp_number',
        'email',
    ];

    public static function current(): self
    {
        return Cache::remember('site_settings', 3600, function () {
            return static::firstOrCreate([]);
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('site_settings');
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->hero_image
            ? Storage::disk('public')->url($this->hero_image)
            : null;
    }

    public function getWhatsappLinkAttribute(): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', $this->whatsapp_number);

        return 'https://wa.me/'.$number;
    }

    public function getSocialLinksAttribute(): array
    {
        return array_filter([
            'instagram' => ['url' => $this->instagram_url, 'icon' => 'bi-instagram', 'label' => 'Instagram'],
            'tiktok' => ['url' => $this->tiktok_url, 'icon' => 'bi-tiktok', 'label' => 'TikTok'],
            'facebook' => ['url' => $this->facebook_url, 'icon' => 'bi-facebook', 'label' => 'Facebook'],
            'youtube' => ['url' => $this->youtube_url, 'icon' => 'bi-youtube', 'label' => 'YouTube'],
            'twitter' => ['url' => $this->twitter_url, 'icon' => 'bi-twitter-x', 'label' => 'X'],
            'linkedin' => ['url' => $this->linkedin_url, 'icon' => 'bi-linkedin', 'label' => 'LinkedIn'],
        ], fn ($item) => ! empty($item['url']));
    }
}
