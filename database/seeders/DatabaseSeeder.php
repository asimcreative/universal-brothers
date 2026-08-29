<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PackageCategorySeeder::class,
            PackageSeriesSeeder::class,
            HotelSeeder::class,
            HajjPackageSeeder::class,
            TourismPackageSeeder::class,
            TestimonialSeeder::class,
            OfficeSeeder::class,
            SiteSettingSeeder::class,
            FaqSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
