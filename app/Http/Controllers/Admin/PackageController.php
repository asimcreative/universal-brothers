<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageRequest;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = Package::query()
            ->with('category', 'series')
            ->when($request->filled('category'), fn ($q) => $q->where('package_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('package_category_id')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        $categories = PackageCategory::orderBy('sort_order')->get();

        return view('admin.packages.index', compact('packages', 'categories'));
    }

    public function create(): View
    {
        return view('admin.packages.form', [
            'package' => new Package,
            'categories' => PackageCategory::orderBy('sort_order')->get(),
            'series' => PackageSeries::orderBy('sort_order')->get(),
        ]);
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $package = new Package($request->safe()->except(['cover_image', 'inclusions_text', 'exclusions_text', 'itinerary', 'tiers']));
        $this->applyBooleans($package, $request);

        if ($request->hasFile('cover_image')) {
            $package->cover_image = $request->file('cover_image')->store('packages', 'public');
        }

        $package->save();

        $this->syncNestedData($package, $request);

        return redirect()->route('admin.packages.index')->with('status', 'Package created.');
    }

    public function edit(Package $package): View
    {
        $package->load('itineraryDays', 'priceTiers.roomPrices', 'inclusions', 'exclusions');

        return view('admin.packages.form', [
            'package' => $package,
            'categories' => PackageCategory::orderBy('sort_order')->get(),
            'series' => PackageSeries::orderBy('sort_order')->get(),
        ]);
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $package->fill($request->safe()->except(['cover_image', 'inclusions_text', 'exclusions_text', 'itinerary', 'tiers']));
        $this->applyBooleans($package, $request);

        if ($request->hasFile('cover_image')) {
            if ($package->cover_image) {
                Storage::disk('public')->delete($package->cover_image);
            }
            $package->cover_image = $request->file('cover_image')->store('packages', 'public');
        }

        $package->save();

        $this->syncNestedData($package, $request);

        return redirect()->route('admin.packages.index')->with('status', 'Package updated.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('admin.packages.index')->with('status', 'Package deleted.');
    }

    private function applyBooleans(Package $package, Request $request): void
    {
        foreach (['is_shifting', 'has_aziziya', 'is_featured', 'is_seasonal', 'is_promotional'] as $flag) {
            $package->{$flag} = $request->boolean($flag);
        }

        if ($package->status === 'published' && ! $package->published_at) {
            $package->published_at = now();
        }
    }

    private function syncNestedData(Package $package, Request $request): void
    {
        $package->itineraryDays()->delete();
        foreach ($request->input('itinerary', []) as $row) {
            if (blank($row['city'] ?? null) && blank($row['accommodation_a'] ?? null)) {
                continue;
            }
            $package->itineraryDays()->create([
                'day_number' => $row['day_number'] ?? 1,
                'date_gregorian' => $row['date_gregorian'] ?? null,
                'date_hijri_label' => $row['date_hijri_label'] ?? null,
                'city' => $row['city'] ?? null,
                'accommodation_a' => $row['accommodation_a'] ?? null,
                'accommodation_b' => $row['accommodation_b'] ?? null,
            ]);
        }

        $package->priceTiers()->delete();
        foreach ($request->input('tiers', []) as $tierIndex => $tier) {
            $prices = $tier['prices'] ?? [];
            if (collect($prices)->filter(fn ($p) => $p !== null && $p !== '')->isEmpty()) {
                continue;
            }
            $priceTier = $package->priceTiers()->create([
                'label' => $tier['label'] ?? null,
                'sort_order' => $tierIndex,
            ]);

            $roomOrder = ['sharing' => 0, 'quad' => 1, 'triple' => 2, 'double' => 3];
            foreach ($prices as $roomType => $price) {
                if ($price === null || $price === '') {
                    continue;
                }
                $priceTier->roomPrices()->create([
                    'room_type' => $roomType,
                    'price' => $price,
                    'currency' => $package->currency,
                    'sort_order' => $roomOrder[$roomType] ?? 9,
                ]);
            }
        }

        $package->inclusions()->delete();
        $this->createFeatureLines($package, $request->input('inclusions_text', ''), 'inclusion');

        $package->exclusions()->delete();
        $this->createFeatureLines($package, $request->input('exclusions_text', ''), 'exclusion');

        $allPrices = $package->priceTiers()->with('roomPrices')->get()
            ->flatMap(fn ($t) => $t->roomPrices->pluck('price'))
            ->filter(fn ($p) => $p !== null)
            ->map(fn ($p) => (float) $p);

        if ($allPrices->isNotEmpty()) {
            $package->forceFill(['starting_price' => $allPrices->min()])->save();
        }
    }

    private function createFeatureLines(Package $package, string $text, string $type): void
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $relation = $type === 'inclusion' ? $package->inclusions() : $package->exclusions();

        foreach ($lines as $i => $line) {
            $relation->create(['type' => $type, 'description' => $line, 'sort_order' => $i]);
        }
    }
}
