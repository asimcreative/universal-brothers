<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_price_tier_id')->constrained()->cascadeOnDelete();
            $table->enum('room_type', ['sharing', 'quad', 'triple', 'double'])->comment('Sharing/Quad/Triple/Double per person, matching the brochure room-type rows');
            $table->decimal('price', 12, 2)->nullable()->comment('Null when brochure marks the cell N/A for this tier');
            $table->enum('currency', ['USD', 'PKR'])->default('USD');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_room_prices');
    }
};
