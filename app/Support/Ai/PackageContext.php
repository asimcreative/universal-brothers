<?php

namespace App\Support\Ai;

use App\Models\Package;
use Illuminate\Support\Str;

/**
 * Renders one package into grounded plain text for the model.
 *
 * Every figure here is read from the database at the moment the question is
 * asked. Nothing is cached, converted or inferred. If a currency column is
 * null it is reported as "not published" rather than omitted, because silence
 * invites the model to fill the gap — and the one thing this assistant must
 * never do is invent a Hajj price.
 */
class PackageContext
{
    private const CURRENCIES = [
        'usd' => ['column' => 'price_usd', 'label' => 'USD'],
        'pkr' => ['column' => 'price_pkr', 'label' => 'PKR'],
        'sar' => ['column' => 'price_sar', 'label' => 'SAR'],
    ];

    /**
     * Every relation render() reads.
     *
     * Public and shared so the retriever can eager-load them across the WHOLE
     * package collection in one query each. Without that, render()'s own
     * loadMissing() runs per package and turns three packages into three times
     * the relation queries — a textbook N+1, measured at 45 queries for three
     * packages before this was pulled up to the collection.
     *
     * loadMissing() stays in render() as a safety net for a direct caller, but
     * on the retrieval path it is a no-op because everything is already loaded.
     */
    public const RELATIONS = [
        'category:id,name,slug',
        'series:id,name',
        'variants',
        'roomOptions',
        'accommodations',
        'itineraryDays',
        'inclusions',
        'exclusions',
        'upgrades',
        'transportation',
        'packageNotes',
        'mashaerDetails',
        'aziziya.roomOptions',
        'aziziya.services',
    ];

