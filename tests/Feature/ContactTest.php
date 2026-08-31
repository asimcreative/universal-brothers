<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_shows_real_office_details(): void
    {
        Office::factory()->create([
            'label' => 'Head Office — Karachi',
            'address' => 'A-9, 1st Floor, Hassan Homes, Karachi',
            'phone_primary' => '(92-21) 111-102-786',
        ]);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('Head Office — Karachi');
        $response->assertSee('(92-21) 111-102-786');
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md M-1: the admin Office form captured
     * a Google Maps embed but no public view ever rendered it.
     */
    public function test_contact_page_renders_an_offices_google_maps_embed_when_present(): void
    {
        Office::factory()->create([
            'label' => 'Head Office — Karachi',
            'google_maps_embed' => '<iframe src="https://www.google.com/maps/embed?test" title="map"></iframe>',
        ]);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('https://www.google.com/maps/embed?test', false);
    }

    /**
     * Regression (frontend visual redesign, 2026-08-30): the rendering path
     * for `google_maps_embed` was already covered above, but the real
     * seeded head office had never actually had this field populated — the
     * Contact page directive asked for a map, and OfficeSeeder now embeds
     * one for the office's real, already-published address (a plain
     * keyless Google Maps query, not the paid JS SDK, so no API key or
     * invented location is involved).
     */
    public function test_the_real_seeded_head_office_has_a_maps_embed(): void
    {
        $this->seed(\Database\Seeders\OfficeSeeder::class);

        $office = Office::where('label', 'Head Office — Karachi')->firstOrFail();

        $this->assertNotEmpty($office->google_maps_embed);
        $this->assertStringContainsString('google.com/maps', $office->google_maps_embed);
        $this->assertStringContainsString(rawurlencode($office->address), $office->google_maps_embed);
    }

    public function test_visitor_can_submit_contact_form(): void
    {
        Office::factory()->create();

        $response = $this->post('/contact', [
            'name' => 'Sara Ahmed',
            'email' => 'sara@example.com',
            'phone' => '+923001112222',
            'message' => 'Interested in a Tourism package to Skardu.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inquiries', [
            'name' => 'Sara Ahmed',
            'email' => 'sara@example.com',
        ]);
    }

    public function test_contact_form_requires_all_fields(): void
    {
        Office::factory()->create();

        $response = $this->post('/contact', []);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'message']);
    }
}
