<?php

use App\Support\Library\LibraryBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Link the fourteen packages added in September to the reusable library.
 *
 * The backfill that builds the library and links every package row to it ran
 * on 14 September, when there were twelve packages. The other fourteen
 * arrived afterwards, through a migration of their own, and nothing has run
 * the backfill since — so on production every one of their hotel,
 * transport and upgrade rows has a null library link.
 *
 * Nobody browsing the site would see it. What breaks is the admin: the hotel
 * library does not count those packages as using a record, a library update
 * pushed to packages never reaches them, and the usage guard that refuses to
 * delete a record in use does not know they use it.
 *
 * The backfill only touches rows that have no link yet and reuses identical
 * records rather than creating duplicates, so running it again is safe — it
 * has a test that asserts exactly that ("running the backfill again changes
 * nothing"). It also now knows that "Dar Al Tawhid IHG", which the September
 * brochures print, is the hotel already seeded as "Dar Al Tawhid
 * Intercontinental Makkah", so linking these packages does not leave two
 * records for one building.
 */
return new class extends Migration
{
    public function up(): void
    {
        $result = (new LibraryBackfill)->run();

        Log::info('Package library backfill, for the September packages', $result);
    }

    public function down(): void
    {
        // Nothing to undo: this created no library records that the original
        // backfill would not have, and unlinking rows would only put the
        // admin back where it was unable to see them.
    }
};
