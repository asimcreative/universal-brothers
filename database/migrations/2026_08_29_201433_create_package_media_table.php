<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Package-specific media breakdown (directive §21) — gallery/hotel/
 * accommodation/aziziya image sets, distinct from `packages.cover_image`
 * (kept as the card/listing thumbnail) and from the sitewide Media Gallery
 * module (admin/media, gallery_type: gallery/event/promo — unrelated,
 * cross-site content, not package-specific). No real photography exists to
 * seed here yet — see FINAL_GAP_ANALYSIS.md's asset-handling section; this
 * table exists so the CMS architecture is ready the moment real photos are
 * supplied, not populated with placeholders now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['gallery', 'hotel', 'accommodation', 'aziziya', 'other']);
            $table->string('image_path')->nullable();
            $table->string('video_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_media');
    }
};
