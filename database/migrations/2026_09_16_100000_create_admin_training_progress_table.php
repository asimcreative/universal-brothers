<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each admin's progress through the video training (issue #12): where they
 * stopped in each chapter, how far they have watched, and whether they have
 * finished it. One row per admin per chapter; chapters themselves live in
 * resources/data/admin-video-training.php, not in the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chapter_key', 60);
            $table->unsignedInteger('position_seconds')->default(0);
            $table->unsignedInteger('furthest_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chapter_key']);
            $table->index(['user_id', 'last_watched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_training_progress');
    }
};
