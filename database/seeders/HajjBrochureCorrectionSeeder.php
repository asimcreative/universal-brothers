<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageAccommodation;
use App\Models\PackageItineraryDay;
use App\Models\PackageRoomOption;
use App\Models\PackageVariant;
use Illuminate\Database\Seeder;

/**
 * Bring the seeded Hajj data in line with the client's current brochures.
 *
 * The database was seeded from "HAJJ 2027 Packages overseas.pdf" (20 Aug
 * 2026). The client has since supplied "HAJJ 2027 Packages US$.pdf" (25 Aug
 * 2026), which supersedes it, and separately re-exported the overseas deck
 * with the same corrections. Two independently produced documents therefore
 * agree on every figure below, against one superseded one.
 *
 * HajjPackageSeeder has been corrected too, so a fresh install is right from
 * the start. This seeder exists for environments that were already seeded —
 * production above all, where re-running HajjPackageSeeder is not an option
 * because it deletes and rebuilds its child rows, which would destroy the
 * PKR/SAR prices that came from a different deck and any edit made in the
 * admin.
 *
 * Every write is conditional on the row still holding the exact superseded
 * value. A row that already carries the corrected figure is left alone (so
 * this is idempotent), and so is a row holding anything else — if somebody has
 * edited a price in the admin, that edit is theirs and this must not silently
 * overwrite it.
 *
 * NOT changed here, deliberately:
 *
 * - PKR and SAR. Those come from the PKR deck (7 Sep 2026) and the Riyal deck
 *   (25 Aug 2026), and both still print the older figures for these three
 *   packages, so the client's own decks disagree with each other. Converting
 *   USD into PKR/SAR ourselves would mean publishing a Hajj price that appears
 *   in no brochure at all. The mismatch is reported to the client instead.
 *
 * - UB013's name. The current brochure titles it "VOCO BY IHG – MEDINAH FIRST"
 *   and its itinerary starts "To Medinah", while the database says "Makkah
 *   First (Short Package)". Correcting that means changing a published URL,
 *   which is a separate decision and a separate change.
 */
class HajjBrochureCorrectionSeeder extends Seeder
{
    /**
     * code => variant code ('-' when the package has no variants)
     *      => sharing type => [superseded USD, corrected USD]
     */
    private const USD = [
        // The 20 Aug deck printed 18850 for this quad — above the same
        // package's own DOUBLE price, which cannot be right. The 25 Aug deck
        // reads 13850, which sits below the triple (15200) and double (17950)
        // as a quad must. A corrected typo rather than a price change.
        'UB006' => [
            'B' => ['quad' => [18850, 13850]],
        ],
        'UB008' => [
            'A' => [
                'quad' => [13850, 13250],
                'triple' => [15200, 14600],
                'double' => [18220, 17900],
            ],
            'B' => [
                'sharing_room' => [13000, 12450],
                'quad' => [13000, 12450],
                'triple' => [14650, 14050],
                'double' => [17400, 16990],
            ],
        ],
        'UB010' => [
            'A' => [
                'quad' => [13550, 12890],
                'triple' => [14650, 14050],
                'double' => [17400, 16990],
            ],
            'B' => [
                'sharing_room' => [12750, 12200],
                'quad' => [12750, 12200],
                'triple' => [14390, 13790],
                'double' => [16850, 16450],
            ],
        ],
    ];

    /**
     * Hotels the brochure renamed. Both are upgrades, so the star rating moves
     * with the name and the two must never be applied apart.
     *
     * [old name, old stars, new name, new stars, old itinerary label, new itinerary label]
     */
    private const HOTELS = [
        [
            'Taibah Front / Similar', 3,
            'Dallah Taibah (Premier Floor)', 4,
            'Taibah Front / Similar ★★★',
            'Dallah Taibah (Premier Floor) ★★★★',
        ],
        [
            // The brochure prints this one as four stars and a plus.
            // star_rating is an integer column, so it holds 4 and the
            // itinerary label keeps the "+" exactly as printed.
            'Abraaj Tower / Swiss Maqam', 5,
            'Pullman Zamzam Makkah', 4,
            'Abraaj Tower / Swiss Maqam ★★★★★',
            'Pullman Zamzam Makkah ★★★★+',
        ],
    ];

    /** Variant labels carry the short form of the B-column hotel. */
    private const VARIANT_LABELS = [
        'Taibah Front' => 'Dallah Taibah',
    ];

    public function run(): void
    {
        $prices = $this->correctPrices();
        $hotels = $this->correctHotels();
        $labels = $this->correctVariantLabels();

        // packages.starting_price is derived from the room options, so it has
        // to be recomputed after they move — and it is NOT only a display
        // nicety on the listing card: it is what the detail page publishes as
        // the Schema.org Offer price, so leaving it stale feeds the superseded
        // figure straight to search engines while the visible table shows the
        // corrected one.
        $starting = $this->correctStartingPrices();

        $this->command?->info(sprintf(
            'Brochure corrections — prices: %d updated, %d already correct, %d left alone; '
            .'accommodations: %d; itinerary rows: %d; variant labels: %d; starting prices: %d.',
            $prices['updated'],
            $prices['already'],
            $prices['skipped'],
            $hotels['accommodations'],
            $hotels['itinerary'],
            $labels,
            $starting
        ));
    }

