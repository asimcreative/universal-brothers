<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PackageController extends Controller
{
    public function category(Request $request, string $categorySlug): View|Response
    {
        $category = PackageCategory::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();

        $packages = $category->packages()
            ->published()
            ->with('series')
            ->when($request->filled('series'), fn ($q) => $q->whereHas('series', fn ($s) => $s->where('slug', $request->string('series'))))
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->paginate(9)
            ->withQueryString();

        $series = $category->series()->where('is_active', true)->orderBy('sort_order')->get();

        return view('packages.category', compact('category', 'packages', 'series'));
    }

    public function show(string $categorySlug, Package $package): View
    {
        abort_unless($package->category->slug === $categorySlug && $package->status === 'published', 404);

        $package->load('itineraryDays', 'priceTiers.roomPrices', 'inclusions', 'exclusions', 'series', 'category');

        $related = Package::published()
            ->where('package_category_id', $package->package_category_id)
            ->where('id', '!=', $package->id)
            ->orderBy('is_featured', 'desc')
            ->limit(3)
            ->get();

        return view('packages.show', compact('package', 'related'));
    }
}
