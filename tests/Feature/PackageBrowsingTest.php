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

    /**
     * Regression (frontend visual redesign, 2026-08-30): all 35 real seeded
     * Tourism packages have `summary` literally set to an internal
     * data-recovery note ("Recovered from the live tourism...listing
     * pages... this content could not be recovered and is not invented
     * here") — honest, deliberate, but written for an internal audience,
     * not a visitor. It was rendering verbatim on the listing card, the
     * detail page hero, and the SEO meta description. `Package::publicSummary()`
     * substitutes an honest "still being finalized" line for that one
     * known string while leaving every other real summary untouched.
     */
    public function test_the_internal_recovery_note_never_reaches_a_visitor(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Tourism', 'slug' => 'tourism']);
        $package = Package::factory()->create([
            'package_category_id' => $category->id,
            'name' => 'Skardu Tour',
            'summary' => 'Recovered from the live tourism.universalbrothers.com listing pages. Full itinerary, inclusions, and hotel details pending — every individual product detail page on the live site currently returns a server error, so this content could not be recovered and is not invented here.',
        ]);

        $listing = $this->get('/tourism');
        $listing->assertOk();
        $listing->assertDontSee('is not invented here');

        $detail = $this->get('/tourism/'.$package->slug);
        $detail->assertOk();
        $detail->assertDontSee('is not invented here');
        $detail->assertDontSee('server error');

        $this->assertSame(
            'Recovered from the live tourism.universalbrothers.com listing pages. Full itinerary, inclusions, and hotel details pending — every individual product detail page on the live site currently returns a server error, so this content could not be recovered and is not invented here.',
            $package->fresh()->summary,
            'the raw column must stay untouched — only the public-facing render is substituted'
        );
    }

    public function test_a_real_summary_is_shown_unchanged(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Umrah', 'slug' => 'umrah']);
        $package = Package::factory()->create([
            'package_category_id' => $category->id,
            'summary' => 'A comfortable 10-night Umrah package with direct Madinah arrival.',
        ]);

        $response = $this->get('/umrah/'.$package->slug);

        $response->assertOk();
        $response->assertSee('A comfortable 10-night Umrah package with direct Madinah arrival.');
    }

    public function test_unknown_category_returns_404(): void
    {
        $response = $this->get('/not-a-real-category');

        $response->assertNotFound();
    }

    /**
     * Rewritten for the redesigned Hajj package data model (see
     * HajjPackagePublicTest.php for the full new-schema coverage) — Hajj
     * packages route to a dedicated view (PackageController::showHajj())
     * that reads roomOptions/variants, not the generic priceTiers/
     * roomPrices tables this test originally exercised.
     */
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

        $package->roomOptions()->create([
            'sharing_type' => 'double', 'display_label' => 'Double Sharing', 'occupancy' => 2,
            'price_basis' => 'per_person', 'price_usd' => 26850, 'is_available' => true,
        ]);

        $package->inclusions()->create(['type' => 'inclusion', 'description' => 'Meet & assist at the airport']);
        $package->exclusions()->create(['type' => 'exclusion', 'description' => 'Airline ticket']);

        $response = $this->get('/hajj/'.$package->slug);

        $response->assertOk();
        $response->assertSee('Executive Platinum Test Package');
        $response->assertSee('UB001');
        $response->assertSee('Dar Al Taqwa');
        $response->assertSee('data-usd="26850.00"', false);
        $response->assertSee('Meet &amp; assist at the airport', false);
        $response->assertSee('Airline ticket');
    }

    /**
     * Rewritten for the redesigned Hajj package data model: upgrades (e.g.
     * Kaba view supplement) are now real per-package rows in
     * `package_upgrades` with the package's own correct value, rather than
     * category-wide PackageAddon rows matched by string-searching the
     * addon's name for "(Non-Aziziya series)"/"(Aziziya series)" — real FK
     * scoping makes the old name-matching test moot (see
     * FINAL_CODE_REVIEW.md-style reasoning: the correct value is
     * structurally guaranteed per package, not filtered post hoc).
     *
     * The value is asserted as the RENDERED price rather than a `data-usd`
     * attribute. `package_upgrades` stores one `price` in one `currency` —
     * there is no column per currency, unlike `package_room_options` — but the
     * old template still fed upgrades through the room-price currency
     * switcher, so choosing SAR blanked every upgrade on the page to "N/A"
     * even though the price was perfectly well known. Upgrades are now printed
     * in the currency they are actually sold in. The assertion's intent is
     * unchanged and just as strict: each package shows its own upgrade value,
     * and never the other package's.
     */
    public function test_package_detail_shows_its_own_upgrade_with_the_correct_value(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        $nonAziziyaPackage = Package::factory()->create(['package_category_id' => $category->id, 'has_aziziya' => false]);
        $aziziyaPackage = Package::factory()->create(['package_category_id' => $category->id, 'has_aziziya' => true, 'slug' => 'aziziya-test-package']);

        $nonAziziyaPackage->upgrades()->create(['name' => 'Kaba view supplement', 'price' => 2200, 'currency' => 'USD', 'price_basis' => 'per person']);
        $aziziyaPackage->upgrades()->create(['name' => 'Kaba view supplement', 'price' => 1050, 'currency' => 'USD', 'price_basis' => 'per person']);

        $nonAziziyaResponse = $this->get('/hajj/'.$nonAziziyaPackage->slug);
        $nonAziziyaResponse->assertOk();
        $nonAziziyaResponse->assertSee('Optional Upgrades');
        $nonAziziyaResponse->assertSee('Kaba view supplement');
        $nonAziziyaResponse->assertSee('US$2,200');

        $aziziyaResponse = $this->get('/hajj/'.$aziziyaPackage->slug);
        $aziziyaResponse->assertOk();
        $aziziyaResponse->assertSee('US$1,050');
        $aziziyaResponse->assertDontSee('US$2,200');
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
