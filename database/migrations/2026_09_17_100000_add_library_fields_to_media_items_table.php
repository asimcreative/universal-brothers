<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Media Gallery becomes the admin's one media library.
 *
 * Images uploaded from the page builder or the text editor are stored in the
 * same table, in the "library" collection, so they never appear on the public
 * Media page by accident (that page shows only the "gallery" collection, which
 * every existing row belongs to). Alt text and a caption are added for every
 * image, and the file's real type, size and dimensions are recorded at upload.
 *
 * Additive only: existing rows keep every value and become collection "gallery".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->string('collection', 20)->default('gallery')->after('gallery_type')->index();
            $table->string('alt_text')->nullable()->after('title');
            $table->string('caption', 500)->nullable()->after('alt_text');
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('mime_type', 100)->nullable()->after('original_name');
            $table->unsignedInteger('file_size')->nullable()->after('mime_type');
            $table->unsignedInteger('width')->nullable()->after('file_size');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->foreignId('uploaded_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropIndex(['collection']);
            $table->dropColumn(['collection', 'alt_text', 'caption', 'original_name', 'mime_type', 'file_size', 'width', 'height']);
        });
    }
};
