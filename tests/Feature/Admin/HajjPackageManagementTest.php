<?php

namespace Tests\Feature\Admin;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the new Hajj-specific admin surface (Admin\HajjPackageController),
 * built for the redesigned Hajj package data model — Package A/B variants,
 * dynamic sharing types with multi-currency columns, a fully separate
 * Aziziya sub-schema, Mina/Arafat detail, transportation, notes and
 * upgrades. See docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md for the
 * real brochure structure this mirrors.
 */
class HajjPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PackageCategory $hajjCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->hajjCategory = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
    }

    public function test_guest_cannot_access_hajj_package_admin(): void
    {
        $this->get('/admin/hajj-packages')->assertRedirect('/admin/login');
    }

    private function fullPackagePayload(): array
    {
        return [
            'code' => 'UB001-TEST',
            'name' => 'Executive Platinum Intercon / Fairmont — Medinah First',
            'package_type' => 'Executive Platinum',
            'slug' => 'ub001-test',
            'summary' => 'Hajj 2027 test package.',
            'duration_days' => 13,
            'duration_label' => '13 Days Package',
            'medinah_first' => '1',
            'is_shifting' => '',
            'status' => 'published',
            'season_year' => 2027,
            'season_label' => 'Hajj 2027 / 1448 AH',
            'variants' => [
                ['code' => 'A', 'label' => 'Dar Al Tawhid Intercontinental'],
                ['code' => 'B', 'label' => 'Fairmont Clock Tower'],
            ],
            'accommodations' => [
                ['location' => 'medinah', 'variant_code' => '', 'hotel_name' => 'Dar Al Taqwa', 'star_rating' => 5, 'meal_plan' => 'Half board', 'nights' => 3],
                ['location' => 'makkah', 'variant_code' => 'A', 'hotel_name' => 'Dar Al Tawhid Intercontinental', 'star_rating' => 5, 'meal_plan' => 'Half board', 'nights' => 4],
                ['location' => 'makkah', 'variant_code' => 'B', 'hotel_name' => 'Fairmont Clock Tower', 'star_rating' => 5, 'meal_plan' => 'Half board', 'nights' => 4],
            ],
            'room_options' => [
                ['variant_code' => 'A', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_basis' => 'per_person', 'price_usd' => '', 'is_available' => ''],
                ['variant_code' => 'A', 'sharing_type' => 'triple', 'occupancy' => 3, 'display_label' => 'Triple Sharing', 'price_basis' => 'per_person', 'price_usd' => '22450', 'is_available' => '1'],
                ['variant_code' => 'B', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_basis' => 'per_person', 'price_usd' => '16300', 'is_available' => '1'],
            ],
            'aziziya' => [
                'status' => 'optional',
                'accommodation_name' => '',
                'duration_days' => 5,
            ],
            'aziziya_room_options' => [
                ['sharing_type' => 'family_room', 'display_label' => 'Family Room', 'pricing_type' => 'supplement', 'price_basis' => 'flat', 'price_usd' => '5500'],
            ],
            'mashaer' => [
                'mina' => ['maktab' => 'A', 'zone' => 'Zone 1', 'accommodation_type' => 'Sofa cum bed 50-55cm'],
                'arafat' => ['tent_type' => 'Air Conditioned Marquee'],
            ],
            'transportation' => [
                ['from_location' => 'Jeddah Airport', 'to_location' => 'Makkah Hotel', 'transport_type' => 'airport_transfer', 'is_included' => '', 'price' => '165', 'currency' => 'USD', 'price_basis' => 'per person'],
            ],
            'itinerary' => [
                ['day_number' => 1, 'date_gregorian' => '2027-05-07', 'date_hijri_label' => '01 Zil Hajj', 'city' => 'To Medinah', 'accommodation_a' => 'Dar Al Taqwa', 'accommodation_b' => ''],
            ],
            'inclusions_text' => "Meet & assist at the airport\nHajj training program",
            'exclusions_text' => "Airline ticket\nQurbani actual cost",
            'upgrades' => [
                ['name' => 'Kaba view supplement', 'price' => '2200', 'currency' => 'USD', 'price_basis' => 'per person'],
            ],
            'notes' => [
                ['note_type' => 'pricing', 'content' => 'Book Early, Prices and Packages Subject to Change.', 'is_important' => '1'],
            ],
        ];
    }

    public function test_create_and_edit_forms_render_successfully(): void
    {
        $this->actingAs($this->admin)->get('/admin/hajj-packages/create')->assertOk();

        $this->actingAs($this->admin)->post('/admin/hajj-packages', $this->fullPackagePayload());
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();

        $response = $this->actingAs($this->admin)->get("/admin/hajj-packages/{$package->id}/edit");
        $response->assertOk();
        $response->assertSee($package->name);
        $response->assertSee('Dar Al Tawhid Intercontinental');
        $response->assertSee('16300');
    }

    public function test_admin_can_create_a_full_hajj_package(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/hajj-packages', $this->fullPackagePayload());

        $response->assertRedirect(route('admin.hajj-packages.index'));

        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $this->assertSame($this->hajjCategory->id, $package->package_category_id);
        $this->assertTrue($package->medinah_first);
        $this->assertFalse($package->has_aziziya, 'this package is Non-Aziziya-base with only an optional Aziziya upgrade — has_aziziya must stay false');

        $this->assertSame(2, $package->variants()->count());
        $variantA = $package->variants()->where('code', 'A')->firstOrFail();
        $variantB = $package->variants()->where('code', 'B')->firstOrFail();

        $this->assertSame(3, $package->accommodations()->count());
        $this->assertSame($variantA->id, $package->accommodations()->where('hotel_name', 'Dar Al Tawhid Intercontinental')->first()->variant_id);

        $this->assertSame(3, $package->roomOptions()->count());
        $naQuad = $package->roomOptions()->where('variant_id', $variantA->id)->where('sharing_type', 'quad')->firstOrFail();
        $this->assertFalse($naQuad->is_available, 'a blank price with no "available" checkbox must seed as a real N/A row, not a guessed price');
        $this->assertNull($naQuad->price_usd);

        $priceB = $package->roomOptions()->where('variant_id', $variantB->id)->where('sharing_type', 'quad')->firstOrFail();
        $this->assertEquals(16300, $priceB->price_usd);
        $this->assertNull($priceB->price_pkr, 'no PKR value was submitted — must stay null, never invented');

        $this->assertNotNull($package->aziziya);
        $this->assertSame('optional', $package->aziziya->status);
        $familyRoom = $package->aziziya->roomOptions()->where('sharing_type', 'family_room')->firstOrFail();
        $this->assertSame('supplement', $familyRoom->pricing_type);
        $this->assertEquals(5500, $familyRoom->price_usd);

        $this->assertSame('A', $package->mashaerDetails()->where('location', 'mina')->firstOrFail()->maktab);
        $this->assertSame('Air Conditioned Marquee', $package->mashaerDetails()->where('location', 'arafat')->firstOrFail()->tent_type);

        $this->assertSame(1, $package->transportation()->count());
        $this->assertSame(2, $package->inclusions()->count());
        $this->assertSame(1, $package->upgrades()->count());
        $this->assertSame(1, $package->packageNotes()->count());
        $this->assertTrue($package->packageNotes()->first()->is_important);

        // starting_price is the lowest available USD room option (16300), not
        // the N/A cell and not the family-room/upgrade supplements.
        $this->assertEquals(16300, $package->fresh()->starting_price);
    }

    public function test_has_aziziya_syncs_true_only_when_aziziya_status_is_included(): void
    {
        $payload = $this->fullPackagePayload();
        $payload['code'] = 'UB023-TEST';
        $payload['slug'] = 'ub023-test';
        $payload['aziziya']['status'] = 'included';

        $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);

        $package = Package::where('code', 'UB023-TEST')->firstOrFail();
        $this->assertTrue($package->has_aziziya);
        $this->assertSame('included', $package->aziziya->status);
    }

    public function test_admin_can_update_a_hajj_package_and_nested_data_is_replaced_not_duplicated(): void
    {
        $this->actingAs($this->admin)->post('/admin/hajj-packages', $this->fullPackagePayload());
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();

        $payload = $this->fullPackagePayload();
        $payload['name'] = 'Updated Name';
        $payload['room_options'][1]['price_usd'] = '99999';

        $response = $this->actingAs($this->admin)->put("/admin/hajj-packages/{$package->id}", $payload);

        $response->assertRedirect(route('admin.hajj-packages.index'));
        $package->refresh();
        $this->assertSame('Updated Name', $package->name);
        $this->assertSame(2, $package->variants()->count(), 'variants must be replaced, not duplicated, on update');
        $this->assertSame(3, $package->roomOptions()->count());
    }

    public function test_admin_can_delete_a_hajj_package(): void
    {
        $this->actingAs($this->admin)->post('/admin/hajj-packages', $this->fullPackagePayload());
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete("/admin/hajj-packages/{$package->id}");

        $response->assertRedirect(route('admin.hajj-packages.index'));
        $this->assertSoftDeleted($package);
    }

    /**
     * Regression for FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1/H-1/H-2: two
     * variant rows submitted with the same code used to reach the database
     * unvalidated, cascade-delete the package's existing accommodations/
     * room-options/Aziziya-room-options via the first delete-then-recreate
     * step, then throw an unhandled `QueryException` on the real unique
     * constraint — permanently destroying live pricing data with a raw 500.
     * Validation must now reject this before the sync ever runs, and the
     * package's real data must survive completely untouched.
     */
    public function test_duplicate_variant_codes_are_rejected_and_existing_data_survives(): void
    {
        $this->actingAs($this->admin)->post('/admin/hajj-packages', $this->fullPackagePayload());
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $originalRoomOptionCount = $package->roomOptions()->count();
        $originalAccommodationCount = $package->accommodations()->count();

        $payload = $this->fullPackagePayload();
        $payload['variants'][1]['code'] = 'A'; // duplicate of variants[0]

        $response = $this->actingAs($this->admin)->put("/admin/hajj-packages/{$package->id}", $payload);

        $response->assertSessionHasErrors('variants');
        $response->assertStatus(302); // a validation redirect, never a raw 500
        $package->refresh();
        $this->assertSame($originalRoomOptionCount, $package->roomOptions()->count(), 'rejected submission must not touch existing data at all');
        $this->assertSame($originalAccommodationCount, $package->accommodations()->count());
        $this->assertSame(2, $package->variants()->count());
    }

    /**
     * Regression for FINAL_CODE_REVIEW_HAJJ_REDESIGN.md H-2: a typo'd
     * variant_code (e.g. "AA" instead of "A") used to silently resolve to
     * null — "applies to every variant" — showing a price/accommodation
     * meant for one variant under all of them, with no error anywhere.
     */
    public function test_an_unresolvable_variant_code_reference_is_rejected(): void
    {
        $payload = $this->fullPackagePayload();
        $payload['accommodations'][1]['variant_code'] = 'Z';

        $response = $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);

        $response->assertSessionHasErrors('accommodations.1.variant_code');
        $this->assertDatabaseMissing('packages', ['code' => 'UB001-TEST']);
    }

    /**
     * Regression for FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-2: a `<input
     * type="file">` can never be pre-filled by the browser, so an existing
     * media row's file input is always empty on every subsequent edit —
     * the sync must not treat "no new file this time" as "delete the
     * photo", and must carry the existing image_path forward via the
     * row's hidden id.
     */
    public function test_media_image_persists_across_an_unrelated_edit_without_reupload(): void
    {
        Storage::fake('public');
        $payload = $this->fullPackagePayload();
        $payload['media'] = [
            ['media_type' => 'gallery', 'file' => UploadedFile::fake()->image('mina.jpg'), 'caption' => 'Mina camp'],
        ];

        $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $media = $package->media()->firstOrFail();
        $originalPath = $media->image_path;
        Storage::disk('public')->assertExists($originalPath);

        $updatePayload = $this->fullPackagePayload();
        $updatePayload['name'] = 'Updated Name Only';
        $updatePayload['media'] = [
            ['id' => $media->id, 'media_type' => 'gallery', 'caption' => 'Mina camp'], // no 'file' — nothing re-uploaded
        ];

        $this->actingAs($this->admin)->put("/admin/hajj-packages/{$package->id}", $updatePayload);

        $this->assertSame(1, $package->media()->count(), 'the existing media row must survive, not be deleted and left uncreated');
        $media->refresh();
        $this->assertSame($originalPath, $media->image_path, 'the real uploaded photo must not be silently dropped just because the file input was empty on this edit');
        Storage::disk('public')->assertExists($originalPath);
    }

    /**
     * Regression for a release-gate code-review finding: the Media
     * repeater was the only section on this form that didn't wrap its
     * initial values in `old()`, so a validation failure elsewhere on the
     * form (e.g. a duplicate variant code) silently reverted the Media
     * section to stale DB state instead of the admin's just-typed edits —
     * and on create(), where no DB row exists yet, would have dropped any
     * media rows the admin had added entirely. Also proves the "keep
     * current image" thumbnail/hint still renders after such a redisplay,
     * since `old('media')` never carries `image_path` (no such form field
     * exists — a file input can't be re-populated) and the template must
     * backfill it from the real record via each row's own hidden `id`.
     */
    public function test_media_section_preserves_admins_edits_and_existing_image_hint_after_a_validation_failure(): void
    {
        Storage::fake('public');
        $payload = $this->fullPackagePayload();
        $payload['media'] = [
            ['media_type' => 'gallery', 'file' => UploadedFile::fake()->image('mina.jpg'), 'caption' => 'Original caption'],
        ];
        $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $media = $package->media()->firstOrFail();

        $updatePayload = $this->fullPackagePayload();
        $updatePayload['variants'][1]['code'] = 'A'; // duplicate — triggers a validation failure elsewhere on the form
        $updatePayload['media'] = [
            ['id' => $media->id, 'media_type' => 'gallery', 'caption' => 'Admin just typed this new caption'],
        ];

        $response = $this->actingAs($this->admin)->put("/admin/hajj-packages/{$package->id}", $updatePayload);
        $response->assertSessionHasErrors('variants');

        $editPage = $this->actingAs($this->admin)->get("/admin/hajj-packages/{$package->id}/edit");
        $editPage->assertOk();
        $editPage->assertSee('Admin just typed this new caption'); // the admin's edit, not the stale DB value
        $editPage->assertSee('leave blank to keep current'); // the existing-image hint, backfilled via the row\'s id
        $editPage->assertSee($media->fresh()->image_path, false); // the actual thumbnail src still resolves
    }

    /**
     * Companion to the above: a media row genuinely removed from the
     * repeater (not resubmitted at all) must still be deleted, along with
     * its stored file — the fix must not make media rows immortal, only
     * protect ones the admin didn't touch.
     */
    public function test_removing_a_media_row_deletes_it_and_its_stored_file(): void
    {
        Storage::fake('public');
        $payload = $this->fullPackagePayload();
        $payload['media'] = [
            ['media_type' => 'gallery', 'file' => UploadedFile::fake()->image('mina.jpg')],
        ];
        $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);
        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $originalPath = $package->media()->firstOrFail()->image_path;

        $updatePayload = $this->fullPackagePayload();
        $updatePayload['media'] = []; // repeater row removed entirely

        $this->actingAs($this->admin)->put("/admin/hajj-packages/{$package->id}", $updatePayload);

        $this->assertSame(0, $package->media()->count());
        Storage::disk('public')->assertMissing($originalPath);
    }

    /**
     * Regression for FINAL_CODE_REVIEW_HAJJ_REDESIGN.md M-1: PHP treats the
     * literal string "0" as falsy, so a genuinely free upgrade/transport/
     * service priced at exactly 0 previously saved with `currency = null` —
     * an internally inconsistent row the public page's currency-matching
     * logic would then never show under any currency button.
     */
    public function test_a_zero_price_upgrade_still_gets_a_currency_not_null(): void
    {
        $payload = $this->fullPackagePayload();
        $payload['upgrades'][0]['price'] = '0';
        $payload['upgrades'][0]['currency'] = 'USD';

        $this->actingAs($this->admin)->post('/admin/hajj-packages', $payload);

        $package = Package::where('code', 'UB001-TEST')->firstOrFail();
        $upgrade = $package->upgrades()->firstOrFail();
        $this->assertEquals(0, $upgrade->price);
        $this->assertSame('USD', $upgrade->currency, 'a real $0 price must still carry its currency, not be silently nulled by PHP\'s "0"-string falsiness');
    }
}
