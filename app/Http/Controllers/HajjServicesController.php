<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use Illuminate\View\View;

class HajjServicesController extends Controller
{
    public function index(): View
    {
        $category = PackageCategory::where('slug', 'hajj')->where('is_active', true)->first();

        $packages = $category
            ? $category->packages()->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(6)->get()
            : collect();

        if ($category) {
            $packages->each(fn (Package $package) => $package->setRelation('category', $category));
        }

        $faqs = Faq::where('is_active', true)->where('category', 'hajj')->orderBy('sort_order')->get();

        $nextFlightDate = SiteSetting::get('hajj_next_flight_date');
        $minaCampLocation = SiteSetting::get('mina_camp_location');

        return view('hajj-services', compact('packages', 'faqs', 'nextFlightDate', 'minaCampLocation'));
    }
}
