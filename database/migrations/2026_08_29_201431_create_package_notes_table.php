<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured package notes (directive §19) — general/pricing/accommodation/
 * booking/travel/important/disclaimer, repeatable per package.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->enum('note_type', ['general', 'pricing', 'accommodation', 'booking', 'travel', 'important', 'disclaimer']);
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('is_important')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_notes');
    }
};
