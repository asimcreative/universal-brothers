<?php

namespace App\Support\Packages;

use App\Models\Package;
use App\Models\PackageSeries;
use App\Models\TransportOption;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Everything the builder says about how complete a package is: the problems
 * that block publishing, the advice worth checking, the checklist, the
 * completion percentage and the review-screen summary.
 *
 * It is the single source for all of them. The builder asks the server for it
 * (HajjPackageController::assess) with the unsaved form, the listing and the
 * dashboard compute it from saved packages, and the tests assert on it — so
 * the checklist a user sees can never disagree with what publishing allows.
 *
 * Input is a PackageFormState-shaped array; `$package` (when it exists) supplies
 * what the form cannot carry: saved photos and the review fingerprint.
 */
class PackageReview
{
    /** Checklist items, in the order the admin works: key => [label, step]. */
    public const CHECKLIST = [
        'basics' => ['Basic information completed', 'basics'],
        'setup' => ['Package configuration completed', 'setup'],
        'options' => ['Package options added', 'options'],
        'pricing' => ['Room prices added', 'pricing'],
        'hotels' => ['Accommodation selected', 'hotels'],
        'journey' => ['Itinerary completed', 'journey'],
        'mashaer' => ['Mashaer information added', 'mashaer'],
        'transport' => ['Transport and meals selected', 'transport'],
        'services' => ['Inclusions and exclusions reviewed', 'services'],
        'notes' => ['Notes reviewed', 'notes'],
        'images' => ['Images uploaded', 'media'],
        'seo' => ['SEO completed', 'media'],
        'review' => ['Final review completed', 'review'],
    ];

    private array $problems;

    /**
     * `$context` carries what the form cannot: `new_cover` / `new_media` are
     * photos chosen but not yet uploaded, `remove_cover` a ticked "remove",
     * and `reviewed` that the admin has looked at the review since the last
     * change.
     *
     * @param  array{new_cover?: bool, new_media?: int, remove_cover?: bool, reviewed?: bool}  $context
     */
    public function __construct(
        private array $state,
        private ?Package $package = null,
        private array $context = [],
    ) {
        $this->problems = PackageCompleteness::problems($state);
    }

    public static function forPackage(Package $package): self
    {
        return new self(PackageFormState::fromPackage($package), $package, ['reviewed' => self::isReviewed($package)]);
    }

    /**
     * A fingerprint of everything a visitor would see, taken from the saved
     * package. Status, featured, order and photos are left out: publishing a
     * reviewed package must not "un-review" it.
     */
    public static function fingerprint(Package $package): string
    {
        $state = collect(PackageFormState::fromPackage($package))
            ->except(['status', 'is_featured', 'sort_order', 'media', 'internal_notes'])
            ->all();

        return hash('sha256', json_encode($state));
    }

    public static function isReviewed(Package $package): bool
    {
        return filled($package->reviewed_hash) && hash_equals($package->reviewed_hash, self::fingerprint($package));
    }

    // ------------------------------------------------------------------
    // Problems, advice, checklist
    // ------------------------------------------------------------------

    /** @return list<array{step: string, field: string, message: string}> Blocking: publishing is refused. */
    public function problems(): array
    {
        return $this->problems;
    }

    public function canPublish(): bool
    {
        return $this->problems === [];
    }

