<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * PKR and SAR room prices, transcribed from the client's own 2027 brochures.
 *
 * Source: `docs/source-documents/HAJJ 2027 Packages PKR.pdf` and
 * `HAJJ 2027 Packages Riyal ....pdf` — the ROOM TYPE table on each package's
 * page. Both decks are image-only PowerPoint exports with no text layer, so the
 * figures below were read off the rendered pages (see `.visual-audit/pricestrip.php`,
 * which crops the header and price band from each page for exactly this).
 *
 * Until now the catalogue held 63 USD prices and not a single SAR or PKR one,
 * so the currency switcher showed "N/A" for two of its three options on every
 * package. This fills those two columns from the real brochures. Nothing is
 * converted and nothing is estimated: where a brochure cell reads "NA" the
 * column is left null, exactly as the USD column already is for that row.
 *
 * Keyed by package code => variant code ('A'/'B', or '-' for a package with no
 * variants) => sharing type => [pkr, sar].
 */
class HajjPriceCurrencySeeder extends Seeder
{
    private const PRICES = [
        'UB001' => [
            'A' => ['triple' => [6372000, 82000], 'double' => [7600000, 98000]],
            'B' => ['quad' => [4640000, 59500], 'triple' => [5140000, 66000], 'double' => [5910000, 76000]],
        ],
        'UB003' => [
            'A' => ['triple' => [6140000, 79000], 'double' => [7373000, 95000]],
            'B' => ['quad' => [4525000, 58000], 'triple' => [4900000, 63000], 'double' => [5680000, 73000]],
        ],
        'UB004' => [
            'A' => ['quad' => [4250000, 54500], 'triple' => [4640000, 59500], 'double' => [5485000, 70500]],
            'B' => ['quad' => [4050000, 51900], 'triple' => [4440000, 56900], 'double' => [5200000, 66900]],
        ],
        'UB006' => [
            'A' => ['quad' => [4370000, 56000], 'triple' => [4485000, 57500], 'double' => [5250000, 67500]],
            'B' => ['quad' => [3945000, 50500], 'triple' => [4330000, 55500], 'double' => [5100000, 65500]],
        ],
        'UB008' => [
            'A' => ['quad' => [3785000, 48400], 'triple' => [4160000, 53300], 'double' => [5085000, 65300]],
            'B' => ['sharing_room' => [3550000, 45400], 'quad' => [3550000, 45400], 'triple' => [4000000, 51300], 'double' => [4830000, 62000]],
        ],
        'UB010' => [
            'A' => ['quad' => [3700000, 47400], 'triple' => [4000000, 51300], 'double' => [4830000, 62000]],
            'B' => ['sharing_room' => [3485000, 44500], 'quad' => [3485000, 44500], 'triple' => [3930000, 50300], 'double' => [4680000, 60000]],
        ],
        'UB011' => [
            '-' => ['quad' => [2999000, 38200], 'triple' => [3290000, 42000], 'double' => [3830000, 49000]],
        ],
        'UB013' => [
            '-' => ['quad' => [2999000, 38200], 'triple' => [3290000, 42000], 'double' => [3830000, 49000]],
        ],
        'UB015' => [
            'A' => ['quad' => [3330000, 42500], 'triple' => [3640000, 46500], 'double' => [4140000, 53000]],
            'B' => ['quad' => [3175000, 40500], 'triple' => [3485000, 44500], 'double' => [3870000, 49500]],
        ],
        'UB016' => [
            'A' => ['quad' => [3175000, 40500], 'triple' => [3400000, 43500], 'double' => [3675000, 47000]],
            'B' => ['quad' => [3060000, 39000], 'triple' => [3250000, 41500], 'double' => [3525000, 45000]],
        ],
        'UB023' => [
            'A' => ['quad' => [2985000, 38000], 'triple' => [3060000, 39000], 'double' => [3215000, 41000]],
            'B' => ['quad' => [2790000, 35500], 'triple' => [2870000, 36500], 'double' => [2945000, 37500]],
        ],
        'UB024' => [
            '-' => ['quad' => [2635000, 33500], 'triple' => [2675000, 34000], 'double' => [2715000, 34500]],
        ],
    ];

