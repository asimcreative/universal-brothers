<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aziziya accommodation as its own dedicated structure (directive §7),
 * deliberately separate from the main package's room pricing — the single
 * most important correction the directive asked for. One row per package,
 * but a package can have a row here even when its base `aziziya_status` is
 * `optional` on a package that is otherwise Non-Aziziya (every one of the 8
 * Non-Aziziya packages in this brochure offers an optional Aziziya family
 * room upgrade — see package_aziziya_room_options/services below).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_aziziya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['included', 'not_included', 'optional', 'not_applicable']);
            $table->string('accommodation_name')->nullable()->comment('e.g. "AZIZIYA Accommodation - A Class"');
            $table->string('location_note')->nullable();
            $table->string('walk_distance')->nullable()->comment('e.g. "30 to 50-minute walk to Mina camp"');
            $table->unsignedInteger('duration_days')->nullable()->comment('e.g. 5 — "duration of 05 days of Hajj"');
            $table->unsignedTinyInteger('average_occupancy')->nullable()->comment('e.g. 4 — "Average of 4 persons per room"');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_aziziya');
    }
};
