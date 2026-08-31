<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Package;
use App\Models\PackageCategory;
use Illuminate\View\View;

class UmrahServicesController extends Controller
{
    public function index(): View
    {
        $category = PackageCategory::where('slug', 'umrah')->where('is_active', true)->first();

        $packages = $category
            ? $category->packages()->published()->with('series')->orderBy('is_featured', 'desc')->orderBy('sort_order')->limit(6)->get()
            : collect();

        if ($category) {
            $packages->each(fn (Package $package) => $package->setRelation('category', $category));
        }

        $faqs = Faq::where('is_active', true)->where('category', 'umrah')->orderBy('sort_order')->get();

        return view('umrah-services', compact('packages', 'faqs'));
    }
}
