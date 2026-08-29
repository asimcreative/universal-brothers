<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\NewsArticleController as AdminNewsArticleController;
use App\Http\Controllers\Admin\OfficeController as AdminOfficeController;
use App\Http\Controllers\Admin\PackageCategoryController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiries.store');

Route::get('/{category}', [PackageController::class, 'category'])
    ->whereIn('category', ['hajj', 'umrah', 'tourism'])
    ->name('packages.category');

Route::get('/{category}/{package:slug}', [PackageController::class, 'show'])
    ->whereIn('category', ['hajj', 'umrah', 'tourism'])
    ->name('packages.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('packages', AdminPackageController::class)->except(['show']);

        Route::get('categories', [PackageCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}/edit', [PackageCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [PackageCategoryController::class, 'update'])->name('categories.update');
        Route::post('categories/{category}/series', [PackageCategoryController::class, 'storeSeries'])->name('categories.series.store');
        Route::delete('categories/{category}/series/{series}', [PackageCategoryController::class, 'destroySeries'])->name('categories.series.destroy');

        Route::resource('testimonials', AdminTestimonialController::class)->except(['show']);
        Route::resource('faqs', AdminFaqController::class)->except(['show']);
        Route::resource('sliders', AdminSliderController::class)->except(['show']);
        Route::resource('news', AdminNewsArticleController::class)->except(['show']);
        Route::resource('offices', AdminOfficeController::class)->except(['show']);
        Route::resource('pages', AdminPageController::class)->except(['show']);

        Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('inquiries.show');
        Route::put('inquiries/{inquiry}', [AdminInquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('inquiries/{inquiry}', [AdminInquiryController::class, 'destroy'])->name('inquiries.destroy');

        Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update');
    });
});

// Catch-all static CMS page route — must stay last so it never shadows a more
// specific route above (contact, sitemap, category/package routes, admin/*).
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('pages.show');
