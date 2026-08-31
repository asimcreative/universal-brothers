<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FINAL_CODE_REVIEW.md H-4: Package uses SoftDeletes, but a plain column-level
 * UNIQUE constraint on `slug`/`code` blocks reusing a trashed package's value
 * even though the admin can no longer see that row anywhere. Neither MySQL 8
 * nor SQLite support a portable partial/filtered unique index (`WHERE
 * deleted_at IS NULL`) through Laravel's engine-agnostic schema builder, so
 * true uniqueness is enforced at the application layer instead — see
 * PackageRequest's `Rule::unique(...)->where(fn ($q) => $q->whereNull('deleted_at'))`.
 * A plain (non-unique) index is kept here for route-model-binding lookup
 * performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropUnique(['code']);
            $table->index('slug');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['code']);
            $table->unique('slug');
            $table->unique('code');
        });
    }
};
