<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the 12 real Hajj 2027 / 1448 AH packages using the redesigned,
 * brochure-accurate data model (Package A/B variants, dynamic sharing
 * types, a fully separate Aziziya sub-schema, Mina/Arafat detail,
 * transportation, notes, upgrades). Every fact here was transcribed
 * directly from "HAJJ 2027 Packages overseas.pdf" during a full page-by-
 * page re-read — see docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md for
 * the complete extraction this seeder is built from, including every
 * brochure inconsistency found (and deliberately preserved, not "fixed").
 *
 * The brochure is USD-only ("US$ PACKAGES (20 AUG 2026)" on its cover) — no
 * PKR or SAR package price appears anywhere in it. price_pkr/price_sar
 * columns exist for the CMS to support (per the business rule: one package,
 * three currencies) but are deliberately left null here; only price_usd is
 * ever seeded with a real value. Never invented.
 */
class HajjPackageSeeder extends Seeder
{
    private const SEASON_YEAR = 2027;
    private const SEASON_LABEL = 'Hajj 2027 / 1448 AH';

    /**
     * Create only the packages that are not in the database yet.
     *
     * Off by default: locally the point of re-running this seeder is to
     * refresh everything from the brochure. On the live site it is the
     * opposite — the twelve packages that were already there may have been
     * edited in the admin since, and this seeder replaces a package's
     * variants, itinerary, accommodation, rooms and notes wholesale. Adding
     * the fourteen new ones must not quietly undo the client's own edits to
     * the other twelve.
     */
    public bool $onlyMissing = false;

