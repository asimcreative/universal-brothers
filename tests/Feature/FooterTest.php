<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Page;
use App\Models\PackageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md M-7: the footer's "Services" list
     * was static hardcoded copy (including a "Visa Consultancy" line with no
     * corresponding real category), not driven by the actual CMS taxonomy —
     * renaming/adding/removing a category via the admin panel silently never
     * reflected here.
     */
    public function test_footer_services_list_reflects_real_active_package_categories(): void
    {
        PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj', 'is_active' => true]);
        PackageCategory::factory()->create(['name' => 'Retired Category', 'slug' => 'retired', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('packages.category', 'hajj'), false);
        $response->assertDontSee('Visa Consultancy');
        $response->assertDontSee(route('packages.category', 'retired'), false);
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md M-8: the footer's Privacy Policy /
     * Terms & Conditions / Refund Policy links were dead `href="#"` despite
     * the site's forms collecting real PII (CNIC, passport, blood group,
     * next-of-kin). They now point at real, editable CMS pages.
     */
    public function test_footer_legal_links_resolve_to_real_pages(): void
    {
        foreach (['privacy-policy', 'terms-and-conditions', 'refund-policy'] as $slug) {
            Page::create(['title' => ucwords(str_replace('-', ' ', $slug)), 'slug' => $slug, 'body' => 'Placeholder.', 'template' => 'default', 'is_active' => true]);
        }

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee(url('privacy-policy'), false);
        $response->assertSee(url('terms-and-conditions'), false);
        $response->assertSee(url('refund-policy'), false);

        $this->get('/privacy-policy')->assertOk();
        $this->get('/terms-and-conditions')->assertOk();
        $this->get('/refund-policy')->assertOk();
    }
}
