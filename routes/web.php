<?php

use App\Http\Controllers\Admin\AffiliationController as AdminAffiliationController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AwardController as AdminAwardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\HajjPackageController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\MediaItemController as AdminMediaItemController;
use App\Http\Controllers\Admin\NewsArticleController as AdminNewsArticleController;
use App\Http\Controllers\Admin\OfficeController as AdminOfficeController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PackageCategoryController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PackageTemplateController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AffiliationController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqPageController;
use App\Http\Controllers\HajjServicesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MediaPageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TestimonialPageController;
use App\Http\Controllers\UmrahServicesController;
use App\Support\Library\LibraryRegistry;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact-form')->name('contact.store');
Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:inquiry-form')->name('inquiries.store');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/hajj-services', [HajjServicesController::class, 'index'])->name('hajj-services');
Route::get('/umrah-services', [UmrahServicesController::class, 'index'])->name('umrah-services');
Route::get('/awards', [AwardController::class, 'index'])->name('awards');
Route::get('/affiliations', [AffiliationController::class, 'index'])->name('affiliations');
Route::get('/media', [MediaPageController::class, 'index'])->name('media');
Route::get('/testimonials', [TestimonialPageController::class, 'index'])->name('testimonials');
Route::get('/faqs', [FaqPageController::class, 'index'])->name('faqs');

Route::get('/{category}', [PackageController::class, 'category'])
    ->whereIn('category', ['hajj', 'umrah', 'tourism'])
    ->name('packages.category');

Route::get('/{category}/{package:slug}', [PackageController::class, 'show'])
    ->whereIn('category', ['hajj', 'umrah', 'tourism'])
    ->name('packages.show');