    /** @return list<array{step: string, message: string}> Advice: worth checking, never blocks. */
    public function warnings(): array
    {
        $warnings = [];
        $add = function (string $step, string $message) use (&$warnings) {
            $warnings[] = ['step' => $step, 'message' => $message];
        };

        $s = $this->state;

        if (blank($s['summary'] ?? null)) {
            $add('basics', 'There is no short description. Package cards will show only the title.');
        }
        if (blank($s['aziziya']['status'] ?? null)) {
            $add('setup', 'Aziziya is not set. Choose whether it is included, optional or not offered.');
        }

        foreach ($this->options() as $code => $option) {
            if (blank($option['label'])) {
                $add('options', "Option {$code} has no name. Customers see the name, usually its hotel.");
            }
            if ($option['hotels'] === []) {
                $add('hotels', "Option {$code} has no hotel of its own yet.");
            }
        }

        $rooms = $this->rooms();
        foreach ($rooms as $room) {
            if (! $room['available']) {
                continue;
            }
            if ($room['usd'] === null && $room['sar'] === null && $room['pkr'] === null) {
                $add('pricing', "{$room['label']}{$room['for']} is marked available but has no price.");
            }
        }
        // A visitor reads the whole site in one currency, chosen when they
        // arrive. A room with no amount in that currency shows "N/A" — but a
        // package with NO room priced in it is left out of the listing
        // altogether (Package::scopePricedIn), which is the case worth
        // spelling out: publishing it looks like it worked and the package is
        // nowhere to be found.
        $priced = array_filter($rooms, fn ($r) => $r['available'] && ($r['usd'] !== null || $r['sar'] !== null || $r['pkr'] !== null));
        foreach (['usd' => 'USD', 'sar' => 'SAR', 'pkr' => 'PKR'] as $key => $currency) {
            $missing = count(array_filter($priced, fn ($r) => $r[$key] === null));
            if ($missing === 0) {
                continue;
            }
            $add('pricing', match (true) {
                $missing === count($priced) => "No room has a {$currency} price, so this package will not be listed at all for visitors reading in {$currency}.",
                $missing === 1 => "1 room price has no {$currency} amount. Visitors reading in {$currency} will see N/A for it.",
                default => "{$missing} room prices have no {$currency} amount. Visitors reading in {$currency} will see N/A for them.",
            });
        }

        foreach ($this->hotels() as $hotel) {
            if (in_array($hotel['location'], ['makkah', 'medinah'], true) && blank($hotel['meal_plan'])) {
                $add('transport', "{$hotel['name']} has no meal plan.");
            }
        }

        $days = $this->days();
        if ($days === []) {
            $add('journey', 'There is no journey plan yet.');
        } else {
            $length = (int) ($s['duration_days'] ?? 0);
            if ($length > 0 && count($days) < $length) {
                $add('journey', 'The package is '.$length.' days but the journey plan has '.count($days).' '.Str::plural('day', count($days)).'.');
            }
            $undated = count(array_filter($days, fn ($d) => $d['date'] === null));
            if ($undated > 0) {
                $add('journey', $undated === 1 ? '1 day has no English date.' : "{$undated} days have no English date.");
            }
            $previous = null;
            foreach ($days as $day) {
                if ($day['date'] && $previous && $day['date']->lte($previous['date'])) {
                    $add('journey', "Day {$day['number']}'s date ({$day['date']->format('d/m/Y')}) is not after day {$previous['number']}'s.");
                }
                if ($day['date']) {
                    $previous = $day;
                }
            }
            $numbers = array_column($days, 'number');
            if ($numbers !== range(1, count($days))) {
                $add('journey', 'The day numbers are not 1, 2, 3… in order. Use "Renumber" in the journey plan.');
            }
        }

        if (! $this->hasMashaer()) {
            $add('mashaer', 'Mina, Arafat and Muzdalifah are not described.');
        }
        if (empty($s['transportation'])) {
            $add('transport', 'No transport has been added.');
        }
        if (empty($s['inclusions'])) {
            $add('services', 'Nothing is listed as included.');
        }
        if (empty($s['exclusions'])) {
            $add('services', 'Nothing is listed as not included.');
        }
        if (empty($s['notes'])) {
            $add('notes', 'There are no notes for customers, such as ticket and Qurbani.');
        }
        if (! $this->hasMainPhoto()) {
            $add('media', 'There is no main photo. The website will use a stock photograph.');
        }
        if (blank($s['meta_title'] ?? null) || blank($s['meta_description'] ?? null)) {
            $add('media', 'The Google title or description is empty. The package title and short description will be used instead.');
        }

        return $warnings;
    }

