<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_visitor_can_submit_a_package_inquiry(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'hajj']);
        $package = Package::factory()->create(['package_category_id' => $category->id]);

        $response = $this->post('/inquiries', [
            'name' => 'Ahmed Khan',
            'email' => 'ahmed@example.com',
            'phone' => '+923001234567',
            'package_id' => $package->id,
            'package_category_id' => $category->id,
            'message' => 'Please send more details about this package.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Ahmed Khan',
            'email' => 'ahmed@example.com',
            'package_id' => $package->id,
            'status' => 'new',
        ]);
    }

    public function test_inquiry_requires_name_email_and_phone(): void
    {
        $response = $this->post('/inquiries', []);

        $response->assertSessionHasErrors(['name', 'email', 'phone']);
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_hajj_specific_fields_are_captured_as_structured_details(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'hajj']);

        $this->post('/inquiries', [
            'name' => 'Ahmed Khan',
            'email' => 'ahmed@example.com',
            'phone' => '+923001234567',
            'package_category_id' => $category->id,
            'room_type' => 'double',
            'cnic' => '42101-1234567-1',
            'blood_group' => 'O+',
        ]);

        $inquiry = Inquiry::first();

        $this->assertSame('double', $inquiry->hajj_details['room_type']);
        $this->assertSame('42101-1234567-1', $inquiry->hajj_details['cnic']);
        $this->assertSame('O+', $inquiry->hajj_details['blood_group']);
    }
}
