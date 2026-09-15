<?php

namespace App\Support\Library;

use App\Models\Hotel;
use App\Models\ItineraryTemplate;
use App\Models\MashaerLocation;
use App\Models\MealPlan;
use App\Models\NoteTemplate;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use App\Models\PackageTemplate;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;

/**
 * The pick-lists the package builder offers: active library records, journey
 * and package templates, and the other Hajj packages content can be copied
 * from. Embedded in the page as JSON once, so the builder needs no extra
 * request to open a picker.
 *
 * Archived records are left out — but a record a package already links to is
 * still shown for that package, so opening an older package never makes one
 * of its own hotels look unknown.
 */
class PackageBuilderData
{
    public static function for(Package $package): array
    {
        $linkedHotelIds = $package->exists ? $package->accommodations()->pluck('hotel_id')->filter()->all() : [];
        $used = fn (string $type) => LibraryRegistry::find($type)?->usageCounts() ?? [];
        [$hotelUse, $mealUse, $transportUse, $inclusionUse, $exclusionUse, $upgradeUse, $mashaerUse, $noteUse] =
            array_map($used, ['hotels', 'meal-plans', 'transport', 'inclusions', 'exclusions', 'upgrades', 'mashaer', 'notes']);

        return [
            'hotels' => Hotel::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $linkedHotelIds))
                ->ordered()
                ->get(['id', 'name', 'city', 'location', 'address', 'star_rating', 'description', 'website_url', 'map_url', 'is_active'])
                ->map(fn (Hotel $h) => [
                    'id' => $h->id, 'name' => $h->name, 'location' => $h->location, 'location_label' => $h->locationLabel(),
                    'city' => $h->city, 'address' => $h->address, 'star_rating' => $h->star_rating, 'description' => $h->description,
                    'website_url' => $h->website_url, 'map_url' => $h->map_url, 'archived' => ! $h->is_active, 'used' => $hotelUse[$h->id] ?? 0,
                ])
                ->values(),

            'mealPlans' => MealPlan::active()->ordered()->get(['id', 'name'])
                ->map(fn (MealPlan $m) => ['id' => $m->id, 'name' => $m->name, 'used' => $mealUse[$m->id] ?? 0])->values(),

            'transport' => TransportOption::active()->ordered()->get()
                ->map(fn (TransportOption $t) => [
                    'id' => $t->id, 'name' => $t->name, 'transport_type' => $t->transport_type,
                    'from_location' => $t->from_location, 'to_location' => $t->to_location,
                    'is_included' => $t->is_included, 'price' => $t->price !== null ? (float) $t->price : null,
                    'currency' => $t->currency, 'price_basis' => $t->price_basis, 'notes' => $t->notes, 'used' => $transportUse[$t->id] ?? 0,
                ])->values(),

            'inclusions' => ServiceItem::inclusions()->active()->ordered()->get(['id', 'title', 'description', 'category'])
                ->map(fn (ServiceItem $s) => [...$s->only(['id', 'title', 'description', 'category']), 'used' => $inclusionUse[$s->id] ?? 0])->values(),
            'exclusions' => ServiceItem::exclusions()->active()->ordered()->get(['id', 'title', 'description', 'category'])
                ->map(fn (ServiceItem $s) => [...$s->only(['id', 'title', 'description', 'category']), 'used' => $exclusionUse[$s->id] ?? 0])->values(),

            'upgrades' => UpgradeOption::active()->ordered()->get()
                ->map(fn (UpgradeOption $u) => [
                    'id' => $u->id, 'name' => $u->name, 'description' => $u->description,
                    'price' => $u->price !== null ? (float) $u->price : null, 'currency' => $u->currency,
                    'price_basis' => $u->price_basis, 'is_included' => $u->is_included, 'notes' => $u->conditions, 'used' => $upgradeUse[$u->id] ?? 0,
                ])->values(),

            'mashaer' => MashaerLocation::active()->ordered()->get()
                ->map(fn (MashaerLocation $m) => array_merge(
                    ['id' => $m->id, 'name' => $m->name, 'location' => $m->location, 'used' => $mashaerUse[$m->id] ?? 0, 'edit_url' => route('admin.library.edit', ['mashaer', $m->id])],
                    collect(MashaerLocation::FACT_FIELDS)->mapWithKeys(fn ($f) => [$f => $m->{$f}])->all(),
                ))->values(),

            'notes' => NoteTemplate::active()->ordered()->get()
                ->map(fn (NoteTemplate $n) => [
                    'id' => $n->id, 'title' => $n->title, 'heading' => $n->heading, 'category' => $n->category,
                    'note_type' => $n->note_type, 'content' => $n->content, 'is_important' => $n->is_important, 'used' => $noteUse[$n->id] ?? 0,
                ])->values(),

            'journeyTemplates' => ItineraryTemplate::active()->ordered()->get(['id', 'name', 'days'])->values(),

            'packageTemplates' => PackageTemplate::active()->ordered()->get(['id', 'name', 'description'])->values(),

            'packages' => Package::query()
                ->where('package_category_id', PackageCategory::where('slug', 'hajj')->value('id'))
                ->when($package->exists, fn ($q) => $q->where('id', '!=', $package->id))
                ->notArchived()
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name', 'status'])
                ->map(fn (Package $p) => [
                    'id' => $p->id, 'label' => trim("{$p->code} — {$p->name}", ' —').($p->isPublished() ? '' : ' (draft)'),
                    'url' => route('admin.hajj-packages.content', $p),
                ])->values(),

            'series' => PackageSeries::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->orderBy('sort_order')->get(['id', 'name'])->values(),
        ];
    }
}
