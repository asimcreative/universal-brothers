<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageAddon;
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

        // Every package here was just loaded through $category, so avoid an
        // N+1 lazy-load of $package->category (used by package-card) per card.
        foreach ($packages as $package) {
            $package->setRelation('category', $category);
        }

        return view('packages.category', compact('category', 'packages', 'series'));
    }

    public function show(string $categorySlug, Package $package): View
    {
        $package->load('itineraryDays', 'priceTiers.roomPrices', 'inclusions', 'exclusions', 'series', 'category');

        abort_unless($package->category->slug === $categorySlug && $package->status === 'published', 404);

        $related = Package::published()
            ->where('package_category_id', $package->package_category_id)
            ->where('id', '!=', $package->id)
            ->orderBy('is_featured', 'desc')
            ->limit(3)
            ->get();

        // $related all share the same category as $package, already loaded above.
        foreach ($related as $relatedPackage) {
            $relatedPackage->setRelation('category', $package->category);
        }

        // Add-ons attached directly to this package, plus ones that apply
        // category-wide (e.g. the real brochure's Kaba view supplement,
        // extra-night pricing, VIP transport — seeded at the Hajj category
        // level since they apply across its packages, not one specifically).
        $addons = PackageAddon::where('is_active', true)
            ->where(function ($query) use ($package) {
                $query->where('package_id', $package->id)
                    ->orWhere(function ($categoryWide) use ($package) {
                        $categoryWide->whereNull('package_id')
                            ->where('package_category_id', $package->package_category_id);
                    });
            })
            ->orderBy('sort_order')
            ->get()
            // The Hajj seeder names series-specific add-ons explicitly (e.g.
            // "Kaba view supplement (Aziziya series)") since the schema has
            // no formal per-series add-on link — filter out the variant that
            // doesn't match this package's own Aziziya status rather than
            // showing both on every package.
            ->reject(function (PackageAddon $addon) use ($package) {
                if ($package->has_aziziya === true) {
                    return str_contains($addon->name, '(Non-Aziziya series)');
                }
                if ($package->has_aziziya === false) {
                    return str_contains($addon->name, '(Aziziya series)');
                }

                return false;
            });

        return view('packages.show', compact('package', 'related', 'addons'));
    }
}
