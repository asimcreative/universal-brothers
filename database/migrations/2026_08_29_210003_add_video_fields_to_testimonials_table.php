<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('photo');
            $table->string('video_thumbnail')->nullable()->after('video_url');
            $table->string('package_label')->nullable()->after('service_tag');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'video_thumbnail', 'package_label']);
        });
    }
};
