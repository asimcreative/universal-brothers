<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage gap closed 2026-09-05: `HajjServicesController` and
 * `UmrahServicesController` had no PHPUnit coverage at all — only Playwright
 * rendering/responsive checks, which prove a page renders but never prove it
 * passes the *right* data to the view (published-only filtering, per-category
 * FAQ separation, the 6-package cap, or the CMS-driven settings).
 */
class ServicePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_hajj_services_page_shows_only_published_hajj_packages(): void
    {
        $hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => true]);

        Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Published Hajj Package']);
        Package::factory()->draft()->create(['package_category_id' => $hajj->id, 'name' => 'Draft Hajj Package']);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $response->assertSee('Published Hajj Package');
        $response->assertDontSee('Draft Hajj Package');
    }

    public function test_hajj_services_page_caps_the_package_list_at_six(): void
    {
        $hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => true]);
        Package::factory()->count(9)->create(['package_category_id' => $hajj->id]);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $this->assertCount(6, $response->viewData('packages'));
    }

    public function test_hajj_services_page_shows_only_hajj_category_faqs(): void
    {
        PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => true]);

        Faq::create(['category' => 'hajj', 'question' => 'A real Hajj question?', 'answer' => 'Yes.', 'sort_order' => 1, 'is_active' => true]);
        Faq::create(['category' => 'umrah', 'question' => 'An Umrah question?', 'answer' => 'No.', 'sort_order' => 1, 'is_active' => true]);
        Faq::create(['category' => 'hajj', 'question' => 'An inactive Hajj question?', 'answer' => 'Hidden.', 'sort_order' => 2, 'is_active' => false]);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $response->assertSee('A real Hajj question?');
        $response->assertDontSee('An Umrah question?');
        $response->assertDontSee('An inactive Hajj question?');
    }

    public function test_hajj_services_page_surfaces_cms_driven_settings(): void
    {
        PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => true]);
        // RefreshDatabase starts empty — no seeders run — so the setting row
        // has to be created here rather than assumed to exist.
        SiteSetting::create(['key' => 'mina_camp_location', 'value' => 'Zone 9, Category Z', 'group' => 'general']);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $this->assertSame('Zone 9, Category Z', $response->viewData('minaCampLocation'));
    }

    public function test_hajj_services_page_survives_a_missing_or_inactive_category(): void
    {
        // Deactivated category — the controller must degrade to an empty
        // collection, not throw on a null relation.
        PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => false]);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('packages'));
    }

    public function test_umrah_services_page_shows_only_published_umrah_packages_and_umrah_faqs(): void
    {
        $umrah = PackageCategory::factory()->create(['name' => 'Umrah', 'slug' => 'umrah', 'is_active' => true]);

        Package::factory()->create(['package_category_id' => $umrah->id, 'name' => 'Published Umrah Package']);
        Package::factory()->draft()->create(['package_category_id' => $umrah->id, 'name' => 'Draft Umrah Package']);

        Faq::create(['category' => 'umrah', 'question' => 'A real Umrah question?', 'answer' => 'Yes.', 'sort_order' => 1, 'is_active' => true]);
        Faq::create(['category' => 'hajj', 'question' => 'A Hajj-only question?', 'answer' => 'No.', 'sort_order' => 1, 'is_active' => true]);

        $response = $this->get('/umrah-services');

        $response->assertOk();
        $response->assertSee('Published Umrah Package');
        $response->assertDontSee('Draft Umrah Package');
        $response->assertSee('A real Umrah question?');
        $response->assertDontSee('A Hajj-only question?');
    }

    public function test_umrah_services_page_survives_a_missing_category(): void
    {
        $response = $this->get('/umrah-services');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('packages'));
    }
}
