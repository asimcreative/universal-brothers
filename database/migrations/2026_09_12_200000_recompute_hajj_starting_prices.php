<?php

use Database\Seeders\HajjBrochureCorrectionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Finish the brochure correction on environments that already ran the first
 * one.
 *
 * The first pass moved the room prices but not `packages.starting_price`,
 * which is derived from them — and which the detail page publishes as the
 * Schema.org Offer price. Production was therefore left showing the corrected
 * figures in its price table while handing the superseded one to search
 * engines, and showing it on the listing card.
 *
 * A second migration rather than an edit to the first, because the first has
 * already run everywhere and an edited migration would never run again.
 *
 * HajjBrochureCorrectionSeeder is idempotent and derives both the superseded
 * and corrected minima itself, so it does the right thing whether the room
 * prices were corrected by the earlier migration or are still untouched.
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
        // Deliberately not reversed, for the same reason as the migration it
        // completes: rolling back would republish superseded prices.
    }
};
