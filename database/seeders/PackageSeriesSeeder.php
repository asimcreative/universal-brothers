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
                'name' => 'Platinum',
                'slug' => 'platinum',
                'description' => 'Hotels in front of the Haram in both cities, non-shifting, with no Aziziya leg.',
                'sort_order' => 1,
            ],
            [
                'package_category_id' => $hajj->id,
                'name' => 'Flex',
                'slug' => 'flex',
                'description' => 'A shifting itinerary: Haram-front hotels for the main stay, with an Aziziya leg over the days of Hajj.',
                'sort_order' => 2,
            ],
            [
                'package_category_id' => $hajj->id,
                'name' => 'Comfort',
                'slug' => 'comfort',
                'description' => 'Longer stays on a shifting itinerary, with an Aziziya leg over the days of Hajj.',
                'sort_order' => 3,
            ],
            [
                'package_category_id' => $hajj->id,
                'name' => 'Value',
                'slug' => 'value',
                'description' => 'Aziziya accommodation throughout the Makkah stay, non-shifting.',
                'sort_order' => 4,
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
