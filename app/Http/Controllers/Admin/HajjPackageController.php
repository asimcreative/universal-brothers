<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HajjPackageRequest;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Dedicated admin surface for Hajj packages — deliberately separate from
 * the generic Admin\PackageController (which Tourism/Umrah keep using
 * unchanged). Hajj packages need Package A/B variants, dynamic sharing
 * types, a fully separate Aziziya sub-schema, Mina/Arafat detail,
 * transportation, structured notes and upgrades — none of which the
 * generic packages/tiers/room-prices tables support without either
 * hardcoding a fixed room-type enum or conflating Aziziya pricing with the
 * main package price, both explicitly ruled out. See
 * docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md for the source data
 * this form's fields are modeled on.
 */
class HajjPackageController extends Controller
{
    public function index(): View
    {
        $category = PackageCategory::where('slug', 'hajj')->firstOrFail();

        $packages = Package::where('package_category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.hajj-packages.index', compact('packages'));
    }

    public function create(): View
    {
        $category = PackageCategory::where('slug', 'hajj')->firstOrFail();

        return view('admin.hajj-packages.form', [
            'package' => new Package(['package_category_id' => $category->id]),
            'series' => PackageSeries::where('package_category_id', $category->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(HajjPackageRequest $request): RedirectResponse
    {
        $category = PackageCategory::where('slug', 'hajj')->firstOrFail();

        $package = new Package($request->safe()->except(['cover_image', 'inclusions_text', 'exclusions_text']));
        $package->package_category_id = $category->id;
        $package->currency = 'USD';
        $this->applyBooleans($package, $request);

        if ($request->hasFile('cover_image')) {
            $package->cover_image = $request->file('cover_image')->store('packages', 'public');
        }

        $package->save();

        $this->syncNestedData($package, $request);

        return redirect()->route('admin.hajj-packages.index')->with('status', 'Hajj package created.');
    }

    public function edit(Package $package): View
    {
        $package->load([
            'itineraryDays', 'inclusions', 'exclusions', 'variants', 'accommodations.variant',
            'roomOptions.variant', 'aziziya.roomOptions.variant', 'aziziya.services', 'mashaerDetails',
            'transportation', 'packageNotes', 'upgrades', 'media',
        ]);

        return view('admin.hajj-packages.form', [
            'package' => $package,
            'series' => PackageSeries::where('package_category_id', $package->package_category_id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(HajjPackageRequest $request, Package $package): RedirectResponse
    {
        $package->fill($request->safe()->except(['cover_image', 'inclusions_text', 'exclusions_text']));
        $this->applyBooleans($package, $request);

        if ($request->hasFile('cover_image')) {
            if ($package->cover_image) {
                Storage::disk('public')->delete($package->cover_image);
            }
            $package->cover_image = $request->file('cover_image')->store('packages', 'public');
        }

        $package->save();

        $this->syncNestedData($package, $request);

        return redirect()->route('admin.hajj-packages.index')->with('status', 'Hajj package updated.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('admin.hajj-packages.index')->with('status', 'Hajj package deleted.');
    }

    private function applyBooleans(Package $package, Request $request): void
    {
        foreach (['is_shifting', 'medinah_first', 'is_featured'] as $flag) {
            $package->{$flag} = $request->boolean($flag);
        }

        if ($package->status === 'published' && ! $package->published_at) {
            $package->published_at = now();
        }
    }

    /**
     * Wrapped in a single transaction: this method deletes and recreates
     * ten related tables in sequence, three of which cascade-delete further
     * child rows via `variant_id` foreign keys. Without a transaction, an
     * exception partway through (e.g. a validation gap letting through a
     * bad enum value, or two variants resolving to the same code) would
     * leave a live, published package with its old pricing/accommodation
     * data already cascade-deleted and no replacement ever created —
     * silent, permanent data loss with a raw 500. See
     * FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1.
     */
    private function syncNestedData(Package $package, Request $request): void
    {
        DB::transaction(function () use ($package, $request) {
            $this->syncNestedDataWithinTransaction($package, $request);
        });
    }

    private function syncNestedDataWithinTransaction(Package $package, Request $request): void
    {
        // Variants first — every other nested table resolves its own
        // "which variant" input by matching the free-text `code` typed in
        // this section (e.g. "A"/"B"), not by array index, so rows can be
        // added/removed/reordered in any repeater without a fragile
        // client-side index-linking scheme.
        $package->variants()->delete();
        $variantIdsByCode = [];
        foreach ($request->input('variants', []) as $i => $row) {
            if (blank($row['code'] ?? null)) {
                continue;
            }
            $normalizedCode = strtoupper(trim($row['code']));
            $variant = $package->variants()->create([
                'code' => $row['code'],
                'label' => $row['label'] ?? null,
                'sort_order' => $i,
            ]);
            $variantIdsByCode[$normalizedCode] = $variant->id;
        }
        // Trimmed/uppercased on both sides so " A" and "a" both resolve to
        // the same variant a row typed "A" — HajjPackageRequest's
        // withValidator() already rejects a genuinely unresolvable or
        // duplicate code before this runs, so a null result here means
        // "this row intentionally applies to every variant", never "the
        // reference silently failed to match" (see
        // FINAL_CODE_REVIEW_HAJJ_REDESIGN.md H-2).
        $resolveVariant = fn (?string $code) => blank($code) ? null : ($variantIdsByCode[strtoupper(trim($code))] ?? null);

        // `has_aziziya` is the legacy flat flag other parts of the app still
        // read (e.g. the admin package list badge) — kept in sync with the
        // richer `package_aziziya.status` below, meaning "the base package
        // includes Aziziya accommodation", not merely "an optional Aziziya
        // upgrade is offered" (every Non-Aziziya package in this brochure
        // offers one, which must not flip this flag true).
        $package->has_aziziya = ($request->input('aziziya.status') === 'included');
        $package->save();

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
                'notes' => $row['notes'] ?? null,
            ]);
        }

        $package->accommodations()->delete();
        foreach ($request->input('accommodations', []) as $i => $row) {
            if (blank($row['hotel_name'] ?? null)) {
                continue;
            }
            $package->accommodations()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'location' => $row['location'],
                'hotel_name' => $row['hotel_name'],
                'star_rating' => $row['star_rating'] ?? null,
                'meal_plan' => $row['meal_plan'] ?? null,
                'distance_note' => $row['distance_note'] ?? null,
                'nights' => $row['nights'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        $package->roomOptions()->delete();
        foreach ($request->input('room_options', []) as $i => $row) {
            if (blank($row['sharing_type'] ?? null)) {
                continue;
            }
            $package->roomOptions()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'sharing_type' => $row['sharing_type'],
                'occupancy' => $row['occupancy'] ?? null,
                'display_label' => $row['display_label'] ?? $row['sharing_type'],
                'price_basis' => $row['price_basis'] ?? 'per_person',
                'price_pkr' => $row['price_pkr'] ?? null,
                'price_sar' => $row['price_sar'] ?? null,
                'price_usd' => $row['price_usd'] ?? null,
                'is_available' => ! empty($row['is_available']),
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        $this->syncAziziya($package, $request, $resolveVariant);

        $package->mashaerDetails()->delete();
        foreach (['mina', 'arafat'] as $location) {
            $row = $request->input("mashaer.{$location}", []);
            if (collect($row)->filter()->isEmpty()) {
                continue;
            }
            $package->mashaerDetails()->create(array_merge(['location' => $location], $row));
        }

        $package->transportation()->delete();
        foreach ($request->input('transportation', []) as $i => $row) {
            if (blank($row['transport_type'] ?? null)) {
                continue;
            }
            $package->transportation()->create([
                'from_location' => $row['from_location'] ?? null,
                'to_location' => $row['to_location'] ?? null,
                'transport_type' => $row['transport_type'],
                'is_included' => ! empty($row['is_included']),
                'price' => $row['price'] ?? null,
                'currency' => ($row['price'] ?? null) !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        $package->packageNotes()->delete();
        foreach ($request->input('notes', []) as $i => $row) {
            if (blank($row['content'] ?? null)) {
                continue;
            }
            $package->packageNotes()->create([
                'note_type' => $row['note_type'] ?? 'general',
                'title' => $row['title'] ?? null,
                'content' => $row['content'],
                'is_important' => ! empty($row['is_important']),
                'sort_order' => $i,
            ]);
        }

        $package->upgrades()->delete();
        foreach ($request->input('upgrades', []) as $i => $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }
            $package->upgrades()->create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price' => $row['price'] ?? null,
                'currency' => ($row['price'] ?? null) !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'is_included' => ! empty($row['is_included']),
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        // A file input can never be pre-filled by the browser, so an
        // unresubmitted image is expected on every edit, not a signal to
        // delete it. Each row carries a hidden `id` for its existing
        // PackageMedia row (blank for a brand-new row); only rows whose id
        // was NOT resubmitted are deleted, and a resubmitted row with no
        // new file keeps its existing image_path rather than losing it.
        // See FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-2.
        $submittedMediaIds = [];
        foreach ($request->input('media', []) as $i => $row) {
            $file = $request->file("media.{$i}.file");
            $existingId = $row['id'] ?? null;
            $existing = $existingId ? $package->media()->find($existingId) : null;

            if (! $file && ! $existing && blank($row['video_url'] ?? null)) {
                continue;
            }

            $attributes = [
                'media_type' => $row['media_type'] ?? 'gallery',
                'video_url' => $row['video_url'] ?? null,
                'alt_text' => $row['alt_text'] ?? null,
                'caption' => $row['caption'] ?? null,
                'sort_order' => $i,
            ];

            if ($file) {
                if ($existing && $existing->image_path) {
                    Storage::disk('public')->delete($existing->image_path);
                }
                $attributes['image_path'] = $file->store('packages/media', 'public');
            } elseif ($existing) {
                $attributes['image_path'] = $existing->image_path;
            }

            if ($existing) {
                $existing->update($attributes);
                $submittedMediaIds[] = $existing->id;
            } else {
                $submittedMediaIds[] = $package->media()->create($attributes)->id;
            }
        }
        $package->media()->whereNotIn('id', $submittedMediaIds)->get()->each(function ($media) {
            if ($media->image_path) {
                Storage::disk('public')->delete($media->image_path);
            }
            $media->delete();
        });

        $package->inclusions()->delete();
        $this->createFeatureLines($package, $request->input('inclusions_text') ?? '', 'inclusion');

        $package->exclusions()->delete();
        $this->createFeatureLines($package, $request->input('exclusions_text') ?? '', 'exclusion');

        $lowestUsd = $package->roomOptions()->where('is_available', true)->whereNotNull('price_usd')->min('price_usd');
        if ($lowestUsd !== null) {
            $package->forceFill(['starting_price' => $lowestUsd])->save();
        }
    }

    private function syncAziziya(Package $package, Request $request, Closure $resolveVariant): void
    {
        $package->aziziya()->delete();
        $azData = $request->input('aziziya', []);
        if (blank($azData['status'] ?? null)) {
            return;
        }

        $aziziya = $package->aziziya()->create([
            'status' => $azData['status'],
            'accommodation_name' => $azData['accommodation_name'] ?? null,
            'location_note' => $azData['location_note'] ?? null,
            'walk_distance' => $azData['walk_distance'] ?? null,
            'duration_days' => $azData['duration_days'] ?? null,
            'average_occupancy' => $azData['average_occupancy'] ?? null,
            'description' => $azData['description'] ?? null,
            'notes' => $azData['notes'] ?? null,
        ]);

        foreach ($request->input('aziziya_room_options', []) as $i => $row) {
            if (blank($row['sharing_type'] ?? null)) {
                continue;
            }
            $aziziya->roomOptions()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'sharing_type' => $row['sharing_type'],
                'occupancy' => $row['occupancy'] ?? null,
                'display_label' => $row['display_label'] ?? $row['sharing_type'],
                'pricing_type' => $row['pricing_type'] ?? 'included',
                'price_basis' => $row['price_basis'] ?? 'per_person',
                'price_pkr' => $row['price_pkr'] ?? null,
                'price_sar' => $row['price_sar'] ?? null,
                'price_usd' => $row['price_usd'] ?? null,
                'description' => $row['description'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        foreach ($request->input('aziziya_services', []) as $i => $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }
            $aziziya->services()->create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'is_included' => ! empty($row['is_included']),
                'price' => $row['price'] ?? null,
                'currency' => ($row['price'] ?? null) !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
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
