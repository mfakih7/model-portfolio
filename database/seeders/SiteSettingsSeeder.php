<?php

namespace Database\Seeders;

use App\Models\AboutContent;
use App\Models\PortfolioCategory;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'model_name' => 'Alex Rivera',
                'model_title' => 'International Fashion Model',
                'intro_text' => 'Elevating brands through editorial excellence, runway presence, and timeless style. Available for fashion campaigns, commercial shoots, and luxury collaborations worldwide.',
                'meta_title' => 'Alex Rivera | Fashion Model Portfolio',
                'meta_description' => 'Official portfolio of Alex Rivera — international fashion model. View editorial work, runway, and commercial campaigns. Book via WhatsApp.',
                'instagram_url' => 'https://instagram.com',
                'tiktok_url' => 'https://tiktok.com',
                'facebook_url' => 'https://facebook.com',
                'youtube_url' => 'https://youtube.com',
                'twitter_url' => 'https://x.com',
                'linkedin_url' => 'https://linkedin.com',
                'whatsapp_number' => '1234567890',
                'email' => 'contact@alexrivera.com',
            ]
        );

        AboutContent::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'The Journey Behind the Lens',
                'short_bio' => 'From local runway debuts to international fashion weeks, my career is built on authenticity, discipline, and a passion for storytelling through imagery.',
                'full_story' => "My journey into fashion began with a single editorial shoot that changed everything. What started as curiosity quickly became a calling — a dedication to craft, movement, and the art of presence.\n\nOver the years, I've collaborated with leading designers, luxury brands, and creative directors across Europe, the Middle East, and North America. Each project deepens my understanding of light, form, and narrative.\n\nWhen I'm not on set, I train, study contemporary fashion, and mentor aspiring models. I believe the best portfolios tell a story — and I'm here to help brands tell theirs.",
            ]
        );

        $categories = [
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Casual', 'slug' => 'casual'],
            ['name' => 'Sportswear', 'slug' => 'sportswear'],
            ['name' => 'Commercial', 'slug' => 'commercial'],
            ['name' => 'Editorial', 'slug' => 'editorial'],
        ];

        foreach ($categories as $category) {
            PortfolioCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'status' => true]
            );
        }
    }
}