// Public AI assistant endpoints. Declared before the admin group and well
// before the catch-all `/{slug}` page route at the bottom, which would
// otherwise swallow anything under /ai.
//
// These are POST-only and session-backed: the conversation is resolved from
// the session cookie, never from a client-supplied id, so one visitor can
// never reach another's chat. Rate limiting is applied inside the controller
// because the budget is admin-configurable rather than a fixed middleware
// string.
Route::prefix('ai')->name('ai.')->group(function () {
    Route::post('/chat', [AiChatController::class, 'send'])->name('chat');
    // Reuses the same per-IP budget as the site's own inquiry form: this
    // writes to the same `inquiries` table, so it is the same abuse surface
    // and should not be a way around that limit.
    Route::post('/lead', [AiChatController::class, 'lead'])
        ->middleware('throttle:inquiry-form')
        ->name('lead');
    Route::post('/reset', [AiChatController::class, 'reset'])->name('reset');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:admin-login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('packages', AdminPackageController::class)->except(['show']);
        // Hajj packages get their own dedicated rich admin surface (variants,
        // Aziziya, Mina/Arafat, transportation, notes, upgrades) — Tourism/
        // Umrah keep using the generic packages CRUD above, unchanged.
        // Explicit parameter name: same reason as the `news`/`media`
        // resources above — the controller type-hints `Package $package`,
        // and Route::resource('hajj-packages', ...) would otherwise
        // generate a mismatched {hajj_package} parameter.
        // Registered before the resource so "assess" is never read as a {package}.
        Route::post('hajj-packages/assess', [HajjPackageController::class, 'assess'])->name('hajj-packages.assess');
        Route::resource('hajj-packages', HajjPackageController::class)
            ->except(['show'])
            ->parameters(['hajj-packages' => 'package']);
        Route::prefix('hajj-packages/{package}')->name('hajj-packages.')->group(function () {
            Route::post('assess', [HajjPackageController::class, 'assess'])->name('assess-existing');
            Route::patch('quick/{action}', [HajjPackageController::class, 'quick'])
                ->whereIn('action', ['publish', 'unpublish', 'feature', 'unfeature', 'archive', 'restore'])
                ->name('quick');
            Route::post('duplicate', [HajjPackageController::class, 'duplicate'])->name('duplicate');
            Route::get('content', [HajjPackageController::class, 'content'])->name('content');
            Route::post('save-as-template', [HajjPackageController::class, 'saveAsTemplate'])->name('save-template');
            Route::post('apply-template', [HajjPackageController::class, 'applyTemplate'])->name('apply-template');
            // A draft preview needs BOTH a logged-in admin (this group's
            // middleware) and an unexpired signature — a leaked link alone
            // shows nothing, and neither does a session on a guessed URL.
            Route::get('preview', [HajjPackageController::class, 'preview'])->middleware('signed')->name('preview');
        });

        Route::resource('package-templates', PackageTemplateController::class)
            ->except(['show'])
            ->parameters(['package-templates' => 'template']);
        Route::patch('package-templates/{template}/archive', [PackageTemplateController::class, 'archive'])->name('package-templates.archive');
        Route::patch('package-templates/{template}/restore', [PackageTemplateController::class, 'restore'])->name('package-templates.restore');

        // The reusable package library: hotels, meal plans, transport, included
        // and not-included services, additional options, Mashaer arrangements,
        // notes and journey templates — one controller, see LibraryRegistry.
        Route::prefix('library/{type}')->name('library.')
            ->whereIn('type', LibraryRegistry::keys())
            ->group(function () {
                Route::get('/', [LibraryController::class, 'index'])->name('index');
                Route::get('create', [LibraryController::class, 'create'])->name('create');
                Route::post('/', [LibraryController::class, 'store'])->name('store');
                Route::get('{id}/edit', [LibraryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::put('{id}', [LibraryController::class, 'update'])->whereNumber('id')->name('update');
                Route::get('{id}/usage', [LibraryController::class, 'usage'])->whereNumber('id')->name('usage');
                Route::post('{id}/push', [LibraryController::class, 'push'])->whereNumber('id')->name('push');
                Route::post('{id}/duplicate', [LibraryController::class, 'duplicate'])->whereNumber('id')->name('duplicate');
                Route::patch('{id}/archive', [LibraryController::class, 'archive'])->whereNumber('id')->name('archive');
                Route::patch('{id}/restore', [LibraryController::class, 'restore'])->whereNumber('id')->name('restore');
                Route::delete('{id}', [LibraryController::class, 'destroy'])->whereNumber('id')->name('destroy');
            });

        // The admin guide: help centre, first-visit welcome and the guided tour.
        Route::get('help', fn () => redirect()->route('admin.guide.index'))->name('help');
        Route::get('guide', [GuideController::class, 'index'])->name('guide.index');
        Route::get('guide/{section}', [GuideController::class, 'show'])->name('guide.show');
        Route::post('guide/{section}/complete', [GuideController::class, 'complete'])->name('guide.complete');
        Route::delete('guide/{section}/complete', [GuideController::class, 'uncomplete'])->name('guide.uncomplete');
        Route::post('onboarding/tour', [OnboardingController::class, 'tour'])->name('onboarding.tour');
        Route::post('onboarding/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
        Route::post('onboarding/restart', [OnboardingController::class, 'restart'])->name('onboarding.restart');

        Route::get('categories', [PackageCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}/edit', [PackageCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [PackageCategoryController::class, 'update'])->name('categories.update');
        Route::post('categories/{category}/series', [PackageCategoryController::class, 'storeSeries'])->name('categories.series.store');
        Route::delete('categories/{category}/series/{series}', [PackageCategoryController::class, 'destroySeries'])->name('categories.series.destroy');

        Route::resource('testimonials', AdminTestimonialController::class)->except(['show']);
        Route::resource('faqs', AdminFaqController::class)->except(['show']);
        Route::resource('awards', AdminAwardController::class)->except(['show']);
        Route::resource('affiliations', AdminAffiliationController::class)->except(['show']);
        Route::resource('sliders', AdminSliderController::class)->except(['show']);
        // Explicit parameter name: the controller type-hints `NewsArticle $article`
        // (clearer than `$news`, which reads oddly next to the `NewsArticle` class),
        // but Route::resource('news', ...) otherwise generates a {news} route
        // parameter — implicit model binding requires the two to match by name,
        // so without this it silently fails and every edit/update/destroy request
        // was resolving a fresh, empty NewsArticle instead of the real one.
        Route::resource('news', AdminNewsArticleController::class)
            ->except(['show'])
            ->parameters(['news' => 'article']);
        Route::resource('offices', AdminOfficeController::class)->except(['show']);
        Route::resource('pages', AdminPageController::class)->except(['show']);
        // Explicit parameter name: learned from the News bug (see
        // FINAL_AUDIT_REPORT.md §5) — Laravel's singularization of "media"
        // is not guaranteed to match the controller's `$item` type-hint, so
        // pin it rather than assume.
        Route::resource('media', AdminMediaItemController::class)
            ->except(['show'])
            ->parameters(['media' => 'item']);

        Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('inquiries.show');
        Route::put('inquiries/{inquiry}', [AdminInquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('inquiries/{inquiry}', [AdminInquiryController::class, 'destroy'])->name('inquiries.destroy');

        Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update');

        Route::get('ai', [AiAssistantController::class, 'index'])->name('ai.index');
        Route::put('ai', [AiAssistantController::class, 'update'])->name('ai.update');
        Route::delete('ai/key', [AiAssistantController::class, 'clearKey'])->name('ai.key.clear');
        Route::post('ai/reindex', [AiAssistantController::class, 'reindex'])->name('ai.reindex');
        Route::match(['get', 'post'], 'ai/test', [AiAssistantController::class, 'test'])->name('ai.test');
        Route::get('ai/conversations', [AiAssistantController::class, 'conversations'])->name('ai.conversations');
        // Declared after the collection route so `ai/conversations` is never
        // matched as a {conversation} id.
        Route::get('ai/conversations/{conversation}', [AiAssistantController::class, 'conversation'])->name('ai.conversation');

        // Restricted separately to super_admin via UserPolicy — every other
        // admin route above is reachable by both roles (documented, deliberate
        // two-role scope), but account management is the one screen where the
        // role split must actually mean something.
        Route::resource('users', AdminUserController::class)->except(['show']);
    });
});

// Catch-all static CMS page route — must stay last so it never shadows a more
// specific route above (contact, sitemap, category/package routes, admin/*).
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('pages.show');
