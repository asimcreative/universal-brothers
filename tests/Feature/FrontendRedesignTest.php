<?php

namespace Tests\Feature;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageAccommodation;
use App\Models\PackageAziziya;
use App\Models\PackageCategory;
use App\Models\PackageRoomOption;
use App\Models\PackageVariant;
use App\Models\Testimonial;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the new public pages and Hajj listing filters built for the
 * "Complete Frontend Redesign" directive: Awards, Affiliations,
 * Testimonials (video-priority), FAQs (grouped by category), Media
 * (News/Gallery/Videos tabs), Hajj/Umrah Services landing pages, the
 * restructured About Us page, and the real-data-driven Hajj filter bar.
 */
class FrontendRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_awards_page_shows_only_real_active_awards(): void
    {
        Award::create(['name' => 'Visible Award', 'sort_order' => 0, 'is_active' => true]);
        Award::create(['name' => 'Hidden Award', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/awards');

        $response->assertOk();
        $response->assertSee('Visible Award');
        $response->assertDontSee('Hidden Award');
    }

    public function test_affiliations_page_shows_only_real_active_affiliations(): void
    {
        Affiliation::create(['organization_name' => 'Visible Org', 'sort_order' => 0, 'is_active' => true]);
        Affiliation::create(['organization_name' => 'Hidden Org', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->get('/affiliations');

        $response->assertOk();
        $response->assertSee('Visible Org');
        $response->assertDontSee('Hidden Org');
    }

    public function test_testimonials_page_prioritizes_video_testimonials_over_text(): void
    {
        Testimonial::create([
            'name' => 'Video Pilgrim', 'quote' => 'Great trip.', 'service_tag' => 'hajj',
            'video_url' => 'https://www.youtube.com/embed/example', 'is_active' => true,
        ]);
        Testimonial::create([
            'name' => 'Text Pilgrim', 'quote' => 'Also great.', 'service_tag' => 'umrah',
            'is_active' => true,
        ]);

        $response = $this->get('/testimonials');

        $response->assertOk();
        $response->assertSeeInOrder(['Pilgrim Stories on Video', 'Video Pilgrim', 'More From Our Pilgrims', 'Text Pilgrim']);
    }

    public function test_faqs_page_groups_questions_by_real_category(): void
    {
        Faq::create(['category' => 'hajj', 'question' => 'Hajj question?', 'answer' => 'Hajj answer.', 'is_active' => true]);
        Faq::create(['category' => 'umrah', 'question' => 'Umrah question?', 'answer' => 'Umrah answer.', 'is_active' => true]);
        Faq::create(['category' => 'general', 'question' => 'Hidden question?', 'answer' => 'Hidden.', 'is_active' => false]);

        $response = $this->get('/faqs');

        $response->assertOk();
        // assertSeeInOrder actually proves grouping — each question appears
        // directly under its own category heading, not just anywhere on the page.
        $response->assertSeeInOrder(['Hajj', 'Hajj question?', 'Umrah', 'Umrah question?']);
        $response->assertDontSee('Hidden question?');
    }

    /**
     * Regression for a release-gate SEO finding: the FAQs page had no
     * structured data at all, despite being an ideal, low-risk FAQPage
     * rich-result candidate — every other schema.org block on the site is
     * the one site-wide TravelAgency block in the layout.
     */
    public function test_faqs_page_emits_valid_faqpage_structured_data(): void
    {
        Faq::create(['category' => 'hajj', 'question' => 'Real question?', 'answer' => 'Real answer.', 'is_active' => true]);

        $response = $this->get('/faqs');
        $response->assertOk();

        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $faqSchema = collect($matches[1])->map(fn ($json) => json_decode($json, true))->firstWhere('@type', 'FAQPage');

        $this->assertNotNull($faqSchema, 'no valid FAQPage JSON-LD block found');
        $this->assertSame('Real question?', $faqSchema['mainEntity'][0]['name']);
        $this->assertSame('Real answer.', $faqSchema['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_media_page_shows_real_news_gallery_and_video_items(): void
    {
        NewsArticle::create(['title' => 'Real News Item', 'slug' => 'real-news-item', 'body' => 'Body.', 'is_active' => true, 'published_at' => now()]);
        \App\Models\MediaItem::create([
            'media_type' => 'image', 'gallery_type' => 'gallery', 'title' => 'Real Gallery Photo',
            'file_path' => 'media/real-gallery-photo.jpg', 'sort_order' => 0, 'is_active' => true,
        ]);
        \App\Models\MediaItem::create([
            'media_type' => 'video', 'gallery_type' => 'gallery', 'title' => 'Real Video Item',
            'video_url' => 'https://www.youtube.com/embed/real-media-video', 'sort_order' => 0, 'is_active' => true,
        ]);

        $response = $this->get('/media');

        $response->assertOk();
        $response->assertSee('Real News Item');
        $response->assertSee('real-gallery-photo.jpg', false);
        $response->assertSee('Real Video Item');
        $response->assertSee('real-media-video', false);
    }

    public function test_hajj_services_page_shows_real_hajj_packages_and_process_timeline(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Real Hajj Package', 'status' => 'published']);

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $response->assertSee('Real Hajj Package');
        $response->assertSee('Registration');
        $response->assertSee('Return Home');
    }

    public function test_hajj_services_page_next_flight_date_is_cms_driven_not_hardcoded(): void
    {
        \App\Models\SiteSetting::set('hajj_next_flight_date', '15 May 2027');

        $response = $this->get('/hajj-services');

        $response->assertOk();
        $response->assertSee('15 May 2027');
    }

    public function test_hajj_services_page_shows_honest_placeholder_when_no_flight_date_set(): void
    {
        $response = $this->get('/hajj-services');

        $response->assertOk();
        $response->assertSee('To be announced');
    }

    public function test_umrah_services_page_loads(): void
    {
        $response = $this->get('/umrah-services');

        $response->assertOk();
        $response->assertSee('Your Umrah. Your Time. Your Journey.');
    }

    public function test_about_us_page_shows_real_awards_and_affiliations_sections(): void
    {
        \App\Models\Page::updateOrCreate(
            ['slug' => 'about-us'],
            ['title' => 'About Us', 'body' => 'Our story.', 'template' => 'about', 'is_active' => true]
        );
        Award::create(['name' => 'Structured Award', 'sort_order' => 0, 'is_active' => true]);
        Affiliation::create(['organization_name' => 'Structured Affiliation', 'sort_order' => 0, 'is_active' => true]);

        $response = $this->get('/about-us');

        $response->assertOk();
        $response->assertSee('Structured Award');
        $response->assertSee('Structured Affiliation');
    }

    public function test_hajj_listing_filter_by_days_returns_only_matching_duration(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Thirteen Day Package', 'duration_days' => 13, 'status' => 'published']);
        Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Twenty Day Package', 'duration_days' => 20, 'status' => 'published']);

        $response = $this->get('/hajj?days=13');

        $response->assertOk();
        $response->assertSee('Thirteen Day Package');
        $response->assertDontSee('Twenty Day Package');
    }

    public function test_hajj_listing_filter_by_variant_code_returns_only_packages_with_that_variant(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $withA = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Has Variant A', 'status' => 'published']);
        $withoutA = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'No Variant A', 'status' => 'published']);
        PackageVariant::create(['package_id' => $withA->id, 'code' => 'A', 'sort_order' => 0]);

        $response = $this->get('/hajj?variant=A');

        $response->assertOk();
        $response->assertSee('Has Variant A');
        $response->assertDontSee('No Variant A');
    }

    public function test_hajj_listing_filter_by_aziziya_status_returns_only_matching_packages(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $included = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Aziziya Included Package', 'status' => 'published']);
        $notIncluded = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'No Aziziya Package', 'status' => 'published']);
        PackageAziziya::create(['package_id' => $included->id, 'status' => 'included']);
        PackageAziziya::create(['package_id' => $notIncluded->id, 'status' => 'not_applicable']);

        $response = $this->get('/hajj?aziziya=included');

        $response->assertOk();
        $response->assertSee('Aziziya Included Package');
        $response->assertDontSee('No Aziziya Package');
    }

    public function test_hajj_listing_filter_by_five_star_returns_only_packages_with_a_five_star_accommodation(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $fiveStar = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Five Star Package', 'status' => 'published']);
        $threeStar = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Three Star Package', 'status' => 'published']);
        PackageAccommodation::create(['package_id' => $fiveStar->id, 'location' => 'makkah', 'hotel_name' => 'Test Hotel', 'star_rating' => 5, 'sort_order' => 0]);
        PackageAccommodation::create(['package_id' => $threeStar->id, 'location' => 'makkah', 'hotel_name' => 'Test Hotel', 'star_rating' => 3, 'sort_order' => 0]);

        $response = $this->get('/hajj?star5=1');

        $response->assertOk();
        $response->assertSee('Five Star Package');
        $response->assertDontSee('Three Star Package');
    }

    public function test_hajj_listing_filter_by_sharing_type_returns_only_matching_packages(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $quad = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Quad Sharing Package', 'status' => 'published']);
        $double = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Double Sharing Package', 'status' => 'published']);
        // Priced, because the listing now leaves out a package it cannot
        // price in the currency the visitor is reading — and these rooms
        // carried no price in any currency at all.
        PackageRoomOption::create(['package_id' => $quad->id, 'sharing_type' => 'quad', 'display_label' => 'Quad Sharing', 'price_pkr' => 1_200_000, 'sort_order' => 0]);
        PackageRoomOption::create(['package_id' => $double->id, 'sharing_type' => 'double', 'display_label' => 'Double Sharing', 'price_pkr' => 2_400_000, 'sort_order' => 0]);

        $response = $this->get('/hajj?sharing=quad');

        $response->assertOk();
        $response->assertSee('Quad Sharing Package');
        $response->assertDontSee('Double Sharing Package');
    }

    public function test_hajj_listing_filter_by_price_range_returns_only_matching_packages(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $cheap = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Cheap Package', 'status' => 'published']);
        $expensive = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Expensive Package', 'status' => 'published']);
        PackageRoomOption::create(['package_id' => $cheap->id, 'sharing_type' => 'quad', 'display_label' => 'Quad', 'price_pkr' => 5000, 'sort_order' => 0]);
        PackageRoomOption::create(['package_id' => $expensive->id, 'sharing_type' => 'quad', 'display_label' => 'Quad', 'price_pkr' => 50000, 'sort_order' => 0]);

        $response = $this->get('/hajj?price_min=1000&price_max=10000');

        $response->assertOk();
        $response->assertSee('Cheap Package');
        $response->assertDontSee('Expensive Package');
    }

    /**
     * Regression for FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md M-1: price_min
     * and price_max were checked via two independent whereHas() subqueries,
     * so a package with one room below the range and a different room above
     * it satisfied both conditions even though no single room option was
     * actually priced inside the requested range.
     */
    public function test_hajj_listing_price_filter_requires_the_same_room_to_satisfy_both_bounds(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $noRoomInRange = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'No Room In Range Package', 'status' => 'published']);
        $roomInRange = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Room In Range Package', 'status' => 'published']);

        PackageRoomOption::create(['package_id' => $noRoomInRange->id, 'sharing_type' => 'quad', 'display_label' => 'Quad', 'price_pkr' => 800, 'sort_order' => 0]);
        PackageRoomOption::create(['package_id' => $noRoomInRange->id, 'sharing_type' => 'double', 'display_label' => 'Double', 'price_pkr' => 3000, 'sort_order' => 1]);
        PackageRoomOption::create(['package_id' => $roomInRange->id, 'sharing_type' => 'quad', 'display_label' => 'Quad', 'price_pkr' => 1500, 'sort_order' => 0]);

        $response = $this->get('/hajj?price_min=1000&price_max=2000');

        $response->assertOk();
        $response->assertSee('Room In Range Package');
        $response->assertDontSee('No Room In Range Package');
    }

    /**
     * The filter compared the figure typed into it against `price_usd`
     * whichever currency was on screen. A range entered while reading in
     * rupees was therefore measured against dollars and matched nothing, so
     * for two readers in three the filter simply looked broken.
     */
    public function test_the_price_filter_measures_against_the_currency_the_visitor_is_reading(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $package = Package::factory()->create(['package_category_id' => $hajj->id, 'name' => 'Both Currencies Package', 'status' => 'published']);
        PackageRoomOption::create([
            'package_id' => $package->id, 'sharing_type' => 'quad', 'display_label' => 'Quad',
            'price_pkr' => 1_500_000, 'price_usd' => 5_000, 'sort_order' => 0,
        ]);

        // A rupee range, read in rupees: found.
        $this->withSession([Currency::SESSION_KEY => 'PKR'])
            ->get('/hajj?price_min=1000000&price_max=2000000')
            ->assertOk()->assertSee('Both Currencies Package');

        // The same figures read in dollars mean something else entirely, and
        // must not match. This is the assertion that failed before the fix:
        // the room's $5,000 was compared against a 1,000,000-2,000,000 range
        // in every currency alike.
        $this->withSession([Currency::SESSION_KEY => 'USD'])
            ->get('/hajj?price_min=1000000&price_max=2000000')
            ->assertOk()->assertDontSee('Both Currencies Package');

        // And a dollar range does find it, read in dollars.
        $this->withSession([Currency::SESSION_KEY => 'USD'])
            ->get('/hajj?price_min=4000&price_max=6000')
            ->assertOk()->assertSee('Both Currencies Package');
    }

    public function test_hajj_listing_filter_options_are_derived_from_real_data_not_hardcoded(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'is_active' => true]);
        $package = Package::factory()->create(['package_category_id' => $hajj->id, 'status' => 'published']);
        PackageRoomOption::create(['package_id' => $package->id, 'sharing_type' => 'family_suite', 'display_label' => 'Family Suite', 'sort_order' => 0]);

        $response = $this->get('/hajj');

        $response->assertOk();
        // A sharing type that isn't Quad/Triple/Double must still appear —
        // proves the filter list is queried, not a fixed enum of 3 options.
        $response->assertSee('Family Suite');
    }
}
