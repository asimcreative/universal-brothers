<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HAJJ PACKAGE SYSTEM redesign: adds the two Hajj-specific single-value
 * fields the brochure actually distinguishes (Medinah First / Makkah First,
 * and a free-text package type like "Executive Platinum") directly on
 * `packages`, and widens the currency enums used across the schema to
 * include SAR alongside the existing USD/PKR — the brochure itself is
 * USD-only (see docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md), so no
 * SAR/PKR *values* are seeded, but the column must support them per the
 * "one package, three currencies" business rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('medinah_first')->nullable()->after('is_shifting');
            $table->string('package_type')->nullable()->after('name');
        });

        $this->widenCurrencyEnum('packages', 'currency', "'USD'");
        $this->widenCurrencyEnum('package_room_prices', 'currency', "'USD'");
        $this->widenCurrencyEnum('package_addons', 'currency', "'USD'");
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['medinah_first', 'package_type']);
        });
    }

    /**
     * SQLite has no native ALTER on a CHECK/enum constraint — Laravel's
     * schema builder recreates the table under the hood via
     * `Schema::table` + `change()`, which requires doctrine/dbal on older
     * Laravel versions but works natively here since Laravel 11+'s SQLite
     * grammar. MySQL uses a plain `MODIFY COLUMN`.
     */
    private function widenCurrencyEnum(string $table, string $column, string $default): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $column, $default) {
                $blueprint->enum($column, ['USD', 'PKR', 'SAR'])->default(trim($default, "'"))->change();
            });

            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM('USD','PKR','SAR') NOT NULL DEFAULT {$default}");
    }
};
