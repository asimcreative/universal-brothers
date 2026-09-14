<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\AiKnowledgeEntry;
use App\Models\Hotel;
use App\Models\MashaerLocation;
use App\Models\NoteTemplate;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageTemplate;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;
use App\Models\User;
use App\Support\Ai\KnowledgeIndexer;
use App\Support\Ai\PackageContext;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageFormState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The step-by-step Hajj package builder, driven through its real HTTP routes
 * (issue #10): saving intents, publishing rules, hotel options, reusable
 * content links, internal notes, duplication, quick actions, preview and
 * templates. The seeded brochure packages are used wherever the question is
 * "does this work on the client's real data".
 */
class PackageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function seedBrochure(): void
    {
        Artisan::call('db:seed');
    }

    private function hajjCategory(): PackageCategory
    {
        return PackageCategory::firstOrCreate(['slug' => 'hajj'], ['name' => 'Hajj', 'is_active' => true]);
    }

    /** A complete, publishable package with two hotel options. */
    private function payload(array $overrides = []): array
    {
        $this->hajjCategory();

        return array_replace_recursive([
            'name' => 'Builder Test Package',
            'code' => 'UB900',
            'duration_days' => 14,
            'medinah_first' => '1',
            'is_shifting' => '0',
            'aziziya' => ['status' => 'optional'],
            'variants' => [
                ['code' => 'A', 'label' => 'Swissotel'],
                ['code' => 'B', 'label' => 'Fairmont'],
            ],
            'accommodations' => [
                ['location' => 'medinah', 'variant_code' => '', 'hotel_name' => 'Dar Al Taqwa', 'star_rating' => 5],
                ['location' => 'makkah', 'variant_code' => 'A', 'hotel_name' => 'Swissotel Makkah', 'star_rating' => 5],
                ['location' => 'makkah', 'variant_code' => 'B', 'hotel_name' => 'Fairmont Clock Tower', 'star_rating' => 5],
            ],
            'room_options' => [
                ['variant_code' => 'A', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_usd' => '12000', 'is_available' => '1'],
                ['variant_code' => 'B', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_usd' => '15000', 'price_sar' => '56000', 'is_available' => '1'],
            ],
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Real data
    // ------------------------------------------------------------------

    public function test_every_live_package_meets_the_publishing_rules(): void
    {
        $this->seedBrochure();

        foreach (Package::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->get() as $package) {
            $this->assertSame([], PackageCompleteness::problems(PackageFormState::fromPackage($package)), "{$package->code} must be publishable as seeded");
        }
    }

    /**
     * The strongest data-preservation guarantee: every real brochure package
     * opens in the builder and, saved straight back through the HTTP route
     * with nothing changed, keeps every option, price, hotel, day, note,
     * service, transport leg, upgrade and library link exactly as it was.
     */
    public function test_every_seeded_package_saves_back_through_the_builder_unchanged(): void
    {
        $this->seedBrochure();

        foreach (Package::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->get() as $package) {
            $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', $package))->assertOk();

            $before = PackageFormState::fromPackage($package->fresh());
            $submitted = collect($before)->except(['media'])->all() + ['_intent' => 'save'];

            $this->actingAs($this->admin)
                ->put(route('admin.hajj-packages.update', $package), $submitted)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.hajj-packages.edit', $package));

            $after = PackageFormState::fromPackage($package->fresh());
            $this->assertEquals(collect($before)->except('media')->all(), collect($after)->except('media')->all(), "{$package->code} changed on a no-op save");
            $this->assertSame('published', $package->fresh()->status);
        }
    }

    // ------------------------------------------------------------------
    // Saving and publishing
    // ------------------------------------------------------------------

    public function test_an_incomplete_draft_can_be_saved_and_gets_a_web_address_from_its_title(): void
    {
        $this->hajjCategory();

        $response = $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), [
            'name' => 'Just A Title For Now',
            '_intent' => 'draft',
        ]);

        $package = Package::where('name', 'Just A Title For Now')->firstOrFail();
        $response->assertRedirect(route('admin.hajj-packages.edit', $package));
        $this->assertSame('draft', $package->status);
        $this->assertSame('just-a-title-for-now', $package->slug);
        $this->assertNull($package->published_at);
    }

    public function test_publishing_an_incomplete_package_is_refused_and_names_the_steps_to_fix(): void
    {
        $this->hajjCategory();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.hajj-packages.create'))
            ->post(route('admin.hajj-packages.store'), ['name' => 'Half Done', '_intent' => 'publish']);

        $response->assertRedirect(route('admin.hajj-packages.create'));
        $response->assertSessionHasErrors(['publish.basics', 'publish.hotels', 'publish.pricing']);
        $this->assertDatabaseMissing('packages', ['name' => 'Half Done']);

        $page = $this->actingAs($this->admin)->get(route('admin.hajj-packages.create'));
        $page->assertOk();
        $page->assertSee('The package cannot be published yet.');
        $page->assertSee('data-step-link="pricing"', false);
        $page->assertSee('Add at least one available room type with a price.');
        // What the admin typed survives the refusal.
        $page->assertSee('value="Half Done"', false);
    }

    /**
     * Rows removed in the builder leave gaps in the submitted indexes. A
     * rejected save must show each error next to the row that caused it,
     * which only works if the redisplayed rows keep the submitted indexes.
     */
    public function test_a_field_error_is_shown_on_the_row_that_caused_it(): void
    {
        $payload = $this->payload(['_intent' => 'save']);
        $payload['room_options'] = [
            2 => ['variant_code' => 'A', 'sharing_type' => 'quad', 'price_usd' => '12000', 'is_available' => '1'],
            7 => ['variant_code' => 'B', 'sharing_type' => 'quad', 'price_usd' => 'twelve thousand', 'is_available' => '1'],
        ];

        $this->actingAs($this->admin)
            ->from(route('admin.hajj-packages.create'))
            ->post(route('admin.hajj-packages.store'), $payload)
            ->assertSessionHasErrors('room_options.7.price_usd');

        $html = $this->actingAs($this->admin)->get(route('admin.hajj-packages.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="room_options\[7\]\[price_usd\]"[^>]*value="twelve thousand"[^>]*class="form-control\s+is-invalid\s*"/', $html);
        $this->assertMatchesRegularExpression('/name="room_options\[2\]\[price_usd\]"[^>]*value="12000"[^>]*class="form-control\s*"/', $html);
    }

    public function test_every_hotel_option_needs_a_price_before_publishing(): void
    {
        $payload = $this->payload(['_intent' => 'publish']);
        unset($payload['room_options'][1]);

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)
            ->assertSessionHasErrors('publish.pricing');

        $this->assertSame(0, Package::count());
    }

    public function test_publish_intent_makes_the_package_live_and_records_who_did_it(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']))
            ->assertSessionHasNoErrors();

        $package = Package::where('code', 'UB900')->firstOrFail();
        $this->assertSame('published', $package->status);
        $this->assertNotNull($package->published_at);
        $this->get('/hajj/'.$package->slug)->assertOk()->assertSee('Builder Test Package');
        $this->assertTrue(AdminActivity::where('subject_id', $package->id)->where('user_id', $this->admin->id)->exists());
    }

    public function test_save_keeps_a_live_package_live_and_continue_opens_the_next_step(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']));
        $package = Package::where('code', 'UB900')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.hajj-packages.update', $package), $this->payload(['name' => 'Renamed', '_intent' => 'continue', '_step' => 'pricing']))
            ->assertRedirect(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'hotels']));

        $this->assertSame('published', $package->fresh()->status);
        $this->assertSame('Renamed', $package->fresh()->name);
    }

    public function test_draft_intent_on_a_live_package_takes_it_off_the_website(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']));
        $package = Package::where('code', 'UB900')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.hajj-packages.update', $package), $this->payload(['_intent' => 'draft']));

        $this->assertSame('draft', $package->fresh()->status);
        $this->get('/hajj/'.$package->slug)->assertNotFound();
    }

    public function test_preview_intent_saves_then_opens_the_signed_preview(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'preview']));

        $package = Package::where('code', 'UB900')->firstOrFail();
        $this->assertSame('draft', $package->status, 'previewing must never publish');
        $this->assertStringContainsString("/admin/hajj-packages/{$package->id}/preview?expires=", $response->headers->get('Location'));
        $this->assertStringContainsString('signature=', $response->headers->get('Location'));
    }

    // ------------------------------------------------------------------
    // Options, library links, content
    // ------------------------------------------------------------------

    public function test_options_a_b_and_c_keep_their_own_hotels_and_prices(): void
    {
        $payload = $this->payload(['_intent' => 'publish']);
        $payload['variants'][] = ['code' => 'c', 'label' => 'Voco'];
        $payload['accommodations'][] = ['location' => 'makkah', 'variant_code' => 'C', 'hotel_name' => 'Voco Makkah', 'star_rating' => 4];
        $payload['room_options'][] = ['variant_code' => 'C', 'sharing_type' => 'triple', 'occupancy' => 3, 'display_label' => 'Triple Sharing', 'price_usd' => '9900', 'price_pkr' => '2900000', 'is_available' => '1'];

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();

        $package = Package::where('code', 'UB900')->firstOrFail();
        $variants = $package->variants()->pluck('id', 'code');
        $this->assertSame(['A', 'B', 'C'], $variants->keys()->all(), 'option letters are stored in capitals');

        $this->assertSame('Voco Makkah', $package->accommodations()->where('variant_id', $variants['C'])->value('hotel_name'));
        $this->assertNull($package->accommodations()->where('hotel_name', 'Dar Al Taqwa')->value('variant_id'), 'a hotel for every option belongs to no single option');

        $roomC = $package->roomOptions()->where('variant_id', $variants['C'])->firstOrFail();
        $this->assertEquals(9900, $roomC->price_usd);
        $this->assertEquals(2900000, $roomC->price_pkr);
        $this->assertNull($roomC->price_sar, 'a currency left empty stays empty');
        $this->assertEquals(56000, $package->roomOptions()->where('variant_id', $variants['B'])->value('price_sar'));
        $this->assertEquals(9900, $package->fresh()->starting_price);

        $this->get('/hajj/'.$package->slug)->assertOk()->assertSee('Voco')->assertSee('data-usd="9900.00"', false);
    }

    public function test_a_room_named_only_by_its_label_is_kept(): void
    {
        $payload = $this->payload(['_intent' => 'save']);
        $payload['room_options'][] = ['variant_code' => 'A', 'sharing_type' => '', 'display_label' => 'Quint Sharing', 'price_usd' => '8000', 'is_available' => '1'];

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();

        $room = Package::where('code', 'UB900')->firstOrFail()->roomOptions()->where('display_label', 'Quint Sharing')->firstOrFail();
        $this->assertSame('quint_sharing', $room->sharing_type);
    }

    public function test_picking_a_saved_hotel_links_it_and_a_change_in_the_package_leaves_the_saved_hotel_alone(): void
    {
        $hotel = Hotel::create(['name' => 'Swissotel Makkah', 'slug' => 'swissotel-makkah', 'city' => 'Makkah', 'location' => 'makkah', 'star_rating' => 5]);

        $payload = $this->payload(['_intent' => 'save']);
        $payload['accommodations'][1] = ['location' => 'makkah', 'variant_code' => 'A', 'hotel_id' => $hotel->id, 'hotel_name' => ''];
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();

        $package = Package::where('code', 'UB900')->firstOrFail();
        $row = $package->accommodations()->where('hotel_id', $hotel->id)->firstOrFail();
        $this->assertSame('Swissotel Makkah', $row->hotel_name, 'an empty name is filled from the saved hotel');
        $this->assertSame(5, (int) $row->star_rating);

        $payload['accommodations'][1]['hotel_name'] = 'Swissotel Makkah (Kaaba view floors)';
        $this->actingAs($this->admin)->put(route('admin.hajj-packages.update', $package), $payload)->assertSessionHasNoErrors();

        $this->assertSame('Swissotel Makkah (Kaaba view floors)', $package->accommodations()->where('hotel_id', $hotel->id)->value('hotel_name'));
        $this->assertSame('Swissotel Makkah', $hotel->fresh()->name, 'a package-specific change must never edit the saved hotel');
    }

    public function test_saved_transport_notes_services_upgrades_and_mashaer_are_linked_and_copied(): void
    {
        $transport = TransportOption::create(['name' => 'Airport to hotel', 'transport_type' => 'airport_transfer', 'from_location' => 'Jeddah Airport', 'to_location' => 'Makkah Hotel', 'is_included' => true]);
        $note = NoteTemplate::create(['title' => 'Ticket note', 'heading' => 'Tickets', 'note_type' => 'important', 'content' => 'Ticket & Qurbani not included.', 'is_important' => true]);
        $service = ServiceItem::create(['type' => 'inclusion', 'title' => 'Ziyarat', 'description' => 'Ziyarat in Madinah with guidance']);
        $upgrade = UpgradeOption::create(['name' => 'Kaaba View Supplement', 'price' => 2200, 'currency' => 'USD', 'price_basis' => 'per person']);
        $mina = MashaerLocation::create(['name' => 'Mina A', 'location' => 'mina', 'maktab' => 'A', 'zone' => 'Zone 1']);

        $payload = $this->payload([
            '_intent' => 'save',
            'transportation' => [['transport_option_id' => $transport->id]],
            'notes' => [['note_template_id' => $note->id]],
            'inclusions' => [['service_item_id' => $service->id]],
            'upgrades' => [['upgrade_option_id' => $upgrade->id]],
            'mashaer' => ['mina' => ['mashaer_location_id' => $mina->id]],
        ]);

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();
        $package = Package::where('code', 'UB900')->firstOrFail();

        $leg = $package->transportation()->firstOrFail();
        $this->assertSame([$transport->id, 'Jeddah Airport', 'airport_transfer'], [$leg->transport_option_id, $leg->from_location, $leg->transport_type]);

        $packageNote = $package->packageNotes()->firstOrFail();
        $this->assertSame([$note->id, 'Tickets', 'Ticket & Qurbani not included.', true], [$packageNote->note_template_id, $packageNote->title, $packageNote->content, $packageNote->is_important]);

        $this->assertSame('Ziyarat in Madinah with guidance', $package->inclusions()->where('service_item_id', $service->id)->value('description'));
        $this->assertEquals(2200, $package->upgrades()->where('upgrade_option_id', $upgrade->id)->value('price'));

        $minaRow = $package->mashaerDetails()->where('location', 'mina')->firstOrFail();
        $this->assertSame([$mina->id, 'A', 'Zone 1'], [$minaRow->mashaer_location_id, $minaRow->maktab, $minaRow->zone]);
    }

    public function test_the_same_included_service_is_saved_once(): void
    {
        $payload = $this->payload(['_intent' => 'save', 'inclusions' => [
            ['description' => 'Hajj training program'],
            ['description' => '  hajj   training program '],
            ['description' => 'Religious guide book'],
        ]]);

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame(['Hajj training program', 'Religious guide book'], Package::where('code', 'UB900')->firstOrFail()->inclusions()->pluck('description')->all());
    }

    public function test_muzdalifah_is_saved_and_shown_on_the_package_page(): void
    {
        $payload = $this->payload(['_intent' => 'publish', 'mashaer' => ['muzdalifah' => ['tent_type' => 'Open ground with floor mats', 'meal_plan' => 'Snack box']]]);

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $payload)->assertSessionHasNoErrors();
        $package = Package::where('code', 'UB900')->firstOrFail();

        $this->assertSame('Open ground with floor mats', $package->mashaerDetails()->where('location', 'muzdalifah')->value('tent_type'));
        $this->get('/hajj/'.$package->slug)->assertOk()->assertSee('Muzdalifah')->assertSee('Open ground with floor mats');
    }

    public function test_internal_notes_never_reach_the_website_the_ai_assistant_or_json(): void
    {
        $marker = 'INTERNAL-ONLY supplier contract renews in March';
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish', 'internal_notes' => $marker]));
        $package = Package::where('code', 'UB900')->firstOrFail();

        $this->assertSame($marker, $package->internal_notes);
        $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', $package))->assertSee($marker);

        auth()->logout();
        $this->get('/hajj/'.$package->slug)->assertOk()->assertDontSee('INTERNAL-ONLY');
        $this->get('/hajj')->assertOk()->assertDontSee('INTERNAL-ONLY');

        $package->load(PackageContext::RELATIONS);
        $this->assertStringNotContainsString('INTERNAL-ONLY', PackageContext::render($package, ['USD']));
        $this->assertStringNotContainsString('INTERNAL-ONLY', $package->toJson());

        app(KnowledgeIndexer::class)->rebuild();
        $this->assertFalse(AiKnowledgeEntry::where('body', 'like', '%INTERNAL-ONLY%')->orWhere('keywords', 'like', '%INTERNAL-ONLY%')->exists());
    }

    public function test_the_brochure_audit_remarks_are_internal_notes_not_public_text(): void
    {
        $this->seedBrochure();

        foreach (['UB004', 'UB008', 'UB011', 'UB013'] as $code) {
            $package = Package::where('code', $code)->firstOrFail();

            $this->assertNull($package->description, "{$code} description must not carry the audit remark");
            $this->assertStringStartsWith('Brochure', (string) $package->internal_notes);
            $this->assertFalse($package->packageNotes()->where('content', 'like', 'Brochure%')->exists());

            $this->get('/hajj/'.$package->slug)->assertOk()
                ->assertDontSee('Brochure inconsistency')
                ->assertDontSee('Brochure names this')
                ->assertDontSee('recorded as found');
        }
    }

    // ------------------------------------------------------------------
    // Duplicate
    // ------------------------------------------------------------------

    public function test_duplicating_copies_everything_into_a_new_draft_and_leaves_the_original_untouched(): void
    {
        $this->seedBrochure();
        Storage::fake('public');

        $original = Package::where('code', 'UB001')->firstOrFail();
        $original->forceFill(['cover_image' => UploadedFile::fake()->image('cover.jpg')->store('packages', 'public')])->save();
        $original->media()->create(['media_type' => 'gallery', 'image_path' => UploadedFile::fake()->image('g.jpg')->store('packages/media', 'public'), 'caption' => 'Gallery']);
        $before = PackageFormState::fromPackage($original->fresh());

        $response = $this->actingAs($this->admin)->post(route('admin.hajj-packages.duplicate', $original));

        $copy = Package::where('code', 'UB001-COPY')->firstOrFail();
        $response->assertRedirect(route('admin.hajj-packages.edit', $copy));

        $this->assertSame('draft', $copy->status);
        $this->assertFalse($copy->is_featured);
        $this->assertNull($copy->published_at);
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertStringEndsWith('(Copy)', $copy->name);

        $copyState = PackageFormState::fromPackage($copy);
        foreach (PackageFormState::SECTIONS as $section) {
            $this->assertEquals($before[$section], $copyState[$section], "{$section} must be copied exactly, including library links");
        }

        // Each option-specific row points at the COPY's options, not the original's.
        $copyVariantIds = $copy->variants()->pluck('id');
        $this->assertTrue($copy->roomOptions()->whereNotNull('variant_id')->pluck('variant_id')->every(fn ($id) => $copyVariantIds->contains($id)));

        // Photos are new files, so editing the copy can never delete the original's.
        $this->assertNotNull($copy->cover_image);
        $this->assertNotSame($original->fresh()->cover_image, $copy->cover_image);
        Storage::disk('public')->assertExists($copy->cover_image);
        $this->assertNotSame($original->media()->value('image_path'), $copy->media()->value('image_path'));

        $this->assertEquals(collect($before)->except('media')->all(), collect(PackageFormState::fromPackage($original->fresh()))->except('media')->all(), 'the original must not change');
        $this->assertSame('published', $original->fresh()->status);
        $this->get('/hajj/'.$copy->slug)->assertNotFound();
    }

    public function test_duplicating_twice_gives_each_copy_its_own_code_and_address(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']));
        $original = Package::where('code', 'UB900')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.duplicate', $original));
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.duplicate', $original));

        $this->assertEqualsCanonicalizing(['UB900', 'UB900-COPY', 'UB900-COPY-2'], Package::pluck('code')->all());
        $this->assertSame(3, Package::pluck('slug')->unique()->count());
    }

    // ------------------------------------------------------------------
    // Quick actions and archive
    // ------------------------------------------------------------------

    public function test_quick_publish_refuses_an_incomplete_draft(): void
    {
        $this->hajjCategory();
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), ['name' => 'Unfinished', '_intent' => 'draft']);
        $package = Package::where('name', 'Unfinished')->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.hajj-packages.index'))
            ->patch(route('admin.hajj-packages.quick', [$package, 'publish']))
            ->assertRedirect(route('admin.hajj-packages.index'))
            ->assertSessionHasErrors();

        $this->assertSame('draft', $package->fresh()->status);
    }

    public function test_quick_actions_publish_feature_unpublish_archive_and_restore(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'draft']));
        $package = Package::where('code', 'UB900')->firstOrFail();
        $quick = fn (string $action) => $this->actingAs($this->admin)->from(route('admin.hajj-packages.index'))->patch(route('admin.hajj-packages.quick', [$package, $action]));

        $quick('publish')->assertSessionHasNoErrors();
        $this->assertSame('published', $package->fresh()->status);
        $this->get('/hajj/'.$package->slug)->assertOk();

        $quick('feature');
        $this->assertTrue($package->fresh()->is_featured);

        $quick('unpublish');
        $this->assertSame('draft', $package->fresh()->status);

        $quick('publish');
        $quick('archive');
        $archived = $package->fresh();
        $this->assertNotNull($archived->archived_at);
        $this->assertSame('draft', $archived->status, 'archiving takes a package off the website');
        $this->assertFalse($archived->is_featured);
        $this->get('/hajj/'.$package->slug)->assertNotFound();

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.index'))->assertDontSee('Builder Test Package');
        $this->actingAs($this->admin)->get(route('admin.hajj-packages.index', ['status' => 'archived']))->assertSee('Builder Test Package');

        $quick('restore');
        $this->assertNull($package->fresh()->archived_at);
        $this->assertSame('draft', $package->fresh()->status);

        $this->assertSame(
            ['published', 'featured', 'unpublished', 'published', 'archived', 'restored'],
            AdminActivity::where('subject_id', $package->id)->where('action', '!=', 'created')->orderBy('id')->pluck('action')->all()
        );
    }

    public function test_builder_actions_refuse_a_package_that_is_not_hajj(): void
    {
        $tourism = PackageCategory::factory()->create(['slug' => 'tourism']);
        $package = Package::factory()->create(['package_category_id' => $tourism->id]);

        $this->actingAs($this->admin)->patch(route('admin.hajj-packages.quick', [$package, 'archive']))->assertNotFound();
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.duplicate', $package))->assertNotFound();
        $this->actingAs($this->admin)->get(URL::temporarySignedRoute('admin.hajj-packages.preview', now()->addHour(), ['package' => $package]))->assertNotFound();
        $this->assertNull($package->fresh()->archived_at);
    }

    public function test_the_listing_filters_by_status_code_arrival_aziziya_featured_and_days(): void
    {
        $this->seedBrochure();
        $index = fn (array $query) => $this->actingAs($this->admin)->get(route('admin.hajj-packages.index', $query))->assertOk();

        $index(['q' => 'UB013'])->assertSee('Voco by IHG — Makkah First')->assertDontSee('Makkah Tower — Medinah First');
        $index(['arrival' => 'makkah'])->assertSee('UB013')->assertDontSee('>UB001<', false);
        $index(['aziziya' => 'included'])->assertSee('UB015')->assertDontSee('>UB001<', false);
        $index(['featured' => 'yes'])->assertSee('UB023')->assertDontSee('>UB003<', false);
        $index(['days' => 10])->assertSee('UB003')->assertDontSee('>UB001<', false);
        $index(['status' => 'draft'])->assertSee('No packages match these filters.');
    }

    // ------------------------------------------------------------------
    // Preview
    // ------------------------------------------------------------------

    public function test_a_draft_preview_needs_an_admin_and_a_valid_signature(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'draft']));
        $package = Package::where('code', 'UB900')->firstOrFail();
        $signed = URL::temporarySignedRoute('admin.hajj-packages.preview', now()->addHour(), ['package' => $package]);

        $preview = $this->actingAs($this->admin)->get($signed);
        $preview->assertOk();
        $preview->assertSee('Builder Test Package');
        $preview->assertSee('It is a draft and visitors cannot see it.');
        $preview->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.preview', $package))->assertForbidden();

        $expired = URL::temporarySignedRoute('admin.hajj-packages.preview', now()->subMinute(), ['package' => $package]);
        $this->actingAs($this->admin)->get($expired)->assertForbidden();

        auth()->logout();
        $this->get($signed)->assertRedirect('/admin/login');
        $this->get('/hajj/'.$package->slug)->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Templates
    // ------------------------------------------------------------------

    public function test_saving_a_package_as_a_template_keeps_content_but_never_identity_or_internal_notes(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish', 'internal_notes' => 'private remark']));
        $package = Package::where('code', 'UB900')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.hajj-packages.save-template', $package), ['template_name' => 'Two options, 14 days'])
            ->assertSessionHasNoErrors();

        $template = PackageTemplate::where('name', 'Two options, 14 days')->firstOrFail();
        $this->assertSame($package->id, $template->source_package_id);
        $this->assertCount(2, $template->payload['variants']);
        $this->assertCount(2, $template->payload['room_options']);
        foreach (['code', 'slug', 'name', 'status', 'is_featured', 'internal_notes', 'media', 'meta_title'] as $identity) {
            $this->assertArrayNotHasKey($identity, $template->payload, "templates must not store {$identity}");
        }

        $create = $this->actingAs($this->admin)->get(route('admin.hajj-packages.create', ['template' => $template->id]));
        $create->assertOk()->assertSee('Started from the template')->assertSee('Fairmont Clock Tower')->assertSee('15000')->assertDontSee('private remark');
    }

    public function test_a_template_can_be_built_and_edited_in_the_builder(): void
    {
        $this->hajjCategory();
        $this->actingAs($this->admin)->get(route('admin.package-templates.create'))->assertOk()->assertSee('Template name');

        $payload = collect($this->payload())->except(['name', 'code'])->all() + ['template_name' => 'Flex 14'];
        $this->actingAs($this->admin)->post(route('admin.package-templates.store'), $payload)->assertSessionHasNoErrors();

        $template = PackageTemplate::where('name', 'Flex 14')->firstOrFail();
        $this->assertSame('Fairmont Clock Tower', $template->payload['accommodations'][2]['hotel_name']);

        $this->actingAs($this->admin)->get(route('admin.package-templates.edit', $template))->assertOk()->assertSee('Fairmont Clock Tower');
        $this->actingAs($this->admin)->put(route('admin.package-templates.update', $template), array_merge($payload, ['template_name' => 'Flex 14 (2028)']))->assertSessionHasNoErrors();
        $this->assertSame('Flex 14 (2028)', $template->fresh()->name);
    }

    public function test_applying_a_template_replaces_a_drafts_content_and_keeps_its_identity(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']));
        $source = Package::where('code', 'UB900')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.save-template', $source), ['template_name' => 'Source']);
        $template = PackageTemplate::firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), ['name' => 'Empty draft', 'code' => 'UB901', '_intent' => 'draft', 'internal_notes' => 'keep me']);
        $draft = Package::where('code', 'UB901')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.hajj-packages.apply-template', $draft), ['template_id' => $template->id])
            ->assertRedirect(route('admin.hajj-packages.edit', $draft));

        $draft->refresh();
        $this->assertSame(['Empty draft', 'UB901', 'draft', 'keep me'], [$draft->name, $draft->code, $draft->status, $draft->internal_notes]);
        $this->assertSame(2, $draft->variants()->count());
        $this->assertSame(2, $draft->roomOptions()->count());
        $this->assertSame(14, (int) $draft->duration_days, 'an empty basic field is filled from the template');
    }

    public function test_a_template_is_never_applied_to_a_published_package(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish']));
        $live = Package::where('code', 'UB900')->firstOrFail();
        $template = PackageTemplate::create(['name' => 'Empty', 'payload' => PackageFormState::forTemplate(PackageFormState::blank())]);
        $before = PackageFormState::fromPackage($live);

        $this->actingAs($this->admin)
            ->from(route('admin.hajj-packages.edit', $live))
            ->post(route('admin.hajj-packages.apply-template', $live), ['template_id' => $template->id])
            ->assertSessionHasErrors('template_id');

        $this->assertEquals($before, PackageFormState::fromPackage($live->fresh()));
    }

    public function test_the_content_endpoint_for_copying_leaves_out_identity_photos_and_internal_notes(): void
    {
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), $this->payload(['_intent' => 'publish', 'internal_notes' => 'secret']));
        $package = Package::where('code', 'UB900')->firstOrFail();

        $json = $this->actingAs($this->admin)->getJson(route('admin.hajj-packages.content', $package))->assertOk()->json();

        $this->assertCount(2, $json['content']['room_options']);
        $this->assertArrayNotHasKey('internal_notes', $json['content']);
        $this->assertArrayNotHasKey('slug', $json['content']);
        $this->assertStringNotContainsString('secret', json_encode($json));

        auth()->logout();
        $this->get(route('admin.hajj-packages.content', $package))->assertRedirect('/admin/login');
    }

    public function test_new_admin_pages_require_login(): void
    {
        $this->hajjCategory();

        foreach ([
            route('admin.package-templates.index'),
            route('admin.library.index', 'hotels'),
            route('admin.help'),
        ] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }
}
