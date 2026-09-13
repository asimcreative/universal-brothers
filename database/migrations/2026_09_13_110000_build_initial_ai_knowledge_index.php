<?php

use App\Support\Ai\KnowledgeIndexer;
use Illuminate\Database\Migrations\Migration;

/**
 * Build the knowledge index once, on the deploy that introduces the assistant.
 *
 * Without this the index is empty on a fresh environment, and an empty index
 * means the retriever matches nothing: the assistant would answer every
 * question with "I don't have that information" while looking perfectly
 * healthy. That failure is silent, which is the worst kind here.
 *
 * It runs here for the same reason the brochure backfill did: this project's
 * deploy runs `artisan migrate --force` and nothing else — no `db:seed`, no
 * custom commands — so a step that is not a migration does not happen on
 * production. That lesson cost a full round trip on issue #5.
 *
 * The index is rebuilt wholesale each time, so this is safe to re-run. After
 * this, content changes are picked up by `php artisan ai:index` or the
 * "Rebuild knowledge index" button in the admin. Prices and package facts do
 * not need either — they are read live at answer time.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(KnowledgeIndexer::class)->rebuild();
    }

    public function down(): void
    {
        // The table is dropped by the migration that created it; there is
        // nothing to undo here, and clearing a search index on rollback would
        // only make a rolled-back environment harder to diagnose.
    }
};
