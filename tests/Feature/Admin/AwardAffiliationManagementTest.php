<?php

namespace Tests\Feature\Admin;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the new Award and Affiliation admin CRUD controllers, built for
 * the frontend redesign's Awards & Recognition / Affiliations pages (see
 * FRONTEND_IMPLEMENTATION_PLAN.md). Follows the same "leaving sort_order
 * blank" regression pattern as ContentManagementTest, since these
 * controllers were modeled on the same Faq/Office pattern.
 */
class AwardAffiliationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        Storage::fake('public');
    }

    public function test_admin_can_create_an_award_leaving_sort_order_blank(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/awards', [
            'name' => 'Test Excellence Award',
            'awarding_organization' => 'Test Awarding Body',
            'year' => '2020',
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.awards.index'));
        $this->assertDatabaseHas('awards', ['name' => 'Test Excellence Award', 'sort_order' => 0]);
    }

    public function test_admin_can_upload_an_award_image_and_it_persists_across_an_unrelated_edit(): void
    {
        $file = UploadedFile::fake()->image('award.jpg');

        $this->actingAs($this->admin)->post('/admin/awards', [
            'name' => 'Photographed Award',
            'image' => $file,
            'sort_order' => 0,
            'is_active' => '1',
        ]);

        $award = Award::where('name', 'Photographed Award')->firstOrFail();
        Storage::disk('public')->assertExists($award->image);

        // Editing an unrelated field without re-uploading a file must keep
        // the existing image — a file input can never be pre-filled, so
        // omitting the field must not be read as "delete the photo."
        $this->actingAs($this->admin)->put("/admin/awards/{$award->id}", [
            'name' => 'Photographed Award (Updated)',
            'sort_order' => 0,
            'is_active' => '1',
        ]);

        $award->refresh();
        $this->assertNotNull($award->image);
        Storage::disk('public')->assertExists($award->image);
    }

    public function test_admin_can_update_and_delete_an_award(): void
    {
        $award = Award::create(['name' => 'Old Name', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($this->admin)->put("/admin/awards/{$award->id}", [
            'name' => 'New Name',
            'sort_order' => 0,
            'is_active' => '1',
        ]);
        $this->assertDatabaseHas('awards', ['id' => $award->id, 'name' => 'New Name']);

        $this->actingAs($this->admin)->delete("/admin/awards/{$award->id}");
        $this->assertDatabaseMissing('awards', ['id' => $award->id]);
    }

    public function test_admin_can_create_an_affiliation_leaving_sort_order_blank(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/affiliations', [
            'organization_name' => 'Test Organization',
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.affiliations.index'));
        $this->assertDatabaseHas('affiliations', ['organization_name' => 'Test Organization', 'sort_order' => 0]);
    }

    public function test_admin_can_update_and_delete_an_affiliation(): void
    {
        $affiliation = Affiliation::create(['organization_name' => 'Old Org', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($this->admin)->put("/admin/affiliations/{$affiliation->id}", [
            'organization_name' => 'New Org',
            'sort_order' => 0,
            'is_active' => '1',
        ]);
        $this->assertDatabaseHas('affiliations', ['id' => $affiliation->id, 'organization_name' => 'New Org']);

        $this->actingAs($this->admin)->delete("/admin/affiliations/{$affiliation->id}");
        $this->assertDatabaseMissing('affiliations', ['id' => $affiliation->id]);
    }

    public function test_guest_cannot_access_award_admin(): void
    {
        $this->get('/admin/awards')->assertRedirect(route('admin.login'));
    }

    public function test_guest_cannot_access_affiliation_admin(): void
    {
        $this->get('/admin/affiliations')->assertRedirect(route('admin.login'));
    }
}
