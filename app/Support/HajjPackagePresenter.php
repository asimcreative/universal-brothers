<?php

namespace App\Support;

use App\Models\Package;
use App\Models\PackageVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * View model for the public Hajj package detail page.
 *
 * The detail page has to serve twelve genuinely different package shapes, and
 * a matrix of the real data shows they do NOT share a structure:
 *
 *   - 9 packages have two variants; 3 have none at all (UB011, UB013, UB024),
 *     where room options hang directly off the package.
 *   - The variants are NOT always "the Makkah hotel". UB001/UB003 vary the
 *     Makkah hotel and share one Madinah hotel; UB004/UB006/UB008/UB010 do the
 *     opposite — they vary the MADINAH hotel and share the Makkah one. Any
 *     hard-coded "Choose your Makkah hotel" heading would be factually wrong on
 *     a third of the catalogue.
 *   - Sharing types vary (UB008/UB010 add `sharing_room`, whose occupancy is
 *     null), some room options exist but are explicitly unavailable, Aziziya is
 *     `optional` on eight packages and `included` on four, and exactly half the
 *     upgrade rows carry no price.
 *
 * So the page is driven entirely by what each package actually holds. Nothing
 * here keys off a slug or a package code, which means a package added through
 * the admin tomorrow gets the same experience with no template work.
 */
class HajjPackagePresenter
{
    public function __construct(private Package $package)
    {
    }

    public function package(): Package
    {
        return $this->package;
    }

    // ---------------------------------------------------------------------
    // Package options (variants) and their pricing
    // ---------------------------------------------------------------------

    /**
     * Room options grouped under the option they belong to.
     *
     * This is the central fix for the page's worst UX problem. Previously room
     * prices were rendered as one flat grid with the hotel name as a small
     * eyebrow on each card, so Package A and Package B rows alternated down the
     * page and the customer had to read every caption to work out which hotel a
     * price belonged to — while the block that actually explained "A = this
     * hotel, B = that hotel" sat *below* the prices.
     *
     * Each group carries its own hotels and its own rooms, so a price can never
     * be separated from the option it belongs to.
     *
     * A package may legitimately have variant-scoped rooms AND unscoped rooms
     * at the same time (the feature-test fixture does), so an unscoped group is
     * appended when such rooms exist.
     *
     * @return Collection<int, array{variant: ?PackageVariant, key: string, title: string, subtitle: ?string, hotels: Collection, rooms: Collection, fromPrice: ?float, roomCount: int}>
     */
    public function optionGroups(): Collection
    {
        $groups = collect();

        foreach ($this->package->variants->sortBy('sort_order') as $variant) {
            $rooms = $this->package->roomOptions
                ->where('variant_id', $variant->id)
                ->sortBy('sort_order')
                ->values();

            $hotels = $this->package->accommodations
                ->where('variant_id', $variant->id)
                ->sortBy('sort_order')
                ->values();

            if ($rooms->isEmpty() && $hotels->isEmpty()) {
                continue;
            }

            $groups->push([
                'variant' => $variant,
                'key' => 'variant-'.$variant->id,
                'title' => 'Package '.$variant->code,
                // The variant's own label is the hotel name in this dataset;
                // fall back to the linked accommodation if a package ever
                // stores it the other way round.
                'subtitle' => $variant->label ?: $hotels->first()?->hotel_name,
                'hotels' => $hotels,
                'rooms' => $rooms,
                'fromPrice' => $this->lowestIn($rooms),
                // The cheapest room ITSELF, not just its dollar figure. The
                // option card's "From" line used to be given the USD number
                // alone, so it had no PKR or SAR value to switch to and went to
                // "N/A" the moment a visitor changed currency — while the rows
                // directly beneath it showed real prices.
                'fromRoom' => $this->cheapestRoom($rooms),
                'roomCount' => $rooms->count(),
            ]);
        }

        $unscoped = $this->package->roomOptions
            ->whereNull('variant_id')
            ->sortBy('sort_order')
            ->values();

        if ($unscoped->isNotEmpty()) {
            $groups->push([
                'variant' => null,
                'key' => 'shared',
                // Wording differs depending on whether this is the only group
                // or sits alongside real options.
                'title' => $groups->isEmpty() ? 'Room Options' : 'Available With Any Option',
                'subtitle' => null,
                'hotels' => collect(),
                'rooms' => $unscoped,
                'fromPrice' => $this->lowestIn($unscoped),
                'fromRoom' => $this->cheapestRoom($unscoped),
                'roomCount' => $unscoped->count(),
            ]);
        }

        return $groups;
    }

