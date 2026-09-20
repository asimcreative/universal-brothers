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
            AwardSeeder::class,
            AffiliationSeeder::class,
            AboutPageSeeder::class,
            LegalPageSeeder::class,
            AdminUserSeeder::class,

            // The header's top strip: the announcements its ticker reads, and
            // a link for each social platform so its icon appears. Both had
            // only ever existed as hand-made rows, so a fresh database showed
            // a silent ticker and one lonely icon.
            SiteChromeSeeder::class,

            // These two run AFTER the content seeders above, because they
            // enrich rows those seeders create rather than creating their own.
            // Both are idempotent and both leave anything the client has
            // uploaded or edited alone.
            HajjPriceCurrencySeeder::class,
            CompanyImageSeeder::class,

            // Last: builds the reusable package library from the packages
            // seeded above and links them to it. On an existing database the
            // same work already ran as a migration; it is idempotent.
            PackageLibrarySeeder::class,
        ]);
    }
}
