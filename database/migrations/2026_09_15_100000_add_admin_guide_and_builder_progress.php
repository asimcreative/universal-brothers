<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guided onboarding, the help centre, and resumable package drafts (issue #11).
 *
 * - `users`: whether the admin has dismissed the welcome panel, and where they
 *   are in the guided tour, so the tour can be skipped and resumed on another
 *   day or another computer — a browser-only flag would restart it
 *   everywhere else.
 * - `admin_guide_completions`: guide sections each admin has marked as read.
 * - `packages.builder_step`: the step a draft was last saved on, so reopening
 *   it continues there.
 * - `packages.reviewed_hash`: a fingerprint of the package content at the
 *   moment the admin completed the final review. The review counts as done
 *   only while the content still matches it.
 * - `package_itinerary_days.transport`: how pilgrims travel that day.
 *
 * All additive and nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('is_active');
            $table->string('tour_status', 20)->default('not_started')->after('onboarding_dismissed_at')
                ->comment('not_started | in_progress | paused | completed');
            $table->unsignedTinyInteger('tour_step')->default(0)->after('tour_status');
        });

        Schema::create('admin_guide_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 60);
            $table->timestamp('completed_at');

            $table->unique(['user_id', 'section_key']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->string('builder_step', 20)->nullable()->after('archived_at');
            $table->string('reviewed_hash', 64)->nullable()->after('builder_step');
        });

        Schema::table('package_itinerary_days', function (Blueprint $table) {
            $table->string('transport')->nullable()->after('accommodation_b');
        });
    }

    public function down(): void
    {
        Schema::table('package_itinerary_days', function (Blueprint $table) {
            $table->dropColumn('transport');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['builder_step', 'reviewed_hash']);
        });

        Schema::dropIfExists('admin_guide_completions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_dismissed_at', 'tour_status', 'tour_step']);
        });
    }
};