    /** True when the package offers a genuine choice the customer has to make. */
    public function hasChoice(): bool
    {
        return $this->package->variants->count() > 1;
    }

    /**
     * What the options actually differ on, derived from the data.
     *
     * Returns e.g. "Makkah Hotel", "Madinah Hotel", or the neutral
     * "Package Option" when variants span several cities or carry no linked
     * accommodation. This is what keeps the heading truthful across the
     * catalogue instead of assuming every package varies its Makkah hotel.
     */
    public function choiceScope(): string
    {
        $locations = $this->package->accommodations
            ->whereNotNull('variant_id')
            ->pluck('location')
            ->filter()
            ->unique()
            ->values();

        if ($locations->count() === 1) {
            return $this->locationLabel($locations->first()).' Hotel';
        }

        return 'Package Option';
    }

    public function choiceHeading(): string
    {
        return 'Choose Your '.$this->choiceScope();
    }

    /**
     * Accommodation that applies no matter which option is chosen.
     *
     * Aziziya rows are held back when the package also carries a
     * `package_aziziya` record, because that record renders its own section
     * with the same hotel name — four packages (UB015, UB016, UB023, UB024)
     * store "AZIZIYA Accommodation - A Class" in BOTH tables, and rendering
     * both printed the identical hotel twice on one page. The accommodation
     * row's nights figure is not lost: it is folded into the Aziziya section's
     * facts by `aziziyaFacts()`, and its meal plan still reaches the Meals
     * section through `mealsByLocation()`.
     */
    public function sharedAccommodation(): Collection
    {
        return $this->package->accommodations
            ->whereNull('variant_id')
            ->reject(fn ($a) => $this->aziziyaLabel() && $a->location === 'aziziya')
            ->sortBy('sort_order')
            ->groupBy('location');
    }

    public function locationLabel(?string $location): string
    {
        return match ($location) {
            'makkah' => 'Makkah',
            'medinah' => 'Madinah',
            'aziziya' => 'Aziziya',
            null, '' => 'Accommodation',
            default => Str::headline($location),
        };
    }

    // ---------------------------------------------------------------------
    // Pricing
    // ---------------------------------------------------------------------

    /**
     * The cheapest available room in a group, by its USD price.
     *
     * Ordered by the currency being read, not by dollars. The three brochures
     * are separately printed price lists rather than conversions of one
     * another, so the room that is cheapest in dollars need not be the one
     * that is cheapest in rupees — and a page that says "from" about one room
     * while quoting another's figure is simply wrong.
     */
    public function cheapestRoom(Collection $rooms, ?string $currency = null): mixed
    {
        $column = Currency::column($currency ?? Currency::current());

        return $rooms
            ->filter(fn ($r) => $r->is_available && ! is_null($r->{$column}))
            ->sortBy(fn ($r) => (float) $r->{$column})
            ->first();
    }

    /** The lowest available room price in the currency being read. */
    public function lowestIn(Collection $rooms, ?string $currency = null): ?float
    {
        $column = Currency::column($currency ?? Currency::current());

        $prices = $rooms
            ->filter(fn ($r) => $r->is_available && ! is_null($r->{$column}))
            ->map(fn ($r) => (float) $r->{$column});

        return $prices->isNotEmpty() ? $prices->min() : null;
    }

    /**
     * Which currencies this package actually holds prices in.
     *
     * Every room option across the 26 brochure packages now carries all
     * three, so in practice this returns all three — but a package added from
     * a single price list will not, and knowing which are really populated
     * lets the page say so plainly rather than silently blanking a price.
     * (This read "the brochure is USD-only" until the September brochures
     * arrived, which is the kind of claim worth re-checking, not trusting.)
     *
     * @return array<string, bool>
     */
    public function currencyAvailability(): array
    {
        $rows = $this->package->roomOptions
            ->concat($this->package->aziziya?->roomOptions ?? collect());

        $has = ['USD' => false, 'SAR' => false, 'PKR' => false];

        foreach ($rows as $row) {
            foreach ($has as $code => $_) {
                if (! is_null($row->{'price_'.strtolower($code)})) {
                    $has[$code] = true;
                }
            }
        }

        return $has;
    }

    /** @return list<string> */
    public function populatedCurrencies(): array
    {
        return array_keys(array_filter($this->currencyAvailability()));
    }

    // ---------------------------------------------------------------------
    // Journey
    // ---------------------------------------------------------------------

