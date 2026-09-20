<?php

use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Four Hajj series, named the way the brochure names the packages.
 *
 * The site grouped Hajj packages the way the brochure's own section headings
 * do — "Platinum Packages (Makkah & Medinah Series)", "…With Aziziya (Makkah &
 * Medinah Series)", "…With Aziziya (Medinah Series)". Those are accurate but
 * unreadable as a filter, and the middle one holds two different tiers: the
 * Flex packages and the Comfort packages sit together in it.
 *
 * The client asked for the four tiers a customer actually chooses between,
 * and they are already written on every package:
 *
 *     EXECUTIVE PLATINUM  INTERCON   MEDINAH FIRST  (14 DAYS)   UB001-UB013
 *     EXECUTIVE PLATINUM  FLEX 14    MAKKAH FIRST   (14 DAYS)   UB014-UB017
 *     EXECUTIVE PLATINUM  COMFORT    MEDINAH FIRST  (17 DAYS)   UB018-UB021
 *     EXECUTIVE PLATINUM  VALUE      MAKKAH FIRST   (13 DAYS)   UB022-UB026
 *
 * So the tier is read off the name, with the code ranges from the brochure's
 * table of contents as the check. Nothing is invented: every package already
 * carries its tier word.
 *
 * Idempotent. The three old series are renamed in place where they map one to
 * one, so every package keeps its series row and nothing is orphaned; Comfort
 * is the only new row. Running this twice changes nothing.
 */
return new class extends Migration
{
    /** The tier each code range belongs to, straight from the brochure index. */
    private const RANGES = [
        'platinum' => [1, 13],
        'flex' => [14, 17],
        'comfort' => [18, 21],
        'value' => [22, 26],
    ];

    private const SERIES = [
        'platinum' => [
            'name' => 'Platinum',
            'description' => 'Hotels in front of the Haram in both cities, non-shifting, with no Aziziya leg.',
            'sort_order' => 1,
        ],
        'flex' => [
            'name' => 'Flex',
            'description' => 'A shifting itinerary: Haram-front hotels for the main stay, with an Aziziya leg over the days of Hajj.',
            'sort_order' => 2,
        ],
        'comfort' => [
            'name' => 'Comfort',
            'description' => 'Longer stays on a shifting itinerary, with an Aziziya leg over the days of Hajj.',
            'sort_order' => 3,
        ],
        'value' => [
            'name' => 'Value',
            'description' => 'Aziziya accommodation throughout the Makkah stay, non-shifting.',
            'sort_order' => 4,
        ],
    ];

    /** Which old slug becomes which new one, so no package loses its series. */
    private const RENAMES = [
        'platinum-non-aziziya' => 'platinum',
        'platinum-with-aziziya' => 'flex',
        'platinum-value-aziziya' => 'value',
    ];

    public function up(): void
    {
        $hajj = PackageCategory::where('slug', 'hajj')->first();

        if (! $hajj) {
            return;
        }

        foreach (self::RENAMES as $from => $to) {
            $existing = PackageSeries::where('slug', $from)->first();

            if ($existing && ! PackageSeries::where('slug', $to)->exists()) {
                $existing->forceFill(['slug' => $to] + self::SERIES[$to])->save();
            }
        }

        foreach (self::SERIES as $slug => $attributes) {
            PackageSeries::updateOrCreate(
                ['slug' => $slug],
                $attributes + ['package_category_id' => $hajj->id, 'is_active' => true],
            );
        }

        $ids = PackageSeries::whereIn('slug', array_keys(self::SERIES))->pluck('id', 'slug');

        // Re-file every Hajj package by the tier its own code sits in. A package
        // whose code is not a UB number is left exactly where it is — that is
        // how anything the client added by hand in the admin survives this.
        foreach (DB::table('packages')->where('package_category_id', $hajj->id)->get(['id', 'code']) as $package) {
            if (! preg_match('/^UB0*(\d{1,3})$/i', (string) $package->code, $match)) {
                continue;
            }

            $number = (int) $match[1];

            foreach (self::RANGES as $slug => [$from, $to]) {
                if ($number >= $from && $number <= $to) {
                    DB::table('packages')->where('id', $package->id)->update(['package_series_id' => $ids[$slug]]);
                    break;
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::RENAMES) as $new => $old) {
            $series = PackageSeries::where('slug', $new)->first();

            if ($series) {
                $series->forceFill(['slug' => $old])->save();
            }
        }

        // Comfort had no predecessor, so it is only removed if nothing uses it.
        $comfort = PackageSeries::where('slug', 'comfort')->first();

        if ($comfort && ! DB::table('packages')->where('package_series_id', $comfort->id)->exists()) {
            $comfort->delete();
        }
    }
};