    public function run(): void
    {
        $category = PackageCategory::where('slug', 'hajj')->firstOrFail();
        // The four tiers the brochure names on every package: Platinum, Flex,
        // Comfort, Value. Which one a package belongs to is written in its own
        // title, and the code ranges in the brochure's index agree.
        $nonAziziyaSeries = PackageSeries::where('slug', 'platinum')->firstOrFail();
        $withAziziyaSeries = PackageSeries::where('slug', 'flex')->firstOrFail();
        $comfortSeries = PackageSeries::where('slug', 'comfort')->firstOrFail();
        $valueSeries = PackageSeries::where('slug', 'value')->firstOrFail();

        $existing = $this->onlyMissing
            ? Package::whereIn('code', array_column($this->packages(), 'code'))->pluck('code')->all()
            : [];

        foreach ($this->packages() as $index => $data) {
            if (in_array($data['code'], $existing, true)) {
                continue;
            }

            $isAziziyaGroup = in_array($data['series'], ['with_aziziya', 'comfort', 'value'], true);
            $seriesId = match ($data['series']) {
                'with_aziziya' => $withAziziyaSeries->id,
                'comfort' => $comfortSeries->id,
                'value' => $valueSeries->id,
                default => $nonAziziyaSeries->id,
            };

            $package = Package::updateOrCreate(
                ['code' => $data['code']],
                [
                    'package_category_id' => $category->id,
                    'package_series_id' => $seriesId,
                    'package_type' => 'Executive Platinum',
                    'name' => $data['name'],
                    'slug' => Str::slug($data['code'].'-'.$data['name']),
                    'summary' => 'Hajj 2027 / 1448 AH — '.$data['duration_label'].', '.($data['is_shifting'] ? 'shifting' : 'non-shifting').' itinerary'.($isAziziyaGroup ? ', with Aziziya accommodation' : ', optional Aziziya upgrade available').'.',
                    // The brochure-audit remark some packages carry was written
                    // for the people maintaining this data, not for pilgrims. It
                    // used to be stored here AND as a note, so it printed twice on
                    // the public page and reached the AI assistant (issue #10).
                    'description' => null,
                    'internal_notes' => $data['description'] ?? null,
                    'duration_days' => $data['duration_days'],
                    'duration_label' => $data['duration_label'],
                    'is_shifting' => $data['is_shifting'],
                    'medinah_first' => $data['medinah_first'],
                    'has_aziziya' => $isAziziyaGroup,
                    'season_year' => self::SEASON_YEAR,
                    'season_label' => self::SEASON_LABEL,
                    'currency' => 'USD',
                    'is_featured' => in_array($data['code'], ['UB001', 'UB011', 'UB023'], true),
                    'is_seasonal' => true,
                    'status' => 'published',
                    'published_at' => now(),
                    'sort_order' => $index + 1,
                    'meta_title' => $data['name'].' — Hajj 2027 | Universal Brothers',
                    'meta_description' => 'Hajj 2027 package '.$data['code'].': '.$data['duration_label'].' itinerary with real hotel accommodation, Mina Zone 1 Category A tents, and Arafat AC marquee. Prices subject to change.',
                ]
            );

            $this->syncVariants($package, $data['variants'] ?? []);
            $this->syncItinerary($package, $data['itinerary']);
            $this->syncAccommodations($package, $data['accommodations']);
            $this->syncRoomOptions($package, $data['room_options']);
            $this->syncAziziya($package, $data['aziziya'] ?? null);
            $this->syncMashaer($package);
            $this->syncTransportation($package);
            $this->syncFeatures($package, $isAziziyaGroup ? $this->aziziyaInclusions() : $this->nonAziziyaInclusions(), 'inclusion');
            $this->syncFeatures($package, $this->sharedExclusions(), 'exclusion');
            $this->syncUpgrades($package, $isAziziyaGroup);
            $this->syncNotes($package, $data, $isAziziyaGroup);

            $lowestUsd = $package->roomOptions()->where('is_available', true)->whereNotNull('price_usd')->min('price_usd');
            if ($lowestUsd !== null) {
                $package->forceFill(['starting_price' => $lowestUsd])->save();
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function packages(): array
    {
        return [
            [
                'code' => 'UB001', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Intercon / Fairmont — Medinah First',
                'duration_days' => 13, 'duration_label' => '13 Days Package',
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Tawhid Intercontinental', 'B' => 'Fairmont Clock Tower'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [13, '2027-05-19', '13 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Dar Al Taqwa', 5, 3],
                    ['makkah', 'A', 'Dar Al Tawhid Intercontinental', 5, 4],
                    ['makkah', 'B', 'Fairmont Clock Tower', 5, 4],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', null],
                    ['A', 'triple', 3, 'Triple Sharing', 22450],
                    ['A', 'double', 2, 'Double Sharing', 26850],
                    ['B', 'quad', 4, 'Quad Sharing', 16300],
                    ['B', 'triple', 3, 'Triple Sharing', 18100],
                    ['B', 'double', 2, 'Double Sharing', 20850],
                ],
            ],
            [
                'code' => 'UB003', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Intercon / Fairmont — Medinah First',
                'duration_days' => 10, 'duration_label' => '10 Days Package',
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Tawhid Intercontinental', 'B' => 'Fairmont Clock Tower'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Dar Al Taqwa', 5, 2],
                    ['makkah', 'A', 'Dar Al Tawhid Intercontinental', 5, 2],
                    ['makkah', 'B', 'Fairmont Clock Tower', 5, 2],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', null],
                    ['A', 'triple', 3, 'Triple Sharing', 21650],
                    ['A', 'double', 2, 'Double Sharing', 26050],
                    ['B', 'quad', 4, 'Quad Sharing', 15890],
                    ['B', 'triple', 3, 'Triple Sharing', 17250],
                    ['B', 'double', 2, 'Double Sharing', 20000],
                ],
            ],
            [
                'code' => 'UB004', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Swissotel — Medinah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'description' => "Brochure inconsistency preserved as printed: headed \"14 Days Package\" but the itinerary table lists 13 numbered days (7-19 May).",
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa/Hilton', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa/Hilton', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['makkah', null, 'Swissotel Makkah', 5, 4],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 14950],
                    ['A', 'triple', 3, 'Triple Sharing', 16300],
                    ['A', 'double', 2, 'Double Sharing', 19300],
                    ['B', 'quad', 4, 'Quad Sharing', 14200],
                    ['B', 'triple', 3, 'Triple Sharing', 15590],
                    ['B', 'double', 2, 'Double Sharing', 18350],
                ],
            ],
            [
                'code' => 'UB006', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Swissotel — Medinah First (Short Package)',
                'duration_days' => 10, 'duration_label' => 'Short Package — 10 Days Package',
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 2],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 2],
                    ['makkah', null, 'Swissotel Makkah', 5, 2],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 15350],
                    ['A', 'triple', 3, 'Triple Sharing', 15750],
                    ['A', 'double', 2, 'Double Sharing', 18500],
                    ['B', 'quad', 4, 'Quad Sharing', 13850],
                    ['B', 'triple', 3, 'Triple Sharing', 15200],
                    ['B', 'double', 2, 'Double Sharing', 17950],
                ],
            ],
            [
                'code' => 'UB008', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Makkah Tower — Medinah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'description' => "Brochure inconsistency preserved as printed: headed \"14 Days Package\" but the itinerary table lists 13 numbered days (7-19 May).",
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa/Hilton', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa/Hilton', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['makkah', null, 'Makkah Tower', 4, 4],
                ],
                'room_options' => [
                    ['A', 'sharing_room', null, 'Sharing Room', null],
                    ['A', 'quad', 4, 'Quad Sharing', 13250],
                    ['A', 'triple', 3, 'Triple Sharing', 14600],
                    ['A', 'double', 2, 'Double Sharing', 17900],
                    ['B', 'sharing_room', null, 'Sharing Room', 12450],
                    ['B', 'quad', 4, 'Quad Sharing', 12450],
                    ['B', 'triple', 3, 'Triple Sharing', 14050],
                    ['B', 'double', 2, 'Double Sharing', 16990],
                ],
            ],
            [
                'code' => 'UB010', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Makkah Tower — Medinah First',
                'duration_days' => 10, 'duration_label' => '10 Days Package',
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 2],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 2],
                    ['makkah', null, 'Makkah Tower', 4, 2],
                ],
                'room_options' => [
                    ['A', 'sharing_room', null, 'Sharing Room', null],
                    ['A', 'quad', 4, 'Quad Sharing', 12890],
                    ['A', 'triple', 3, 'Triple Sharing', 14050],
                    ['A', 'double', 2, 'Double Sharing', 16990],
                    ['B', 'sharing_room', null, 'Sharing Room', 12200],
                    ['B', 'quad', 4, 'Quad Sharing', 12200],
                    ['B', 'triple', 3, 'Triple Sharing', 13790],
                    ['B', 'double', 2, 'Double Sharing', 16450],
                ],
            ],
            [
                'code' => 'UB011', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Voco by IHG — Medinah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'description' => "Brochure inconsistency preserved as printed: headed \"14 Days Package\" but the itinerary table lists 13 numbered days (7-19 May).",
                'medinah_first' => true, 'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['makkah', null, 'Voco Makkah By IHG', 4, 4],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 10450],
                    [null, 'triple', 3, 'Triple Sharing', 11500],
                    [null, 'double', 2, 'Double Sharing', 13425],
                ],
            ],
            [
                'code' => 'UB013', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Voco by IHG — Makkah First (Short Package)',
                'duration_days' => 10, 'duration_label' => 'Short Package — 10 Days Package',
                'description' => 'Brochure names this "Makkah First" (and the table of contents separately lists it as "14 Days" — also wrong) though the printed itinerary still visits Medinah before Makkah, identically to UB011. Reproduced exactly as printed on this package\'s own page, not corrected. Its Quad/Triple/Double prices are also identical to UB011 despite the different duration — recorded as found.',
                'medinah_first' => false, 'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Dallah Taibah (Premier Floor)', 4, 2],
                    ['makkah', null, 'Voco Makkah By IHG', 4, 2],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 10450],
                    [null, 'triple', 3, 'Triple Sharing', 11500],
                    [null, 'double', 2, 'Double Sharing', 13425],
                ],
            ],
            [
                'code' => 'UB015', 'series' => 'with_aziziya',
                'name' => 'Executive Platinum Flex 14 — Medinah First',
                'duration_days' => 14, 'duration_label' => 'Flex 14 Days',
                'medinah_first' => true, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [14, '2027-05-20', '14 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['makkah', null, 'Pullman Zamzam Makkah', 4, 4],
                    ['aziziya', null, 'AZIZIYA Accommodation - A Class', null, 2],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 11650],
                    ['A', 'triple', 3, 'Triple Sharing', 12750],
                    ['A', 'double', 2, 'Double Sharing', 14520],
                    ['B', 'quad', 4, 'Quad Sharing', 11100],
                    ['B', 'triple', 3, 'Triple Sharing', 12200],
                    ['B', 'double', 2, 'Double Sharing', 13560],
                ],
            ],
            [
                'code' => 'UB016', 'series' => 'with_aziziya',
                'name' => 'Executive Platinum Flex 10 — Medinah First',
                'duration_days' => 10, 'duration_label' => 'Flex 10 Days',
                'medinah_first' => true, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 2],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 2],
                    ['makkah', null, 'Pullman Zamzam Makkah', 4, 2],
                    ['aziziya', null, 'AZIZIYA Accommodation - A Class', null, 1],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 11100],
                    ['A', 'triple', 3, 'Triple Sharing', 11920],
                    ['A', 'double', 2, 'Double Sharing', 12880],
                    ['B', 'quad', 4, 'Quad Sharing', 10685],
                    ['B', 'triple', 3, 'Triple Sharing', 11390],
                    ['B', 'double', 2, 'Double Sharing', 12330],
                ],
            ],
            [
                'code' => 'UB023', 'series' => 'value',
                'name' => 'Executive Platinum Value 14 — Medinah First',
                'duration_days' => 14, 'duration_label' => 'Value 14 Days',
                'medinah_first' => true, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [14, '2027-05-20', '14 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['aziziya', null, 'AZIZIYA Accommodation - A Class', null, 8],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 10410],
                    ['A', 'triple', 3, 'Triple Sharing', 10685],
                    ['A', 'double', 2, 'Double Sharing', 11230],
                    ['B', 'quad', 4, 'Quad Sharing', 9725],
                    ['B', 'triple', 3, 'Triple Sharing', 10000],
                    ['B', 'double', 2, 'Double Sharing', 10275],
                ],
            ],
            [
                'code' => 'UB024', 'series' => 'value',
                'name' => 'Executive Platinum Value 10 — Medinah First',
                'duration_days' => 10, 'duration_label' => 'Value 10 Days',
                'medinah_first' => true, 'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Al Aqeeq / Dallah Taibah / Similar ★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Al Aqeeq / Dallah Taibah / Similar', 3, 1],
                    ['aziziya', null, 'AZIZIYA Accommodation - A Class', null, 4],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 9175],
                    [null, 'triple', 3, 'Triple Sharing', 9315],
                    [null, 'double', 2, 'Double Sharing', 9450],
                ],
            ],

            // ---------------------------------------------------------------
            // The fourteen packages the older dollar-only brochure did not
            // carry. Read page by page out of the three September 2026
            // brochures — rupee, riyal and dollar — and generated rather than
            // typed: fourteen itineraries and three price lists is nine
            // hundred lines, and one slip is a wrong price on a public site.
            //
            // Brochure discrepancies are carried through as notes, not fixed:
            // UB018-UB021 print their code as "UHS 0xx", UB020's header says
            // 14 days over a 13-row table, and UB021 repeats UB020's prices.
            // ---------------------------------------------------------------
            [
                'code' => 'UB002', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Intercon / Fairmont — Makkah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Tawhid Intercontinental', 'B' => 'Fairmont Clock Tower'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Dar Al Tawhid Intercontinental ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', 'A', 'Dar Al Tawhid Intercontinental', 5, 6],
                    ['makkah', 'B', 'Fairmont Clock Tower', 5, 6],
                    ['medinah', null, 'Dar Al Taqwa', 5, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', null, null, null],
                    ['A', 'triple', 3, 'Triple Sharing', 22450, 6372000, 82000],
                    ['A', 'double', 2, 'Double Sharing', 26850, 7600000, 98000],
                    ['B', 'quad', 4, 'Quad Sharing', 16300, 4640000, 59500],
                    ['B', 'triple', 3, 'Triple Sharing', 18100, 5140000, 66000],
                    ['B', 'double', 2, 'Double Sharing', 20850, 5910000, 76000],
                ],
                'brochure_notes' => [
                    'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB005', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Swissotel — Makkah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa / Hilton', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Swissotel Makkah', 5, 6],
                    ['medinah', 'A', 'Dar Al Taqwa / Hilton', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 14950, 4250000, 54500],
                    ['A', 'triple', 3, 'Triple Sharing', 16300, 4640000, 59500],
                    ['A', 'double', 2, 'Double Sharing', 19300, 5485000, 70500],
                    ['B', 'quad', 4, 'Quad Sharing', 14200, 4050000, 51900],
                    ['B', 'triple', 3, 'Triple Sharing', 15590, 4440000, 56900],
                    ['B', 'double', 2, 'Double Sharing', 18350, 5200000, 66900],
                ],
                'brochure_notes' => [
                    'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB007', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Swissotel — Makkah First (Short Package)',
                'duration_days' => 9, 'duration_label' => 'Short Package — 09 Days Package',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => [],
                'itinerary' => [
                    [1, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [2, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [3, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [4, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [5, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [7, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [8, '2027-05-19', '13 Zil Hajj', 'Medinah', 'Al Aqeeq ★★★★ / Dallah Taibah ★★★★ / Similar', null],
                    [9, '2027-05-20', '14 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Swissotel Makkah', 5, 3],
                    ['medinah', null, 'Al Aqeeq / Dallah Taibah / Similar', 4, 1],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 13850, 3945000, 50500],
                    [null, 'triple', 3, 'Triple Sharing', 15200, 4330000, 55500],
                    [null, 'double', 2, 'Double Sharing', 17950, 5100000, 65500],
                ],
                'brochure_notes' => [
                    'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB009', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Makkah Tower — Makkah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa / Hilton', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Makkah Tower', 4, 6],
                    ['medinah', 'A', 'Dar Al Taqwa / Hilton', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'sharing', null, 'Sharing Room', null, null, null],
                    ['A', 'quad', 4, 'Quad Sharing', 13250, 3785000, 48400],
                    ['A', 'triple', 3, 'Triple Sharing', 14600, 4160000, 53300],
                    ['A', 'double', 2, 'Double Sharing', 17900, 5085000, 65300],
                    ['B', 'sharing', null, 'Sharing Room', 12450, 3550000, 45400],
                    ['B', 'quad', 4, 'Quad Sharing', 12450, 3550000, 45400],
                    ['B', 'triple', 3, 'Triple Sharing', 14050, 4000000, 51300],
                    ['B', 'double', 2, 'Double Sharing', 16990, 4830000, 62000],
                ],
                'brochure_notes' => [
                    'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB012', 'series' => 'non_aziziya',
                'name' => 'Executive Platinum Voco by IHG — Makkah First',
                'duration_days' => 14, 'duration_label' => '14 Days Package',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => [],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dallah Taibah (Premier Floor) ★★★★', null],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Voco Makkah By IHG', 4, 6],
                    ['medinah', null, 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 10450, 2999000, 38200],
                    [null, 'triple', 3, 'Triple Sharing', 11500, 3290000, 42000],
                    [null, 'double', 2, 'Double Sharing', 13425, 3830000, 49000],
                ],
                'brochure_notes' => [
                    'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB014', 'series' => 'with_aziziya',
                'name' => 'Executive Platinum Flex 14 — Makkah First',
                'duration_days' => 14, 'duration_label' => 'Flex 14 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Pullman Zamzam Makkah', 4, 4],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 2],
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 11650, 3330000, 42500],
                    ['A', 'triple', 3, 'Triple Sharing', 12750, 3640000, 46500],
                    ['A', 'double', 2, 'Double Sharing', 14520, 4140000, 53000],
                    ['B', 'quad', 4, 'Quad Sharing', 11100, 3175000, 40500],
                    ['B', 'triple', 3, 'Triple Sharing', 12200, 3485000, 44500],
                    ['B', 'double', 2, 'Double Sharing', 13560, 3870000, 49500],
                ],
                'brochure_notes' => [
                    'Special facility: Aziziya family room for 5 days of Hajj also included.',
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB017', 'series' => 'with_aziziya',
                'name' => 'Executive Platinum Flex 09 — Makkah First',
                'duration_days' => 9, 'duration_label' => 'Flex 09 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => true,
                'variants' => [],
                'itinerary' => [
                    [1, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [2, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Pullman Zamzam Makkah ★★★★+', null],
                    [3, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [4, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [5, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [7, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-19', '13 Zil Hajj', 'Medinah', 'Al Aqeeq ★★★★ / Dallah Taibah ★★★★ / Similar', null],
                    [9, '2027-05-20', '14 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Pullman Zamzam Makkah', 4, 2],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 1],
                    ['medinah', null, 'Al Aqeeq / Dallah Taibah / Similar', 4, 1],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 10685, 3060000, 39000],
                    [null, 'triple', 3, 'Triple Sharing', 11370, 3250000, 41500],
                    [null, 'double', 2, 'Double Sharing', 12330, 3525000, 45000],
                ],
                'brochure_notes' => [
                    'Special facility: Aziziya family room for 5 days of Hajj also included.',
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB018', 'series' => 'comfort',
                'name' => 'Executive Platinum Comfort — Medinah First',
                'duration_days' => 17, 'duration_label' => 'Comfort 17 Days — Medinah',
                'medinah_first' => true, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Tawhid IHG', 'B' => 'Fairmont Clock Tower'],
                'itinerary' => [
                    [1, '2027-05-04', '27 Zil Qad', 'To Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [2, '2027-05-05', '28 Zil Qad', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [3, '2027-05-06', '29 Zil Qad', 'Medinah', 'Dar Al Taqwa ★★★★★', null],
                    [4, '2027-05-07', '01 Zil Hajj', 'To Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [5, '2027-05-08', '02 Zil Hajj', 'Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [6, '2027-05-09', '03 Zil Hajj', 'Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [7, '2027-05-10', '04 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-11', '05 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [9, '2027-05-12', '06 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [10, '2027-05-13', '07 Zil Hajj', 'To Mina', 'AZIZIYA Accommodation - A CLASS', null],
                    [11, '2027-05-14', '08 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [13, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [14, '2027-05-17', '11 Zil Hajj', 'To Makkah', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [15, '2027-05-18', '12 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [16, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [17, '2027-05-20', '14 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['medinah', null, 'Dar Al Taqwa', 5, 3],
                    ['makkah', 'A', 'Dar Al Tawhid IHG', 5, 3],
                    ['makkah', 'B', 'Fairmont Clock Tower', 5, 3],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 5],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', null, null, null],
                    ['A', 'triple', 3, 'Triple Sharing', 11170, 3199000, 40800],
                    ['A', 'double', 2, 'Double Sharing', 11790, 3370000, 43000],
                    ['B', 'quad', 4, 'Quad Sharing', 10520, 3015000, 38400],
                    ['B', 'triple', 3, 'Triple Sharing', 10800, 3085000, 39300],
                    ['B', 'double', 2, 'Double Sharing', 11250, 3215000, 41000],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                    'Brochure prints the code as “UHS 018” on this page; the index lists UB018. Recorded as found.',
                ],
            ],
            [
                'code' => 'UB019', 'series' => 'comfort',
                'name' => 'Executive Platinum Comfort — Makkah First',
                'duration_days' => 17, 'duration_label' => 'Comfort 17 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Tawhid IHG', 'B' => 'Fairmont Clock Tower'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Makkah', 'Dar Al Tawhid IHG ★★★★★', 'Fairmont Clock Tower ★★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [14, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [15, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [16, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [17, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', 'A', 'Dar Al Tawhid IHG', 5, 3],
                    ['makkah', 'B', 'Fairmont Clock Tower', 5, 3],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 6],
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', null, null, null],
                    ['A', 'triple', 3, 'Triple Sharing', 11900, 3399000, 43400],
                    ['A', 'double', 2, 'Double Sharing', 12800, 3650000, 46700],
                    ['B', 'quad', 4, 'Quad Sharing', 11100, 3175000, 40500],
                    ['B', 'triple', 3, 'Triple Sharing', 11480, 3285000, 41900],
                    ['B', 'double', 2, 'Double Sharing', 12250, 3499000, 44700],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                    'Brochure prints the code as “UHS 019” on this page; the index lists UB019. Recorded as found.',
                ],
            ],
            [
                'code' => 'UB020', 'series' => 'comfort',
                'name' => 'Executive Platinum Comfort 14 — Medinah First',
                'duration_days' => 14, 'duration_label' => 'Comfort 14 Days — Medinah',
                'medinah_first' => true, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Makkah Tower / Abraj Tower ★★★★★', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Makkah Tower / Abraj Tower ★★★★★', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [13, '2027-05-19', '13 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                    ['makkah', null, 'Makkah Tower / Abraj Tower', 5, 2],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 10950, 3140000, 40000],
                    ['A', 'triple', 3, 'Triple Sharing', 11230, 3215000, 41000],
                    ['A', 'double', 2, 'Double Sharing', 11500, 3290000, 42000],
                    ['B', 'quad', 4, 'Quad Sharing', 10410, 2985000, 38000],
                    ['B', 'triple', 3, 'Triple Sharing', 10685, 3060000, 39000],
                    ['B', 'double', 2, 'Double Sharing', 10950, 3140000, 40000],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                    'Header says 14 days; the itinerary table has 13 rows. Recorded as found.',
                    'Brochure prints the code as “UHS 020” on this page; the index lists UB020. Recorded as found.',
                ],
            ],
            [
                'code' => 'UB021', 'series' => 'comfort',
                'name' => 'Executive Platinum Comfort 14 — Makkah First',
                'duration_days' => 14, 'duration_label' => 'Comfort 14 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => true,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Makkah Tower / Abraj Tower ★★★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Makkah Tower / Abraj Tower ★★★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [11, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [12, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [13, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [14, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'Makkah Tower / Abraj Tower', 5, 2],
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 4],
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 10950, 3140000, 40000],
                    ['A', 'triple', 3, 'Triple Sharing', 11230, 3215000, 41000],
                    ['A', 'double', 2, 'Double Sharing', 11500, 3290000, 42000],
                    ['B', 'quad', 4, 'Quad Sharing', 10410, 2985000, 38000],
                    ['B', 'triple', 3, 'Triple Sharing', 10685, 3060000, 39000],
                    ['B', 'double', 2, 'Double Sharing', 10950, 3140000, 40000],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                    'Brochure prints the code as “UHS 021” on this page; the index lists UB021. Recorded as found.',
                    'Prices on this page are identical to UB020. Recorded as found.',
                ],
            ],
            [
                'code' => 'UB022', 'series' => 'value',
                'name' => 'Executive Platinum Value 13 — Makkah First',
                'duration_days' => 13, 'duration_label' => 'Value 13 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => ['A' => 'Dar Al Taqwa', 'B' => 'Dallah Taibah (Premier Floor)'],
                'itinerary' => [
                    [1, '2027-05-10', '4-6 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [2, '2027-05-13', '07 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [3, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [4, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [5, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [7, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [9, '2027-05-20', '14 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [10, '2027-05-21', '15 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [11, '2027-05-22', '16 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Dallah Taibah (Premier Floor) ★★★★'],
                    [12, '2027-05-23', '17 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 4],
                    ['medinah', 'A', 'Dar Al Taqwa', 5, 3],
                    ['medinah', 'B', 'Dallah Taibah (Premier Floor)', 4, 3],
                ],
                'room_options' => [
                    ['A', 'quad', 4, 'Quad Sharing', 10410, 2985000, 38000],
                    ['A', 'triple', 3, 'Triple Sharing', 10685, 3060000, 39000],
                    ['A', 'double', 2, 'Double Sharing', 11230, 3215000, 41000],
                    ['B', 'quad', 4, 'Quad Sharing', 9725, 2790000, 35500],
                    ['B', 'triple', 3, 'Triple Sharing', 10000, 2870000, 36500],
                    ['B', 'double', 2, 'Double Sharing', 10275, 2945000, 37500],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                    'Header says 13 days; the table has 12 rows because the first covers 10-12 May. Recorded as found.',
                ],
            ],
            [
                'code' => 'UB025', 'series' => 'value',
                'name' => 'Executive Platinum Value 09 — Makkah First',
                'duration_days' => 9, 'duration_label' => 'Value 9-10 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => false,
                'variants' => [],
                'itinerary' => [
                    [1, '2027-05-10', '4-6 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [2, '2027-05-13', '07 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [3, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services) / AZIZIYA Accommodation - A CLASS', null],
                    [4, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services) / AZIZIYA Accommodation - A CLASS', null],
                    [5, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services) / AZIZIYA Accommodation - A CLASS', null],
                    [6, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services) / AZIZIYA Accommodation - A CLASS', null],
                    [7, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-19', '13 Zil Hajj', 'To Medinah', '4 Star Hotel ★★★★', null],
                    [9, '2027-05-20', '14 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 3],
                    ['medinah', null, '4 Star Hotel', 4, 1],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 9175, 2635000, 33500],
                    [null, 'triple', 3, 'Triple Sharing', 9315, 2675000, 34000],
                    [null, 'double', 2, 'Double Sharing', 9450, 2715000, 34500],
                ],
                'brochure_notes' => [
                    'Makkah and Medinah nights can be reduced; the package price remains the same.',
                    'Transfer from Makkah to Medinah by car.',
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                ],
            ],
            [
                'code' => 'UB026', 'series' => 'value',
                'name' => 'Executive Platinum Value 20 — Makkah First',
                'duration_days' => 20, 'duration_label' => 'Value 20 Days — Makkah',
                'medinah_first' => false, 'is_shifting' => true,
                'variants' => [],
                'itinerary' => [
                    [1, '2027-05-10', '4-6 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [2, '2027-05-13', '07 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [3, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [4, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [5, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [7, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [8, '2027-05-19', '13 Zil Hajj', 'Makkah', 'AZIZIYA Accommodation - A CLASS', null],
                    [9, '2027-05-20', '14 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [10, '2027-05-21', '15 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [11, '2027-05-22', '16 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [12, '2027-05-23', '17 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [13, '2027-05-24', '18 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [14, '2027-05-25', '19 Zil Hajj', 'Makkah', 'Abraj Tower / Makkah Hotel & Tower ★★★★★', null],
                    [15, '2027-05-26', '20 Zil Hajj', 'To Medinah', 'Dar Al Taqwa / Hilton ★★★★★', null],
                    [16, '2027-05-27', '21 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', null],
                    [17, '2027-05-28', '22 Zil Hajj', 'Medinah', 'Dar Al Taqwa / Hilton ★★★★★', null],
                    [18, '2027-05-29', '23 Zil Hajj', null, 'DEPARTURE TO AIRPORT', null],
                ],
                'accommodations' => [
                    ['makkah', null, 'AZIZIYA Accommodation - A CLASS', null, 4],
                    ['makkah', null, 'Abraj Tower / Makkah Hotel & Tower', 5, 6],
                    ['medinah', null, 'Dar Al Taqwa / Hilton', 5, 3],
                ],
                'room_options' => [
                    [null, 'quad', 4, 'Quad Sharing', 11100, 3175000, 40500],
                    [null, 'triple', 3, 'Triple Sharing', 11370, 3250000, 41500],
                    [null, 'double', 2, 'Double Sharing', 11920, 3400000, 43500],
                ],
                'brochure_notes' => [
                    'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
                    'Supplement for Aziziya family room: double PKR 308000 per person, triple PKR 154000 per person.',
                    'Shuttle included, Aziziya to Haram.',
                    'Ticket not included.',
                ],
            ],
        ];
    }

    private function syncVariants(Package $package, array $variants): void
    {
        $package->variants()->delete();
        $i = 0;
        foreach ($variants as $code => $label) {
            $package->variants()->create(['code' => $code, 'label' => $label, 'sort_order' => $i++]);
        }
    }

    private function variantIdMap(Package $package): array
    {
        return $package->variants()->get()->pluck('id', 'code')->all();
    }

    private function syncItinerary(Package $package, array $itinerary): void
    {
        $package->itineraryDays()->delete();
        foreach ($itinerary as [$day, $date, $hijri, $city, $a, $b]) {
            $package->itineraryDays()->create([
                'day_number' => $day, 'date_gregorian' => $date, 'date_hijri_label' => $hijri,
                'city' => $city, 'accommodation_a' => $a, 'accommodation_b' => $b,
            ]);
        }
    }

    private function syncAccommodations(Package $package, array $accommodations): void
    {
        $package->accommodations()->delete();
        $variantIds = $this->variantIdMap($package);
        foreach ($accommodations as $i => [$location, $variantCode, $hotelName, $stars, $nights]) {
            $package->accommodations()->create([
                'variant_id' => $variantCode ? ($variantIds[$variantCode] ?? null) : null,
                'location' => $location,
                'hotel_name' => $hotelName,
                'star_rating' => $stars,
                'meal_plan' => in_array($location, ['makkah', 'medinah'], true) ? 'Half board (breakfast & dinner)' : ($location === 'aziziya' ? 'Full board buffet (breakfast, lunch, dinner)' : null),
                'nights' => $nights,
                'sort_order' => $i,
            ]);
        }
    }

    private function syncRoomOptions(Package $package, array $roomOptions): void
    {
        $package->roomOptions()->delete();
        $variantIds = $this->variantIdMap($package);
        foreach ($roomOptions as $i => $row) {
            // The twelve packages taken from the older dollar-only brochure
            // carry a dollar price here and get their rupee and riyal columns
            // from HajjPriceCurrencySeeder. The fourteen added from the three
            // September brochures carry all three, read off their own page in
            // each currency, so they are given here and nothing is converted.
            [$variantCode, $sharingType, $occupancy, $label, $priceUsd] = $row;
            $pricePkr = $row[5] ?? null;
            $priceSar = $row[6] ?? null;

            $package->roomOptions()->create([
                'variant_id' => $variantCode ? ($variantIds[$variantCode] ?? null) : null,
                'sharing_type' => $sharingType,
                'occupancy' => $occupancy,
                'display_label' => $label,
                'price_basis' => 'per_person',
                'price_usd' => $priceUsd,
                'price_pkr' => $pricePkr,
                'price_sar' => $priceSar,
                'is_available' => $priceUsd !== null || $pricePkr !== null || $priceSar !== null,
                'sort_order' => $i,
            ]);
        }
    }

    private function syncAziziya(Package $package, ?array $override): void
    {
        $package->aziziya()->delete();
        $variantIds = $this->variantIdMap($package);
        $isIncluded = $package->has_aziziya;

        $aziziya = $package->aziziya()->create([
            'status' => $isIncluded ? 'included' : 'optional',
            'accommodation_name' => $isIncluded ? 'AZIZIYA Accommodation - A Class' : null,
            'location_note' => $isIncluded ? 'Directly opposite the Jamarat escalator, near Mina camps.' : null,
            'walk_distance' => $isIncluded ? '30 to 50-minute walk to the Mina camp and back.' : null,
            'duration_days' => 5,
            'average_occupancy' => $isIncluded ? 4 : null,
            'description' => $isIncluded
                ? 'Walk Less, Pray More — Ultimate Convenience Near Jamarat & Mina. Strategically located directly opposite the Jamarat escalator, minimizing walking distance and preserving energy for a more focused Hajj.'
                : null,
            'notes' => 'Aziziya Accommodation services are not comparable to hotel services.',
        ]);

        if ($isIncluded) {
            // UB015/UB016 ("Makkah & Medinah series"): family room included in
            // the main package price at no extra charge, per that page's own
            // "Aziziya family room included" note — no separate supplement
            // given, unlike UB023/UB024 below.
            if ($package->code === 'UB015' || $package->code === 'UB016') {
                $aziziya->roomOptions()->create([
                    'sharing_type' => 'family_room', 'display_label' => 'Family Room', 'occupancy' => null,
                    'pricing_type' => 'included', 'price_basis' => 'included',
                    'notes' => 'Aziziya family room for 5 days of Hajj included at no extra charge.',
                ]);
            }

            // UB023/UB024 ("Medinah series"): real per-room-type supplement.
            if ($package->code === 'UB023' || $package->code === 'UB024') {
                $aziziya->roomOptions()->create([
                    'sharing_type' => 'family_room_double', 'display_label' => 'Family Room (Double)', 'occupancy' => 2,
                    'pricing_type' => 'supplement', 'price_basis' => 'per_person', 'price_usd' => 1100, 'sort_order' => 0,
                ]);
                $aziziya->roomOptions()->create([
                    'sharing_type' => 'family_room_triple', 'display_label' => 'Family Room (Triple)', 'occupancy' => 3,
                    'pricing_type' => 'supplement', 'price_basis' => 'per_person', 'price_usd' => 550, 'sort_order' => 1,
                ]);
            }

            foreach ($this->aziziyaAmenities() as $i => $amenity) {
                $aziziya->services()->create(['name' => $amenity, 'is_included' => true, 'sort_order' => $i]);
            }
        } else {
            // Every Non-Aziziya package offers this same optional upgrade,
            // per its own page note (identical US$5500 value across all 8).
            $aziziya->roomOptions()->create([
                'sharing_type' => 'family_room', 'display_label' => 'Family Room', 'occupancy' => null,
                'pricing_type' => 'supplement', 'price_basis' => 'flat, for 5 days of Hajj', 'price_usd' => 5500,
                'notes' => 'Family Rooms available in the Aziziya Building for the 5 days of Hajj.',
            ]);
        }
    }

    private function syncMashaer(Package $package): void
    {
        $package->mashaerDetails()->delete();

        $package->mashaerDetails()->create([
            'location' => 'mina', 'maktab' => 'A', 'category' => 'Category A', 'zone' => 'Zone 1',
            'accommodation_type' => 'Sofa-cum-bed (50-55cm each); tent may be combined; avg. 16 people per tent, per Saudi Talimaat.',
            'meal_plan' => 'Full board buffet (for Group Maktab A Category Hujjaj)',
            'bathroom' => 'Private bathroom for UB Group',
            'air_conditioning' => 'Air-conditioned tent',
            'transportation' => 'Bullet train Makkah↔Medinah, or private luxury buses (2025 model) for Mashaer days with bathroom',
            'other_services' => 'Pillow, bed sheet, blanket provided (Services by Saudi Company). Mic and speaker installed for religious guidance.',
            'notes' => 'Best location in Mina, very near Jamarat.',
        ]);

        $package->mashaerDetails()->create([
            'location' => 'arafat', 'tent_type' => 'Air Conditioned Marquee',
            'meal_plan' => 'Full board with meals and hot & cold drinks',
            'bathroom' => 'Private bathroom for UB Group',
            'air_conditioning' => 'Air-conditioned',
            'other_services' => 'Floor mat and snack box provided in Muzdalifah (Services by Saudi Company).',
        ]);
    }

    private function syncTransportation(Package $package): void
    {
        $package->transportation()->delete();

        // The paid legs carry a figure per currency, each transcribed from the
        // brochure printed in it — the taxi rates from the package pages, the
        // VIP GMC from the services page. Nothing is converted: US$165 is
        // PKR 46,000 and SAR 600 because that is what the three brochures
        // say, not because of a rate.
        $rows = [
            ['Jeddah/Medinah Hajj Terminal', 'Hotel', 'airport_transfer', true, null, null, null, null, 'Group arrival transfer by bus, provided by NAQABA / Saudi Moallim (subject to approval handling).'],
            ['Mina', 'Arafat / Muzdalifah / Mina', 'mashaer', true, null, null, null, null, 'Private Special Luxury Busses with bathroom.'],
            ['Makkah', 'Medinah', 'train_or_bus', true, null, null, null, null, 'Bullet train Makkah↔Medinah, or bus.'],
            ['Jeddah Airport', 'Makkah Hotel', 'car_taxi', false, 165, 46000, 600, 'per person, round trip', null],
            ['Medinah Airport', 'Medinah Hotel', 'car_taxi', false, 40, 11600, 150, 'per person, round trip', null],
            ['Mina/Arafat/Muzdalifah', 'and back', 'vip_gmc', false, 9600, 2695000, 35000, 'per GMC (Land Cruiser, max 6 persons), for 5 days of Hajj', 'Urdu/English-speaking chauffeur with mobile phone, 08–13 Zil Hajj.'],
        ];

        foreach ($rows as $i => [$from, $to, $type, $included, $usd, $pkr, $sar, $basis, $notes]) {
            $package->transportation()->create([
                'from_location' => $from, 'to_location' => $to, 'transport_type' => $type,
                'is_included' => $included,
                'price' => $usd, 'currency' => $usd === null ? null : 'USD',
                'price_usd' => $usd, 'price_pkr' => $pkr, 'price_sar' => $sar,
                'price_basis' => $basis, 'notes' => $notes, 'sort_order' => $i,
            ]);
        }
    }

    private function syncUpgrades(Package $package, bool $isAziziyaGroup): void
    {
        $package->upgrades()->delete();

        // The Aziziya series' Kaba view supplement has NO rupee figure, and
        // that null is deliberate: the US$ and Riyal brochures publish it
        // (US$1,050 / SAR 3,800) and the PKR brochure does not list the item
        // at all. A blank column means the brochure is silent, and the page
        // then says "Price on request" instead of quoting dollars to someone
        // reading rupees.
        $package->upgrades()->create([
            'name' => 'Kaba View Supplement',
            'price' => $isAziziyaGroup ? 1050 : 2200,
            'currency' => 'USD',
            'price_usd' => $isAziziyaGroup ? 1050 : 2200,
            'price_pkr' => $isAziziyaGroup ? null : 616000,
            'price_sar' => $isAziziyaGroup ? 3800 : 8000,
            'price_basis' => 'per person', 'sort_order' => 0,
        ]);

        if (! $isAziziyaGroup) {
            // Quad and triple are both US$600 and yet 168,600 and 169,400 in
            // rupees. Transcribed, not calculated.
            $nights = [
                ['Additional Medinah Night — Double', 850, 231000, 3000],
                ['Additional Medinah Night — Triple', 600, 169400, 2200],
                ['Additional Medinah Night — Quad', 600, 168600, 2190],
            ];
            foreach ($nights as $i => [$name, $usd, $pkr, $sar]) {
                $package->upgrades()->create([
                    'name' => $name, 'price' => $usd, 'currency' => 'USD',
                    'price_usd' => $usd, 'price_pkr' => $pkr, 'price_sar' => $sar,
                    'price_basis' => 'per night per person', 'sort_order' => $i + 1,
                ]);
            }
        }

        $package->upgrades()->create([
            'name' => 'Deluxe Family Tent Upgrade', 'description' => 'Private Deluxe Family Tent with attached bathroom.',
            'notes' => 'Price on request.', 'sort_order' => 10,
        ]);
        $package->upgrades()->create([
            'name' => '5-Person Sharing Tent', 'description' => 'With attached bathroom.', 'notes' => 'Price on request.', 'sort_order' => 11,
        ]);
        $package->upgrades()->create([
            'name' => '8-Person Sharing Tent', 'description' => 'With attached bathroom.', 'notes' => 'Price on request.', 'sort_order' => 12,
        ]);
    }

    private function syncNotes(Package $package, array $data, bool $isAziziyaGroup): void
    {
        $package->packageNotes()->delete();
        $i = 0;

        $package->packageNotes()->create([
            'note_type' => 'pricing', 'content' => 'Book Early, Prices and Packages Subject to Change.',
            'is_important' => true, 'sort_order' => $i++,
        ]);

        // Anything the package's own brochure page says that the shared notes
        // below do not cover — a supplement, a caveat, or a discrepancy in the
        // brochure itself, recorded rather than quietly corrected.
        foreach ($data['brochure_notes'] ?? [] as $note) {
            $package->packageNotes()->create([
                'note_type' => 'general', 'content' => $note, 'sort_order' => $i++,
            ]);
        }
        $package->packageNotes()->create([
            'note_type' => 'accommodation',
            'content' => $isAziziyaGroup
                ? 'Aziziya rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.'
                : 'Makkah hotel rooms will be retained from 08 Zil Hajj to 12 Zil Hajj.',
            'sort_order' => $i++,
        ]);
        $package->packageNotes()->create([
            'note_type' => 'general', 'title' => '"Makkah (similar)"',
            'content' => 'Means Abraj Tower / Jabal e Omar project hotels close to Haram — any five-star hotel by Saudi standards (Swiss Maqam, Hajar Tower, Swissotel, Safwa Orchid, Al Marwa, Hayat Regency, Address Hotel, Jumeirah Hotel, Hilton Convention, Double Tree, Marriott, etc.).',
            'sort_order' => $i++,
        ]);
        $package->packageNotes()->create([
            'note_type' => 'disclaimer',
            'content' => 'Rates & hotels subject to change (currency difference); prices subject to change even after booking / Saudi Talimaat changes.',
            'sort_order' => $i++,
        ]);
        $package->packageNotes()->create([
            // The PKR brochure prints "Ticket not Included" and lists Qurbani
            // among the inclusions; the Riyal and US$ ones print "Ticket &
            // Qurbani not Included". Both are true of their own list, so the
            // note says which is which instead of picking one.
            'note_type' => 'important', 'content' => 'Airline ticket not included. Qurbani is included on the rupee price list and charged separately on the riyal and US dollar lists.', 'is_important' => true, 'sort_order' => $i++,
        ]);

        if (! $isAziziyaGroup) {
            $package->packageNotes()->create([
                'note_type' => 'pricing', 'content' => 'No of days of stay in Makkah can be reduced but price remains the same.',
                'sort_order' => $i++,
            ]);
        }

        if (in_array($package->code, ['UB008', 'UB010'], true)) {
            $package->packageNotes()->create([
                'note_type' => 'accommodation', 'is_important' => true,
                'content' => 'Makkah Tower rooms have stairs. "Sharing Room" means 4 to 5 persons in a room.',
                'sort_order' => $i++,
            ]);
        }
    }

    private function syncFeatures(Package $package, array $lines, string $type): void
    {
        $relation = $type === 'inclusion' ? $package->inclusions() : $package->exclusions();
        $relation->delete();
        foreach ($lines as $i => $line) {
            $relation->create(['type' => $type, 'description' => $line, 'sort_order' => $i]);
        }
    }

    /** @return string[] */
    private function nonAziziyaInclusions(): array
    {
        return [
            'Meet & assist at the airport Jeddah/Medinah Hajj Terminal (subject to approval handling)',
            'Group arrival transfer by bus from airport to hotel, provided by NAQABA/Saudi Moallim',
            'Accommodation in Makkah with breakfast and dinner (Saudi Stars standard), 4–8 Zil Hajj and 12–14 Zil Hajj except Hajj days',
            'Accommodation in Medinah with breakfast and dinner (Saudi Stars standard); one night may be reduced per final itinerary',
            'Full-board buffet meals served in Mina and Arafat from 08 to 12 Zil Hajj',
            '5-day Platinum Mashaer arrangement (08–12 Zil Hajj) with Makkah hotel room retained (without meals during those days)',
            'Private special luxury buses with bathroom (Mina–Arafat–Muzdalifah–Mina); Makkah↔Medinah transfer by train',
            'Best-location Maktab (A) tent in Mina, very near Jamarat, sofa-cum-bed (50–55cm), private toilet, Group Maktab A category',
            'Mashaer-days tent services: pillow, bed sheet, blanket, air-conditioned tent, buffet meal, hot & cold drinks (avg. 16 people/tent, may be combined)',
            'Tent in Arafat with meals and hot & cold drinks; floor mat and snack box in Muzdalifah; mic and speaker installed for religious guidance',
            'Ziyarat in Medinah with guidance',
            'Hajj training program and guidance in Pakistan/Saudi Arabia',
            'Religious guide book',
            'Assistance in Tawaf-e-Ziyarah',
            'Assistance in doing Qurbani',
        ];
    }

    /** @return string[] */
    private function aziziyaInclusions(): array
    {
        return [
            'Meet & assist at the airport Jeddah/Medinah Hajj Terminal (subject to approval handling)',
            'Group arrival transfer by bus from airport to hotel, provided by NAQABA/Saudi Moallim',
            'Average 4-person-sharing Aziziya accommodation, A-class air-conditioned building with proper beds (pillow, bed sheet, blanket)',
            'Full-board meals (breakfast, lunch, dinner) with hot & cold drinks in the Aziziya building, except Hajj days',
            'Accommodation in Makkah and Medinah hotels with breakfast and dinner (Saudi Stars standard), where the itinerary includes a hotel stay',
            'Full-board buffet meals served in Mina from 08 to 12 Zil Hajj',
            '5-day Platinum Mashaer arrangement (08–12 Zil Hajj) with Aziziya room retained',
            'Private special luxury buses with bathroom (Mina–Arafat–Muzdalifah–Mina); Makkah↔Medinah transfer by bus/train',
            'Best-location Maktab (A) tent in Mina, very near Jamarat, sofa-cum-bed, private toilet',
            'Mashaer-days tent services (pillow, bed sheet, blanket, A/C tent, buffet meal, hot & cold drinks, avg. 16/tent)',
            'Tent in Arafat with meals and drinks; Muzdalifah floor mat and snack box; mic and speaker for religious guidance',
            'Ziyarat in Medinah with guidance',
            'Hajj training program and guidance',
            'Religious guide book',
            'Assistance in doing Qurbani',
            'Assistance in Tawaf-e-Ziyarah',
        ];
    }

    /** @return string[] */
    private function sharedExclusions(): array
    {
        return [
            'Airline ticket (approx. PKR 335,000 from Karachi / PKR 345,000 from North Pakistan; fares vary for Hajis travelling from international destinations) — KHI-JED-KHI or KHI/JED/MED-KHI routing, PSF inclusive',
            // Stated as the difference it actually is, rather than as one
            // figure. The PKR brochure lists Qurbani among the inclusions;
            // the Riyal and US$ brochures list a charge for it (SAR 750 /
            // US$200). A reader of the rupee list was being told they owed
            // two hundred dollars for something their own list includes.
            'Qurbani cost, on the riyal and US dollar price lists only (approx. SAR 750 / US$200) — the rupee list includes it. Assistance in arranging it is included either way',
        ];
    }

    /** @return string[] */
    private function aziziyaAmenities(): array
    {
        return [
            'Accommodation centrally located for easy access to Haram and Mina',
            'Fully air-conditioned rooms with a mini fridge and attached bathroom',
            'Separate prayer areas for men and women',
            'Daily religious talks & Islamic guidance by Moulana',
            '2 lifts in the Aziziya Accommodation with wheelchair access',
            'Large dining area for buffet meals (separate sections for men and women)',
            'Proper beds with high-quality mattresses, bed sheets, pillow covers, blankets, and towels',
            'Shuttle service from Aziziya to Haram',
            'Spacious reception area with free WiFi in the lobby',
            'Facility for safekeeping of valuables and cash in the accommodation lockers',
            'Complimentary washing machine available',
            'Daily bathroom cleaning with 24/7 staff availability',
            'Large LCD screen in the lobby for live telecast from Haram',
            'Water coolers, tea/coffee dispensers, iron, and ironing board on every floor',
        ];
    }
}