    /**
     * The stops of the journey, in order, taken strictly from the itinerary.
     *
     * Itinerary rows store cities like "To Medinah", "Medinah", "To Makkah" —
     * the travel days and the stay days for the same place. Those collapse to
     * one stop so the overview reads as a journey rather than a log, and
     * consecutive repeats are removed. Nothing is added: if the itinerary never
     * mentions Arafat, Arafat does not appear here, even though the Mashaer
     * section may describe it.
     *
     * @return list<string>
     */
    public function journeyStops(): array
    {
        $stops = [];

        foreach ($this->package->itineraryDays->sortBy('day_number') as $day) {
            $city = trim((string) $day->city);
            if ($city === '') {
                continue;
            }

            $city = trim(preg_replace('/^(to|towards|depart(ure)?\s+(to|for))\s+/i', '', $city));
            if ($city === '') {
                continue;
            }

            $city = Str::title($city);
            if (end($stops) !== $city) {
                $stops[] = $city;
            }
        }

        return $stops;
    }

    // ---------------------------------------------------------------------
    // Quick facts
    // ---------------------------------------------------------------------

    /**
     * Six to eight scannable facts, built only from fields this package has.
     *
     * @return list<array{icon: string, label: string, value: string}>
     */
    public function quickFacts(): array
    {
        $p = $this->package;
        $facts = [];

        if ($p->duration_label || $p->duration_days) {
            $facts[] = ['icon' => 'bi-calendar-range', 'label' => 'Duration', 'value' => $p->duration_label ?: $p->duration_days.' Days'];
        }

        if (! is_null($p->medinah_first)) {
            $facts[] = ['icon' => 'bi-geo-alt', 'label' => 'Arrival', 'value' => $p->medinah_first ? 'Madinah First' : 'Makkah First'];
        }

        if (! is_null($p->is_shifting)) {
            $facts[] = ['icon' => 'bi-arrow-left-right', 'label' => 'Itinerary', 'value' => $p->is_shifting ? 'Shifting' : 'Non-Shifting'];
        }

        foreach (['makkah', 'medinah'] as $city) {
            $nights = $p->accommodations->where('location', $city)->max('nights');
            if ($nights) {
                $facts[] = [
                    'icon' => 'bi-building',
                    'label' => $this->locationLabel($city).' Stay',
                    'value' => $nights.' '.Str::plural('Night', $nights),
                ];
            }
        }

        if ($aziziya = $this->aziziyaLabel()) {
            $facts[] = ['icon' => 'bi-house-door', 'label' => 'Aziziya', 'value' => $aziziya];
        }

        if ($p->transportation->isNotEmpty()) {
            $included = $p->transportation->where('is_included', true)->count();
            if ($included) {
                $facts[] = ['icon' => 'bi-bus-front', 'label' => 'Transport', 'value' => 'Included'];
            }
        }

        if ($meal = $p->accommodations->pluck('meal_plan')->filter()->first()) {
            $facts[] = ['icon' => 'bi-cup-hot', 'label' => 'Meals', 'value' => Str::of($meal)->before('(')->trim()->headline()];
        }

        return array_slice($facts, 0, 8);
    }

    public function aziziyaLabel(): ?string
    {
        $aziziya = $this->package->aziziya;

        if (! $aziziya || $aziziya->status === 'not_applicable') {
            return null;
        }

        return match ($aziziya->status) {
            'included' => 'Included',
            'optional' => 'Optional Upgrade',
            'not_included' => 'Not Included',
            default => Str::headline($aziziya->status),
        };
    }

    /**
     * Meal plans by city, deduplicated.
     *
     * @return Collection<string, string>
     */
    public function mealsByLocation(): Collection
    {
        // Meal plans live on two different tables — the hotel rows and the
        // Mina/Arafat rows — and a visitor reading "Meals" expects both. They
        // are merged here rather than in the view so the section can simply be
        // hidden when the package records none at all.
        $fromHotels = $this->package->accommodations
            ->filter(fn ($a) => filled($a->meal_plan))
            ->map(fn ($a) => ['location' => $a->location, 'plan' => $a->meal_plan]);

        $fromMashaer = $this->package->mashaerDetails
            ->filter(fn ($m) => filled($m->meal_plan))
            ->map(fn ($m) => ['location' => $m->location, 'plan' => $m->meal_plan]);

        return $fromHotels
            ->concat($fromMashaer)
            ->groupBy('location')
            ->map(fn ($rows) => $rows->pluck('plan')->unique()->implode(' / '));
    }

