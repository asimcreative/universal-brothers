<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\NewsArticle;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The same homepage data, rendered on the designer's template.
     *
     * Temporary, and deliberately a second action rather than a flag on
     * `index()`: the live homepage must keep rendering exactly as it does while
     * the port is in progress, and the two need to be openable side by side.
     */
    public function templatePreview(): View
    {
        $view = $this->index();

        return view('template.home', $view->getData());
    }

    public function index(): View
    {
        $hajjCategory = PackageCategory::where('slug', 'hajj')->where('is_active', true)->first();
        $umrahCategory = PackageCategory::where('slug', 'umrah')->where('is_active', true)->first();
        $tourismCategory = PackageCategory::where('slug', 'tourism')->where('is_active', true)->first();

        // One featured row per category. The homepage shows all three behind
        // tabs in a single section, so tourism is loaded the same way the
        // other two always were rather than being the only category whose
        // packages the homepage could not show.
        $featured = fn (?PackageCategory $category) => $category
            ? $category->packages()->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(3)->get()
            : collect();

        $hajjPackages = $featured($hajjCategory);
        $umrahPackages = $featured($umrahCategory);
        $tourismPackages = $featured($tourismCategory);

        // Each list was just loaded through its own category, so it's already
        // in memory — avoids an N+1 lazy-load of $package->category (used by
        // package-card) per card.
        $hajjPackages->each(fn (Package $package) => $package->setRelation('category', $hajjCategory));
        $umrahPackages->each(fn (Package $package) => $package->setRelation('category', $umrahCategory));
        $tourismPackages->each(fn (Package $package) => $package->setRelation('category', $tourismCategory));

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

        // "Filter My Packages" is an approved homepage block (see
        // docs/source-documents/1a-website-flow-extracted.md, homepage item g)
        // that had never been built. The duration options are read from the
        // real published Hajj packages rather than hardcoded, so the widget can
        // never offer a length that returns no results, and it submits straight
        // to the existing listing filters — no parallel filtering logic.
        $filterDurations = $hajjCategory
            ? Package::where('package_category_id', $hajjCategory->id)->published()
                ->whereNotNull('duration_days')->distinct()->orderBy('duration_days')
                ->pluck('duration_days')
            : collect();

        return view('home', compact(
            'hajjCategory', 'umrahCategory', 'tourismCategory',
            'hajjPackages', 'umrahPackages', 'tourismPackages',
            'sliders', 'videoTestimonials', 'textTestimonials', 'news',
            'awards', 'affiliations', 'stats', 'counters', 'filterDurations'
        ));
    }
}