    /**
     * Recompute starting_price the same way HajjPackageSeeder derives it —
     * the lowest available USD room price — but only where it still holds a
     * derived value rather than one somebody set on purpose.
     *
     * HajjPackagePresenter treats starting_price as an editorial field the
     * admin can override, so anything that is neither the superseded minimum
     * nor the corrected one was set deliberately and is left alone.
     *
     * Both minima are computed from the room options WITHOUT depending on
     * whether correctPrices() has already run — which matters because the
     * price half of this fix shipped a commit before the starting_price half,
     * so on production the rooms are already corrected while starting_price
     * still holds the superseded figure. Keying off "the value before this run"
     * would have skipped exactly the environment that needs repairing.
     */
    private function correctStartingPrices(): int
    {
        $changed = 0;

        foreach (Package::whereIn('code', array_keys(self::USD))->get() as $package) {
            $corrected = $this->lowestUsd($package, 1);

            if ($corrected === null || $this->isSameMoney($package->starting_price, $corrected)) {
                continue;
            }

            if (! $this->isSameMoney($package->starting_price, $this->lowestUsd($package, 0))) {
                continue;
            }

            $package->forceFill(['starting_price' => $corrected])->save();
            $changed++;
        }

        return $changed;
    }

    /**
     * The lowest available USD room price for a package, reading the const
     * table's superseded ($index 0) or corrected ($index 1) figure for any row
     * this seeder covers and the stored price for every other row.
     */
    private function lowestUsd(Package $package, int $index): ?float
    {
        $wanted = [];

        foreach (self::USD[$package->code] ?? [] as $variantCode => $rooms) {
            $variantId = $variantCode === '-'
                ? null
                : PackageVariant::where('package_id', $package->id)->where('code', $variantCode)->value('id');

            foreach ($rooms as $sharingType => $pair) {
                $wanted[$variantId.'|'.$sharingType] = (float) $pair[$index];
            }
        }

        $lowest = null;

        $options = PackageRoomOption::where('package_id', $package->id)
            ->where('is_available', true)
            ->get();

        foreach ($options as $option) {
            $price = $wanted[$option->variant_id.'|'.$option->sharing_type] ?? (
                $option->price_usd === null ? null : (float) $option->price_usd
            );

            if ($price === null) {
                continue;
            }

            $lowest = $lowest === null ? $price : min($lowest, $price);
        }

        return $lowest;
    }

    /** @return array{updated:int, already:int, skipped:int} */
    private function correctPrices(): array
    {
        $updated = $already = $skipped = 0;

        foreach (self::USD as $code => $variants) {
            $package = Package::where('code', $code)->first();

            if (! $package) {
                continue;
            }

            foreach ($variants as $variantCode => $rooms) {
                $variantId = $variantCode === '-'
                    ? null
                    : PackageVariant::where('package_id', $package->id)->where('code', $variantCode)->value('id');

                foreach ($rooms as $sharingType => [$was, $now]) {
                    $option = PackageRoomOption::where('package_id', $package->id)
                        ->where('variant_id', $variantId)
                        ->where('sharing_type', $sharingType)
                        ->first();

                    if (! $option) {
                        continue;
                    }

                    if ($this->isSameMoney($option->price_usd, $now)) {
                        $already++;

                        continue;
                    }

                    if (! $this->isSameMoney($option->price_usd, $was)) {
                        // Someone has changed this since; leave their value.
                        $skipped++;

                        continue;
                    }

                    $option->price_usd = $now;
                    $option->save();
                    $updated++;
                }
            }
        }

        return ['updated' => $updated, 'already' => $already, 'skipped' => $skipped];
    }

    /** @return array{accommodations:int, itinerary:int} */
    private function correctHotels(): array
    {
        $accommodations = $itinerary = 0;

        foreach (self::HOTELS as [$oldName, $oldStars, $newName, $newStars, $oldLabel, $newLabel]) {
            $accommodations += PackageAccommodation::where('hotel_name', $oldName)
                ->where('star_rating', $oldStars)
                ->update(['hotel_name' => $newName, 'star_rating' => $newStars]);

            foreach (['accommodation_a', 'accommodation_b'] as $column) {
                $itinerary += PackageItineraryDay::where($column, $oldLabel)
                    ->update([$column => $newLabel]);
            }
        }

        return ['accommodations' => $accommodations, 'itinerary' => $itinerary];
    }

    private function correctVariantLabels(): int
    {
        $changed = 0;

        foreach (self::VARIANT_LABELS as $old => $new) {
            $changed += PackageVariant::where('label', $old)->update(['label' => $new]);
        }

        return $changed;
    }

    /**
     * price_usd is a decimal cast, so a stored "13850.00" never equals the int
     * 13850 under a string comparison. Compare numerically.
     */
    private function isSameMoney(mixed $stored, int|float $wanted): bool
    {
        return ! is_null($stored) && abs((float) $stored - (float) $wanted) < 0.005;
    }
}
