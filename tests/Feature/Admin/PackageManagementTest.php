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

    /**
     * CRITICAL regression, admin panel redesign (2026-08-31): the generic
     * (Tourism/Umrah) package controller had no category scoping at all —
     * Route::resource's implicit `{package}` binding resolves ANY package
     * by ID, Hajj included. `edit`/`update` built a form and sync around
     * `priceTiers` (Hajj packages price via the separate `roomOptions`
     * relation instead) whose `update()` unconditionally deletes and
     * replaces `itineraryDays`/`inclusions`/`exclusions` — real Hajj
     * package data — with whatever that mismatched form happened to
     * submit. Found via a visual review of the redesigned admin listing
     * showing real Hajj packages (UB001, UB003, ...) mixed into a page
     * newly labeled "Umrah & Tourism Packages", not by reading either
     * controller in isolation. Never caught by any prior audit because
     * each admin surface was only ever tested reachable through its own
     * intended route.
     */
    public function test_generic_controller_never_reaches_a_hajj_package(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj']);
        $package = Package::factory()->create(['package_category_id' => $hajj->id]);
        $package->itineraryDays()->create(['day_number' => 1, 'city' => 'Real Hajj City', 'accommodation_a' => 'Real Hajj Hotel']);
        $package->inclusions()->create(['type' => 'inclusion', 'description' => 'Real Hajj inclusion', 'sort_order' => 0]);

        // The generic listing must never show a Hajj package.
        $indexResponse = $this->actingAs($this->admin)->get('/admin/packages');
        $indexResponse->assertDontSee($package->name);

        // Editing it through the generic route must redirect to the real
        // Hajj admin surface, not render a form built for the wrong schema.
        $editResponse = $this->actingAs($this->admin)->get("/admin/packages/{$package->id}/edit");
        $editResponse->assertRedirect(route('admin.hajj-packages.edit', $package));

        // A crafted update POST against the generic route must redirect
        // (not corrupt data) — the real itinerary/inclusions must survive
        // completely untouched.
        $updateResponse = $this->actingAs($this->admin)->put("/admin/packages/{$package->id}", [
            'package_category_id' => $hajj->id,
            'name' => 'Corrupted By Generic Form',
            'slug' => $package->slug,
            'currency' => 'USD',
            'status' => 'published',
            'itinerary' => [],
        ]);
        $updateResponse->assertRedirect(route('admin.hajj-packages.edit', $package));

        $package->refresh();
        $this->assertNotSame('Corrupted By Generic Form', $package->name);
        $this->assertSame(1, $package->itineraryDays()->count());
        $this->assertSame('Real Hajj City', $package->itineraryDays()->first()->city);
        $this->assertSame(1, $package->inclusions()->count());
    }

    public function test_guest_cannot_access_package_admin(): void
    {
        $response = $this->get('/admin/packages');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_a_package_with_full_itinerary_and_pricing(): void
    {
        // Regression: this generic (Tourism/Umrah) package controller must
        // never be reachable for a real Hajj-category package — 'hajj' is
        // deliberately avoided here as a fixture slug so these tests can't
        // silently start exercising that guard instead of what they mean to
        // test. See PackageController::nonHajjCategories() and the new
        // test_generic_controller_never_reaches_a_hajj_package below.
        $category = PackageCategory::factory()->create(['slug' => 'tourism-test']);

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

    /**
     * Regression for a release-gate CRITICAL/HIGH-adjacent finding
     * (FINAL_CODE_REVIEW_FRONTEND_REDESIGN-follow-up review): `tiers.*.prices`
     * validation only describes the 4 real room-type keys without rejecting
     * an unlisted one, and the controller read raw `$request->input('tiers')`
     * rather than `$request->safe()` — so a submission containing an
     * unrecognized key under `prices` (unreachable through the shipped
     * admin form, but not blocked server-side either) would have reached
     * `roomPrices()->create()` and thrown an unhandled QueryException against
     * `package_room_prices.room_type`'s DB-level enum, mid-sync, after the
     * package's old itinerary/pricing had already been deleted. The
     * controller now filters to the known room-type keys before creating
     * any row, and the whole sync runs inside a transaction as a second
     * layer of protection.
     */
    public function test_an_unknown_room_type_key_is_silently_ignored_not_a_crash(): void
    {
        // Regression: this generic (Tourism/Umrah) package controller must
        // never be reachable for a real Hajj-category package — 'hajj' is
        // deliberately avoided here as a fixture slug so these tests can't
        // silently start exercising that guard instead of what they mean to
        // test. See PackageController::nonHajjCategories() and the new
        // test_generic_controller_never_reaches_a_hajj_package below.
        $category = PackageCategory::factory()->create(['slug' => 'tourism-test']);

        $response = $this->actingAs($this->admin)->post('/admin/packages', [
            'package_category_id' => $category->id,
            'code' => 'UB998',
            'name' => 'Test Unknown Room Type Package',
            'slug' => 'test-unknown-room-type-package',
            'currency' => 'USD',
            'status' => 'published',
            'tiers' => [
                ['label' => 'Package A', 'prices' => ['quad' => '13850', 'king' => '99999']],
            ],
        ]);

        $response->assertRedirect(route('admin.packages.index'));

        $package = Package::where('code', 'UB998')->firstOrFail();
        $this->assertSame(1, $package->priceTiers()->first()->roomPrices()->count());
        $this->assertDatabaseHas('package_room_prices', ['room_type' => 'quad', 'price' => 13850]);
        $this->assertDatabaseMissing('package_room_prices', ['room_type' => 'king']);
    }

    public function test_admin_can_update_a_package_and_nested_data_is_replaced_not_duplicated(): void
    {
        // Regression: this generic (Tourism/Umrah) package controller must
        // never be reachable for a real Hajj-category package — 'hajj' is
        // deliberately avoided here as a fixture slug so these tests can't
        // silently start exercising that guard instead of what they mean to
        // test. See PackageController::nonHajjCategories() and the new
        // test_generic_controller_never_reaches_a_hajj_package below.
        $category = PackageCategory::factory()->create(['slug' => 'tourism-test']);
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

    public function test_admin_can_create_a_package_leaving_inclusions_and_exclusions_blank(): void
    {
        // Regression test: a real browser submits a blank <textarea> as an
        // empty string, which Laravel's ConvertEmptyStringsToNull middleware
        // converts to null — and $request->input('key', 'default') does NOT
        // fall back to 'default' when the key is present-but-null, only when
        // the key is absent entirely. Passing an explicit empty string here
        // (not omitting the key) reproduces that real-world request shape,
        // which a PHPUnit test omitting the key entirely would not catch.
        $category = PackageCategory::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/packages', [
            'package_category_id' => $category->id,
            'name' => 'Bare Package',
            'slug' => 'bare-package',
            'currency' => 'USD',
            'status' => 'draft',
            'inclusions_text' => '',
            'exclusions_text' => '',
        ]);

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertDatabaseHas('packages', ['slug' => 'bare-package']);
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

    /**
     * Regression for FINAL_CODE_REVIEW.md H-4: Package uses SoftDeletes, but
     * the slug/code uniqueness rules checked the raw table without excluding
     * trashed rows — an admin who deleted a package and tried to recreate it
     * with the same slug/code got an unrecoverable "already taken" error
     * pointing at a record they can no longer see anywhere in the UI.
     */
    public function test_a_soft_deleted_packages_slug_and_code_can_be_reused(): void
    {
        $category = PackageCategory::factory()->create();
        $old = Package::factory()->create(['package_category_id' => $category->id, 'slug' => 'reused-slug', 'code' => 'UB-REUSE']);
        $old->delete();
        $this->assertSoftDeleted($old);

        $response = $this->actingAs($this->admin)->post('/admin/packages', [
            'package_category_id' => $category->id,
            'code' => 'UB-REUSE',
            'name' => 'Recreated Package',
            'slug' => 'reused-slug',
            'currency' => 'USD',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertDatabaseHas('packages', ['slug' => 'reused-slug', 'code' => 'UB-REUSE', 'deleted_at' => null]);
    }
}
