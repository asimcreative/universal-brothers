<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repeatable Aziziya amenities (directive §10) — e.g. shuttle service,
 * WiFi, lockers, laundry. The brochure lists these as included amenities
 * with no individual price, so `is_included` defaults true and price stays
 * null for the real seeded rows; the fields exist for a future genuinely
 * priced optional service without inventing one now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_aziziya_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_aziziya_id')->constrained('package_aziziya')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_included')->default(true);
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('currency', ['USD', 'PKR', 'SAR'])->nullable();
            $table->string('price_basis')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_aziziya_services');
    }
};