    /**
     * Aziziya supplements, keyed by the USD figure already in the database.
     *
     * Every package page in all three decks quotes the same Aziziya Family Room
     * supplement, and the three decks state it as US$ 5,500 / PKR 1,540,000 /
     * SAR 20,000 for the same line. Keying on the USD value the database
     * already holds means a row is only touched when its dollar figure proves
     * it is that supplement — no guessing which row is which.
     */
    private const AZIZIYA_SUPPLEMENTS = [
        // "Aziziya Accommodation — Family Rooms ... for 05 days of Hajj with
        // Supplement", quoted identically on every non-Aziziya package page.
        '5500' => [1540000, 20000],

        // "Supplement for Aziziya Family Room — Double / Triple (Per Person)",
        // on the WITH-AZIZIYA pages (UB023, UB024). Each figure is read from
        // its own deck; none is converted from another currency, even though
        // the three happen to sit at a consistent rate.
        '1100' => [308000, 4000],   // Double, per person
        '550' => [154000, 2000],    // Triple, per person
    ];

    public function run(): void
    {
        $updated = 0;
        $unchanged = 0;
        $unmatched = [];

        foreach (self::PRICES as $code => $byVariant) {
            $package = Package::where('code', $code)->with(['variants', 'roomOptions.variant'])->first();

            if (! $package) {
                $unmatched[] = "{$code}: package not found";
                continue;
            }

            foreach ($byVariant as $variantCode => $bySharing) {
                foreach ($bySharing as $sharing => [$pkr, $sar]) {
                    $rows = $package->roomOptions->filter(function ($room) use ($variantCode, $sharing) {
                        $matchesVariant = $variantCode === '-'
                            ? is_null($room->variant_id)
                            : ($room->variant?->code === $variantCode);

                        return $matchesVariant && $room->sharing_type === $sharing;
                    });

                    if ($rows->isEmpty()) {
                        $unmatched[] = "{$code} {$variantCode} {$sharing}: no matching room option";
                        continue;
                    }

                    foreach ($rows as $room) {
                        // Only the two empty columns are touched. The USD price
                        // already in the database is the authority for USD and
                        // is never rewritten here.
                        //
                        // Compared NUMERICALLY. These columns are decimal-cast,
                        // so the stored value reads back as "6372000.00" while
                        // the brochure figure here is the integer 6372000 — a
                        // string comparison never matched and the seeder rewrote
                        // all 71 rows on every run. Seeders in this project must
                        // be safe AND inert on a second run.
                        if ($this->same($room->price_pkr, $pkr) && $this->same($room->price_sar, $sar)) {
                            $unchanged++;
                            continue;
                        }

                        $room->forceFill(['price_pkr' => $pkr, 'price_sar' => $sar])->save();
                        $updated++;
                    }
                }
            }
        }

        // Aziziya room options live on their own table and carry their own
        // per-currency columns, so they were blank for the same reason the main
        // rooms were.
        $aziziyaUpdated = 0;
        $aziziyaSkipped = [];

        foreach (Package::whereNotNull('code')->with('aziziya.roomOptions')->get() as $package) {
            foreach ($package->aziziya?->roomOptions ?? [] as $room) {
                $usd = is_null($room->price_usd) ? null : (string) (int) $room->price_usd;
                $match = $usd === null ? null : (self::AZIZIYA_SUPPLEMENTS[$usd] ?? null);

                if (! $match) {
                    if (! is_null($room->price_usd)) {
                        $aziziyaSkipped[] = "{$package->code}: aziziya room at US$ {$room->price_usd} has no brochure figure";
                    }
                    continue;
                }

                [$pkr, $sar] = $match;
                if ($this->same($room->price_pkr, $pkr) && $this->same($room->price_sar, $sar)) {
                    $unchanged++;
                    continue;
                }

                $room->forceFill(['price_pkr' => $pkr, 'price_sar' => $sar])->save();
                $aziziyaUpdated++;
            }
        }

        $this->command?->info("HajjPriceCurrencySeeder: {$updated} room prices and {$aziziyaUpdated} Aziziya supplements updated, {$unchanged} already current.");

        foreach ($aziziyaSkipped as $problem) {
            $this->command?->warn("  {$problem}");
        }

        foreach ($unmatched as $problem) {
            $this->command?->warn("  {$problem}");
        }
    }

    /**
     * Numeric equality for a decimal-cast column against a plain integer.
     *
     * `price_pkr` and `price_sar` are decimal-cast, so a stored 6372000 reads
     * back as the string "6372000.00". Comparing that to the integer 6372000 as
     * strings never matches, which made the first version of this seeder rewrite
     * every row on every run — it worked, but it was not inert, and a seeder in
     * this project has to be both.
     */
    private function same(mixed $stored, int|float $wanted): bool
    {
        return ! is_null($stored) && abs((float) $stored - (float) $wanted) < 0.005;
    }
}
