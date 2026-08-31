<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully(): void
    {
        Office::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Universal Brothers');
    }

    public function test_homepage_shows_featured_packages_per_category(): void
    {
        Office::factory()->create();
        $hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        Package::factory()->create([
            'package_category_id' => $hajj->id,
            'name' => 'Executive Platinum Test Package',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Featured Hajj Packages');
        $response->assertSee('Executive Platinum Test Package');
    }

    public function test_homepage_shows_active_testimonials(): void
    {
        Office::factory()->create();
        Testimonial::create([
            'name' => 'Jane Traveler',
            'quote' => 'Fantastic service throughout our Hajj journey.',
            'service_tag' => 'hajj',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Jane Traveler');
    }

    /**
     * Regression for FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md M-2: the
     * combined testimonial pool was capped to 6 rows by sort_order BEFORE
     * splitting video from text, so a video testimonial ranked outside the
     * top 6 never made it into the pool to be prioritized at all.
     */
    public function test_homepage_shows_a_video_testimonial_even_when_ranked_below_six_text_testimonials(): void
    {
        Office::factory()->create();
        foreach (range(1, 6) as $i) {
            Testimonial::create([
                'name' => "Text Pilgrim {$i}", 'quote' => 'A fine trip.', 'service_tag' => 'hajj',
                'sort_order' => $i, 'is_active' => true,
            ]);
        }
        Testimonial::create([
            'name' => 'Low-Ranked Video Pilgrim', 'quote' => 'Watch my story.', 'service_tag' => 'hajj',
            'video_url' => 'https://www.youtube.com/embed/low-ranked', 'sort_order' => 99, 'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Low-Ranked Video Pilgrim');
    }

    /**
     * Regression for a release-gate QA finding: the trust ticker, header,
     * and several prose sections hardcoded "Category A Mina Camp"/"IATA
     * Registered Operator"/"two decades"/"20 years" as literal text
     * instead of reading the same SiteSetting the rest of the page already
     * used — an admin updating `years_in_operation` or `mina_camp_location`
     * would leave these sentences silently stale.
     */
    public function test_homepage_mina_camp_and_iata_facts_are_cms_driven(): void
    {
        Office::factory()->create();
        SiteSetting::set('mina_camp_location', 'Zone 9, Category Z');
        SiteSetting::set('years_in_operation', '33+');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Zone 9, Category Z');
        $response->assertSee('33+ Years of Experience');
        $response->assertDontSee('Category A Mina Camp');
        $response->assertDontSee('two decades');
    }

    public function test_homepage_iata_registered_badge_can_be_turned_off_via_settings(): void
    {
        Office::factory()->create();
        SiteSetting::set('iata_registered', '');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('IATA Registered Operator');
    }

    public function test_homepage_hides_inactive_testimonials(): void
    {
        Office::factory()->create();
        Testimonial::create([
            'name' => 'Hidden Reviewer',
            'quote' => 'This should not appear.',
            'service_tag' => 'hajj',
            'is_active' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Hidden Reviewer');
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md M-2: the animated stat-counter
     * widget hardcoded literal numbers instead of reading the same
     * admin-editable/live-counted facts the hero and trust ticker already
     * use — so the two would silently show different numbers for the same
     * fact the moment an admin edited a Setting.
     */
    public function test_homepage_stat_counters_reflect_real_settings_and_live_package_counts(): void
    {
        Office::factory()->create();
        SiteSetting::set('years_in_operation', '33+');
        SiteSetting::set('pilgrims_served', '99,000+');
        SiteSetting::set('industry_awards_count', '4');
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj']);
        Package::factory()->count(2)->create(['package_category_id' => $hajj->id, 'status' => 'published']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-counter-target="33"', false);
        $response->assertSee('data-counter-target="99000"', false);
        $response->assertSee('data-counter-target="2"', false);
        $response->assertSee('data-counter-target="4"', false);
    }
}
