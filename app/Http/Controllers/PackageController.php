<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageRoomOption;
use App\Models\PackageVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function category(Request $request, string $categorySlug): View
    {
        $category = PackageCategory::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();

        $query = $category->packages()
            ->published()
            ->with('series')
            ->when($request->filled('series'), fn ($q) => $q->whereHas('series', fn ($s) => $s->where('slug', $request->string('series'))));

        $hajjFilters = null;

        if ($category->slug === 'hajj') {
            $hajjFilters = $this->hajjFilterOptions($category->id);
            $query = $this->applyHajjFilters($query, $request);
        }

        $packages = $query->orderBy('is_featured', 'desc')->orderBy('sort_order')->paginate(9)->withQueryString();

        $series = $category->series()->where('is_active', true)->orderBy('sort_order')->get();

        // Every package here was just loaded through $category, so avoid an
        // N+1 lazy-load of $package->category (used by package-card) per card.
        foreach ($packages as $package) {
            $package->setRelation('category', $category);
        }

        return view('packages.category', compact('category', 'packages', 'series', 'hajjFilters'));
    }

    public function show(string $categorySlug, Package $package): View
    {
        $package->load('itineraryDays', 'inclusions', 'exclusions', 'series', 'category');

        abort_unless($package->category->slug === $categorySlug && $package->status === 'published', 404);

        if ($package->isHajj()) {
            return $this->showHajj($package);
        }

        $package->load('priceTiers.roomPrices');

        $related = Package::published()
            ->where('package_category_id', $package->package_category_id)
            ->where('id', '!=', $package->id)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        // $related all share the same category as $package, already loaded above.
        foreach ($related as $relatedPackage) {
            $relatedPackage->setRelation('category', $package->category);
        }

        return view('packages.show', compact('package', 'related'));
    }

    private function showHajj(Package $package): View
    {
        $package->load([
            'variants', 'accommodations.variant', 'roomOptions.variant',
            'aziziya.roomOptions.variant', 'aziziya.services', 'mashaerDetails',
            'transportation', 'packageNotes', 'upgrades', 'media',
        ]);

        $related = Package::published()
            ->where('package_category_id', $package->package_category_id)
            ->where('id', '!=', $package->id)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        foreach ($related as $relatedPackage) {
            $relatedPackage->setRelation('category', $package->category);
        }

        return view('packages.show-hajj', compact('package', 'related'));
    }

    /**
     * Real, live filter option lists for the Hajj listing — every value is
     * queried from actual seeded data, never a fixed/guessed enum. A 4th (or
     * 5th) sharing type or variant code that shows up in future packages
     * appears here automatically, with no template change required.
     */
    private function hajjFilterOptions(int $categoryId): array
    {
        return [
            'days' => Package::where('package_category_id', $categoryId)->published()
                ->whereNotNull('duration_days')->distinct()->orderBy('duration_days')
                ->pluck('duration_days'),
            'variants' => PackageVariant::whereHas('package', fn ($q) => $q->where('package_category_id', $categoryId)->published())
                ->select('code')->distinct()->orderBy('code')->pluck('code'),
            'sharingTypes' => PackageRoomOption::whereHas('package', fn ($q) => $q->where('package_category_id', $categoryId)->published())
                ->select('sharing_type')->distinct()->orderBy('sharing_type')->pluck('sharing_type'),
        ];
    }

    private function applyHajjFilters($query, Request $request)
    {
        $priceMin = $request->filled('price_min') && is_numeric($request->input('price_min')) ? (float) $request->input('price_min') : null;
        $priceMax = $request->filled('price_max') && is_numeric($request->input('price_max')) ? (float) $request->input('price_max') : null;

        return $query
            ->when($request->filled('days') && is_numeric($request->input('days')), fn ($q) => $q->where('duration_days', (int) $request->input('days')))
            ->when($request->filled('variant'), fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('code', $request->string('variant'))))
            ->when($request->filled('arrival'), function ($q) use ($request) {
                $q->where('medinah_first', $request->input('arrival') === 'madina');
            })
            ->when($request->filled('aziziya') && $request->input('aziziya') !== 'any', function ($q) use ($request) {
                $q->whereHas('aziziya', fn ($a) => $a->where('status', $request->string('aziziya')));
            })
            ->when($request->boolean('star5'), function ($q) {
                $q->whereHas('accommodations', fn ($a) => $a->where('star_rating', 5));
            })
            ->when($request->filled('sharing'), function ($q) use ($request) {
                $q->whereHas('roomOptions', fn ($r) => $r->where('sharing_type', $request->string('sharing')));
            })
            // Both bounds must be checked against the SAME room option — two
            // independent whereHas() calls would each compile to their own
            // EXISTS subquery, so a package with one $800 room and one $3,000
            // room would satisfy a $1,000-$2,000 filter even though no single
            // room is actually in that range.
            ->when($priceMin !== null || $priceMax !== null, function ($q) use ($priceMin, $priceMax) {
                $q->whereHas('roomOptions', function ($r) use ($priceMin, $priceMax) {
                    if ($priceMin !== null) {
                        $r->where('price_usd', '>=', $priceMin);
                    }
                    if ($priceMax !== null) {
                        $r->where('price_usd', '<=', $priceMax);
                    }
                });
            });
    }
}
