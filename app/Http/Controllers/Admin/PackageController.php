<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageRequest;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use App\Support\Content\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = Package::query()
            ->with('category', 'series')
            ->whereHas('category', fn ($q) => $q->where('slug', '!=', 'hajj'))
            ->when($request->filled('category'), fn ($q) => $q->where('package_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('package_category_id')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        $categories = $this->nonHajjCategories();

        return view('admin.packages.index', compact('packages', 'categories'));
    }

    public function create(): View
    {
        return view('admin.packages.form', [
            'package' => new Package,
            'categories' => $this->nonHajjCategories(),
            'series' => PackageSeries::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Hajj packages use a completely separate data model (variants,
     * accommodations, Aziziya, Mashaer, room options — none of which this
     * generic controller's form or sync logic knows about) and their own
     * dedicated admin surface (`HajjPackageController`). Route::resource's
     * implicit model binding on `{package}` has no category awareness, so
     * without this exclusion the generic index/create/edit/update routes
     * could reach a real Hajj package by ID and silently corrupt it: `edit`
     * would use a form built for `priceTiers` (Hajj packages price through
     * the unrelated `roomOptions` relation instead), and `update`'s sync
     * unconditionally deletes and replaces the package's real
     * `itineraryDays`/`inclusions`/`exclusions` with whatever that
     * mismatched form happened to submit — found via a visual review of
     * this listing showing real Hajj packages ("UB001", "UB003", ...)
     * mixed into what the admin UI redesign relabeled "Umrah & Tourism
     * Packages", not by reading either controller in isolation.
     */
    private function nonHajjCategories()
    {
        return PackageCategory::where('slug', '!=', 'hajj')->orderBy('sort_order')->get();
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

    public function edit(Package $package): View|RedirectResponse
    {
        if ($package->isHajj()) {
            return redirect()->route('admin.hajj-packages.edit', $package);
        }

        $package->load('itineraryDays', 'priceTiers.roomPrices', 'inclusions', 'exclusions');

        return view('admin.packages.form', [
            'package' => $package,
            'categories' => $this->nonHajjCategories(),
            'series' => PackageSeries::orderBy('sort_order')->get(),
        ]);
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        if ($package->isHajj()) {
            return redirect()->route('admin.hajj-packages.edit', $package);
        }

        $package->fill($request->safe()->except(['cover_image', 'inclusions_text', 'exclusions_text', 'itinerary', 'tiers']));
        $package->description = RichText::clean($request->validated('description'), 'standard');
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

    /**
     * Wraps the delete-then-recreate nested sync in a transaction so a
     * mid-loop failure (e.g. a DB-level constraint violation) can't leave
     * a package's itinerary/pricing/inclusions partially deleted with no
     * replacement — the same real data-loss risk this codebase already
     * found and fixed once for the Hajj admin controller (see
     * FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1). This generic controller had
     * the identical unguarded pattern, just never independently reviewed
     * until now.
     */
    private function syncNestedData(Package $package, Request $request): void
    {
        DB::transaction(function () use ($package, $request) {
            $this->syncNestedDataWithinTransaction($package, $request);
        });
    }

    private function syncNestedDataWithinTransaction(Package $package, Request $request): void
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
        // package_room_prices.room_type is a DB-level enum('sharing','quad',
        // 'triple','double') — filtering to that exact key-set here (not
        // just via the request validation rules, which only describe the 4
        // known keys without rejecting an unlisted one) guarantees no other
        // value can ever reach the create() call and throw an unhandled
        // QueryException mid-sync, regardless of what a request contains.
        $knownRoomTypes = ['sharing', 'quad', 'triple', 'double'];
        foreach ($request->input('tiers', []) as $tierIndex => $tier) {
            $prices = array_intersect_key($tier['prices'] ?? [], array_flip($knownRoomTypes));
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
        $this->createFeatureLines($package, $request->input('inclusions_text') ?? '', 'inclusion');

        $package->exclusions()->delete();
        $this->createFeatureLines($package, $request->input('exclusions_text') ?? '', 'exclusion');

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
