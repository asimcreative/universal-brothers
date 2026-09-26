<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give the optional extras a price per currency, the way rooms already have.
 *
 * `package_upgrades` and `package_transportation` each held one `price` and
 * one `currency`, and every row was in dollars — they were read out of the
 * US$ brochure when the first twelve packages were extracted. Once the site
 * started quoting a single currency chosen by the visitor, a page read in
 * rupees showed its room table in rupees and then, further down, "US$165"
 * for the airport transfer and "US$2,200" for the Kaba view supplement.
 *
 * The figures are not conversions. Each is transcribed from the brochure
 * printed in that currency:
 *
 *   PKR pg 27  Medinah night: DOUBLE 231000, TRIPLE 169400, QUAD 168600
 *              Kaba view 616000; VIP GMC 2695000
 *   PKR pg 26  taxi Jeddah-Makkah 46000, Medinah-Medinah 11600
 *   SAR pg 27  Medinah night: DOUBLE 3000, TRIPLE 2200, QUAD 2190
 *              Kaba view 8000; VIP GMC 35000
 *   SAR pg 26  taxi Jeddah-Makkah 600, Medinah-Medinah 150
 *   SAR pg 43  Kaba view (with Aziziya) 3800
 *
 * Note that quad and triple are both US$600 and yet 168600 and 169400 in
 * rupees. That is what the brochures say, and it is the reason none of this
 * is computed from an exchange rate.
 *
 * One figure is deliberately left null: the Kaba view supplement on the
 * with-Aziziya packages. The US$ and Riyal brochures publish it (US$1050,
 * SAR 3800); the PKR brochure does not list it at all. A blank column means
 * "this brochure does not publish that price", exactly as it does for rooms,
 * and the page says so rather than inventing a number.
 */
return new class extends Migration
{
    /** Keyed by the USD figure the row already carries. */
    private const UPGRADES = [
        // usd  => [pkr, sar]
        '600.00' => null,      // resolved by name below — quad and triple differ
        '850.00' => [231000, 3000],
        '2200.00' => [616000, 8000],
        '1050.00' => [null, 3800],
    ];

    private const NIGHTS = [
        'Quad' => [168600, 2190],
        'Triple' => [169400, 2200],
        'Double' => [231000, 3000],
    ];

    private const TRANSPORT = [
        // usd  => [pkr, sar]
        '165.00' => [46000, 600],
        '40.00' => [11600, 150],
        '9600.00' => [2695000, 35000],
    ];

    public function up(): void
    {
        foreach (['package_upgrades', 'package_transportation'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (['pkr', 'sar', 'usd'] as $code) {
                    if (! Schema::hasColumn($table, 'price_'.$code)) {
                        $blueprint->decimal('price_'.$code, 12, 2)->nullable()->after('price');
                    }
                }
            });
        }

        // Everything already stored is a dollar figure; move it into the
        // column that says so, leaving `price`/`currency` untouched so an
        // admin screen that still reads them keeps working.
        DB::table('package_upgrades')->whereNotNull('price')
            ->where('currency', 'USD')->update(['price_usd' => DB::raw('price')]);
        DB::table('package_transportation')->whereNotNull('price')
            ->where('currency', 'USD')->update(['price_usd' => DB::raw('price')]);

        foreach (DB::table('package_upgrades')->whereNotNull('price_usd')->get() as $row) {
            [$pkr, $sar] = $this->upgradeFigures($row);

            DB::table('package_upgrades')->where('id', $row->id)
                ->update(['price_pkr' => $pkr, 'price_sar' => $sar]);
        }

        foreach (self::TRANSPORT as $usd => [$pkr, $sar]) {
            DB::table('package_transportation')->where('price_usd', $usd)
                ->update(['price_pkr' => $pkr, 'price_sar' => $sar]);
        }
    }

    /** @return array{0: ?int, 1: ?int} */
    private function upgradeFigures(object $row): array
    {
        // The two Medinah-night rows share a dollar figure and differ in
        // rupees, so the room type decides rather than the amount.
        foreach (self::NIGHTS as $room => $figures) {
            if (str_contains($row->name, 'Medinah Night') && str_contains($row->name, $room)) {
                return $figures;
            }
        }

        $known = self::UPGRADES[number_format((float) $row->price_usd, 2, '.', '')] ?? null;

        return $known ?? [null, null];
    }

    public function down(): void
    {
        foreach (['package_upgrades', 'package_transportation'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['price_pkr', 'price_sar', 'price_usd']);
            });
        }
    }
};