    /** @return list<array{key: string, label: string, step: string, done: bool}> */
    public function checklist(): array
    {
        $problemSteps = collect($this->problems)->pluck('step')->unique();
        $s = $this->state;

        $done = [
            'basics' => ! $problemSteps->contains('basics'),
            'setup' => filled($s['aziziya']['status'] ?? null),
            // Vacuously true for a one-hotel-set package — but not for an empty
            // form, where nothing about options has been decided yet.
            'options' => collect($this->options())->every(fn ($o) => filled($o['label']))
                && ($this->options() !== [] || $this->hotels() !== [] || $this->rooms() !== []),
            'pricing' => ! $problemSteps->contains('pricing'),
            'hotels' => ! $problemSteps->contains('hotels') && collect($this->options())->every(fn ($o) => $o['hotels'] !== []),
            'journey' => $this->days() !== [] && ! collect($this->warnings())->contains(fn ($w) => $w['step'] === 'journey' && ! str_starts_with($w['message'], 'The package is')),
            'mashaer' => $this->hasMashaer(),
            'transport' => ! empty($s['transportation']) && collect($this->hotels())
                ->filter(fn ($h) => in_array($h['location'], ['makkah', 'medinah'], true))
                ->every(fn ($h) => filled($h['meal_plan'])),
            'services' => ! empty($s['inclusions']) && ! empty($s['exclusions']),
            'notes' => ! empty($s['notes']),
            'images' => $this->hasMainPhoto(),
            'seo' => filled($s['meta_title'] ?? null) && filled($s['meta_description'] ?? null),
            'review' => (bool) ($this->context['reviewed'] ?? false) && $this->canPublish(),
        ];

        return collect(self::CHECKLIST)
            ->map(fn ($item, $key) => ['key' => $key, 'label' => $item[0], 'step' => $item[1], 'done' => $done[$key]])
            ->values()
            ->all();
    }

    public function percent(): int
    {
        $items = $this->checklist();

        return (int) round(count(array_filter($items, fn ($i) => $i['done'])) / count($items) * 100);
    }

    /** Tick marks for the step list. `extras` is optional and ticks once anything is added. */
    public function stepStatus(): array
    {
        $check = collect($this->checklist())->keyBy('key');

        return [
            'basics' => $check['basics']['done'],
            'setup' => $check['setup']['done'],
            'options' => $check['options']['done'],
            'pricing' => $check['pricing']['done'],
            'hotels' => $check['hotels']['done'],
            'journey' => $check['journey']['done'],
            'mashaer' => $check['mashaer']['done'],
            'transport' => $check['transport']['done'],
            'services' => $check['services']['done'],
            'extras' => ! empty($this->state['upgrades']),
            'notes' => $check['notes']['done'],
            'media' => $check['images']['done'] && $check['seo']['done'],
            'review' => $check['review']['done'],
            'publish' => $this->package?->isPublished() ?? false,
        ];
    }

    /** The first step with an unfinished checklist item — where "continue" should go. */
    public function nextStep(): string
    {
        return collect($this->checklist())->first(fn ($i) => ! $i['done'])['step'] ?? 'publish';
    }

    // ------------------------------------------------------------------
    // Review screen data
    // ------------------------------------------------------------------

