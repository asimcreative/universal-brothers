<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Package (A) / Package (B)" accommodation variants (brochure directive
 * §3). Not every Hajj package has variants (e.g. UB011/UB013/UB024 print a
 * single accommodation/price column) — packages without a row here simply
 * have no variant split; package_accommodations/package_room_options rows
 * with a null variant_id apply regardless of variant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10)->comment('Brochure letter, e.g. "A", "B" — free string, not a fixed enum, so a future Package C needs no schema change');
            $table->string('label')->nullable()->comment('e.g. "Dar Al Tawhid Intercontinental" — optional descriptive label beyond the bare code');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_variants');
    }
};
