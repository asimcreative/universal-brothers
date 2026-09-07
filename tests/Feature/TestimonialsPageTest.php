<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage gap closed 2026-09-05: `TestimonialPageController` had no PHPUnit
 * coverage. Its one real piece of logic — splitting testimonials into video
 * vs text by whether `video_url` is filled — was never asserted anywhere,
 * even though the approved website flow specifically prioritises video
 * testimonials and the public page renders the two groups differently.
 */
class TestimonialsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_testimonials_split_into_video_and_text_groups_by_video_url(): void
    {
        Testimonial::create(['name' => 'Video Pilgrim', 'quote' => 'A filmed reflection.', 'service_tag' => 'hajj', 'video_url' => 'https://example.com/embed/xyz', 'sort_order' => 1, 'is_active' => true]);
        Testimonial::create(['name' => 'Writing Pilgrim', 'quote' => 'A written reflection.', 'service_tag' => 'umrah', 'sort_order' => 2, 'is_active' => true]);

        $response = $this->get('/testimonials');

        $response->assertOk();

        $video = $response->viewData('videoTestimonials');
        $text = $response->viewData('textTestimonials');

        $this->assertCount(1, $video);
        $this->assertCount(1, $text);
        $this->assertSame('Video Pilgrim', $video->first()->name);
        $this->assertSame('Writing Pilgrim', $text->first()->name);
    }

    public function test_an_empty_string_video_url_counts_as_a_text_testimonial(): void
    {
        // `blank()`/`filled()` treat '' as empty — an admin saving the form
        // with the video field left blank must not create a video card with
        // no video in it.
        Testimonial::create(['name' => 'Blank Video Field', 'quote' => 'Saved with an empty video field.', 'service_tag' => 'hajj', 'video_url' => '', 'sort_order' => 1, 'is_active' => true]);

        $response = $this->get('/testimonials');

        $response->assertOk();
        $this->assertCount(0, $response->viewData('videoTestimonials'));
        $this->assertCount(1, $response->viewData('textTestimonials'));
    }

    public function test_inactive_testimonials_are_never_shown(): void
    {
        Testimonial::create(['name' => 'Hidden Pilgrim', 'quote' => 'Should not appear.', 'service_tag' => 'hajj', 'sort_order' => 1, 'is_active' => false]);
        Testimonial::create(['name' => 'Visible Pilgrim', 'quote' => 'Should appear.', 'service_tag' => 'hajj', 'sort_order' => 2, 'is_active' => true]);

        $response = $this->get('/testimonials');

        $response->assertOk();
        $response->assertSee('Visible Pilgrim');
        $response->assertDontSee('Hidden Pilgrim');
    }

    public function test_testimonials_page_renders_its_honest_empty_state(): void
    {
        $response = $this->get('/testimonials');

        $response->assertOk();
        $response->assertSee('No testimonials have been published yet.');
    }
}
