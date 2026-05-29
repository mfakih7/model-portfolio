<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use App\Models\PortfolioImage;
use App\Models\SiteSetting;

class HomeController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::current();
        $about = AboutContent::current();
        $featuredImages = PortfolioImage::with('category')
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        if ($featuredImages->isEmpty()) {
            $featuredImages = PortfolioImage::with('category')
                ->orderBy('sort_order')
                ->limit(6)
                ->get();
        }

        return view('public.home', compact('settings', 'about', 'featuredImages'));
    }
}
