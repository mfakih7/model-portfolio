<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share site settings with all public views
        View::composer(['layouts.public', 'public.*'], function ($view) {
            $view->with('settings', SiteSetting::current());
        });
    }
}
