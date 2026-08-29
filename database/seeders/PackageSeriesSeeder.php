<?php

namespace Database\Seeders;

use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Database\Seeder;

class PackageSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $hajj = PackageCategory::where('slug', 'hajj')->firstOrFail();
        $tourism = PackageCategory::where('slug', 'tourism')->firstOrFail();

        $series = [
            [
                'package_category_id' => $hajj->id,
                'name' => 'Platinum — Non-Aziziya (Makkah & Medinah Series)',
                'slug' => 'platinum-non-aziziya',
                'description' => 'In front of Haram, non-shifting, no Aziziya leg.',
                'sort_order' => 1,
            ],
            [
                'package_category_id' => $hajj->id,
                'name' => 'Platinum — With Aziziya (Flex Series)',
                'slug' => 'platinum-with-aziziya',
                'description' => 'Shifting itinerary including an Aziziya accommodation leg.',
                'sort_order' => 2,
            ],
            [
                'package_category_id' => $hajj->id,
                'name' => 'Platinum Value — With Aziziya (Medinah Series)',
                'slug' => 'platinum-value-aziziya',
                'description' => 'Non-shifting value tier with Aziziya accommodation.',
                'sort_order' => 3,
            ],
            [
                'package_category_id' => $tourism->id,
                'name' => 'Domestic',
                'slug' => 'domestic',
                'description' => 'Domestic Pakistan tours.',
                'sort_order' => 1,
            ],
            [
                'package_category_id' => $tourism->id,
                'name' => 'International',
                'slug' => 'international',
                'description' => 'International tours.',
                'sort_order' => 2,
            ],
        ];

        foreach ($series as $s) {
            PackageSeries::updateOrCreate(
                ['package_category_id' => $s['package_category_id'], 'slug' => $s['slug']],
                $s
            );
        }
    }
}
