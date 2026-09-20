<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
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

        // The services a pilgrim gets at each stage of the journey, gathered
        // for the page rather than for one package. Every package carries the
        // same Mina and Arafat arrangements — they are the company's standing
        // offering, not a per-package variable — so one representative row per
        // stage is the whole truth, and `distinct` proves it rather than
        // assuming it.
        $stages = DB::table('package_mashaer_details')
            ->select('location', 'maktab', 'category', 'zone', 'tent_type', 'accommodation_type',
                'meal_plan', 'bathroom', 'air_conditioning', 'other_services', 'notes')
            ->distinct()
            ->orderBy('location')
            ->get()
            ->groupBy('location');

        // Routes only. Including the note made every row distinct, because a
        // note mentions the package it belongs to, and the page would have
        // listed the same six journeys 174 times.
        $transport = DB::table('package_transportation')
            ->select('from_location', 'to_location', 'transport_type')
            ->selectRaw('MAX(is_included) as is_included')
            ->groupBy('from_location', 'to_location', 'transport_type')
            ->orderBy('transport_type')
            ->get();

        return view('hajj-services', compact('packages', 'faqs', 'nextFlightDate', 'minaCampLocation', 'stages', 'transport'));
    }
}
