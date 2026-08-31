<?php

namespace Tests\Feature;

use App\Models\Package;
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

    public function test_all_twelve_real_hajj_packages_are_seeded(): void
    {
        Artisan::call('db:seed');

        $hajjPackages = Package::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->get();

        $this->assertCount(12, $hajjPackages);
        $this->assertEqualsCanonicalizing(
            ['UB001', 'UB003', 'UB004', 'UB006', 'UB008', 'UB010', 'UB011', 'UB013', 'UB015', 'UB016', 'UB023', 'UB024'],
            $hajjPackages->pluck('code')->all()
        );
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
