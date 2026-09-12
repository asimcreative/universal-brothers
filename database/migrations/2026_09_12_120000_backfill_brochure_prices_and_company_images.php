<?php

use Database\Seeders\CompanyImageSeeder;
use Database\Seeders\HajjPriceCurrencySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Backfill the PKR/SAR room prices and the company's own award/affiliation
 * images onto an environment that already has its packages seeded.
 *
 * Why this is a migration and not left to `db:seed`: the production deploy runs
 * `artisan migrate --force` but never `db:seed`, so registering the two seeders
 * in DatabaseSeeder only ever reached local environments. The first deploy of
 * this work shipped the code perfectly and left production with the old data —
 * every price still USD-only, every award still a generated seal — because
 * nothing on the server runs seeders.
 *
 * Backfilling existing rows with reference data the business already owns is a
 * normal data migration, and doing it here means it happens exactly once per
 * environment, automatically, in the same step that already runs on deploy.
 *
 * Both seeders are idempotent and neither overwrites anything a user has
 * uploaded or edited, so re-running is harmless.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => HajjPriceCurrencySeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => CompanyImageSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Deliberately not reversed. Rolling back would blank real brochure
        // prices and the client's own award photography, which is destructive
        // and is never what "undo this migration" should mean here.
    }
};
