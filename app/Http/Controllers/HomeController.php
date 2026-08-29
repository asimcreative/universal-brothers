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
            $query->published()->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(6);
        }])->orderBy('sort_order')->get();

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
