<?php

use Database\Seeders\HajjBrochureCorrectionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Apply the current brochures' corrections to an environment that was already
 * seeded from the superseded 20 Aug 2026 deck.
 *
 * As with the previous backfill: the deploy runs `artisan migrate --force` and
 * never `db:seed`, so a seeder registered in DatabaseSeeder only ever reaches
 * local environments. Published prices being wrong on the live site is not
 * something to leave waiting for a manual step, so it runs here.
 *
 * The seeder only writes where a row still holds the exact superseded value,
 * which makes this safe to run repeatedly and safe against anything the client
 * has since edited in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => HajjBrochureCorrectionSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Deliberately not reversed. Rolling back would republish prices the
        // client's current brochures contradict, and restore hotel names they
        // have since upgraded.
    }
};
