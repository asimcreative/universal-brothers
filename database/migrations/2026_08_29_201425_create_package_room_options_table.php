<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Main package room/sharing pricing (directive §4-6). `sharing_type` is a
 * free string, not a fixed enum — the brochure already prints a 4th type
 * ("Sharing Room", UB008/UB010) beyond Quad/Triple/Double, and the
 * directive explicitly forbids hardcoding to only three. One row per
 * (package, variant-or-shared, sharing type); a null variant_id means the
 * price applies regardless of variant (packages with no A/B split).
 * `is_available = false` represents a real brochure "N/A" cell (e.g.
 * UB001 Package A Quad), not a missing/unseeded value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_room_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('package_variants')->cascadeOnDelete();
            $table->string('sharing_type')->comment('e.g. "quad", "triple", "double", "sharing_room" — free string, not hardcoded');
            $table->unsignedTinyInteger('occupancy')->nullable()->comment('Null where the brochure gives no single number (e.g. "Sharing Room" 4-5 persons per T&C #28)');
            $table->string('display_label')->comment('Brochure-facing label, e.g. "Quad Sharing", "Sharing Room"');
            $table->string('price_basis')->default('per_person');
            $table->decimal('price_pkr', 12, 2)->nullable();
            $table->decimal('price_sar', 12, 2)->nullable();
            $table->decimal('price_usd', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_room_options');
    }
};
