<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_inquiry_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $inquiry = Inquiry::create([
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'phone' => '+923001234567',
            'status' => 'new',
        ]);

        $show = $this->actingAs($admin)->get("/admin/inquiries/{$inquiry->id}");
        $show->assertOk();
        $show->assertSee('Test Lead');

        $update = $this->actingAs($admin)->put("/admin/inquiries/{$inquiry->id}", ['status' => 'contacted']);
        $update->assertRedirect();

        $this->assertSame('contacted', $inquiry->fresh()->status);
    }

    public function test_guest_cannot_view_inquiries(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Test Lead', 'email' => 'lead@example.com', 'phone' => '123', 'status' => 'new',
        ]);

        $response = $this->get("/admin/inquiries/{$inquiry->id}");

        $response->assertRedirect('/admin/login');
    }
}
