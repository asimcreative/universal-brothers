<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->json('hajj_details')->nullable()->comment('Optional structured fields mirroring the brochure Hajj Application (CNIC, DOB, blood group, next of kin, Mehram details, room type, etc.)');
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');
            $table->string('source_page')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
