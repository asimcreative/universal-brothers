<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aziziya's own sharing/pricing structure (directive §8-9) — never the
 * same pricing records as package_room_options. `pricing_type` captures
 * whether a value is the included base price or a supplement/optional
 * upgrade (e.g. the family-room supplement is real brochure data, and must
 * never be mistaken for the main package price). Family Room is modeled as
 * just another row here (pricing_type=supplement) rather than a separate
 * mechanism, since the brochure treats it as another Aziziya room type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_aziziya_room_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_aziziya_id')->constrained('package_aziziya')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('package_variants')->cascadeOnDelete();
            $table->string('sharing_type');
            $table->unsignedTinyInteger('occupancy')->nullable();
            $table->string('display_label');
            $table->enum('pricing_type', ['included', 'supplement', 'optional', 'upgrade', 'on_request']);
            $table->string('price_basis')->default('per_person');
            $table->decimal('price_pkr', 12, 2)->nullable();
            $table->decimal('price_sar', 12, 2)->nullable();
            $table->decimal('price_usd', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_aziziya_room_options');
    }
};
