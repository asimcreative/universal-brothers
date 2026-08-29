<?php

namespace Tests\Feature\Admin;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_guest_cannot_access_package_admin(): void
    {
        $response = $this->get('/admin/packages');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_a_package_with_full_itinerary_and_pricing(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'hajj']);

        $response = $this->actingAs($this->admin)->post('/admin/packages', [
            'package_category_id' => $category->id,
            'code' => 'UB999',
            'name' => 'Test Executive Package',
            'slug' => 'test-executive-package',
            'currency' => 'USD',
            'status' => 'published',
            'itinerary' => [
                ['day_number' => 1, 'date_gregorian' => '2027-05-07', 'date_hijri_label' => '01 Zil Hajj', 'city' => 'To Medinah', 'accommodation_a' => 'Dar Al Taqwa'],
                ['day_number' => 2, 'date_gregorian' => '2027-05-08', 'date_hijri_label' => '02 Zil Hajj', 'city' => 'Medinah', 'accommodation_a' => 'Dar Al Taqwa'],
            ],
            'tiers' => [
                ['label' => 'Package A', 'prices' => ['quad' => '13850', 'triple' => '15200', 'double' => '18220']],
            ],
            'inclusions_text' => "Meet & assist at the airport\nHajj training program",
            'exclusions_text' => "Airline ticket\nQurbani actual cost",
        ]);

        $response->assertRedirect(route('admin.packages.index'));

        $package = Package::where('code', 'UB999')->firstOrFail();
        $this->assertSame(2, $package->itineraryDays()->count());
        $this->assertSame(1, $package->priceTiers()->count());
        $this->assertSame(2, $package->inclusions()->count());
        $this->assertSame(2, $package->exclusions()->count());
        $this->assertEquals(13850, $package->fresh()->starting_price);
    }

    public function test_admin_can_update_a_package_and_nested_data_is_replaced_not_duplicated(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'hajj']);
        $package = Package::factory()->create(['package_category_id' => $category->id]);
        $package->itineraryDays()->create(['day_number' => 1, 'city' => 'Old City', 'accommodation_a' => 'Old Hotel']);

        $response = $this->actingAs($this->admin)->put("/admin/packages/{$package->id}", [
            'package_category_id' => $category->id,
            'name' => 'Updated Package Name',
            'slug' => $package->slug,
            'currency' => 'USD',
            'status' => 'published',
            'itinerary' => [
                ['day_number' => 1, 'city' => 'New City', 'accommodation_a' => 'New Hotel'],
            ],
        ]);

        $response->assertRedirect(route('admin.packages.index'));

        $package->refresh();
        $this->assertSame('Updated Package Name', $package->name);
        $this->assertSame(1, $package->itineraryDays()->count());
        $this->assertSame('New City', $package->itineraryDays()->first()->city);
    }

    public function test_admin_can_delete_a_package(): void
    {
        $category = PackageCategory::factory()->create();
        $package = Package::factory()->create(['package_category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/packages/{$package->id}");

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertSoftDeleted($package);
    }

    public function test_package_creation_requires_a_unique_slug(): void
    {
        $category = PackageCategory::factory()->create();
        Package::factory()->create(['package_category_id' => $category->id, 'slug' => 'existing-slug']);

        $response = $this->actingAs($this->admin)->post('/admin/packages', [
            'package_category_id' => $category->id,
            'name' => 'Another Package',
            'slug' => 'existing-slug',
            'currency' => 'USD',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('slug');
    }
}
