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
