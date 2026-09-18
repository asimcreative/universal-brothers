<?php

namespace App\Providers;

use App\Listeners\VerifyRequiredPhpExtensions;
use App\Models\NewsArticle;
use App\Models\Office;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        // Found during the frontend visual redesign's screenshot review:
        // Laravel's paginator defaults to its Tailwind view (`sm:hidden` /
        // `hidden sm:flex` classes), but this project only ever loads
        // Bootstrap — with no Tailwind CSS present, those responsive
        // visibility classes do nothing, so the "mobile" and "desktop"
        // pagination variants (and their oversized, unstyled SVG arrows)
        // rendered simultaneously on every paginated listing. Switching to
        // Laravel's bundled Bootstrap 5 pagination view matches the CSS
        // framework this project actually uses.
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

        // Bound as singletons (not queried directly in the closure) so the
        // header, footer, and layouts.app JSON-LD block — all matched by this
        // one composer — share a single query each per request instead of
        // querying again on every view it's attached to (see
        // FINAL_CODE_REVIEW.md M-6).
        $this->app->singleton('nav-categories', fn () => PackageCategory::where('is_active', true)->orderBy('sort_order')->get());
        $this->app->singleton('primary-office', fn () => Office::where('is_active', true)->orderBy('sort_order')->first());

        // The header's announcement ticker. A singleton for the same reason as
        // the two above — the header partial is matched by the composer below
        // and would otherwise re-query on every layout view it is attached to.
        $this->app->singleton('header-announcements', fn () => NewsArticle::where('is_active', true)
            ->latest('published_at')->limit(5)->get(['slug', 'title']));

        // 'contact' added for the frontend visual redesign's quick-action
        // Call/WhatsApp/Email cards — reuses the same singleton, no extra query.
        View::composer(['layouts.partials.*', 'layouts.app', 'layouts.template', 'template.*', 'contact'], function ($view) {
            $view->with('navCategories', app('nav-categories'));
            $view->with('primaryOffice', app('primary-office'));
            $view->with('ubAnnouncements', app('header-announcements'));
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

        // Registered explicitly rather than left to listener auto-discovery:
        // discovery scans app/Listeners on every request unless `event:cache`
        // has run, and this project's deploy caches config, routes and views
        // but not events. One explicit line is cheaper and does not depend on
        // a cache step nobody runs.
        Event::listen(DiagnosingHealth::class, VerifyRequiredPhpExtensions::class);

        // Permissions beyond "is an active admin". Content editors can build
        // and publish pages; these few actions reach further and stay with
        // super admins: the AI provider account and its API key, sections
        // that change several live pages at once, and the text editor's
        // source view.
        Gate::define('manage-ai-settings', fn (User $user) => $user->isSuperAdmin());
        Gate::define('link-saved-sections', fn (User $user) => $user->isSuperAdmin());
        Gate::define('edit-source', fn (User $user) => $user->isSuperAdmin());
    }
}
