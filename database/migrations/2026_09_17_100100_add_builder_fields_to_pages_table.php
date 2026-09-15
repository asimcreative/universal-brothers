<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Page builder, publishing workflow and sharing fields for CMS pages.
 *
 *   status          draft | published | scheduled | archived
 *   sections        the PUBLISHED sections (JSON list; see PAGE_BUILDER_ARCHITECTURE.md)
 *   draft           the working copy an admin is editing (JSON document), or null
 *   focus_keyword, og_title, og_description, og_image, noindex   search and sharing
 *
 * `sections` stays null for every existing page, and a page without sections
 * renders exactly as before from `body` and `template` — nothing on the live
 * site changes until someone publishes a page from the builder.
 *
 * `is_active` is kept and still means "may be shown publicly", so everything
 * that already reads it (sitemap, AI knowledge index, the public route) keeps
 * working. Existing pages get the status their `is_active` already implied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('template')->index();
            $table->longText('sections')->nullable()->after('body');
            $table->longText('draft')->nullable()->after('sections');
            $table->string('focus_keyword', 100)->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->string('og_description', 300)->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->boolean('noindex')->default(false)->after('og_image');
            $table->foreignId('updated_by')->nullable()->after('noindex')->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });

        DB::table('pages')->where('is_active', true)->update(['status' => 'published']);
        DB::table('pages')->where('is_active', false)->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'sections', 'draft', 'focus_keyword', 'og_title', 'og_description', 'og_image', 'noindex']);
        });
    }
};
