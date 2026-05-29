<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PortfolioCategory;
use App\Models\PortfolioImage;
use App\Models\SiteSetting;

class PortfolioController extends Controller
{
    public function index(?string $category = null)
    {
        $settings = SiteSetting::current();
        $categories = PortfolioCategory::where('status', true)->orderBy('name')->get();

        $query = PortfolioImage::with('category')
            ->whereHas('category', fn ($q) => $q->where('status', true))
            ->orderBy('sort_order');

        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $images = $query->get();
        $activeCategory = $category
            ? $categories->firstWhere('slug', $category)
            : null;

        return view('public.portfolio', compact('settings', 'categories', 'images', 'activeCategory', 'category'));
    }
}