    /**
     * @param  array<int, string>  $currencies  Currencies the visitor asked about; all three when unspecified.
     */
    public static function render(Package $package, array $currencies = []): string
    {
        $currencies = array_values(array_intersect(
            $currencies ?: array_keys(self::CURRENCIES),
            array_keys(self::CURRENCIES)
        )) ?: array_keys(self::CURRENCIES);

        $package->loadMissing(self::RELATIONS);

        $lines = [];

        $lines[] = '### '.($package->code ? $package->code.' — ' : '').$package->name;

        if ($url = self::url($package)) {
            $lines[] = "Page: {$url}";
        }

        $facts = array_filter([
            $package->category ? "Category: {$package->category->name}" : null,
            $package->series ? "Series: {$package->series->name}" : null,
            $package->duration_label ? "Duration: {$package->duration_label}" : ($package->duration_days ? "Duration: {$package->duration_days} days" : null),
            $package->season_label ? "Season: {$package->season_label}" : null,
        ]);

        if ($package->isHajj()) {
            $facts[] = 'Arrival city: '.($package->medinah_first ? 'Medinah first' : 'Makkah first');
            $facts[] = 'Hotel movement: '.($package->is_shifting ? 'Shifting' : 'Non-shifting');
            $facts[] = 'Aziziya: '.($package->has_aziziya ? 'Includes Aziziya accommodation' : 'Non-Aziziya');
        }

        if ($facts) {
            $lines[] = implode("\n", $facts);
        }

        if ($summary = $package->publicSummary()) {
            $lines[] = $summary;
        }

        $variantLabels = self::variantLabels($package);

        if (count($variantLabels) > 1) {
            $lines[] = 'This package has separate options: '.implode(' / ', $variantLabels)
                .'. Each has its own hotels and its own prices — never mix them.';
        }

        if ($accommodation = self::accommodation($package, $variantLabels)) {
            $lines[] = "ACCOMMODATION\n".$accommodation;
        }

        if ($prices = self::prices($package, $currencies, $variantLabels)) {
            // The rule is also in the system prompt, but a live test showed it
            // was not enough: asked in Roman Urdu for UB010's quad rate, the
            // model quoted Package A alone (PKR 3,700,000) without naming it,
            // while Package B's quad is cheaper at PKR 3,485,000. A visitor
            // would take the one figure as THE price. An instruction sitting
            // directly on the data it governs is far harder to skip.
            $header = count($variantLabels) > 1
                ? 'ROOM PRICES (per person) — this package has '.count($variantLabels).' options. Any price you give MUST be given for every option below, each named. Never quote just one.'
                : 'ROOM PRICES (per person)';

            $lines[] = $header."\n".$prices;
        }

        if ($aziziya = self::aziziya($package, $currencies, $variantLabels)) {
            $lines[] = $aziziya;
        }

        if ($itinerary = self::itinerary($package)) {
            $lines[] = "ITINERARY\n".$itinerary;
        }

        if ($package->mashaerDetails->isNotEmpty()) {
            $lines[] = "MINA / ARAFAT / MUZDALIFAH\n".$package->mashaerDetails
                ->map(fn ($d) => trim(implode(' | ', array_filter([
                    Str::title((string) $d->location),
                    $d->category ? "Category {$d->category}" : null,
                    $d->zone ? "Zone {$d->zone}" : null,
                    $d->maktab ? "Maktab {$d->maktab}" : null,
                    $d->tent_type,
                    $d->meal_plan ? "Meals: {$d->meal_plan}" : null,
                    $d->air_conditioning ? "AC: {$d->air_conditioning}" : null,
                    $d->bathroom ? "Bathroom: {$d->bathroom}" : null,
                ]))))
                ->implode("\n");
        }

        if ($package->transportation->isNotEmpty()) {
            $lines[] = "TRANSPORT\n".$package->transportation
                ->map(fn ($t) => trim(implode(' ', array_filter([
                    $t->from_location && $t->to_location ? "{$t->from_location} to {$t->to_location}:" : null,
                    $t->transport_type,
                    $t->is_included ? '(included)' : '(not included)',
                    $t->notes,
                ]))))
                ->implode("\n");
        }

        if ($package->inclusions->isNotEmpty()) {
            $lines[] = "INCLUDED\n- ".$package->inclusions->pluck('description')->filter()->implode("\n- ");
        }

        if ($package->exclusions->isNotEmpty()) {
            $lines[] = "NOT INCLUDED\n- ".$package->exclusions->pluck('description')->filter()->implode("\n- ");
        }

        if ($package->upgrades->isNotEmpty()) {
            $lines[] = "OPTIONAL UPGRADES\n".$package->upgrades
                ->map(function ($upgrade) {
                    $price = $upgrade->price !== null
                        ? ' — '.($upgrade->currency ?: 'USD').' '.self::money($upgrade->price).($upgrade->price_basis ? " {$upgrade->price_basis}" : '')
                        : ($upgrade->is_included ? ' — included' : '');

                    return trim('- '.$upgrade->name.$price.($upgrade->description ? ': '.$upgrade->description : ''));
                })
                ->implode("\n");
        }

        if ($package->packageNotes->isNotEmpty()) {
            $lines[] = "NOTES\n- ".$package->packageNotes
                ->map(fn ($n) => trim(($n->title ? "{$n->title}: " : '').$n->content))
                ->filter()
                ->implode("\n- ");
        }

        return implode("\n\n", array_filter($lines));
    }

    /** @return array<int|string, string> variant id => label */
    private static function variantLabels(Package $package): array
    {
        $labels = [];

        foreach ($package->variants as $variant) {
            $labels[$variant->id] = 'Package '.$variant->code.($variant->label ? " ({$variant->label})" : '');
        }

        return $labels ?: ['' => 'This package'];
    }

    /** @param array<int|string, string> $variantLabels */
    private static function accommodation(Package $package, array $variantLabels): ?string
    {
        if ($package->accommodations->isEmpty()) {
            return null;
        }

        return $package->accommodations
            ->map(function ($accommodation) use ($variantLabels) {
                $owner = $accommodation->variant_id
                    ? ($variantLabels[$accommodation->variant_id] ?? 'Package')
                    : 'All options';

                return trim(sprintf(
                    '- %s | %s: %s%s%s%s',
                    $owner,
                    Str::title((string) $accommodation->location),
                    $accommodation->hotel_name,
                    $accommodation->star_rating ? " ({$accommodation->star_rating} star)" : '',
                    $accommodation->nights ? ", {$accommodation->nights} nights" : '',
                    $accommodation->distance_note ? ", {$accommodation->distance_note}" : ''
                ));
            })
            ->implode("\n");
    }

