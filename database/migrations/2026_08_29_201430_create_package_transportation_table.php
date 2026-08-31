<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured transportation (directive §15) — repeatable per package.
 * `transport_type` is a free string (airport_transfer, makkah_medinah,
 * mashaer, train, bus, other, ...) rather than a fixed enum, consistent
 * with the same "no hardcoding" instruction applied to sharing types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_transportation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('transport_type');
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
        Schema::dropIfExists('package_transportation');
    }
};
