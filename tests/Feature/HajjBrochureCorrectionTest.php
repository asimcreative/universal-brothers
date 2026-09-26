<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageAccommodation;
use App\Models\PackageItineraryDay;
use App\Models\PackageRoomOption;
use App\Models\PackageVariant;
use Database\Seeders\HajjBrochureCorrectionSeeder;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Issue #5 — the database held prices and hotel names from the superseded
 * 20 Aug 2026 brochure.
 *
 * Two halves have to stay true together, and the way this comes back is one of
 * them being updated without the other:
 *
 *   1. HajjPackageSeeder must produce the corrected figures, so a fresh
 *      install is right from the start.
 *   2. HajjBrochureCorrectionSeeder must repair an environment that was
 *      already seeded from the old deck — production above all.
 *
 * So these tests assert the corrected values against a FRESH seed (half one),
 * and separately drive the correction seeder over deliberately stale rows
 * (half two), rather than trusting whatever the local database happens to
 * hold right now.
 */
class HajjBrochureCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Read off "HAJJ 2027 Packages US$.pdf" (25 Aug 2026), which supersedes the
     * 20 Aug deck the database was originally seeded from.
     *
     * code => variant => sharing type => USD
     */
    private const BROCHURE_USD = [
        'UB006' => [
            'A' => ['quad' => 15350, 'triple' => 15750, 'double' => 18500],
            'B' => ['quad' => 13850, 'triple' => 15200, 'double' => 17950],
        ],
        'UB008' => [
            'A' => ['sharing_room' => null, 'quad' => 13250, 'triple' => 14600, 'double' => 17900],
            'B' => ['sharing_room' => 12450, 'quad' => 12450, 'triple' => 14050, 'double' => 16990],
        ],
        'UB010' => [
            'A' => ['sharing_room' => null, 'quad' => 12890, 'triple' => 14050, 'double' => 16990],
            'B' => ['sharing_room' => 12200, 'quad' => 12200, 'triple' => 13790, 'double' => 16450],
        ],
        // Unchanged by the new deck — included so a careless "fix" that moved
        // every price would be caught, not just one that moved none.
        'UB001' => [
            'A' => ['quad' => null, 'triple' => 22450, 'double' => 26850],
            'B' => ['quad' => 16300, 'triple' => 18100, 'double' => 20850],
        ],
    ];

    /**
     * The real pipeline, not factories: HajjPackageSeeder needs its category
     * and series rows, and the PKR/SAR assertions need HajjPriceCurrencySeeder,
     * both of which DatabaseSeeder wires up in the right order.
     *
     * HajjBrochureCorrectionSeeder is deliberately NOT part of that chain — it
     * repairs environments seeded from the old deck, and a fresh seed must be
     * correct without it. Every "fresh seed" assertion below would pass
     * spuriously if the correction seeder were quietly fixing it up.
     */
    private function seedPackages(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
    }

    private function usd(string $code, string $variantCode, string $sharingType): ?float
    {
        $package = Package::where('code', $code)->firstOrFail();
        $variantId = PackageVariant::where('package_id', $package->id)
            ->where('code', $variantCode)
            ->value('id');

        $value = PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantId)
            ->where('sharing_type', $sharingType)
            ->value('price_usd');

        return $value === null ? null : (float) $value;
    }

    public function test_a_fresh_seed_matches_the_current_brochure(): void
    {
        $this->seedPackages();

        foreach (self::BROCHURE_USD as $code => $variants) {
            foreach ($variants as $variantCode => $rooms) {
                foreach ($rooms as $sharingType => $expected) {
                    $this->assertSame(
                        $expected === null ? null : (float) $expected,
                        $this->usd($code, $variantCode, $sharingType),
                        "{$code} variant {$variantCode} {$sharingType} does not match the 25 Aug 2026 US\$ brochure."
                    );
                }
            }
        }
    }

    public function test_the_superseded_quad_price_is_gone(): void
    {
        $this->seedPackages();

        // 18850 was above UB006-B's own double price (17950), so it could never
        // have been a real quad rate. Named explicitly because it is the single
        // figure most likely to be reintroduced by a careless merge.
        $this->assertNotSame(18850.0, $this->usd('UB006', 'B', 'quad'));
        $this->assertSame(13850.0, $this->usd('UB006', 'B', 'quad'));
    }

    public function test_a_fresh_seed_carries_the_renamed_hotels(): void
    {
        $this->seedPackages();

        foreach (['Taibah Front / Similar', 'Abraaj Tower / Swiss Maqam'] as $retired) {
            $this->assertSame(
                0,
                PackageAccommodation::where('hotel_name', $retired)->count(),
                "'{$retired}' is no longer in the client's brochure and must not be seeded."
            );
        }

        $this->assertSame(
            4,
            PackageAccommodation::where('hotel_name', 'Dallah Taibah (Premier Floor)')->value('star_rating'),
            'The renamed hotel must carry its upgraded star rating, not the old one.'
        );

        // The itinerary keeps its own copy of the label, and a rename that
        // reaches the accommodation rows but not the itinerary leaves the old
        // name on the part of the page people actually read.
        $this->assertSame(0, PackageItineraryDay::where('accommodation_a', 'like', '%Taibah Front%')->count());
        $this->assertSame(0, PackageItineraryDay::where('accommodation_b', 'like', '%Taibah Front%')->count());
        $this->assertSame(0, PackageItineraryDay::where('accommodation_a', 'like', '%Abraaj Tower%')->count());

        $this->assertGreaterThan(0, PackageItineraryDay::where('accommodation_b', 'like', '%Dallah Taibah (Premier Floor)%')->count());
        $this->assertGreaterThan(0, PackageItineraryDay::where('accommodation_a', 'like', '%Pullman Zamzam Makkah%')->count());
    }

    public function test_unrelated_hotels_are_left_alone(): void
    {
        $this->seedPackages();

        // UB024's Medinah hotel also contains the words "Dallah Taibah" but is
        // a different, unchanged label. A rename done with a loose match would
        // have rewritten it.
        //
        // Asked of UB024 itself rather than by counting the label across the
        // catalogue: three packages print it now, and a count would have to
        // be revised every time another one does — which says nothing about
        // whether the rename stayed where it belonged.
        $ub024 = Package::where('code', 'UB024')->firstOrFail();
        $this->assertSame(
            ['Al Aqeeq / Dallah Taibah / Similar'],
            $ub024->accommodations()->where('location', 'medinah')->pluck('hotel_name')->unique()->values()->all()
        );
    }

    public function test_the_correction_seeder_repairs_an_already_seeded_database(): void
    {
        $this->seedPackages();

        // Put the superseded figures and names back, exactly as a database
        // seeded from the 20 Aug deck holds them.
        $package = Package::where('code', 'UB010')->firstOrFail();
        $variantB = PackageVariant::where('package_id', $package->id)->where('code', 'B')->value('id');

        PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantB)
            ->where('sharing_type', 'double')
            ->update(['price_usd' => 16850]);

        PackageAccommodation::where('hotel_name', 'Dallah Taibah (Premier Floor)')
            ->update(['hotel_name' => 'Taibah Front / Similar', 'star_rating' => 3]);

        PackageItineraryDay::where('accommodation_b', 'Dallah Taibah (Premier Floor) ★★★★')
            ->update(['accommodation_b' => 'Taibah Front / Similar ★★★']);

        $this->assertSame(16850.0, $this->usd('UB010', 'B', 'double'));

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame(16450.0, $this->usd('UB010', 'B', 'double'));
        $this->assertSame(0, PackageAccommodation::where('hotel_name', 'Taibah Front / Similar')->count());
        $this->assertSame(0, PackageItineraryDay::where('accommodation_b', 'like', '%Taibah Front%')->count());
    }

    public function test_the_correction_seeder_never_overwrites_an_edited_price(): void
    {
        $this->seedPackages();

        // A price the client has since set themselves in the admin is neither
        // the superseded value nor the brochure's, and must survive.
        $package = Package::where('code', 'UB010')->firstOrFail();
        $variantB = PackageVariant::where('package_id', $package->id)->where('code', 'B')->value('id');

        PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantB)
            ->where('sharing_type', 'double')
            ->update(['price_usd' => 15999]);

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame(
            15999.0,
            $this->usd('UB010', 'B', 'double'),
            'The correction seeder overwrote a price that was neither the old brochure value nor the new one.'
        );
    }

    public function test_the_correction_seeder_is_idempotent(): void
    {
        $this->seedPackages();
        $this->seed(HajjBrochureCorrectionSeeder::class);

        $before = PackageRoomOption::orderBy('id')->pluck('price_usd', 'id')->toArray();
        $beforeHotels = PackageAccommodation::orderBy('id')->pluck('hotel_name', 'id')->toArray();

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame($before, PackageRoomOption::orderBy('id')->pluck('price_usd', 'id')->toArray());
        $this->assertSame($beforeHotels, PackageAccommodation::orderBy('id')->pluck('hotel_name', 'id')->toArray());
    }

    public function test_the_published_offer_price_follows_the_corrected_rooms(): void
    {
        $this->seedPackages();

        // starting_price is derived from the room options, and the detail page
        // publishes it as the Schema.org Offer price. A correction that moved
        // the room prices but not this would show the right figure in the
        // table while handing the superseded one to search engines — which is
        // exactly what happened on the first pass at this fix.
        foreach (['UB006' => 13850.0, 'UB008' => 12450.0, 'UB010' => 12200.0] as $code => $expected) {
            $package = Package::where('code', $code)->firstOrFail();

            $this->assertSame(
                $expected,
                (float) $package->starting_price,
                "{$code}'s starting price does not match its cheapest corrected room."
            );
        }

        $package = Package::where('code', 'UB010')->firstOrFail();

        // The Offer now states the price in the currency the page is being
        // read in, and says which one it is. It used to publish the dollar
        // figure with `priceCurrency: USD` to every visitor, including one
        // reading a page quoting rupees — a structured-data claim that
        // contradicted the page carrying it.
        $dollars = $this->withSession([Currency::SESSION_KEY => 'USD'])
            ->get(route('packages.show', ['category' => 'hajj', 'package' => $package->slug]));
        $dollars->assertOk();
        $dollars->assertSee('"price":12200', false);
        $dollars->assertSee('"priceCurrency":"USD"', false);
        $dollars->assertDontSee('"price":12750', false);

        $rupees = $this->withSession([Currency::SESSION_KEY => 'PKR'])
            ->get(route('packages.show', ['category' => 'hajj', 'package' => $package->slug]));
        $rupees->assertOk();
        $rupees->assertSee('"price":'.$package->startingPriceIn('PKR'), false);
        $rupees->assertSee('"priceCurrency":"PKR"', false);
        $rupees->assertDontSee('"priceCurrency":"USD"', false);
    }

    public function test_the_correction_seeder_repairs_a_stale_starting_price(): void
    {
        $this->seedPackages();

        $package = Package::where('code', 'UB010')->firstOrFail();
        $variantB = PackageVariant::where('package_id', $package->id)->where('code', 'B')->value('id');

        // A database seeded from the old deck: cheapest room 12750, and
        // starting_price derived from it.
        PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantB)
            ->whereIn('sharing_type', ['sharing_room', 'quad'])
            ->update(['price_usd' => 12750]);
        $package->forceFill(['starting_price' => 12750])->save();

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame(12200.0, (float) $package->fresh()->starting_price);
    }

    public function test_it_repairs_a_starting_price_left_behind_by_the_earlier_fix(): void
    {
        $this->seedPackages();

        // Production's exact state after the first half of this fix shipped:
        // the room prices are already corrected, but starting_price still
        // holds the superseded figure, so the visible table and the published
        // Offer price disagree. A guard keyed off "the value before this run"
        // would skip this case entirely.
        $package = Package::where('code', 'UB010')->firstOrFail();
        $package->forceFill(['starting_price' => 12750])->save();

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame(12200.0, (float) $package->fresh()->starting_price);
    }

    public function test_an_editorial_starting_price_survives(): void
    {
        $this->seedPackages();

        // starting_price is an admin-editable field. A value that is not the
        // old derived minimum was set on purpose.
        $package = Package::where('code', 'UB010')->firstOrFail();
        $package->forceFill(['starting_price' => 9999])->save();

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $this->assertSame(
            9999.0,
            (float) $package->fresh()->starting_price,
            'The correction seeder overwrote a starting price that had been set deliberately.'
        );
    }

    public function test_pkr_and_sar_are_not_touched(): void
    {
        $this->seedPackages();

        $package = Package::where('code', 'UB010')->firstOrFail();
        $variantB = PackageVariant::where('package_id', $package->id)->where('code', 'B')->value('id');

        PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantB)
            ->where('sharing_type', 'double')
            ->update(['price_usd' => 16850, 'price_pkr' => 4680000, 'price_sar' => 60000]);

        $this->seed(HajjBrochureCorrectionSeeder::class);

        $row = PackageRoomOption::where('package_id', $package->id)
            ->where('variant_id', $variantB)
            ->where('sharing_type', 'double')
            ->firstOrFail();

        $this->assertSame(16450.0, (float) $row->price_usd, 'USD should have been corrected.');
        $this->assertSame(
            4680000.0,
            (float) $row->price_pkr,
            'PKR comes from the 7 Sep deck, which still prints the older basis. Converting it ourselves would '
            .'publish a figure that appears in no brochure.'
        );
        $this->assertSame(60000.0, (float) $row->price_sar, 'SAR must be left as the Riyal deck prints it.');
    }
}
