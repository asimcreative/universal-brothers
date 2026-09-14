<?php

namespace App\Support\Packages;

/**
 * What a Hajj package must have before it can go live.
 *
 * A draft may be incomplete — that is what drafts are for. Publishing is
 * refused until every problem listed here is fixed, and each problem names the
 * builder step that fixes it, so the admin is sent to the right place rather
 * than shown a wall of field names.
 *
 * The rules are deliberately few and business-level: a package a pilgrim can
 * actually enquire about needs a name, a code, a length, at least one hotel,
 * and a price for every option it offers. Anything stricter would have to be
 * met by all twelve live brochure packages (it is — see
 * PackageBuilderTest::test_every_live_package_meets_the_publishing_rules).
 */
class PackageCompleteness
{
    /** Builder steps, in order: key => title. */
    public const STEPS = [
        'basics' => 'Basic information',
        'setup' => 'Package setup',
        'options' => 'Hotel options (A / B / C)',
        'pricing' => 'Room prices',
        'hotels' => 'Hotels & accommodation',
        'journey' => 'Journey plan',
        'mashaer' => 'Mina, Arafat & Muzdalifah',
        'transport' => 'Transport & meals',
        'services' => 'Included & not included',
        'extras' => 'Additional options',
        'notes' => 'Notes & policies',
        'media' => 'Photos & search engines',
    ];

    /**
     * @return list<array{step: string, field: string, message: string}>
     */
    public static function problems(array $state): array
    {
        $problems = [];

        if (blank($state['name'] ?? null)) {
            $problems[] = self::problem('basics', 'name', 'Add a package title.');
        }
        if (blank($state['code'] ?? null)) {
            $problems[] = self::problem('basics', 'code', 'Add a package code (for example UB025).');
        }
        if (blank($state['duration_days'] ?? null)) {
            $problems[] = self::problem('basics', 'duration_days', 'Add how many days the package lasts.');
        }

        $hotels = collect($state['accommodations'] ?? [])->filter(fn ($row) => filled($row['hotel_name'] ?? null) || filled($row['hotel_id'] ?? null));
        if ($hotels->isEmpty()) {
            $problems[] = self::problem('hotels', 'accommodations', 'Add at least one hotel.');
        }

        $pricedRooms = collect($state['room_options'] ?? [])->filter(fn ($row) => filled($row['sharing_type'] ?? null)
            && ! empty($row['is_available'])
            && (filled($row['price_usd'] ?? null) || filled($row['price_sar'] ?? null) || filled($row['price_pkr'] ?? null)));

        if ($pricedRooms->isEmpty()) {
            $problems[] = self::problem('pricing', 'room_options', 'Add at least one available room type with a price.');
        }

        $sharedPrices = $pricedRooms->contains(fn ($row) => blank($row['variant_code'] ?? null));
        if (! $sharedPrices) {
            foreach ($state['variants'] ?? [] as $variant) {
                $code = strtoupper(trim((string) ($variant['code'] ?? '')));
                if ($code === '') {
                    continue;
                }

                $priced = $pricedRooms->contains(fn ($row) => strtoupper(trim((string) ($row['variant_code'] ?? ''))) === $code);
                if (! $priced) {
                    $problems[] = self::problem('pricing', 'room_options', "Option {$code} has no room price yet.");
                }
            }
        }

        return $problems;
    }

    /**
     * Steps that currently look finished, for the builder's progress marks.
     * Advisory only — publishing is decided by problems() alone.
     *
     * @return array<string, bool>
     */
    public static function stepStatus(array $state): array
    {
        $problemSteps = collect(self::problems($state))->pluck('step')->unique();

        return [
            'basics' => ! $problemSteps->contains('basics'),
            'setup' => filled($state['aziziya']['status'] ?? null),
            'options' => true,
            'pricing' => ! $problemSteps->contains('pricing'),
            'hotels' => ! $problemSteps->contains('hotels'),
            'journey' => ! empty($state['itinerary']),
            'mashaer' => collect($state['mashaer'] ?? [])->contains(fn ($row) => collect($row)->filter(fn ($v) => filled($v))->isNotEmpty()),
            'transport' => ! empty($state['transportation']),
            'services' => ! empty($state['inclusions']) || ! empty($state['exclusions']),
            'extras' => ! empty($state['upgrades']),
            'notes' => ! empty($state['notes']),
            'media' => filled($state['meta_title'] ?? null) || filled($state['meta_description'] ?? null),
        ];
    }

    /**
     * The builder step that holds a submitted field, so a validation error can
     * send the admin straight to the step that fixes it.
     */
    public static function stepForField(string $key): string
    {
        $parts = explode('.', $key);
        $first = $parts[0];

        if ($first === 'publish') {
            return array_key_exists($parts[1] ?? '', self::STEPS) ? $parts[1] : 'basics';
        }

        if ($key === 'aziziya.status') {
            return 'setup';
        }

        return match (true) {
            in_array($first, ['name', 'code', 'package_type', 'summary', 'description', 'duration_days', 'duration_label', 'season_year', 'season_label', 'is_featured', 'sort_order', 'status', 'template_name', 'template_description'], true) => 'basics',
            in_array($first, ['medinah_first', 'is_shifting', 'package_series_id'], true) => 'setup',
            $first === 'variants' => 'options',
            $first === 'room_options' => 'pricing',
            in_array($first, ['accommodations', 'aziziya', 'aziziya_room_options', 'aziziya_services'], true) => 'hotels',
            $first === 'itinerary' => 'journey',
            $first === 'mashaer' => 'mashaer',
            $first === 'transportation' => 'transport',
            in_array($first, ['inclusions', 'exclusions', 'inclusions_text', 'exclusions_text'], true) => 'services',
            $first === 'upgrades' => 'extras',
            in_array($first, ['notes', 'internal_notes'], true) => 'notes',
            default => 'media',
        };
    }

    private static function problem(string $step, string $field, string $message): array
    {
        return ['step' => $step, 'field' => $field, 'message' => $message];
    }
}