    /** Mashaer rows keyed by location so the view can order them deliberately. */
    public function mashaer(): Collection
    {
        return $this->package->mashaerDetails->keyBy(fn ($m) => Str::lower($m->location));
    }

    public function importantNotes(): Collection
    {
        return $this->package->packageNotes->sortByDesc('is_important')->values();
    }

    /**
     * The headline "from" figure for the hero and the sticky summary.
     *
     * `starting_price` is an editorial field the admin fills in, so it wins
     * when present. When it is empty the cheapest *available, actually priced*
     * room is used instead, which is always truthful because it comes from the
     * same rows the pricing table renders. Packages with neither get no price
     * banner at all rather than a zero or a guess.
     *
     * @return array{amount: float, currency: string}|null
     */
    public function startingFrom(): ?array
    {
        // `starting_price` used to answer this and cannot: it holds one
        // number in one currency, so the hero, the sticky box, the action bar
        // and the page's structured data all quoted dollars to a visitor
        // reading in rupees — directly above a room table quoting rupees.
        // `startingPriceIn` reads the room options for the currency actually
        // in force, and falls back to the stored column only when that column
        // is in the currency being asked for.
        $currency = Currency::current();
        $amount = $this->package->startingPriceIn($currency);

        return is_null($amount) ? null : ['amount' => $amount, 'currency' => $currency];
    }

    /**
     * Human label for a variant, e.g. "Package A — Dar Al Tawhid Intercontinental".
     *
     * Used for the enquiry hand-off so the office receives the customer's
     * actual choice instead of just the package name.
     *
     * @param  array{title: string, subtitle: ?string}  $group
     */
    public function optionLabel(array $group): string
    {
        return $group['subtitle']
            ? $group['title'].' — '.$group['subtitle']
            : $group['title'];
    }

    /**
     * One-line summary of where a given option's own hotels are and for how
     * long, built from the accommodation rows tied to that variant. Null when
     * the variant carries no hotel of its own.
     */
    public function optionStayLine(Collection $hotels): ?string
    {
        if ($hotels->isEmpty()) {
            return null;
        }

        return $hotels
            ->map(function ($a) {
                $bits = [$this->locationLabel($a->location)];
                if ($a->nights) {
                    $bits[] = $a->nights.' '.Str::plural('Night', $a->nights);
                }

                return implode(' · ', $bits);
            })
            ->unique()
            ->implode('  ·  ');
    }

    /**
     * The itinerary's two accommodation columns mapped onto the package's real
     * variant codes.
     *
     * `package_itinerary_days` stores `accommodation_a` / `accommodation_b`
     * positionally. The old template hard-coded the labels "Package A" and
     * "Package B", which is only correct because today's data happens to use
     * those codes — a package whose variants were coded "1"/"2" or
     * "Standard"/"Deluxe" would have been mislabelled. These come from the
     * variant rows in `sort_order`, so the labels always match the option
     * cards above.
     *
     * @return array{a: ?string, b: ?string}
     */
    public function itineraryColumnLabels(): array
    {
        $variants = $this->package->variants->sortBy('sort_order')->values();

        return [
            'a' => $variants->get(0) ? 'Package '.$variants->get(0)->code : null,
            'b' => $variants->get(1) ? 'Package '.$variants->get(1)->code : null,
        ];
    }

    /**
     * A single-currency amount, formatted with its own symbol.
     *
     * Transport legs, upgrades and Aziziya services each store ONE price in
     * ONE currency, unlike room options which carry a column per currency.
     * The old template fed them through the currency switcher anyway, so
     * switching to SAR blanked every upgrade on the page to "N/A" even though
     * the price was perfectly well known — it was simply recorded in dollars.
     * These are rendered in the currency they are actually sold in instead.
     */
    public function money(float|int|string $amount, ?string $currency): string
    {
        // Defaults to the currency being read rather than to dollars: a row
        // that stores no currency of its own belongs to the page it is on.
        // A row that DOES store one — a transport leg priced in riyals, say —
        // keeps it, because nothing here is ever converted.
        $code = strtoupper((string) ($currency ?: Currency::current()));

        return in_array($code, Currency::SUPPORTED, true)
            ? (string) Currency::format($amount, $code)
            : $code.' '.number_format((float) $amount);
    }

