<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\PackageCategory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.partials.*', function ($view) {
            $view->with('navCategories', PackageCategory::where('is_active', true)->orderBy('sort_order')->get());
            $view->with('primaryOffice', Office::where('is_active', true)->orderBy('sort_order')->first());
        });
    }
}
