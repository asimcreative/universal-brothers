<?php

use App\Support\Library\LibraryBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fills the reusable library from the content the live Hajj packages already
 * repeat and links every package row to its library record — see
 * LibraryBackfill for exactly what it touches. Also moves brochure-provenance
 * commentary that was showing on four public package pages into the new
 * admin-only internal notes (issue #10).
 *
 * Idempotent: it only processes rows with no link yet and reuses identical
 * library records, so re-running it (or seeding afterwards) creates nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $result = (new LibraryBackfill)->run();

        Log::info('Package library backfill', $result);
    }

    /**
     * Rolling back 2026_09_14_200000 drops `internal_notes`, which would lose
     * the brochure-audit remarks this moved. Put each one back where it came
     * from — the package description — so a rollback never deletes data. The
     * library tables and links themselves are dropped by that migration.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('packages', 'internal_notes')) {
            return;
        }

        DB::table('packages')
            ->whereNull('description')
            ->whereNotNull('internal_notes')
            ->get(['id', 'internal_notes'])
            ->filter(fn ($package) => LibraryBackfill::isInternalNote($package->internal_notes))
            ->each(fn ($package) => DB::table('packages')->where('id', $package->id)->update(['description' => $package->internal_notes]));
    }
};
