<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\NewsArticle;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Slider;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $hajjCategory = PackageCategory::where('slug', 'hajj')->where('is_active', true)->first();
        $umrahCategory = PackageCategory::where('slug', 'umrah')->where('is_active', true)->first();

        $hajjPackages = $hajjCategory
            ? $hajjCategory->packages()->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(3)->get()
            : collect();
        $umrahPackages = $umrahCategory
            ? $umrahCategory->packages()->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(3)->get()
            : collect();

        // Both lists were just loaded through their own category, so it's
        // already in memory — avoids an N+1 lazy-load of $package->category
        // (used by package-card) per card.
        $hajjPackages->each(fn (Package $package) => $package->setRelation('category', $hajjCategory));
        $umrahPackages->each(fn (Package $package) => $package->setRelation('category', $umrahCategory));

        $sliders = Slider::where('page_context', 'home')->where('is_active', true)->orderBy('sort_order')->get();

        // Video must be prioritized over text (see FRONTEND_IMPLEMENTATION_PLAN.md
        // item 15) — splitting by video/text BEFORE capping each side
        // independently, rather than capping the combined pool first, so a
        // video testimonial ranked outside the top 6 by sort_order still
        // displays instead of being silently excluded by the cap.
        $videoTestimonials = Testimonial::where('is_active', true)->whereNotNull('video_url')
            ->orderBy('sort_order')->limit(3)->get();
        $textTestimonials = Testimonial::where('is_active', true)->whereNull('video_url')
            ->orderBy('sort_order')->limit(6 - $videoTestimonials->count())->get();

        $news = NewsArticle::where('is_active', true)->latest('published_at')->limit(6)->get();

        $awards = Award::where('is_active', true)->orderBy('sort_order')->limit(6)->get();
        $affiliations = Affiliation::where('is_active', true)->orderBy('sort_order')->limit(8)->get();

        $stats = [
            'years' => SiteSetting::get('years_in_operation', '20+'),
            'pilgrims' => SiteSetting::get('pilgrims_served', '10,000+'),
            'awards_count' => SiteSetting::get('industry_awards_count', '20+'),
            'mina_camp_location' => SiteSetting::get('mina_camp_location'),
            'iata_registered' => SiteSetting::get('iata_registered', '1'),
        ];

        // The animated stat-counter widget needs plain integers to count up
        // to — derived from the same admin-editable facts above (or a live
        // count) rather than separate hardcoded numbers, so the two never
        // drift apart the moment an admin edits a Setting (see
        // FINAL_CODE_REVIEW.md M-2).
        $counters = [
            'years' => (int) preg_replace('/\D/', '', $stats['years']) ?: 0,
            'pilgrims' => (int) preg_replace('/\D/', '', $stats['pilgrims']) ?: 0,
            'hajj_packages' => Package::published()->whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->count(),
            'industry_awards' => (int) preg_replace('/\D/', '', (string) $stats['awards_count']) ?: 0,
        ];

        return view('home', compact(
            'hajjCategory', 'umrahCategory', 'hajjPackages', 'umrahPackages',
            'sliders', 'videoTestimonials', 'textTestimonials', 'news',
            'awards', 'affiliations', 'stats', 'counters'
        ));
    }
}