    public function summary(): array
    {
        $s = $this->state;
        $series = filled($s['package_series_id'] ?? null) ? PackageSeries::find($s['package_series_id'])?->name : null;
        $aziziya = [
            'included' => 'Included', 'optional' => 'Optional upgrade',
            'not_included' => 'Not included', 'not_applicable' => 'Not applicable',
        ][$s['aziziya']['status'] ?? ''] ?? 'Not set';

        return [
            'basics' => [
                'Title' => $s['name'] ?: null,
                'Package code' => $s['code'] ?: null,
                'Tier / short title' => $s['package_type'] ?: null,
                'Series' => $series,
                'Length' => filled($s['duration_days'] ?? null) ? trim($s['duration_days'].' days'.(filled($s['duration_label'] ?? null) ? ' — '.$s['duration_label'] : '')) : null,
                'Season' => trim(($s['season_year'] ?? '').' '.(filled($s['season_label'] ?? null) ? '('.$s['season_label'].')' : '')) ?: null,
                'Short description' => $s['summary'] ?: null,
                'Featured' => ! empty($s['is_featured']) ? 'Yes' : 'No',
            ],
            'setup' => [
                'Arrival' => ! empty($s['medinah_first']) ? 'Madinah first' : 'Jeddah (Makkah first)',
                'Makkah stay' => ! empty($s['is_shifting']) ? 'Shifting' : 'Non-shifting',
                'Aziziya' => $aziziya,
            ],
            'options' => $this->options(),
            'rooms' => $this->rooms(),
            'hotels' => $this->hotels(),
            'days' => $this->days(),
            'mashaer' => collect($s['mashaer'] ?? [])
                ->map(fn ($row) => collect($row)->except('mashaer_location_id')->filter(fn ($v) => filled($v))->all())
                ->filter()
                ->all(),
            'transport' => collect($s['transportation'] ?? [])->map(fn ($t) => [
                'route' => trim(collect([$t['from_location'] ?? null, $t['to_location'] ?? null])->filter()->implode(' → ')) ?: (TransportOption::TYPES[$t['transport_type'] ?? ''] ?? Str::headline((string) ($t['transport_type'] ?? ''))),
                'type' => TransportOption::TYPES[$t['transport_type'] ?? ''] ?? Str::headline((string) ($t['transport_type'] ?? '')),
                'cost' => ! empty($t['is_included']) ? 'Included' : (filled($t['price'] ?? null) ? ($t['currency'] ?? 'USD').' '.number_format((float) $t['price']).(filled($t['price_basis'] ?? null) ? ' '.$t['price_basis'] : '') : 'Extra — price on request'),
            ])->values()->all(),
            'inclusions' => collect($s['inclusions'] ?? [])->pluck('description')->filter()->values()->all(),
            'exclusions' => collect($s['exclusions'] ?? [])->pluck('description')->filter()->values()->all(),
            'upgrades' => collect($s['upgrades'] ?? [])->map(fn ($u) => [
                'name' => $u['name'] ?? '',
                'price' => ! empty($u['is_included']) ? 'Included' : (filled($u['price'] ?? null) ? ($u['currency'] ?? 'USD').' '.number_format((float) $u['price']).(filled($u['price_basis'] ?? null) ? ' '.$u['price_basis'] : '') : 'On request'),
            ])->filter(fn ($u) => filled($u['name']))->values()->all(),
            'notes' => collect($s['notes'] ?? [])->map(fn ($n) => [
                'title' => $n['title'] ?? null,
                'content' => $n['content'] ?? '',
                'important' => ! empty($n['is_important']),
                'saved' => filled($n['note_template_id'] ?? null),
            ])->filter(fn ($n) => filled($n['content']))->values()->all(),
            'internal_notes' => filled($s['internal_notes'] ?? null),
            'media' => [
                'Main photo' => $this->hasMainPhoto() ? 'Added' : null,
                'Social sharing image' => ($this->package?->social_image) ? 'Added' : 'Uses the main photo',
                'More photos' => (string) (count(array_filter($this->state['media'] ?? [], fn ($m) => filled($m['image_path'] ?? null) || filled($m['video_url'] ?? null))) + (int) ($this->context['new_media'] ?? 0)),
                'Google title' => $s['meta_title'] ?: null,
                'Google description' => $s['meta_description'] ?: null,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Normalised views of the state
    // ------------------------------------------------------------------

    /** @return array<string, array{label: string, hotels: list<string>, rooms: int}> */
    private function options(): array
    {
        $options = [];
        foreach ($this->state['variants'] ?? [] as $variant) {
            $code = strtoupper(trim((string) ($variant['code'] ?? '')));
            if ($code === '') {
                continue;
            }
            $options[$code] = ['label' => trim((string) ($variant['label'] ?? '')), 'hotels' => [], 'rooms' => 0];
        }

        foreach ($this->hotels() as $hotel) {
            if ($hotel['option'] && isset($options[$hotel['option']])) {
                $options[$hotel['option']]['hotels'][] = $hotel['name'];
            }
        }
        foreach ($this->rooms() as $room) {
            if ($room['option'] && isset($options[$room['option']])) {
                $options[$room['option']]['rooms']++;
            }
        }

        return $options;
    }

    private function rooms(): array
    {
        return collect($this->state['room_options'] ?? [])
            ->filter(fn ($r) => filled($r['sharing_type'] ?? null) || filled($r['display_label'] ?? null))
            ->map(function ($r) {
                $option = strtoupper(trim((string) ($r['variant_code'] ?? ''))) ?: null;
                $label = filled($r['display_label'] ?? null) ? $r['display_label'] : Str::headline((string) $r['sharing_type']);

                return [
                    'option' => $option,
                    'label' => $label,
                    'for' => $option ? " (Option {$option})" : '',
                    'available' => ! empty($r['is_available']),
                    'usd' => self::amount($r['price_usd'] ?? null),
                    'sar' => self::amount($r['price_sar'] ?? null),
                    'pkr' => self::amount($r['price_pkr'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    private function hotels(): array
    {
        return collect($this->state['accommodations'] ?? [])
            ->filter(fn ($h) => filled($h['hotel_name'] ?? null))
            ->map(fn ($h) => [
                'option' => strtoupper(trim((string) ($h['variant_code'] ?? ''))) ?: null,
                'location' => $h['location'] ?? 'makkah',
                'city' => ['makkah' => 'Makkah', 'medinah' => 'Madinah', 'aziziya' => 'Aziziya', 'mina' => 'Mina', 'arafat' => 'Arafat'][$h['location'] ?? ''] ?? Str::headline((string) ($h['location'] ?? '')),
                'name' => $h['hotel_name'],
                'stars' => filled($h['star_rating'] ?? null) ? (int) $h['star_rating'] : null,
                'meal_plan' => $h['meal_plan'] ?? null,
                'nights' => filled($h['nights'] ?? null) ? (int) $h['nights'] : null,
            ])
            ->values()
            ->all();
    }

    private function days(): array
    {
        return collect($this->state['itinerary'] ?? [])
            ->filter(fn ($d) => collect($d)->except('day_number')->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()
            ->map(function ($d, $i) {
                $date = null;
                if (filled($d['date_gregorian'] ?? null)) {
                    try {
                        $date = Carbon::parse($d['date_gregorian'])->startOfDay();
                    } catch (\Throwable) {
                        $date = null;
                    }
                }

                return [
                    'number' => filled($d['day_number'] ?? null) ? (int) $d['day_number'] : $i + 1,
                    'date' => $date,
                    'hijri' => $d['date_hijri_label'] ?? null,
                    'city' => $d['city'] ?? null,
                    'stay' => collect([$d['accommodation_a'] ?? null, $d['accommodation_b'] ?? null])->filter()->unique()->implode(' / '),
                    'transport' => $d['transport'] ?? null,
                ];
            })
            ->all();
    }

    private function hasMashaer(): bool
    {
        return collect($this->state['mashaer'] ?? [])
            ->contains(fn ($row) => collect($row)->except('mashaer_location_id')->filter(fn ($v) => filled($v))->isNotEmpty());
    }

    private function hasMainPhoto(): bool
    {
        return ! empty($this->context['new_cover'])
            || (filled($this->package?->cover_image) && ! ($this->context['remove_cover'] ?? false));
    }

    private static function amount($value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
