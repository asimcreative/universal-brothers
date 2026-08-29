<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('body');
            $table->string('canonical_url')->nullable()->after('meta_description');
            $table->timestamp('published_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'canonical_url', 'published_at']);
        });
    }
};
