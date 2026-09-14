<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\Hotel;
use App\Models\ItineraryTemplate;
use App\Models\Package;
use App\Models\PackageAccommodation;
use App\Models\PackageCategory;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\User;
use App\Support\Library\LibraryRegistry;
use App\Support\Library\PackageBuilderData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reusable package content (issue #10): every library section through its
 * real routes, usage counts, protection of in-use records, archiving, and the
 * explicit "update the packages that use this" action.
 */
class PackageLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        PackageCategory::firstOrCreate(['slug' => 'hajj'], ['name' => 'Hajj', 'is_active' => true]);
    }

    public static function libraryTypes(): array
    {
        return [
            'hotels' => ['hotels', ['name' => 'Hilton Suites Makkah', 'location' => 'makkah', 'star_rating' => '5', 'website_url' => 'https://example.com'], 'Hilton Suites Makkah'],
            'meal plans' => ['meal-plans', ['name' => 'Full board', 'includes_breakfast' => '1', 'includes_lunch' => '1', 'includes_dinner' => '1', 'is_included' => '1'], 'Full board'],
            'transport' => ['transport', ['name' => 'Madinah airport transfer', 'transport_type' => 'airport_transfer', 'is_included' => '0', 'price' => '40', 'currency' => 'USD'], 'Madinah airport transfer'],
            'inclusions' => ['inclusions', ['title' => 'Guide book', 'description' => 'Religious guide book', 'category' => 'guidance'], 'Guide book'],
            'exclusions' => ['exclusions', ['title' => 'Ticket', 'description' => 'Airline ticket', 'category' => 'visa_ticket'], 'Ticket'],
            'upgrades' => ['upgrades', ['name' => 'Kaaba view', 'price' => '2200', 'currency' => 'USD', 'price_basis' => 'per person'], 'Kaaba view'],
            'mashaer' => ['mashaer', ['name' => 'Mina — Zone 1', 'location' => 'mina', 'zone' => 'Zone 1'], 'Mina — Zone 1'],
            'notes' => ['notes', ['title' => 'Qurbani', 'category' => 'qurbani', 'note_type' => 'important', 'content' => 'Qurbani cost is not included.'], 'Qurbani'],
            'journey templates' => ['journey-templates', ['name' => '14 days', 'days' => [['day_number' => '1', 'city' => 'Madinah', 'date_hijri_label' => '01 Zil Hajj']]], '14 days'],
        ];
    }

    #[DataProvider('libraryTypes')]
    public function test_each_library_section_can_be_listed_created_edited_duplicated_archived_and_deleted(string $type, array $payload, string $title): void
    {
        $library = LibraryRegistry::find($type);

        $this->actingAs($this->admin)->get(route('admin.library.index', $type))->assertOk()->assertSee($library->label);
        $this->actingAs($this->admin)->get(route('admin.library.create', $type))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.library.store', $type), $payload + ['is_active' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.library.index', $type));

        $record = $library->query()->where($library->titleColumn(), $title)->firstOrFail();
        $this->actingAs($this->admin)->get(route('admin.library.index', $type))->assertSee($title);
        $this->actingAs($this->admin)->get(route('admin.library.edit', [$type, $record->id]))->assertOk()->assertSee($title);

        $this->actingAs($this->admin)->put(route('admin.library.update', [$type, $record->id]), array_merge($payload, [$library->titleColumn() => $title.' (edited)', 'is_active' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertSame($title.' (edited)', $record->fresh()->libraryTitle());

        $this->actingAs($this->admin)->post(route('admin.library.duplicate', [$type, $record->id]));
        $this->assertTrue($library->query()->where($library->titleColumn(), $title.' (edited) (Copy)')->exists());

        $this->actingAs($this->admin)->patch(route('admin.library.archive', [$type, $record->id]));
        $this->assertFalse($record->fresh()->is_active);
        $this->actingAs($this->admin)->get(route('admin.library.index', $type))->assertDontSee($title.' (edited)</a>', false);
        $this->actingAs($this->admin)->get(route('admin.library.index', ['type' => $type, 'status' => 'archived']))->assertSee($title.' (edited)');

        $this->actingAs($this->admin)->patch(route('admin.library.restore', [$type, $record->id]));
        $this->assertTrue($record->fresh()->is_active);

        $this->actingAs($this->admin)->delete(route('admin.library.destroy', [$type, $record->id]))->assertSessionHasNoErrors();
        $this->assertNull($library->query()->find($record->id));
    }

    public function test_inclusions_and_exclusions_cannot_reach_each_others_records(): void
    {
        $exclusion = ServiceItem::create(['type' => 'exclusion', 'title' => 'Ticket', 'description' => 'Airline ticket']);

        $this->actingAs($this->admin)->get(route('admin.library.edit', ['inclusions', $exclusion->id]))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.library.destroy', ['inclusions', $exclusion->id]))->assertNotFound();
        $this->assertNotNull($exclusion->fresh());
    }

    public function test_validation_explains_problems_in_plain_words(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.library.store', 'hotels'), ['name' => '', 'location' => 'moon', 'website_url' => 'not a link'])
            ->assertSessionHasErrors(['name', 'location', 'website_url']);

        $this->assertSame(0, Hotel::count());
    }

    public function test_a_hotel_photo_is_stored_and_replaced(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('admin.library.store', 'hotels'), [
            'name' => 'Photo Hotel', 'location' => 'makkah', 'cover_image' => UploadedFile::fake()->image('hotel.jpg'),
        ])->assertSessionHasNoErrors();

        $hotel = Hotel::where('name', 'Photo Hotel')->firstOrFail();
        Storage::disk('public')->assertExists($hotel->cover_image);
        $this->assertSame('photo-hotel', $hotel->slug);
        $this->assertSame('Makkah', $hotel->city);
    }

    public function test_usage_counts_protect_records_that_packages_use(): void
    {
        Artisan::call('db:seed');
        $library = LibraryRegistry::find('transport');
        $transport = TransportOption::where('transport_type', 'mashaer')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.library.index', 'transport'))->assertSee('12 packages');

        $usage = $this->actingAs($this->admin)->get(route('admin.library.usage', ['transport', $transport->id]));
        $usage->assertOk()->assertSee('UB001')->assertSee('Update 12 packages');

        $this->actingAs($this->admin)
            ->from(route('admin.library.index', 'transport'))
            ->delete(route('admin.library.destroy', ['transport', $transport->id]))
            ->assertSessionHasErrors('record');
        $this->assertNotNull($transport->fresh(), 'a record in use must never be deleted');

        // Archiving is allowed, removes it from the builder, and leaves packages alone.
        $this->actingAs($this->admin)->patch(route('admin.library.archive', ['transport', $transport->id]));
        $builderIds = collect(PackageBuilderData::for(new Package)['transport'])->pluck('id');
        $this->assertFalse($builderIds->contains($transport->id));
        $this->assertSame(12, $library->packagesUsing($transport)->count());
    }

    public function test_editing_a_library_record_does_not_change_packages_until_the_admin_updates_them(): void
    {
        Artisan::call('db:seed');
        $hotel = Hotel::where('name', 'Swissotel Makkah')->firstOrFail();
        $linkedRows = fn () => PackageAccommodation::where('hotel_id', $hotel->id);
        $this->assertSame(2, $linkedRows()->count());

        $this->actingAs($this->admin)->put(route('admin.library.update', ['hotels', $hotel->id]), [
            'name' => 'Swissotel Al Maqam Makkah', 'location' => 'makkah', 'star_rating' => '5', 'is_active' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($message) => str_contains($message, 'still show the previous details'));

        $this->assertSame(['Swissotel Makkah'], $linkedRows()->pluck('hotel_name')->unique()->values()->all(), 'saving the library record alone must not rewrite live packages');

        $this->actingAs($this->admin)->post(route('admin.library.push', ['hotels', $hotel->id]))->assertSessionHasNoErrors();

        $this->assertSame(['Swissotel Al Maqam Makkah'], $linkedRows()->pluck('hotel_name')->unique()->values()->all());
        $this->assertSame(0, PackageAccommodation::where('hotel_name', 'Swissotel Al Maqam Makkah')->whereNull('hotel_id')->count(), 'only linked rows change');
        $this->assertTrue(AdminActivity::where('action', 'library_pushed')->where('user_id', $this->admin->id)->exists());

        $package = Package::where('code', 'UB004')->firstOrFail();
        $this->get('/hajj/'.$package->slug)->assertOk()->assertSee('Swissotel Al Maqam Makkah');
    }

    public function test_a_hotel_can_be_added_from_inside_the_builder_as_json(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.library.store', 'hotels'), [
            'name' => 'New Madinah Hotel', 'location' => 'medinah', 'star_rating' => '4', 'is_active' => '1',
        ]);

        $response->assertCreated()->assertJsonPath('title', 'New Madinah Hotel')->assertJsonPath('record.location', 'medinah');
        $this->assertTrue(collect(PackageBuilderData::for(new Package)['hotels'])->contains('name', 'New Madinah Hotel'));

        $this->actingAs($this->admin)->postJson(route('admin.library.store', 'hotels'), ['name' => ''])->assertUnprocessable();
    }

    public function test_a_journey_template_keeps_its_days_in_order(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.library.store', 'journey-templates'), [
            'name' => 'Short Madinah first',
            'days' => [
                ['day_number' => '', 'city' => 'To Madinah', 'date_hijri_label' => '04 Zil Hajj'],
                ['day_number' => '', 'city' => '', 'date_hijri_label' => ''],
                ['day_number' => '', 'city' => 'Madinah', 'accommodation_a' => 'Dar Al Taqwa'],
            ],
        ])->assertCreated();

        $template = ItineraryTemplate::where('name', 'Short Madinah first')->firstOrFail();
        $this->assertSame([1, 2], array_column($template->days, 'day_number'), 'empty rows are dropped and days are numbered');
        $this->assertSame('Dar Al Taqwa', $template->days[1]['accommodation_a']);
    }

    public function test_every_library_section_appears_in_the_menu(): void
    {
        $page = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        foreach (LibraryRegistry::all() as $type) {
            $page->assertSee(route('admin.library.index', $type->key), false);
        }
    }
}
