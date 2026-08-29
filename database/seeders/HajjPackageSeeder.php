<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the 12 real Hajj 2027 / 1448 AH packages exactly as printed in
 * "HAJJ 2027 Packages overseas.pdf" (Universal Brothers / Crown Packages).
 * Every code, date, hotel name, and price below was transcribed directly
 * from the brochure — nothing here is invented, per the project's
 * "never fabricate business data" rule (see PROJECT_REQUIREMENTS.md §F).
 *
 * A genuine brochure quirk is preserved deliberately rather than "fixed":
 * UB004/UB008/UB011 are headed "14 Days Package" but their own itinerary
 * table only lists 13 numbered days (7–19 May) — the Aziziya-series "14 day"
 * packages (UB015, UB023) correctly list 14 rows. Both are reproduced as
 * printed.
 */
class HajjPackageSeeder extends Seeder
{
    public function run(): void
    {
        $category = PackageCategory::where('slug', 'hajj')->firstOrFail();
        $nonAziziya = PackageSeries::where('slug', 'platinum-non-aziziya')->firstOrFail();
        $withAziziya = PackageSeries::where('slug', 'platinum-with-aziziya')->firstOrFail();
        $value = PackageSeries::where('slug', 'platinum-value-aziziya')->firstOrFail();

        $nonAziziyaInclusions = $this->nonAziziyaInclusions();
        $nonAziziyaExclusions = $this->sharedExclusions();
        $azizyaInclusions = $this->azizyaInclusions();
        $azizyaExclusions = $this->sharedExclusions();

        $packages = [
            // --- Platinum, Non-Aziziya (Makkah & Medinah series) ---
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB001',
                'name' => 'Executive Platinum Intercon / Fairmont — Medinah First',
                'duration_days' => 13,
                'duration_label' => '13 Days Package',
                'is_shifting' => false,
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
                'tiers' => [
                    ['label' => 'Package A — Dar Al Tawhid Intercontinental', 'prices' => ['quad' => null, 'triple' => 22450, 'double' => 26850]],
                    ['label' => 'Package B — Fairmont Clock Tower', 'prices' => ['quad' => 16300, 'triple' => 18100, 'double' => 20850]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => $nonAziziyaExclusions,
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB003',
                'name' => 'Executive Platinum Intercon / Fairmont — Medinah First',
                'duration_days' => 10,
                'duration_label' => '10 Days Package',
                'is_shifting' => false,
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
                'tiers' => [
                    ['label' => 'Package A — Dar Al Tawhid Intercontinental', 'prices' => ['quad' => null, 'triple' => 21650, 'double' => 26050]],
                    ['label' => 'Package B — Fairmont Clock Tower', 'prices' => ['quad' => 15890, 'triple' => 17250, 'double' => 20000]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => array_merge($nonAziziyaExclusions, ['Kaba view supplement: US$2,200 per person']),
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB004',
                'name' => 'Executive Platinum Swissotel — Medinah First',
                'duration_days' => 14,
                'duration_label' => '14 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
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
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa/Hilton + Swissotel Makkah', 'prices' => ['quad' => 14950, 'triple' => 16300, 'double' => 19300]],
                    ['label' => 'Package B — Taibah Front + Swissotel Makkah', 'prices' => ['quad' => 14200, 'triple' => 15590, 'double' => 18350]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => array_merge($nonAziziyaExclusions, ['Kaba view supplement: US$2,200 per person']),
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB006',
                'name' => 'Executive Platinum Swissotel — Medinah First (Short Package)',
                'duration_days' => 10,
                'duration_label' => 'Short Package — 10 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Swissotel Makkah ★★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Swissotel Makkah ★★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa + Swissotel Makkah', 'prices' => ['quad' => 15350, 'triple' => 15750, 'double' => 18500]],
                    ['label' => 'Package B — Taibah Front + Swissotel Makkah', 'prices' => ['quad' => 18850, 'triple' => 15200, 'double' => 17950]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => array_merge($nonAziziyaExclusions, ['Kaba view supplement: US$2,200 per person']),
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB008',
                'name' => 'Executive Platinum Makkah Tower — Medinah First',
                'duration_days' => 14,
                'duration_label' => '14 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa/Hilton ★★★★★', 'Taibah Front / Similar ★★★'],
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
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa/Hilton + Makkah Tower', 'prices' => ['sharing' => null, 'quad' => 13850, 'triple' => 15200, 'double' => 18220]],
                    ['label' => 'Package B — Taibah Front + Makkah Tower', 'prices' => ['sharing' => 13000, 'quad' => 13000, 'triple' => 14650, 'double' => 17400]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => $nonAziziyaExclusions,
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB010',
                'name' => 'Executive Platinum Makkah Tower — Medinah First',
                'duration_days' => 10,
                'duration_label' => '10 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Makkah Tower ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Makkah Tower ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa + Makkah Tower', 'prices' => ['sharing' => null, 'quad' => 13550, 'triple' => 14650, 'double' => 17400]],
                    ['label' => 'Package B — Taibah Front + Makkah Tower', 'prices' => ['sharing' => 12750, 'quad' => 12750, 'triple' => 14390, 'double' => 16850]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => $nonAziziyaExclusions,
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB011',
                'name' => 'Executive Platinum Voco by IHG — Medinah First',
                'duration_days' => 14,
                'duration_label' => '14 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Taibah Front / Similar ★★★', null],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Taibah Front / Similar ★★★', null],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Taibah Front / Similar ★★★', null],
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
                'tiers' => [
                    ['label' => null, 'prices' => ['quad' => 10450, 'triple' => 11500, 'double' => 13425]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => $nonAziziyaExclusions,
            ],
            [
                'series_id' => $nonAziziya->id,
                'code' => 'UB013',
                'name' => 'Executive Platinum Voco by IHG — Makkah First (Short Package)',
                'duration_days' => 10,
                'duration_label' => 'Short Package — 10 Days Package',
                'is_shifting' => false,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Taibah Front / Similar ★★★', null],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Taibah Front / Similar ★★★', null],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Voco Makkah By IHG ★★★★', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'tiers' => [
                    ['label' => null, 'prices' => ['quad' => 10450, 'triple' => 11500, 'double' => 13425]],
                ],
                'inclusions' => $nonAziziyaInclusions,
                'exclusions' => $nonAziziyaExclusions,
                'note' => 'Brochure names this "Makkah First" though the printed itinerary still visits Medinah before Makkah — reproduced exactly as printed, not corrected.',
            ],

            // --- Platinum, With Aziziya (Flex series, shifting) ---
            [
                'series_id' => $withAziziya->id,
                'code' => 'UB015',
                'name' => 'Executive Platinum Flex 14 — Medinah First',
                'duration_days' => 14,
                'duration_label' => 'Flex 14 Days',
                'is_shifting' => true,
                'has_aziziya' => true,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [4, '2027-05-10', '04 Zil Hajj', 'To Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [5, '2027-05-11', '05 Zil Hajj', 'Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [6, '2027-05-12', '06 Zil Hajj', 'Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [7, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [8, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [10, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [11, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [12, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [13, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Aziziya Accommodation — A Class', null],
                    [14, '2027-05-20', '14 Zil Hajj', null, 'Departure to Airport', null],
                ],
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa + Abraaj Tower', 'prices' => ['quad' => 11650, 'triple' => 12750, 'double' => 14520]],
                    ['label' => 'Package B — Taibah Front + Abraaj Tower', 'prices' => ['quad' => 11100, 'triple' => 12200, 'double' => 13560]],
                ],
                'inclusions' => $azizyaInclusions,
                'exclusions' => array_merge($azizyaExclusions, ['Kaba view supplement: US$2,200 per person (per this package\'s own page note)']),
            ],
            [
                'series_id' => $withAziziya->id,
                'code' => 'UB016',
                'name' => 'Executive Platinum Flex 10 — Medinah First',
                'duration_days' => 10,
                'duration_label' => 'Flex 10 Days',
                'is_shifting' => true,
                'has_aziziya' => true,
                'itinerary' => [
                    [1, '2027-05-10', '04 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-11', '05 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-12', '06 Zil Hajj', 'To Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [4, '2027-05-13', '07 Zil Hajj', 'Makkah', 'Abraaj Tower / Swiss Maqam ★★★★★', null],
                    [5, '2027-05-14', '08 Zil Hajj', 'To Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [6, '2027-05-15', '09 Zil Hajj', 'Mina', 'Arafat Air Conditioned Marquee (Exclusive Services)', null],
                    [7, '2027-05-16', '10 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [8, '2027-05-17', '11 Zil Hajj', 'Mina', 'Zone 1 near to Jamarat A Category (Exclusive Services)', null],
                    [9, '2027-05-18', '12 Zil Hajj', 'To Makkah', 'Aziziya Accommodation — A Class', null],
                    [10, '2027-05-19', '13 Zil Hajj', 'Makkah', 'Departure to Airport', null],
                ],
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa + Abraaj Tower', 'prices' => ['quad' => 11100, 'triple' => 11920, 'double' => 12880]],
                    ['label' => 'Package B — Taibah Front + Abraaj Tower', 'prices' => ['quad' => 10685, 'triple' => 11390, 'double' => 12330]],
                ],
                'inclusions' => $azizyaInclusions,
                'exclusions' => $azizyaExclusions,
            ],

            // --- Platinum Value, With Aziziya (Medinah series, non-shifting) ---
            [
                'series_id' => $value->id,
                'code' => 'UB023',
                'name' => 'Executive Platinum Value 14 — Medinah First',
                'duration_days' => 14,
                'duration_label' => 'Value 14 Days',
                'is_shifting' => false,
                'has_aziziya' => true,
                'itinerary' => [
                    [1, '2027-05-07', '01 Zil Hajj', 'To Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [2, '2027-05-08', '02 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
                    [3, '2027-05-09', '03 Zil Hajj', 'Medinah', 'Dar Al Taqwa ★★★★★', 'Taibah Front / Similar ★★★'],
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
                'tiers' => [
                    ['label' => 'Package A — Dar Al Taqwa + Aziziya', 'prices' => ['quad' => 10410, 'triple' => 10685, 'double' => 11230]],
                    ['label' => 'Package B — Taibah Front + Aziziya', 'prices' => ['quad' => 9725, 'triple' => 10000, 'double' => 10275]],
                ],
                'inclusions' => $azizyaInclusions,
                'exclusions' => array_merge($azizyaExclusions, ['Kaba view supplement: US$1,050 per person']),
            ],
            [
                'series_id' => $value->id,
                'code' => 'UB024',
                'name' => 'Executive Platinum Value 10 — Medinah First',
                'duration_days' => 10,
                'duration_label' => 'Value 10 Days',
                'is_shifting' => false,
                'has_aziziya' => true,
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
                'tiers' => [
                    ['label' => null, 'prices' => ['quad' => 9175, 'triple' => 9315, 'double' => 9450]],
                ],
                'inclusions' => $azizyaInclusions,
                'exclusions' => array_merge($azizyaExclusions, ['Kaba view supplement: US$1,050 per person']),
            ],
        ];

        foreach ($packages as $index => $data) {
            $slug = Str::slug($data['code'].'-'.$data['name']);

            $package = Package::updateOrCreate(
                ['code' => $data['code']],
                [
                    'package_category_id' => $category->id,
                    'package_series_id' => $data['series_id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'summary' => 'Hajj 2027 / 1448 AH — '.$data['duration_label'].', '.($data['is_shifting'] ? 'shifting' : 'non-shifting').' itinerary'.(($data['has_aziziya'] ?? false) ? ', with Aziziya accommodation' : ', no Aziziya leg').'.',
                    'description' => $data['note'] ?? null,
                    'duration_days' => $data['duration_days'],
                    'duration_label' => $data['duration_label'],
                    'is_shifting' => $data['is_shifting'],
                    'has_aziziya' => $data['has_aziziya'] ?? false,
                    'season_year' => 2027,
                    'season_label' => 'Hajj 2027 / 1448 AH',
                    'currency' => 'USD',
                    'starting_price' => $this->cheapestPrice($data['tiers']),
                    'is_featured' => in_array($data['code'], ['UB001', 'UB011', 'UB023'], true),
                    'is_seasonal' => true,
                    'status' => 'published',
                    'published_at' => now(),
                    'sort_order' => $index + 1,
                    'meta_title' => $data['name'].' — Hajj 2027 | Universal Brothers',
                    'meta_description' => 'Hajj 2027 package '.$data['code'].': '.$data['duration_label'].' itinerary with real hotel accommodation, Mina Zone 1 Category A tents, and Arafat AC marquee. Prices subject to change.',
                ]
            );

            $package->itineraryDays()->delete();
            foreach ($data['itinerary'] as [$day, $date, $hijri, $city, $a, $b]) {
                $package->itineraryDays()->create([
                    'day_number' => $day,
                    'date_gregorian' => $date,
                    'date_hijri_label' => $hijri,
                    'city' => $city,
                    'accommodation_a' => $a,
                    'accommodation_b' => $b,
                ]);
            }

            $package->priceTiers()->delete();
            foreach ($data['tiers'] as $tierIndex => $tier) {
                $priceTier = $package->priceTiers()->create([
                    'label' => $tier['label'],
                    'sort_order' => $tierIndex,
                ]);

                $roomOrder = ['sharing' => 0, 'quad' => 1, 'triple' => 2, 'double' => 3];
                foreach ($tier['prices'] as $roomType => $price) {
                    $priceTier->roomPrices()->create([
                        'room_type' => $roomType,
                        'price' => $price,
                        'currency' => 'USD',
                        'sort_order' => $roomOrder[$roomType] ?? 9,
                    ]);
                }
            }

            $package->inclusions()->delete();
            foreach ($data['inclusions'] as $i => $text) {
                $package->inclusions()->create(['type' => 'inclusion', 'description' => $text, 'sort_order' => $i]);
            }

            $package->exclusions()->delete();
            foreach ($data['exclusions'] as $i => $text) {
                $package->exclusions()->create(['type' => 'exclusion', 'description' => $text, 'sort_order' => $i]);
            }
        }

        $this->seedAddons($category->id);
    }

    private function cheapestPrice(array $tiers): ?float
    {
        $all = [];
        foreach ($tiers as $tier) {
            foreach ($tier['prices'] as $price) {
                if ($price !== null) {
                    $all[] = $price;
                }
            }
        }

        return $all === [] ? null : min($all);
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
            'Assistance in doing Qurbani (approx. US$200 charge applies — see exclusions)',
        ];
    }

    /** @return string[] */
    private function azizyaInclusions(): array
    {
        return [
            'Meet & assist at the airport Jeddah/Medinah Hajj Terminal (subject to approval handling)',
            'Group arrival transfer by bus from airport to hotel, provided by NAQABA/Saudi Moallim',
            'Average 4-person-sharing Aziziya accommodation, A-class air-conditioned building with proper beds (pillow, bed sheet, blanket)',
            'Full-board meals (breakfast, lunch, dinner) with hot & cold drinks in the Aziziya building, except Hajj days',
            'Accommodation in Makkah and Medinah hotels with breakfast and dinner (Saudi Stars standard)',
            'Full-board buffet meals served in Mina from 08 to 12 Zil Hajj',
            '5-day Platinum Mashaer arrangement (08–12 Zil Hajj) with Aziziya room retained',
            'Private special luxury buses with bathroom (Mina–Arafat–Muzdalifah–Mina); Makkah↔Medinah transfer by bus/train',
            'Best-location Maktab (A) tent in Mina, very near Jamarat, sofa-cum-bed, private toilet',
            'Mashaer-days tent services (pillow, bed sheet, blanket, A/C tent, buffet meal, hot & cold drinks, avg. 16/tent)',
            'Tent in Arafat with meals and drinks; Muzdalifah floor mat and snack box; mic and speaker for religious guidance',
            'Ziyarat in Medinah with guidance',
            'Hajj training program and guidance',
            'Religious guide book',
            'Aziziya accommodation opposite the Jamarat escalator: AC rooms with mini-fridge and attached bathroom, separate prayer areas, daily religious talks, 2 lifts with wheelchair access, shuttle to Haram (4x/day, 4th–6th Zil Hajj), free WiFi, safekeeping lockers, free washing machine, 24/7 staff',
            'Assistance in doing Qurbani (approx. US$200 charge applies — see exclusions)',
            'Assistance in Tawaf-e-Ziyarah',
        ];
    }

    /** @return string[] */
    private function sharedExclusions(): array
    {
        return [
            'Airline ticket (approx. PKR 335,000 from Karachi / PKR 345,000 from North Pakistan; fares vary for Hajis travelling from international destinations) — KHI-JED-KHI or KHI/JED/MED-KHI routing, PSF inclusive',
            'Qurbani actual cost (approx. US$200) — assistance in arranging it is included, the cost itself is not',
        ];
    }

    private function seedAddons(int $hajjCategoryId): void
    {
        $addons = [
            ['name' => 'Kaba view supplement (Non-Aziziya series)', 'price' => 2200, 'unit' => 'per person'],
            ['name' => 'Kaba view supplement (Aziziya series)', 'price' => 1050, 'unit' => 'per person'],
            ['name' => 'Extra Medinah night — Double room', 'price' => 850, 'unit' => 'per night per person'],
            ['name' => 'Extra Medinah night — Triple/Quad room', 'price' => 600, 'unit' => 'per night per person'],
            ['name' => 'Aziziya family room supplement (non-Aziziya-series upgrade, 5 days of Hajj)', 'price' => 5500, 'unit' => 'flat, for 5 days of Hajj'],
            ['name' => 'Aziziya family room supplement — Double (Aziziya/Value series)', 'price' => 1100, 'unit' => 'per person'],
            ['name' => 'Aziziya family room supplement — Triple (Aziziya/Value series)', 'price' => 550, 'unit' => 'per person'],
            ['name' => 'VIP GMC transport for 5 Mashaer days (Land Cruiser, max 6 persons, Urdu/English chauffeur)', 'price' => 9600, 'unit' => 'per GMC, for 5 days of Hajj'],
            ['name' => 'Family car/taxi — Jeddah Airport ↔ Makkah hotel', 'price' => 165, 'unit' => 'per person, round trip'],
            ['name' => 'Family car/taxi — Medinah Airport ↔ Medinah hotel', 'price' => 40, 'unit' => 'per person, round trip'],
        ];

        foreach ($addons as $i => $addon) {
            PackageAddon::updateOrCreate(
                ['package_category_id' => $hajjCategoryId, 'name' => $addon['name']],
                array_merge($addon, ['currency' => 'USD', 'sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
