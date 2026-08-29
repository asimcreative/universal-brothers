<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('quote');
            $table->enum('service_tag', ['hajj', 'umrah', 'tourism', 'general'])->default('general');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('photo')->nullable();
            $table->string('source')->nullable()->comment('Provenance note, e.g. which live site/page this was recovered from');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
