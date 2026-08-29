<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('package_category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('currency', ['USD', 'PKR'])->default('USD');
            $table->string('unit')->nullable()->comment('e.g. "per person", "per night per person", "for 5 days"');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_addons');
    }
};
