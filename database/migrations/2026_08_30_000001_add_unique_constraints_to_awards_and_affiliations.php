<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md L-8: both seeders correctly use
 * updateOrCreate() keyed on name/organization_name, but neither original
 * migration added a database-level backstop against a duplicate row via
 * the admin CRUD (AwardController::store()/AffiliationController::store()
 * both call plain create() with no uniqueness check) or any other write path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('affiliations', function (Blueprint $table) {
            $table->unique('organization_name');
        });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('affiliations', function (Blueprint $table) {
            $table->dropUnique(['organization_name']);
        });
    }
};
