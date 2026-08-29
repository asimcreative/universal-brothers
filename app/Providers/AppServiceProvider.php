<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\PackageCategory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

        // Named limiters so the contact form, inquiry form, and admin login
        // each get their own independent per-IP budget. Laravel's bare
        // `throttle:5,1` middleware string shares ONE global bucket per IP
        // across every route that uses it — a genuine bug, not just a test
        // artifact: a visitor using both the contact and inquiry forms could
        // get incorrectly locked out of one because they used the other.
        RateLimiter::for('contact-form', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('inquiry-form', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('admin-login', fn ($request) => Limit::perMinute(5)->by($request->ip()));
    }
}
