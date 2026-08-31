<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mina (directive §13) and Arafat (directive §14) structured detail, merged
 * into one table discriminated by `location` — the same pattern already
 * used by package_accommodations for 5 locations, applied here since Mina
 * and Arafat share an identical field set in this brochure (maktab, tent
 * type, meals, bathroom, A/C) and a separate table per location would be
 * an unnecessary duplicate structure for two rows that are, in this real
 * brochure, identical across all 12 packages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_mashaer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->enum('location', ['mina', 'arafat']);
            $table->string('maktab')->nullable();
            $table->string('category')->nullable();
            $table->string('zone')->nullable();
            $table->string('tent_type')->nullable();
            $table->string('accommodation_type')->nullable();
            $table->string('meal_plan')->nullable();
            $table->string('bathroom')->nullable();
            $table->string('air_conditioning')->nullable();
            $table->string('transportation')->nullable();
            $table->text('other_services')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['package_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_mashaer_details');
    }
};
