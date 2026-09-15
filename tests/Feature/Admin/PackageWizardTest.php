<?php

namespace Tests\Feature\Admin;

use App\Models\Hotel;
use App\Models\MashaerLocation;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageTemplate;
use App\Models\ServiceItem;
use App\Models\User;
use App\Support\Library\LibraryRegistry;
use App\Support\Library\PackageBuilderData;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageFormState;
use App\Support\Packages\PackageReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The fourteen-step package wizard (issue #11): the live assessment the
 * builder asks for as the admin types, the Review and Publish steps, the
 * checklist, resuming where the admin stopped, reusable-content usage and
 * copies, and internal-notes privacy — all through the real HTTP routes.
 */
class PackageWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'tour_status' => 'completed']);
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
            'name' => 'Wizard Test Package',
            'code' => 'UB950',
            'duration_days' => 3,
            'medinah_first' => '1',
            'is_shifting' => '0',
            'aziziya' => ['status' => 'not_included'],
            'summary' => 'Three days for the test.',
            'meta_title' => 'Wizard Test Package | Universal Brothers',
            'meta_description' => 'A package for the wizard tests.',
            'variants' => [
                ['code' => 'A', 'label' => 'Swissotel'],
                ['code' => 'B', 'label' => 'Fairmont'],
            ],
            'accommodations' => [
                ['location' => 'medinah', 'variant_code' => '', 'hotel_name' => 'Dar Al Taqwa', 'star_rating' => 5, 'meal_plan' => 'Half board'],
                ['location' => 'makkah', 'variant_code' => 'A', 'hotel_name' => 'Swissotel Makkah', 'star_rating' => 5, 'meal_plan' => 'Half board'],
                ['location' => 'makkah', 'variant_code' => 'B', 'hotel_name' => 'Fairmont Clock Tower', 'star_rating' => 5, 'meal_plan' => 'Half board'],
            ],
            'room_options' => [
                ['variant_code' => 'A', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_usd' => '12000', 'price_sar' => '45000', 'price_pkr' => '3400000', 'is_available' => '1'],
                ['variant_code' => 'B', 'sharing_type' => 'quad', 'occupancy' => 4, 'display_label' => 'Quad Sharing', 'price_usd' => '15000', 'price_sar' => '56000', 'price_pkr' => '4200000', 'is_available' => '1'],
            ],
            'itinerary' => [
                ['day_number' => 1, 'date_gregorian' => '2027-05-07', 'city' => 'Madinah', 'accommodation_a' => 'Dar Al Taqwa'],
                ['day_number' => 2, 'date_gregorian' => '2027-05-08', 'city' => 'To Makkah', 'accommodation_a' => 'Swissotel Makkah', 'accommodation_b' => 'Fairmont Clock Tower', 'transport' => 'Bullet train'],
                ['day_number' => 3, 'date_gregorian' => '2027-05-09', 'city' => 'Makkah', 'accommodation_a' => 'Swissotel Makkah', 'accommodation_b' => 'Fairmont Clock Tower'],
            ],
            'mashaer' => ['mina' => ['zone' => 'Zone 1', 'category' => 'Category A']],
            'transportation' => [['from_location' => 'Madinah', 'to_location' => 'Makkah', 'transport_type' => 'train', 'is_included' => '1']],
            'inclusions' => [['description' => 'Ziyarat in Madinah with guidance']],
            'exclusions' => [['description' => 'Airline ticket']],
            'notes' => [['note_type' => 'important', 'content' => 'Ticket & Qurbani not included.', 'is_important' => '1']],
        ], $overrides);
    }

    private function createPackage(array $overrides = [], string $intent = 'draft'): Package
    {
        $this->actingAs($this->admin)
            ->post(route('admin.hajj-packages.store'), $this->payload($overrides) + ['_intent' => $intent])
            ->assertSessionHasNoErrors();

        return Package::where('code', $overrides['code'] ?? 'UB950')->firstOrFail();
    }

    private function messages(array $items): string
    {
        return implode(' | ', array_column($items, 'message'));
    }

    // ------------------------------------------------------------------
    // The wizard's shape
    // ------------------------------------------------------------------

    public function test_the_builder_has_fourteen_steps_each_with_a_need_help_panel(): void
    {
        $this->hajjCategory();

        $response = $this->actingAs($this->admin)->get(route('admin.hajj-packages.create'))->assertOk();

        $this->assertCount(14, PackageCompleteness::STEPS);
        foreach (PackageCompleteness::STEPS as $key => $title) {
            $response->assertSee('data-step-button="'.$key.'"', false);
            $response->assertSee('data-need-help="'.$key.'"', false);
        }
        $response->assertSee('Step 13');
        $response->assertSee('Step 14');
        $response->assertSee('What is this for?');
        $response->assertSee('Is it required?');
        $response->assertSee('What happens after saving?');
        // Phones get one compact line instead of the full list.
        $response->assertSee('data-steps-toggle', false);
        $response->assertSee('Step <span data-steps-toggle-index>1</span> of 14', false);
        $response->assertSee('Checklist');
    }

    public function test_templates_skip_the_photo_review_and_publish_steps(): void
    {
        $this->hajjCategory();

        $response = $this->actingAs($this->admin)->get(route('admin.package-templates.create'))->assertOk();

        foreach (['media', 'review', 'publish'] as $step) {
            $response->assertDontSee('data-step-button="'.$step.'"', false);
        }
        $response->assertSee('data-step-button="notes"', false);
        $response->assertDontSee('data-assess-url', false);
    }

    public function test_the_wizard_uses_plain_labels_for_arrival_services_and_private_notes(): void
    {
        $this->hajjCategory();

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.create'))
            ->assertSee('Arrive in Madinah')
            ->assertSee('Arrive in Jeddah')
            ->assertSee('Included in this package')
            ->assertSee('Not included in this package')
            ->assertSee('Internal admin notes are never shown to website visitors.')
            ->assertSee('Customer note')
            ->assertSee('Important policy')
            ->assertSee('Terms &amp; conditions', false)
            ->assertSee('Internal admin note')
            ->assertSee('Find a hotel')
            ->assertSee('A day looks like this');
    }

    // ------------------------------------------------------------------
    // Live assessment
    // ------------------------------------------------------------------

    public function test_assessing_an_empty_new_package_lists_what_blocks_publishing_and_saves_nothing(): void
    {
        $this->hajjCategory();

        $json = $this->actingAs($this->admin)
            ->postJson(route('admin.hajj-packages.assess'), ['name' => ''])
            ->assertOk()
            ->json();

        $this->assertFalse($json['can_publish']);
        $this->assertStringContainsString('Add a package title.', $this->messages($json['problems']));
        $this->assertStringContainsString('Add at least one hotel.', $this->messages($json['problems']));
        $this->assertCount(13, $json['checklist']);
        $this->assertSame(
            ['Basic information completed', 'Package configuration completed', 'Package options added', 'Room prices added', 'Accommodation selected', 'Itinerary completed', 'Mashaer information added', 'Transport and meals selected', 'Inclusions and exclusions reviewed', 'Notes reviewed', 'Images uploaded', 'SEO completed', 'Final review completed'],
            array_column($json['checklist'], 'label'),
        );
        $this->assertLessThan(20, $json['percent']);
        $this->assertStringContainsString('must be fixed before publishing', $json['review_html']);
        $this->assertSame(0, Package::count());
    }

    public function test_a_complete_form_can_publish_and_its_checklist_is_nearly_done(): void
    {
        $json = $this->actingAs($this->admin)
            ->postJson(route('admin.hajj-packages.assess'), $this->payload() + ['_new_cover' => '1'])
            ->assertOk()
            ->json();

        $this->assertTrue($json['can_publish'], $this->messages($json['problems']));
        $undone = collect($json['checklist'])->where('done', false)->pluck('key')->all();
        $this->assertSame(['review'], $undone, 'Only the final review should be left');
        $this->assertSame(92, $json['percent']);
        $this->assertStringContainsString('Nothing blocks publishing.', $json['review_html']);
    }

    public function test_assessing_an_existing_package_judges_the_unsaved_form_not_the_saved_package(): void
    {
        $package = $this->createPackage();
        $before = PackageFormState::fromPackage($package->fresh());

        $json = $this->actingAs($this->admin)
            ->postJson(route('admin.hajj-packages.assess-existing', $package), $this->payload(['room_options' => [
                ['variant_code' => 'A', 'sharing_type' => 'quad', 'price_usd' => '', 'price_sar' => '', 'price_pkr' => '', 'is_available' => '1'],
                ['variant_code' => 'B', 'sharing_type' => 'quad', 'price_usd' => '', 'price_sar' => '', 'price_pkr' => '', 'is_available' => '1'],
            ]]))
            ->assertOk()
            ->json();

        $this->assertFalse($json['can_publish']);
        $this->assertStringContainsString('Add at least one available room type with a price.', $this->messages($json['problems']));
        $this->assertEquals($before, PackageFormState::fromPackage($package->fresh()), 'Assessing must never save');
    }

    public function test_the_review_warns_about_the_things_worth_checking(): void
    {
        $payload = $this->payload([
            'duration_days' => 14,
            'room_options' => [
                1 => ['price_sar' => '', 'price_pkr' => ''],
            ],
            'accommodations' => [
                2 => ['variant_code' => '', 'meal_plan' => ''],
            ],
            'itinerary' => [
                2 => ['date_gregorian' => '2027-05-01'],
            ],
        ]);
        $payload['inclusions'] = [];

        $json = $this->actingAs($this->admin)->postJson(route('admin.hajj-packages.assess'), $payload)->assertOk()->json();

        $warnings = $this->messages($json['warnings']);

        $this->assertStringContainsString('The package is 14 days but the journey plan has 3 days.', $warnings);
        $this->assertStringContainsString("Day 3's date (01/05/2027) is not after day 2's.", $warnings);
        $this->assertStringContainsString('1 room price has no SAR amount.', $warnings);
        $this->assertStringContainsString('1 room price has no PKR amount.', $warnings);
        $this->assertStringContainsString('Option B has no hotel of its own yet.', $warnings);
        $this->assertStringContainsString('Fairmont Clock Tower has no meal plan.', $warnings);
        $this->assertStringContainsString('There is no main photo.', $warnings);
        $this->assertStringContainsString('Nothing is listed as included.', $warnings);

        // Advice never blocks publishing.
        $this->assertTrue($json['can_publish']);

        // Every warning offers a way back to the step that fixes it.
        $this->assertStringContainsString('data-step-link="journey"', $json['review_html']);
        $this->assertStringContainsString('data-step-link="pricing"', $json['review_html']);
        $this->assertStringContainsString('data-step-link="hotels"', $json['review_html']);
        $this->assertStringContainsString('data-step-link="media"', $json['review_html']);
    }

    public function test_an_available_room_without_any_price_and_an_unnamed_option_are_pointed_out(): void
    {
        $json = $this->actingAs($this->admin)->postJson(route('admin.hajj-packages.assess'), $this->payload([
            'variants' => [1 => ['label' => '']],
            'room_options' => [2 => ['variant_code' => 'B', 'sharing_type' => 'triple', 'display_label' => 'Triple Sharing', 'is_available' => '1']],
        ]))->assertOk()->json();

        $warnings = $this->messages($json['warnings']);
        $this->assertStringContainsString('Triple Sharing (Option B) is marked available but has no price.', $warnings);
        $this->assertStringContainsString('Option B has no name.', $warnings);
    }

    /** The reported brochure case: UB004 is sold as 14 days, but its printed plan lists 13. */
    public function test_ub004_is_publishable_but_the_review_points_out_its_thirteen_day_plan(): void
    {
        Artisan::call('db:seed');
        $package = Package::where('code', 'UB004')->firstOrFail();

        $review = PackageReview::forPackage($package);

        $this->assertTrue($review->canPublish());
        $this->assertStringContainsString('The package is 14 days but the journey plan has 13 days.', $this->messages($review->warnings()));

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'review']))
            ->assertOk()
            ->assertSee('The package is 14 days but the journey plan has 13 days.');
    }

    public function test_internal_notes_never_appear_in_the_review_or_the_assessment(): void
    {
        $secret = 'Supplier rate 9,999 SAR — do not share';
        $package = $this->createPackage(['internal_notes' => $secret]);

        $json = $this->actingAs($this->admin)
            ->postJson(route('admin.hajj-packages.assess-existing', $package), $this->payload(['internal_notes' => $secret]))
            ->assertOk()
            ->json();

        $this->assertStringNotContainsString('Supplier rate', json_encode($json));
        $this->assertStringContainsString('Internal admin notes: written', $json['review_html']);

        $html = $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'review']))->getContent();
        preg_match('/<div class="review-body" data-review-body>(.*?)<p class="form-help mt-2">/s', $html, $reviewBody);
        $this->assertNotEmpty($reviewBody);
        $this->assertStringNotContainsString('Supplier rate', $reviewBody[1]);

        // And never on the public page or its preview.
        $package->forceFill(['status' => 'published', 'published_at' => now()])->save();
        $this->get(route('packages.show', ['category' => 'hajj', 'package' => $package->slug]))->assertOk()->assertDontSee('Supplier rate');
    }

    public function test_guests_cannot_assess(): void
    {
        $this->hajjCategory();
        $this->postJson(route('admin.hajj-packages.assess'), ['name' => 'x'])->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Review, publish, resume
    // ------------------------------------------------------------------

    public function test_saving_from_the_review_step_ticks_final_review_until_the_package_changes(): void
    {
        $package = $this->createPackage();
        $this->assertFalse(PackageReview::isReviewed($package->fresh()));

        $this->actingAs($this->admin)
            ->put(route('admin.hajj-packages.update', $package), $this->payload() + ['_intent' => 'save', '_step' => 'review', '_reviewed' => '1'])
            ->assertSessionHasNoErrors();

        $package->refresh();
        $this->assertNotNull($package->reviewed_hash);
        $this->assertTrue(PackageReview::isReviewed($package));
        $this->assertTrue(collect(PackageReview::forPackage($package)->checklist())->firstWhere('key', 'review')['done']);

        // Publishing is not a content change.
        $this->actingAs($this->admin)->patch(route('admin.hajj-packages.quick', [$package, 'publish']))->assertSessionHasNoErrors();
        $this->assertTrue(PackageReview::isReviewed($package->fresh()));

        // Changing a price is.
        $this->actingAs($this->admin)
            ->put(route('admin.hajj-packages.update', $package), $this->payload(['room_options' => [['price_usd' => '12500']]]) + ['_intent' => 'save'])
            ->assertSessionHasNoErrors();
        $this->assertFalse(PackageReview::isReviewed($package->fresh()));
    }

    public function test_an_incomplete_package_is_never_marked_reviewed(): void
    {
        $this->hajjCategory();
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), ['name' => 'Half Done', '_intent' => 'draft', '_step' => 'review', '_reviewed' => '1']);

        $package = Package::where('name', 'Half Done')->firstOrFail();
        $this->assertNull($package->reviewed_hash);
    }

    public function test_the_publish_step_explains_the_three_ways_to_finish_and_holds_back_publish_until_ready(): void
    {
        $this->hajjCategory();
        $this->actingAs($this->admin)->post(route('admin.hajj-packages.store'), ['name' => 'Not Ready', '_intent' => 'draft']);
        $incomplete = Package::where('name', 'Not Ready')->firstOrFail();

        $html = $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', ['package' => $incomplete, 'step' => 'publish']))
            ->assertOk()
            ->assertSee('Save as draft')
            ->assertSee('Preview package')
            ->assertSee('Publish package')
            ->assertSee('Fix these first:')
            ->getContent();
        $this->assertMatchesRegularExpression('/data-publish-button[^>]*disabled/s', $html);

        // The server refuses too, whatever the browser does.
        $this->actingAs($this->admin)
            ->put(route('admin.hajj-packages.update', $incomplete), ['name' => 'Not Ready', '_intent' => 'publish'])
            ->assertSessionHasErrors();
        $this->assertSame('draft', $incomplete->fresh()->status);

        $ready = $this->createPackage();
        $html = $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', ['package' => $ready, 'step' => 'publish']))->getContent();
        $this->assertDoesNotMatchRegularExpression('/data-publish-button[^>]*disabled/s', $html);
    }

    public function test_a_draft_reopens_on_the_step_where_it_was_saved(): void
    {
        $package = $this->createPackage();

        $this->actingAs($this->admin)
            ->put(route('admin.hajj-packages.update', $package), $this->payload() + ['_intent' => 'draft', '_step' => 'hotels'])
            ->assertRedirect(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'hotels']));
        $this->assertSame('hotels', $package->fresh()->builder_step);

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', $package))
            ->assertOk()
            ->assertSee('data-initial-step="hotels"', false)
            ->assertSee('where you saved last time');

        // "Save & continue" remembers the step it moves on to.
        $this->actingAs($this->admin)->put(route('admin.hajj-packages.update', $package), $this->payload() + ['_intent' => 'continue', '_step' => 'media']);
        $this->assertSame('review', $package->fresh()->builder_step);

        // An explicit step always wins, and live packages open at the start.
        $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'notes']))->assertSee('data-initial-step="notes"', false);
        $package->forceFill(['status' => 'published'])->save();
        $this->actingAs($this->admin)->get(route('admin.hajj-packages.edit', $package))->assertSee('data-initial-step="basics"', false)->assertDontSee('where you saved last time');
    }

    public function test_the_package_list_and_dashboard_show_how_complete_each_draft_is_with_a_continue_link(): void
    {
        $package = $this->createPackage();
        $package->forceFill(['builder_step' => 'mashaer'])->save();
        $percent = PackageReview::forPackage($package->fresh())->percent();

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.index'))
            ->assertOk()
            ->assertSee($percent.'% complete')
            ->assertSee('aria-label="Continue Wizard Test Package"', false)
            ->assertSee(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'mashaer']), false);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Unfinished drafts')
            ->assertSee('Wizard Test Package')
            ->assertSee($percent.'%')
            ->assertSee(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'mashaer']), false);
    }

    // ------------------------------------------------------------------
    // Journey transport
    // ------------------------------------------------------------------

    public function test_transport_on_a_journey_day_is_saved_and_shown_to_visitors(): void
    {
        $package = $this->createPackage([], 'publish');

        $day = $package->itineraryDays()->where('day_number', 2)->firstOrFail();
        $this->assertSame('Bullet train', $day->transport);
        $this->assertSame('Bullet train', PackageFormState::fromPackage($package->fresh())['itinerary'][1]['transport']);

        $this->get(route('packages.show', ['category' => 'hajj', 'package' => $package->slug]))
            ->assertOk()
            ->assertSee('Bullet train');
    }

    // ------------------------------------------------------------------
    // Reusable content
    // ------------------------------------------------------------------

    public function test_saved_content_shows_how_many_packages_use_it_counting_each_package_once(): void
    {
        $this->hajjCategory();
        $hotel = Hotel::create(['name' => 'Dar Al Taqwa', 'slug' => 'dar-al-taqwa', 'city' => 'Madinah', 'location' => 'medinah', 'star_rating' => 5, 'is_active' => true]);
        $mina = MashaerLocation::create(['name' => 'Mina — Zone 1', 'location' => 'mina', 'zone' => 'Zone 1', 'is_active' => true]);
        $service = ServiceItem::create(['type' => 'inclusion', 'title' => 'Ziyarat', 'description' => 'Ziyarat in Madinah with guidance', 'is_active' => true]);

        $withHotel = fn (string $code) => [
            'code' => $code,
            'accommodations' => [
                ['location' => 'medinah', 'variant_code' => 'A', 'hotel_id' => $hotel->id, 'hotel_name' => 'Dar Al Taqwa'],
                ['location' => 'medinah', 'variant_code' => 'B', 'hotel_id' => $hotel->id, 'hotel_name' => 'Dar Al Taqwa'],
            ],
            'mashaer' => ['mina' => ['mashaer_location_id' => $mina->id, 'zone' => 'Zone 1']],
            'inclusions' => [['service_item_id' => $service->id, 'description' => 'Ziyarat in Madinah with guidance']],
        ];
        $this->createPackage($withHotel('UB951'));
        $deleted = $this->createPackage($withHotel('UB952'));
        $this->createPackage($withHotel('UB953'));
        $deleted->delete();

        $data = json_decode(json_encode(PackageBuilderData::for(new Package)), true);

        $this->assertSame(2, collect($data['hotels'])->firstWhere('id', $hotel->id)['used']);
        $this->assertSame(2, collect($data['mashaer'])->firstWhere('id', $mina->id)['used']);
        $this->assertSame(2, collect($data['inclusions'])->firstWhere('id', $service->id)['used']);
        $this->assertSame(route('admin.library.edit', ['mashaer', $mina->id]), collect($data['mashaer'])->firstWhere('id', $mina->id)['edit_url']);
        $this->assertSame(LibraryRegistry::find('hotels')->packagesUsing($hotel)->count(), collect($data['hotels'])->firstWhere('id', $hotel->id)['used']);
    }

    public function test_editing_shared_content_offers_update_or_a_separate_copy(): void
    {
        $this->hajjCategory();
        $hotel = Hotel::create(['name' => 'Swissotel Makkah', 'slug' => 'swissotel-makkah', 'city' => 'Makkah', 'location' => 'makkah', 'star_rating' => 5, 'is_active' => true]);
        $package = $this->createPackage(['accommodations' => [1 => ['hotel_id' => $hotel->id]]]);

        $this->actingAs($this->admin)->get(route('admin.library.edit', ['hotels', $hotel->id]))
            ->assertOk()
            ->assertSee('This is shared information. Used in 1 package.')
            ->assertSee('Update shared record')
            ->assertSee('Save as a new separate record')
            ->assertSee('Cancel');

        $this->actingAs($this->admin)
            ->put(route('admin.library.update', ['hotels', $hotel->id]), [
                'name' => 'Swissotel Al Maqam', 'location' => 'makkah', 'star_rating' => 5, 'is_active' => '1', 'sort_order' => 0,
                '_save_as' => 'copy',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $copy = Hotel::where('name', 'Swissotel Al Maqam')->firstOrFail();
        $this->assertNotSame($hotel->id, $copy->id);
        $this->assertSame('Swissotel Makkah', $hotel->fresh()->name, 'The original must not change');
        $this->assertSame('Swissotel Makkah', $package->accommodations()->where('hotel_id', $hotel->id)->value('hotel_name'), 'Packages must not change');
        $this->assertSame(0, LibraryRegistry::find('hotels')->packagesUsing($copy)->count());
    }

    public function test_a_copy_that_keeps_the_same_name_is_labelled_as_a_copy(): void
    {
        $this->hajjCategory();
        $hotel = Hotel::create(['name' => 'Fairmont Clock Tower', 'slug' => 'fairmont', 'city' => 'Makkah', 'location' => 'makkah', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('admin.library.update', ['hotels', $hotel->id]), [
            'name' => 'Fairmont Clock Tower', 'location' => 'makkah', 'star_rating' => 4, 'is_active' => '1', 'sort_order' => 0, '_save_as' => 'copy',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hotel::where('name', 'Fairmont Clock Tower (Copy)')->where('star_rating', 4)->exists());
        $this->assertNull($hotel->fresh()->star_rating);
    }

    public function test_a_template_created_package_starts_in_the_wizard_with_review_available(): void
    {
        $this->hajjCategory();
        $template = PackageTemplate::create(['name' => '14 days Madinah first', 'payload' => PackageFormState::forTemplate($this->payload()), 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.hajj-packages.create', ['template' => $template->id]))
            ->assertOk()
            ->assertSee('Started from the template')
            ->assertSee('data-step-button="review"', false)
            ->assertSee('Swissotel Makkah');
    }
}
