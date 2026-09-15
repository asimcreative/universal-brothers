<?php

namespace App\Support\Packages;

use App\Models\MashaerLocation;
use App\Models\Package;
use App\Models\PackageTemplate;

/**
 * One array shape for a Hajj package, used everywhere a package's content has
 * to move as a whole:
 *
 * - the package builder renders from it (edit, create, create-from-template);
 * - HajjPackageWriter saves from it (the builder's submission has this shape);
 * - templates store it, duplication copies through it, and "copy this section
 *   from another package" reads a slice of it as JSON.
 *
 * Keys are the builder's own input names, so a validated request, a stored
 * template payload and a snapshot of a live package are interchangeable.
 */
class PackageFormState
{
    /** Top-level package fields a template may carry. */
    public const TEMPLATE_BASICS = [
        'package_type', 'summary', 'description', 'duration_days', 'duration_label',
        'medinah_first', 'is_shifting', 'season_year', 'season_label', 'package_series_id',
    ];

    /** Nested sections, in builder order. */
    public const SECTIONS = [
        'variants', 'room_options', 'accommodations', 'aziziya', 'aziziya_room_options', 'aziziya_services',
        'itinerary', 'mashaer', 'transportation', 'inclusions', 'exclusions', 'upgrades', 'notes',
    ];

    public const MASHAER_LOCATIONS = ['mina', 'arafat', 'muzdalifah'];

    public static function blank(): array
    {
        return [
            'code' => null, 'name' => null, 'package_type' => null, 'slug' => null,
            'summary' => null, 'description' => null, 'duration_days' => null, 'duration_label' => null,
            'medinah_first' => true, 'is_shifting' => false, 'season_year' => null, 'season_label' => null,
            'is_featured' => false, 'status' => 'draft', 'sort_order' => null, 'package_series_id' => null,
            'meta_title' => null, 'meta_description' => null, 'internal_notes' => null,
            'variants' => [], 'room_options' => [], 'accommodations' => [],
            'aziziya' => ['status' => null], 'aziziya_room_options' => [], 'aziziya_services' => [],
            'itinerary' => [], 'mashaer' => array_fill_keys(self::MASHAER_LOCATIONS, []),
            'transportation' => [], 'inclusions' => [], 'exclusions' => [], 'upgrades' => [], 'notes' => [],
            'media' => [],
        ];
    }

    /**
     * Every relation fromPackage() reads — load these first to avoid a query
     * per section.
     */
    public const RELATIONS = [
        'variants', 'accommodations.variant', 'roomOptions.variant', 'aziziya.roomOptions.variant',
        'aziziya.services', 'mashaerDetails', 'transportation', 'packageNotes', 'upgrades', 'media',
        'itineraryDays', 'inclusions', 'exclusions',
    ];