    /**
     * @param  array<int, string>  $currencies
     * @param  array<int|string, string>  $variantLabels
     */
    private static function prices(Package $package, array $currencies, array $variantLabels): ?string
    {
        if ($package->roomOptions->isEmpty()) {
            return null;
        }

        $grouped = $package->roomOptions->groupBy('variant_id');
        $out = [];

        foreach ($grouped as $variantId => $options) {
            $label = $variantId ? ($variantLabels[$variantId] ?? 'Package') : 'All options';
            $out[] = $label.':';

            foreach ($options as $option) {
                $out[] = '  - '.self::roomLine($option, $currencies);
            }
        }

        return implode("\n", $out);
    }

    /** @param array<int, string> $currencies */
    private static function roomLine(object $option, array $currencies): string
    {
        $name = $option->display_label ?: Str::title(str_replace('_', ' ', (string) $option->sharing_type));

        $parts = [];

        foreach ($currencies as $currency) {
            $column = self::CURRENCIES[$currency]['column'];
            $currencyLabel = self::CURRENCIES[$currency]['label'];
            $value = $option->{$column};

            // An unpublished currency is stated, not skipped: a gap the model
            // cannot see is a gap it will cheerfully fill in.
            $parts[] = $value === null
                ? "{$currencyLabel}: not published"
                : "{$currencyLabel} ".self::money($value);
        }

        return $name.' — '.implode(' | ', $parts);
    }

    /**
     * @param  array<int, string>  $currencies
     * @param  array<int|string, string>  $variantLabels
     */
    private static function aziziya(Package $package, array $currencies, array $variantLabels): ?string
    {
        $aziziya = $package->aziziya;

        if (! $aziziya) {
            return null;
        }

        $lines = ['AZIZIYA'];

        $lines[] = implode("\n", array_filter([
            $aziziya->accommodation_name ? "Accommodation: {$aziziya->accommodation_name}" : null,
            $aziziya->status ? "Status: {$aziziya->status}" : null,
            $aziziya->location_note,
            $aziziya->walk_distance ? "Walking distance: {$aziziya->walk_distance}" : null,
            $aziziya->duration_days ? "Duration: {$aziziya->duration_days} days" : null,
            $aziziya->description,
        ]));

        if ($aziziya->relationLoaded('roomOptions') && $aziziya->roomOptions->isNotEmpty()) {
            $lines[] = 'Aziziya room supplements:';

            foreach ($aziziya->roomOptions as $option) {
                $owner = $option->variant_id ? ($variantLabels[$option->variant_id] ?? 'Package').' ' : '';
                $lines[] = '  - '.$owner.self::roomLine($option, $currencies);
            }
        }

        if ($aziziya->relationLoaded('services') && $aziziya->services->isNotEmpty()) {
            $lines[] = 'Aziziya services: '.$aziziya->services->pluck('name')->filter()->implode(', ');
        }

        return implode("\n", array_filter($lines));
    }

    private static function itinerary(Package $package): ?string
    {
        if ($package->itineraryDays->isEmpty()) {
            return null;
        }

        return $package->itineraryDays
            ->map(function ($day) {
                $stay = array_filter([$day->accommodation_a, $day->accommodation_b]);

                return trim(sprintf(
                    'Day %s%s — %s%s',
                    $day->day_number,
                    $day->date_hijri_label ? " ({$day->date_hijri_label})" : '',
                    $day->city ?: '',
                    $stay ? ': '.implode(' / ', $stay) : ''
                ));
            })
            ->implode("\n");
    }

    private static function money(mixed $value): string
    {
        $number = (float) $value;

        // No decimals on whole amounts: "PKR 6,372,000" rather than
        // "PKR 6,372,000.00", which reads like a typo at this magnitude.
        return number_format($number, fmod($number, 1.0) === 0.0 ? 0 : 2);
    }

    public static function url(Package $package): ?string
    {
        $categorySlug = $package->category?->slug;

        if (! $categorySlug || ! $package->slug) {
            return null;
        }

        return route('packages.show', ['category' => $categorySlug, 'package' => $package->slug]);
    }
}
