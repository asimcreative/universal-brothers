<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured, priced optional upgrades (directive §20) — e.g. Kaba view
 * supplement, additional Medinah night. Deliberately separate from Aziziya
 * upgrades, which live under package_aziziya_room_options instead (per
 * directive: "Aziziya upgrades should remain under Aziziya where
 * applicable... do not mix Aziziya upgrades with the main package price").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable()->comment('Null = on request, no brochure price given');
            $table->enum('currency', ['USD', 'PKR', 'SAR'])->nullable();
            $table->string('price_basis')->nullable();
            $table->boolean('is_included')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_upgrades');
    }
};