    public static function fromPackage(Package $package): array
    {
        $package->loadMissing(self::RELATIONS);

        $mashaer = array_fill_keys(self::MASHAER_LOCATIONS, []);
        foreach ($package->mashaerDetails as $row) {
            $mashaer[$row->location] = array_merge(
                ['mashaer_location_id' => $row->mashaer_location_id],
                collect(MashaerLocation::FACT_FIELDS)->mapWithKeys(fn ($f) => [$f => $row->{$f}])->all(),
            );
        }

        $aziziya = $package->aziziya;

        return array_merge(self::blank(), [
            'code' => $package->code,
            'name' => $package->name,
            'package_type' => $package->package_type,
            'slug' => $package->slug,
            'summary' => $package->summary,
            'description' => $package->description,
            'duration_days' => $package->duration_days,
            'duration_label' => $package->duration_label,
            'medinah_first' => (bool) $package->medinah_first,
            'is_shifting' => (bool) $package->is_shifting,
            'season_year' => $package->season_year,
            'season_label' => $package->season_label,
            'is_featured' => (bool) $package->is_featured,
            'status' => $package->status ?: 'draft',
            'sort_order' => $package->sort_order,
            'package_series_id' => $package->package_series_id,
            'meta_title' => $package->meta_title,
            'meta_description' => $package->meta_description,
            'internal_notes' => $package->internal_notes,

            'variants' => $package->variants->map(fn ($v) => ['code' => $v->code, 'label' => $v->label])->values()->all(),

            'accommodations' => $package->accommodations->map(fn ($a) => [
                'location' => $a->location, 'variant_code' => $a->variant?->code, 'hotel_id' => $a->hotel_id,
                'hotel_name' => $a->hotel_name, 'star_rating' => $a->star_rating, 'meal_plan' => $a->meal_plan,
                'meal_plan_id' => $a->meal_plan_id, 'distance_note' => $a->distance_note, 'nights' => $a->nights,
                'notes' => $a->notes,
            ])->values()->all(),

            'room_options' => $package->roomOptions->map(fn ($r) => [
                'variant_code' => $r->variant?->code, 'sharing_type' => $r->sharing_type, 'occupancy' => $r->occupancy,
                'display_label' => $r->display_label, 'price_basis' => $r->price_basis,
                'price_pkr' => self::money($r->price_pkr), 'price_sar' => self::money($r->price_sar),
                'price_usd' => self::money($r->price_usd), 'is_available' => (bool) $r->is_available, 'notes' => $r->notes,
            ])->values()->all(),

            'aziziya' => $aziziya ? [
                'status' => $aziziya->status, 'accommodation_name' => $aziziya->accommodation_name,
                'location_note' => $aziziya->location_note, 'walk_distance' => $aziziya->walk_distance,
                'duration_days' => $aziziya->duration_days, 'average_occupancy' => $aziziya->average_occupancy,
                'description' => $aziziya->description, 'notes' => $aziziya->notes,
            ] : ['status' => null],

            'aziziya_room_options' => $aziziya ? $aziziya->roomOptions->map(fn ($r) => [
                'variant_code' => $r->variant?->code, 'sharing_type' => $r->sharing_type, 'occupancy' => $r->occupancy,
                'display_label' => $r->display_label, 'pricing_type' => $r->pricing_type, 'price_basis' => $r->price_basis,
                'price_pkr' => self::money($r->price_pkr), 'price_sar' => self::money($r->price_sar),
                'price_usd' => self::money($r->price_usd), 'description' => $r->description, 'notes' => $r->notes,
            ])->values()->all() : [],

            'aziziya_services' => $aziziya ? $aziziya->services->map(fn ($s) => [
                'name' => $s->name, 'description' => $s->description, 'is_included' => (bool) $s->is_included,
                'price' => self::money($s->price), 'currency' => $s->currency, 'price_basis' => $s->price_basis, 'notes' => $s->notes,
            ])->values()->all() : [],

            'itinerary' => $package->itineraryDays->map(fn ($d) => [
                'day_number' => $d->day_number, 'date_gregorian' => $d->date_gregorian?->format('Y-m-d'),
                'date_hijri_label' => $d->date_hijri_label, 'city' => $d->city,
                'accommodation_a' => $d->accommodation_a, 'accommodation_b' => $d->accommodation_b,
                'transport' => $d->transport, 'notes' => $d->notes,
            ])->values()->all(),

            'mashaer' => $mashaer,

            'transportation' => $package->transportation->map(fn ($t) => [
                'transport_option_id' => $t->transport_option_id, 'from_location' => $t->from_location,
                'to_location' => $t->to_location, 'transport_type' => $t->transport_type, 'is_included' => (bool) $t->is_included,
                'price' => self::money($t->price), 'currency' => $t->currency, 'price_basis' => $t->price_basis, 'notes' => $t->notes,
            ])->values()->all(),

            'inclusions' => $package->inclusions->map(fn ($f) => ['service_item_id' => $f->service_item_id, 'description' => $f->description])->values()->all(),
            'exclusions' => $package->exclusions->map(fn ($f) => ['service_item_id' => $f->service_item_id, 'description' => $f->description])->values()->all(),

            'upgrades' => $package->upgrades->map(fn ($u) => [
                'upgrade_option_id' => $u->upgrade_option_id, 'name' => $u->name, 'description' => $u->description,
                'price' => self::money($u->price), 'currency' => $u->currency, 'price_basis' => $u->price_basis,
                'is_included' => (bool) $u->is_included, 'notes' => $u->notes,
            ])->values()->all(),

            'notes' => $package->packageNotes->map(fn ($n) => [
                'note_template_id' => $n->note_template_id, 'note_type' => $n->note_type, 'title' => $n->title,
                'content' => $n->content, 'is_important' => (bool) $n->is_important,
            ])->values()->all(),

            'media' => $package->media->map(fn ($m) => [
                'id' => $m->id, 'media_type' => $m->media_type, 'image_path' => $m->image_path,
                'video_url' => $m->video_url, 'alt_text' => $m->alt_text, 'caption' => $m->caption,
            ])->values()->all(),
        ]);
    }

    /**
     * What a template stores: the content, never the identity. No code, web
     * address, status, featured flag, SEO, media or internal notes — so a
     * template can never collide with, publish, or leak into a live package.
     */
    public static function forTemplate(array $state): array
    {
        $payload = array_intersect_key($state, array_flip(array_merge(self::TEMPLATE_BASICS, self::SECTIONS)));

        return array_merge(
            array_intersect_key(self::blank(), array_flip(array_merge(self::TEMPLATE_BASICS, self::SECTIONS))),
            $payload,
        );
    }

    public static function fromTemplate(PackageTemplate $template): array
    {
        return array_merge(self::blank(), self::forTemplate($template->payload ?? []));
    }

    /**
     * Merges a submitted-but-rejected form (Laravel's old input) over the
     * starting state, so a validation error never throws away what the admin
     * typed. `image_path` has no input of its own and is restored from the
     * package's real media rows.
     */
    public static function withOldInput(array $state, array $old): array
    {
        if ($old === []) {
            return $state;
        }

        $merged = array_merge($state, $old);

        // Row keys are kept as submitted: a validation error is keyed by them
        // (room_options.7.price_usd), so re-indexing would put the message
        // on the wrong row.
        foreach (['variants', 'room_options', 'accommodations', 'aziziya_room_options', 'aziziya_services', 'itinerary', 'transportation', 'inclusions', 'exclusions', 'upgrades', 'notes', 'media'] as $section) {
            $merged[$section] = $old[$section] ?? [];
        }

        foreach (['is_featured', 'is_shifting'] as $flag) {
            $merged[$flag] = ! empty($old[$flag]);
        }

        $merged['mashaer'] = array_merge(array_fill_keys(self::MASHAER_LOCATIONS, []), $old['mashaer'] ?? []);
        $merged['aziziya'] = $old['aziziya'] ?? ['status' => null];

        $pathsById = collect($state['media'] ?? [])->keyBy('id');
        $merged['media'] = collect($merged['media'])->map(function ($row) use ($pathsById) {
            $row['image_path'] = $pathsById->get($row['id'] ?? null)['image_path'] ?? null;

            return $row;
        })->all();

        return $merged;
    }

    /** Decimal casts come back as "16300.00"; the builder shows "16300". */
    private static function money($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (string) (int) $number : (string) $number;
    }
}
