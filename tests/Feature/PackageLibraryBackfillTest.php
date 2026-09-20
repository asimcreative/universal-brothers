<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\MashaerLocation;
use App\Models\MealPlan;
use App\Models\NoteTemplate;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;
use App\Support\Library\LibraryBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration + seeder that builds the reusable library from the live Hajj
 * packages (issue #10). It runs on production data, so it must link every row,
 * never duplicate a record, never change what visitors see except the one
 * documented move of internal notes, and be safe to run again.
 */
class PackageLibraryBackfillTest extends TestCase
{
    use RefreshDatabase;

    private const LINKS = [
        'package_accommodations' => 'hotel_id',
        'package_transportation' => 'transport_option_id',
        'package_features' => 'service_item_id',
        'package_upgrades' => 'upgrade_option_id',
        'package_notes' => 'note_template_id',
        'package_mashaer_details' => 'mashaer_location_id',
    ];

    private function libraryCounts(): array
    {
        return [
            Hotel::count(), MealPlan::count(), TransportOption::count(), ServiceItem::count(),
            UpgradeOption::count(), MashaerLocation::count(), NoteTemplate::count(),
        ];
    }

    public function test_seeding_links_every_live_hajj_row_to_a_library_record(): void
    {
        Artisan::call('db:seed');

        foreach (self::LINKS as $table => $column) {
            $unlinked = DB::table($table)
                ->join('packages', 'packages.id', '=', "{$table}.package_id")
                ->whereNull('packages.deleted_at')
                ->where('packages.package_category_id', PackageCategory::where('slug', 'hajj')->value('id'))
                ->whereNull("{$table}.{$column}")
                ->count();

            $this->assertSame(0, $unlinked, "{$table} has rows without a {$column}");
        }

        // One record per distinct repeated value, not one per package.
        //
        // Stated as a ratio rather than as six fixed numbers. Those numbers
        // were the shape of a twelve-package seed and broke the day the other
        // fourteen arrived, which told us nothing except that the catalogue
        // had grown. What the backfill actually promises is that a value
        // printed on many packages becomes ONE library record: so there must
        // be fewer records than there are rows pointing at them.
        foreach (self::LINKS as $table => $column) {
            $rows = DB::table($table)
                ->join('packages', 'packages.id', '=', "{$table}.package_id")
                ->whereNull('packages.deleted_at')
                ->where('packages.package_category_id', PackageCategory::where('slug', 'hajj')->value('id'))
                ->count();

            if ($rows === 0) {
                continue;
            }

            $records = DB::table($table)
                ->join('packages', 'packages.id', '=', "{$table}.package_id")
                ->whereNull('packages.deleted_at')
                ->where('packages.package_category_id', PackageCategory::where('slug', 'hajj')->value('id'))
                ->distinct()
                ->count("{$table}.{$column}");

            $this->assertGreaterThan(0, $records, "{$table} links to no library record at all");
            $this->assertLessThan($rows, $records, "{$table} has a library record per row rather than per distinct value");
        }

        // The two Mina/Arafat meal wordings are library records of their own,
        // and the hotel meal plans are attached to accommodations.
        $this->assertGreaterThan(0, MealPlan::whereHas('accommodations')->count());
    }

    public function test_printed_hotel_names_are_matched_to_the_seeded_hotels_without_duplicates(): void
    {
        Artisan::call('db:seed');

        // The point of these is the matching, not the tally: a brochure that
        // prints "Dar Al Tawhid Intercontinental" must be recognised as the
        // seeded "… Makkah" hotel rather than quietly creating a second
        // hotel with almost the same name. So: linked at all, and exactly one
        // hotel of that name however many packages print it.
        $intercon = Hotel::where('slug', 'dar-al-tawhid-intercontinental-makkah')->firstOrFail();
        $this->assertGreaterThan(0, $intercon->accommodations()->count(), '"Dar Al Tawhid Intercontinental" must link to the seeded "… Makkah" hotel');
        $this->assertSame(1, Hotel::where('name', 'like', 'Dar Al Tawhid%')->count());

        $this->assertGreaterThan(0, Hotel::where('slug', 'makkah-tower')->firstOrFail()->accommodations()->count(), '"Makkah Tower" must link to "Makkah Tower (Hajar Tower)"');
        $this->assertSame('aziziya', Hotel::where('name', 'AZIZIYA Accommodation - A Class')->value('location'));
        $this->assertSame('medinah', Hotel::where('slug', 'dar-al-taqwa')->value('location'));

        // Hotels the current brochure replaced are archived, not deleted.
        $this->assertFalse(Hotel::where('slug', 'taibah-front-medinah')->value('is_active'));
        $this->assertFalse(Hotel::where('slug', 'abraaj-tower-swiss-maqam')->value('is_active'));
    }

    public function test_running_the_backfill_again_changes_nothing(): void
    {
        Artisan::call('db:seed');
        $counts = $this->libraryCounts();
        $links = collect(self::LINKS)->map(fn ($column, $table) => DB::table($table)->orderBy('id')->pluck($column, 'id')->all())->all();

        $result = (new LibraryBackfill)->run();

        $this->assertSame([], $result);
        $this->assertSame($counts, $this->libraryCounts());
        foreach (self::LINKS as $table => $column) {
            $this->assertSame($links[$table], DB::table($table)->orderBy('id')->pluck($column, 'id')->all());
        }

        // And a full re-seed on top is still stable.
        Artisan::call('db:seed');
        $this->assertSame($counts, $this->libraryCounts());
    }

    /**
     * The production database was seeded before the seeder fix, so it still
     * holds the brochure-audit remark as a public description and a public
     * note. The migration must move it — once — into internal notes.
     */
    public function test_existing_audit_remarks_move_from_public_text_into_internal_notes(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj']);
        $remark = 'Brochure inconsistency preserved as printed: headed "14 Days Package" but the itinerary table lists 13 numbered days (7-19 May).';
        $package = Package::factory()->create(['package_category_id' => $hajj->id, 'description' => $remark]);
        $package->packageNotes()->create(['note_type' => 'general', 'content' => $remark, 'is_important' => true]);
        $keep = $package->packageNotes()->create(['note_type' => 'important', 'content' => 'Ticket & Qurbani not included.']);

        (new LibraryBackfill)->run();
        (new LibraryBackfill)->run();

        $package->refresh();
        $this->assertNull($package->description);
        $this->assertSame($remark, $package->internal_notes, 'moved once, word for word, even when run twice');
        $this->assertSame([$keep->id], $package->packageNotes()->pluck('id')->all(), 'customer-facing notes are untouched');
        $this->assertNotNull($keep->fresh()->note_template_id);
    }

    public function test_the_public_pages_still_show_the_brochure_facts_after_the_backfill(): void
    {
        Artisan::call('db:seed');

        $package = Package::where('code', 'UB001')->firstOrFail();
        $this->get('/hajj/'.$package->slug)->assertOk()
            ->assertSee('Dar Al Tawhid Intercontinental')
            ->assertSee('Fairmont Clock Tower')
            ->assertSee('data-usd="26850.00"', false)
            ->assertSee('Jeddah Airport')
            ->assertSee('Means Abraj Tower');

        // The listing shows nine packages a page; the last brochure package is on page two.
        $this->get('/hajj?page=2')->assertOk()->assertSee('UB024');
    }
}
