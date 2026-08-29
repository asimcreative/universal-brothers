<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number');
            $table->date('date_gregorian')->nullable();
            $table->string('date_hijri_label')->nullable();
            $table->string('city')->nullable();
            $table->string('accommodation_a')->nullable()->comment('Package (A) hotel-tier cell, as printed in the brochure');
            $table->string('accommodation_b')->nullable()->comment('Package (B) hotel-tier cell, null when the package has only one tier');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_itinerary_days');
    }
};
