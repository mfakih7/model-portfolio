<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use App\Models\SiteSetting;

class AboutController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::current();
        $about = AboutContent::current();

        return view('public.about', compact('settings', 'about'));
    }
}
