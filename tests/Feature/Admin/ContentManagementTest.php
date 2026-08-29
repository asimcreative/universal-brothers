<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\Office;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the FAQ, Office, Slider, and Testimonial admin CRUD controllers —
 * previously untested at the PHPUnit level. Each "leaving sort_order blank"
 * test is a regression guard: a real browser submits a blank number input
 * as an empty string, which Laravel's ConvertEmptyStringsToNull middleware
 * turns into null, and the sort_order column is NOT NULL with no default
 * applied on an explicit null insert — this broke all four controllers
 * (Office's admin form has no sort_order field at all, so it failed on
 * every single real submission) until fixed alongside this test.
 */
class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_admin_can_create_a_faq_leaving_sort_order_blank(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/faqs', [
            'category' => 'hajj',
            'question' => 'Is Qurbani included?',
            'answer' => 'Assistance is included; the cost itself is not.',
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseHas('faqs', ['question' => 'Is Qurbani included?', 'sort_order' => 0]);
    }

    public function test_admin_can_create_an_office_leaving_sort_order_blank(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/offices', [
            'label' => 'Test Office',
            'address' => '123 Test Street',
            'sort_order' => '',
            'is_domestic' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.offices.index'));
        $this->assertDatabaseHas('offices', ['label' => 'Test Office', 'sort_order' => 0]);
    }

    public function test_admin_can_create_a_testimonial_leaving_sort_order_blank(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/testimonials', [
            'name' => 'Test Reviewer',
            'quote' => 'Great service.',
            'service_tag' => 'hajj',
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.testimonials.index'));
        $this->assertDatabaseHas('testimonials', ['name' => 'Test Reviewer', 'sort_order' => 0]);
    }

    public function test_admin_can_create_a_slider_leaving_sort_order_blank(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post('/admin/sliders', [
            'title' => 'Test Slide',
            'image' => UploadedFile::fake()->image('slide.jpg'),
            'page_context' => 'home',
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.sliders.index'));
        $this->assertDatabaseHas('sliders', ['title' => 'Test Slide', 'sort_order' => 0]);
    }

    public function test_guest_cannot_manage_faqs_offices_sliders_or_testimonials(): void
    {
        $this->get('/admin/faqs')->assertRedirect('/admin/login');
        $this->get('/admin/offices')->assertRedirect('/admin/login');
        $this->get('/admin/sliders')->assertRedirect('/admin/login');
        $this->get('/admin/testimonials')->assertRedirect('/admin/login');
    }

    public function test_admin_can_update_and_delete_a_faq(): void
    {
        $faq = Faq::create(['category' => 'general', 'question' => 'Old?', 'answer' => 'Old.', 'sort_order' => 0, 'is_active' => true]);

        $update = $this->actingAs($this->admin)->put("/admin/faqs/{$faq->id}", [
            'category' => 'general', 'question' => 'New?', 'answer' => 'New.', 'sort_order' => '', 'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'question' => 'New?']);

        $delete = $this->actingAs($this->admin)->delete("/admin/faqs/{$faq->id}");
        $delete->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_admin_can_update_and_delete_an_office(): void
    {
        $office = Office::factory()->create();

        $update = $this->actingAs($this->admin)->put("/admin/offices/{$office->id}", [
            'label' => 'Updated Office', 'address' => $office->address, 'sort_order' => '', 'is_domestic' => '1', 'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.offices.index'));
        $this->assertDatabaseHas('offices', ['id' => $office->id, 'label' => 'Updated Office']);

        $delete = $this->actingAs($this->admin)->delete("/admin/offices/{$office->id}");
        $delete->assertRedirect(route('admin.offices.index'));
        $this->assertDatabaseMissing('offices', ['id' => $office->id]);
    }

    public function test_admin_can_update_and_delete_a_testimonial(): void
    {
        $testimonial = Testimonial::create(['name' => 'A', 'quote' => 'A', 'service_tag' => 'general', 'sort_order' => 0, 'is_active' => true]);

        $update = $this->actingAs($this->admin)->put("/admin/testimonials/{$testimonial->id}", [
            'name' => 'B', 'quote' => 'B quote', 'service_tag' => 'general', 'sort_order' => '', 'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.testimonials.index'));
        $this->assertDatabaseHas('testimonials', ['id' => $testimonial->id, 'name' => 'B']);

        $delete = $this->actingAs($this->admin)->delete("/admin/testimonials/{$testimonial->id}");
        $delete->assertRedirect(route('admin.testimonials.index'));
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_admin_can_delete_a_slider(): void
    {
        Storage::fake('public');
        $slider = Slider::create([
            'title' => 'Delete Me', 'image' => 'sliders/x.jpg', 'page_context' => 'home', 'sort_order' => 0, 'is_active' => true,
        ]);

        $delete = $this->actingAs($this->admin)->delete("/admin/sliders/{$slider->id}");
        $delete->assertRedirect(route('admin.sliders.index'));
        $this->assertDatabaseMissing('sliders', ['id' => $slider->id]);
    }
}
