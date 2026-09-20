<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the redesigned Hajj package public detail page — Package A/B
 * variants, dynamic sharing types, the fully separate Aziziya sub-schema,
 * and the multi-currency display. Directive §28/29's required test areas:
 * main sharing (quad/triple/double + additional types), all three
 * currencies, Aziziya included/not-included/optional/sharing/supplement/
 * service, and the critical invariant that switching currency changes only
 * price values.
 */
class HajjPackagePublicTest extends TestCase
{
    use RefreshDatabase;

    private PackageCategory $hajj;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
        $this->hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
    }

    private function buildPackage(array $overrides = []): Package
    {
        $package = Package::factory()->create(array_merge([
            'package_category_id' => $this->hajj->id,
            'name' => 'Executive Platinum Test Package',
            'code' => 'UB-TEST',
            'medinah_first' => true,
            'is_shifting' => false,
            'status' => 'published',
        ], $overrides));

        $variantA = $package->variants()->create(['code' => 'A', 'label' => 'Hotel A', 'sort_order' => 0]);
        $variantB = $package->variants()->create(['code' => 'B', 'label' => 'Hotel B', 'sort_order' => 1]);

        $package->roomOptions()->create(['variant_id' => $variantA->id, 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_basis' => 'per_person', 'price_usd' => null, 'is_available' => false]);
        $package->roomOptions()->create(['variant_id' => $variantA->id, 'sharing_type' => 'triple', 'occupancy' => 3, 'display_label' => 'Triple Sharing', 'price_basis' => 'per_person', 'price_usd' => 22450, 'is_available' => true]);
        $package->roomOptions()->create(['variant_id' => $variantB->id, 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_basis' => 'per_person', 'price_usd' => 16300, 'is_available' => true]);
        $package->roomOptions()->create(['variant_id' => $variantB->id, 'sharing_type' => 'double', 'occupancy' => 2, 'display_label' => 'Double Sharing', 'price_basis' => 'per_person', 'price_usd' => 20850, 'is_available' => true]);
        $package->roomOptions()->create(['variant_id' => null, 'sharing_type' => 'sharing_room', 'occupancy' => null, 'display_label' => 'Sharing Room', 'price_basis' => 'per_person', 'price_usd' => 13000, 'is_available' => true]);

        return $package;
    }

    public function test_main_package_sharing_types_and_prices_render(): void
    {
        $package = $this->buildPackage();

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Quad Sharing');
        $response->assertSee('Triple Sharing');
        $response->assertSee('Double Sharing');
        $response->assertSee('Sharing Room');
        $response->assertSee('data-usd="22450.00"', false);
        $response->assertSee('data-usd="16300.00"', false);
        $response->assertSee('data-usd="20850.00"', false);
    }

    public function test_a_real_na_cell_renders_as_not_available_not_a_guessed_price(): void
    {
        $package = $this->buildPackage();

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        // The unavailable Package A Quad row must show N/A, not a price cell.
        $response->assertSeeInOrder(['Quad Sharing', 'N/A', 'Triple Sharing', '22450']);
    }

    public function test_the_page_quotes_whichever_currency_the_visitor_is_reading(): void
    {
        $package = $this->buildPackage();
        $package->roomOptions()->where('sharing_type', 'triple')->update(['price_pkr' => 6300000, 'price_sar' => 84000]);

        // Every currency's real figure still ships in the markup: the three
        // brochures are separate price lists and nothing is ever converted,
        // so the admin preview and these tests can read all three.
        $response = $this->withSession([Currency::SESSION_KEY => 'PKR'])->get("/hajj/{$package->slug}");
        $response->assertOk();
        $response->assertSee('data-pkr="6300000.00"', false);
        $response->assertSee('data-sar="84000.00"', false);

        // What is SHOWN is the one chosen, written the way that currency
        // writes it. This used to assert three `data-currency` buttons,
        // because the page carried its own switcher and rewrote the numbers
        // in JavaScript; the choice is made once for the whole site now, so
        // the thing worth asserting is the figure on the page.
        $response->assertSee('PKR 6,300,000');

        $this->withSession([Currency::SESSION_KEY => 'SAR'])->get("/hajj/{$package->slug}")
            ->assertOk()->assertSee('SAR 84,000');

        $this->withSession([Currency::SESSION_KEY => 'USD'])->get("/hajj/{$package->slug}")
            ->assertOk()->assertSee('US$22,450');
    }

    public function test_aziziya_included_status_renders(): void
    {
        $package = $this->buildPackage();
        $package->aziziya()->create(['status' => 'included', 'accommodation_name' => 'Aziziya A Class', 'average_occupancy' => 4]);

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Included');
        $response->assertSee('Aziziya A Class');
        $response->assertSee('Average 4 persons per room');
    }

    public function test_aziziya_not_included_status_renders(): void
    {
        $package = $this->buildPackage();
        $package->aziziya()->create(['status' => 'not_included']);

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Not Included');
    }

    public function test_aziziya_optional_status_with_sharing_and_supplement_renders(): void
    {
        $package = $this->buildPackage();
        $aziziya = $package->aziziya()->create(['status' => 'optional']);
        $aziziya->roomOptions()->create([
            'sharing_type' => 'family_room', 'display_label' => 'Family Room', 'occupancy' => 4,
            'pricing_type' => 'supplement', 'price_basis' => 'flat', 'price_usd' => 5500,
        ]);

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Optional Upgrade');
        $response->assertSee('Family Room');
        $response->assertSee('supplement');
        $response->assertSee('data-usd="5500.00"', false);
    }

    public function test_aziziya_optional_service_renders(): void
    {
        $package = $this->buildPackage();
        $aziziya = $package->aziziya()->create(['status' => 'included']);
        $aziziya->services()->create(['name' => 'Free WiFi in lobby', 'is_included' => true]);

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Free WiFi in lobby');
    }

    /**
     * The critical invariant test (directive §29): switching currency must
     * change ONLY price values. Every other fact — package name, sharing
     * labels, accommodation, itinerary — must be identical regardless of
     * which currency's data attribute the page happens to be holding.
     * Since currency switching is a client-side JS operation (no
     * server round-trip), the correct way to prove this server-side is to
     * assert the page emits the SAME non-price content once, and carries
     * all three currencies' values simultaneously as data attributes for
     * the client to swap between — not three different server responses.
     */
    public function test_switching_currency_data_changes_only_price_values_not_package_content(): void
    {
        $package = $this->buildPackage();
        $package->roomOptions()->where('sharing_type', 'triple')->update(['price_pkr' => 6300000, 'price_sar' => 84000]);
        $aziziya = $package->aziziya()->create(['status' => 'optional', 'accommodation_name' => 'Aziziya A Class']);
        $aziziya->roomOptions()->create([
            'sharing_type' => 'family_room', 'display_label' => 'Family Room',
            'pricing_type' => 'supplement', 'price_basis' => 'flat', 'price_usd' => 5500, 'price_pkr' => 1540000, 'price_sar' => 20625,
        ]);

        $response = $this->get("/hajj/{$package->slug}");
        $html = $response->getContent();

        // Package-identity facts appear exactly once in the page body (the
        // package name legitimately repeats in <title>/breadcrumb/<h1>,
        // which is normal page chrome, not a currency-driven re-render) —
        // the page is rendered once, not once per currency.
        $this->assertSame(1, substr_count($html, 'Aziziya A Class'));
        $this->assertSame(1, substr_count($html, 'Triple Sharing'));
        $this->assertSame(1, substr_count($html, 'Family Room'));

        // All three currencies' real values are present simultaneously in
        // the same markup for the same room option — proving the client can
        // switch currency without altering anything else on the page.
        $response->assertSee('data-pkr="6300000.00" data-sar="84000.00" data-usd="22450.00"', false);
        $response->assertSee('data-pkr="1540000.00" data-sar="20625.00" data-usd="5500.00"', false);
    }

    /**
     * Regression for the frontend redesign's section-order pass: the
     * detail page previously never rendered a distinct "Package Options"
     * (Package A/B) block, a consolidated "Meals" section, or the Gallery
     * — despite real meal-plan and media data already being seeded/
     * manageable through the admin. All three now surface real data only.
     */
    public function test_package_options_meals_and_gallery_sections_render_real_data(): void
    {
        $package = $this->buildPackage();
        $package->accommodations()->create([
            'location' => 'makkah', 'hotel_name' => 'Test Makkah Hotel',
            'meal_plan' => 'Half board (breakfast & dinner)', 'sort_order' => 0,
        ]);
        $package->media()->create([
            'media_type' => 'gallery', 'image_path' => 'packages/media/test.jpg',
            'caption' => 'Test Gallery Photo', 'sort_order' => 0,
        ]);

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertSee('Package Options');
        $response->assertSee('Package A');
        $response->assertSee('Package B');
        $response->assertSee('Half board (breakfast &amp; dinner)', false);
        $response->assertSee('Test Gallery Photo');
    }

    public function test_gallery_section_is_hidden_when_no_media_exists(): void
    {
        $package = $this->buildPackage();

        $response = $this->get("/hajj/{$package->slug}");

        $response->assertOk();
        $response->assertDontSee('Gallery');
    }
}
