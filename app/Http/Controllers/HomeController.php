<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\PackageCategory;
use App\Models\Slider;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = PackageCategory::with(['packages' => function ($query) {
            $query->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(6);
        }])->orderBy('sort_order')->get();

        // Every package here was just loaded *through* its own category, so
        // the category is already in memory — setRelation avoids an N+1
        // lazy-load of $package->category (used by package-card) per card,
        // for zero extra queries rather than one more batched one.
        $categories->each(function (PackageCategory $category) {
            $category->packages->each(fn ($package) => $package->setRelation('category', $category));
        });

        $sliders = Slider::where('page_context', 'home')->where('is_active', true)->orderBy('sort_order')->get();

        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->limit(6)->get();

        $news = NewsArticle::where('is_active', true)->latest('published_at')->limit(3)->get();

        $stats = [
            'years' => SiteSetting::get('years_in_operation', '20+'),
            'pilgrims' => SiteSetting::get('pilgrims_served', '50,000+'),
            'affiliations' => SiteSetting::get('affiliations', ''),
        ];

        return view('home', compact('categories', 'sliders', 'testimonials', 'news', 'stats'));
    }
}
