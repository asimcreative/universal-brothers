<?php

namespace Database\Seeders;

use App\Models\PackageCategory;
use Illuminate\Database\Seeder;

class PackageCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hajj',
                'slug' => 'hajj',
                'icon' => 'bi-moon-stars',
                'description' => 'Hajj packages with real 2027/1448 AH itineraries, hotels, and pricing.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Umrah',
                'slug' => 'umrah',
                'icon' => 'bi-building',
                'description' => 'Umrah packages — content pending client-supplied package data.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Tourism',
                'slug' => 'tourism',
                'icon' => 'bi-airplane',
                'description' => 'Domestic and international tourism packages.',
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            PackageCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
