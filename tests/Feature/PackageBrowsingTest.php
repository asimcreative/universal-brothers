<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageBrowsingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_category_listing_shows_published_packages(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        Package::factory()->create(['package_category_id' => $category->id, 'name' => 'Visible Package']);

        $response = $this->get('/hajj');

        $response->assertOk();
        $response->assertSee('Visible Package');
    }

    public function test_category_listing_hides_draft_packages(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        Package::factory()->draft()->create(['package_category_id' => $category->id, 'name' => 'Hidden Draft Package']);

        $response = $this->get('/hajj');

        $response->assertOk();
        $response->assertDontSee('Hidden Draft Package');
    }

    public function test_unknown_category_returns_404(): void
    {
        $response = $this->get('/not-a-real-category');

        $response->assertNotFound();
    }

    public function test_package_detail_page_shows_itinerary_and_pricing(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        $package = Package::factory()->create([
            'package_category_id' => $category->id,
            'code' => 'UB001',
            'name' => 'Executive Platinum Test Package',
        ]);

        $package->itineraryDays()->create([
            'day_number' => 1,
            'date_hijri_label' => '01 Zil Hajj',
            'city' => 'To Medinah',
            'accommodation_a' => 'Dar Al Taqwa ★★★★★',
        ]);

        $tier = $package->priceTiers()->create(['label' => 'Package A — Test Hotel']);
        $tier->roomPrices()->create(['room_type' => 'double', 'price' => 26850, 'currency' => 'USD']);

        $package->inclusions()->create(['type' => 'inclusion', 'description' => 'Meet & assist at the airport']);
        $package->exclusions()->create(['type' => 'exclusion', 'description' => 'Airline ticket']);

        $response = $this->get('/hajj/'.$package->slug);

        $response->assertOk();
        $response->assertSee('Executive Platinum Test Package');
        $response->assertSee('UB001');
        $response->assertSee('Dar Al Taqwa');
        $response->assertSee('26,850');
        $response->assertSee('Meet &amp; assist at the airport', false);
        $response->assertSee('Airline ticket');
    }

    public function test_draft_package_detail_returns_404(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        $package = Package::factory()->draft()->create(['package_category_id' => $category->id]);

        $response = $this->get('/hajj/'.$package->slug);

        $response->assertNotFound();
    }

    public function test_package_detail_under_wrong_category_returns_404(): void
    {
        $hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        PackageCategory::factory()->create(['name' => 'Tourism', 'slug' => 'tourism']);
        $package = Package::factory()->create(['package_category_id' => $hajj->id]);

        $response = $this->get('/tourism/'.$package->slug);

        $response->assertNotFound();
    }
}
