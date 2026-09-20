<?php

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Database\Seeders\HajjPackageSeeder;
use Database\Seeders\HajjPriceCurrencySeeder;
use Database\Seeders\SiteChromeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Carry three seeders to an environment that already has the first twelve
 * packages in it.
 *
 * The deploy runs `artisan migrate --force` and never `db:seed`, so a seeder
 * registered in DatabaseSeeder only ever reaches a fresh local database. The
 * same reasoning as the brochure-correction migration before it: fourteen of
 * the client's own packages being absent from their own website is not
 * something to leave waiting for a manual step.
 *
 * What runs, and why each is safe to run again:
 *
 * 1. HajjPackageSeeder in only-missing mode. A full run replaces a package's
 *    variants, itinerary, accommodation, rooms and notes wholesale — right for
 *    a fresh database, wrong for one where the client may have edited the
 *    twelve that are already there. In this mode it skips any code that
 *    already exists, so it only ever adds.
 * 2. HajjPriceCurrencySeeder, which fills the rupee and riyal columns on those
 *    original twelve. It writes only where a value is still missing.
 * 3. SiteChromeSeeder, which gives the header ticker something to say and
 *    creates a settings row per social platform. It never overwrites a link
 *    that already has a value, so a real address entered in the admin
 *    survives.
 *
 * Ordering matters: the migration that re-files the packages under the four
 * brochure tiers runs first (090000), because the seeder looks the Comfort
 * series up by slug and fails loudly if it is not there.
 */
return new class extends Migration
{
    public function up(): void
    {
        // On a database that has never been seeded — `migrate:fresh`, a new
        // developer's first run, CI — none of this applies: every migration
        // runs before the first seeder does, so there is no Hajj category to
        // hang a package on and no Comfort series to file it under, and the
        // seeder that follows in a moment will create all 26 anyway. Without
        // this, that ordering makes `migrate:fresh --seed` fail outright.
        $ready = PackageCategory::where('slug', 'hajj')->exists()
            && PackageSeries::where('slug', 'comfort')->exists();

        if (! $ready) {
            return;
        }

        // Instantiated rather than called through `db:seed`, because the
        // only-missing flag is the whole point and there is no way to pass it
        // through the Artisan command. The seeder writes nothing to the
        // console, so it needs no command bound to it.
        $packages = new HajjPackageSeeder();
        $packages->onlyMissing = true;
        $packages->run();

        Artisan::call('db:seed', [
            '--class' => HajjPriceCurrencySeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => SiteChromeSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Only the fourteen codes this migration introduced. The twelve that
        // were already here are left alone, whatever state they are in.
        //
        // The prices and the site chrome are deliberately not reversed:
        // rolling back would blank price columns and announcements that are
        // correct either way.
        Package::whereIn('code', [
            'UB002', 'UB005', 'UB007', 'UB009', 'UB012', 'UB014', 'UB017',
            'UB018', 'UB019', 'UB020', 'UB021', 'UB022', 'UB025', 'UB026',
        ])->each(fn (Package $package) => $package->delete());
    }
};