    /**
     * Heading for the Mashaer block, named after the places this package
     * actually records — "Mina & Arafat", or whatever else is stored.
     */
    public function mashaerTitle(): string
    {
        $places = $this->mashaerOrdered()
            ->pluck('location')
            ->filter()
            ->map(fn ($l) => Str::title($l))
            ->unique()
            ->values();

        return $places->isEmpty() ? 'Mashaer' : $places->join(' & ', ' & ');
    }

    /**
     * Mashaer rows in pilgrimage order (Mina, then Arafat, then Muzdalifah),
     * with anything unexpected kept at the end rather than dropped.
     */
    public function mashaerOrdered(): Collection
    {
        $order = ['mina' => 0, 'arafat' => 1, 'arafah' => 1, 'muzdalifah' => 2, 'muzdalifa' => 2];

        return $this->package->mashaerDetails
            ->sortBy(fn ($m) => $order[Str::lower((string) $m->location)] ?? 99)
            ->values();
    }

    /**
     * The fields a Mashaer row actually holds, already labelled.
     *
     * @return list<array{label: string, value: string}>
     */
    public function mashaerFacts(object $mashaer): array
    {
        $fields = [
            'maktab' => 'Maktab',
            'category' => 'Category',
            'zone' => 'Zone',
            'tent_type' => 'Tent Type',
            'accommodation_type' => 'Accommodation',
            'meal_plan' => 'Meals',
            'bathroom' => 'Bathroom',
            'air_conditioning' => 'Air Conditioning',
            'transportation' => 'Transportation',
        ];

        $facts = [];
        foreach ($fields as $field => $label) {
            if (filled($mashaer->{$field})) {
                $facts[] = ['label' => $label, 'value' => (string) $mashaer->{$field}];
            }
        }

        return $facts;
    }

    /**
     * Facts about the Aziziya stay — only those the record actually holds.
     *
     * @return list<array{label: string, value: string}>
     */
    public function aziziyaFacts(): array
    {
        $a = $this->package->aziziya;
        if (! $a) {
            return [];
        }

        $facts = [];

        // The number of nights in Aziziya is recorded on the accommodation
        // row, not on the Aziziya record. `sharedAccommodation()` holds that
        // row back to avoid printing the hotel name twice, so the one piece of
        // information it uniquely carries is brought across here.
        $nights = $this->package->accommodations->where('location', 'aziziya')->max('nights');
        if ($nights) {
            $facts[] = ['label' => 'Nights', 'value' => $nights.' '.Str::plural('night', $nights)];
        }

        if (filled($a->location_note)) {
            $facts[] = ['label' => 'Location', 'value' => $a->location_note];
        }
        if (filled($a->walk_distance)) {
            $facts[] = ['label' => 'Distance', 'value' => $a->walk_distance];
        }
        if ($a->average_occupancy) {
            $facts[] = ['label' => 'Occupancy', 'value' => 'Average '.$a->average_occupancy.' persons per room'];
        }
        if ($a->duration_days) {
            $facts[] = ['label' => 'Duration', 'value' => $a->duration_days.' '.Str::plural('day', $a->duration_days).' of Hajj'];
        }

        return $facts;
    }

    /**
     * Sections that actually have content, for the in-page jump nav.
     *
     * Built from the same conditions the template uses, so the nav can never
     * advertise a section that was skipped for lack of data.
     *
     * @return list<array{id: string, label: string}>
     */
    public function sections(): array
    {
        $p = $this->package;

        $candidates = [
            ['id' => 'pricing', 'label' => 'Pricing', 'when' => $p->roomOptions->isNotEmpty()],
            ['id' => 'stay', 'label' => 'Your Stay', 'when' => $this->sharedAccommodation()->isNotEmpty()],
            ['id' => 'aziziya', 'label' => 'Aziziya', 'when' => (bool) $this->aziziyaLabel()],
            ['id' => 'mashaer', 'label' => $this->mashaerTitle(), 'when' => $p->mashaerDetails->isNotEmpty()],
            ['id' => 'itinerary', 'label' => 'Itinerary', 'when' => $p->itineraryDays->isNotEmpty()],
            ['id' => 'included', 'label' => "What's Included", 'when' => $p->inclusions->isNotEmpty() || $p->exclusions->isNotEmpty()],
            ['id' => 'upgrades', 'label' => 'Upgrades', 'when' => $p->upgrades->isNotEmpty()],
            ['id' => 'notes', 'label' => 'Notes', 'when' => $p->packageNotes->isNotEmpty()],
        ];

        return array_values(array_map(
            fn ($c) => ['id' => $c['id'], 'label' => $c['label']],
            array_filter($candidates, fn ($c) => $c['when'])
        ));
    }
}
