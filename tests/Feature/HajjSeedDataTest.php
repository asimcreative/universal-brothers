<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageAccommodation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Runs the real seeders (not factories) and checks the actual Hajj 2027
 * brochure facts survive the full pipeline: seed → DB → public route →
 * rendered HTML. This is the project's core "never invent business data"
 * guarantee, exercised end-to-end rather than asserted only in the seeder.
 */
class HajjSeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_twenty_six_real_hajj_packages_are_seeded(): void
    {
        Artisan::call('db:seed');

        $hajjPackages = Package::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->get();

        // The brochure index, in full. Twelve of these were seeded from the
        // first extraction; the other fourteen came out of the September
        // PKR/Riyal/US$ brochures. Spelling the list out rather than counting
        // is deliberate: a missing code is a package the client has and their
        // website does not, which is the failure this whole exercise began
        // with, and a count would not name it.
        $this->assertEqualsCanonicalizing([
            'UB001', 'UB002', 'UB003', 'UB004', 'UB005', 'UB006', 'UB007', 'UB008', 'UB009',
            'UB010', 'UB011', 'UB012', 'UB013', 'UB014', 'UB015', 'UB016', 'UB017', 'UB018',
            'UB019', 'UB020', 'UB021', 'UB022', 'UB023', 'UB024', 'UB025', 'UB026',
        ], $hajjPackages->pluck('code')->all());

        // And each one in the tier its own title names.
        $tiers = $hajjPackages->groupBy(fn ($p) => $p->series?->slug)->map->count()
            ->only(['platinum', 'flex', 'comfort', 'value'])->sortKeys()->all();
        $this->assertSame(['comfort' => 4, 'flex' => 4, 'platinum' => 13, 'value' => 5], $tiers);
    }

    /**
     * A star rating the admin form would refuse is a package the client
     * cannot open and save without an error, which is how this was found:
     * UB007 and UB017 print "Al Aqeeq ★★★★ / Dallah Taibah ★★★★ / Similar",
     * two four-star hotels offered as alternatives, and the extraction
     * counted every star in the line and called it an eight-star hotel.
     */
    public function test_no_seeded_accommodation_carries_a_rating_the_admin_would_reject(): void
    {
        Artisan::call('db:seed');

        $outOfRange = PackageAccommodation::whereNotNull('star_rating')
            ->where(fn ($q) => $q->where('star_rating', '<', 1)->orWhere('star_rating', '>', 5))
            ->with('package:id,code')
            ->get()
            ->map(fn ($a) => "{$a->package?->code} {$a->hotel_name} = {$a->star_rating}")
            ->all();

        $this->assertSame([], $outOfRange);
    }

    public function test_ub001_detail_page_shows_real_brochure_data(): void
    {
        Artisan::call('db:seed');

        $package = Package::where('code', 'UB001')->firstOrFail();

        $response = $this->get('/hajj/'.$package->slug);

        $response->assertOk();
        $response->assertSee('Dar Al Tawhid Intercontinental');
        $response->assertSee('Fairmont Clock Tower');
        // Prices render client-side from data attributes (currency
        // switcher), not server-formatted text — assert the raw values the
        // brochure prints for UB001's Package A Double ($26,850) and
        // Package B Triple ($18,100).
        $response->assertSee('data-usd="26850.00"', false);
        $response->assertSee('data-usd="18100.00"', false);
        $response->assertSee('Zone 1 near to Jamarat A Category', false);
    }

    public function test_seeders_are_idempotent_when_run_twice(): void
    {
        Artisan::call('db:seed');
        $firstRunCount = Package::count();
        $firstItineraryCount = \App\Models\PackageItineraryDay::count();

        Artisan::call('db:seed');

        $this->assertSame($firstRunCount, Package::count());
        $this->assertSame($firstItineraryCount, \App\Models\PackageItineraryDay::count());
    }

    public function test_tourism_category_has_no_invented_itinerary_data(): void
    {
        Artisan::call('db:seed');

        $skardu = Package::where('name', 'like', '%Splendid Skardu%')->firstOrFail();

        $this->assertSame(0, $skardu->itineraryDays()->count());
        $this->assertSame(0, $skardu->inclusions()->count());
        $this->assertEquals(197500, $skardu->starting_price);
    }
}
