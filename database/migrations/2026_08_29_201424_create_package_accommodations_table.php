<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured accommodation blocks (directive §2/§3), one row per
 * (package, location, variant-or-shared). A null variant_id means this
 * accommodation applies regardless of which Package A/B variant the guest
 * booked — e.g. a shared Makkah hotel while only Medinah splits A/B (UB004),
 * or vice versa (UB001/003). This is distinct from package_itinerary_days'
 * own accommodation_a/accommodation_b text columns, which remain the
 * verbatim per-day display string exactly as printed; this table is the
 * structured master record (hotel name, star rating, nights) used for a
 * dedicated Accommodation admin section and public display, not a
 * duplicate of the itinerary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('package_variants')->cascadeOnDelete();
            $table->enum('location', ['makkah', 'medinah', 'aziziya', 'mina', 'arafat']);
            $table->string('hotel_name')->comment('Hotel name for Makkah/Medinah/Aziziya, or the printed zone/marquee label for Mina/Arafat');
            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->string('meal_plan')->nullable()->comment('e.g. "Half board (breakfast & dinner)", "Full board buffet"');
            $table->string('distance_note')->nullable()->comment('Only populated where the brochure states a real distance/walk time');
            $table->unsignedInteger('nights')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_accommodations');
    }
};
