<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable package content ("the library") plus the columns the redesigned
 * package builder needs.
 *
 * The library is a set of master records an admin writes once and picks from
 * while building a package — hotels, transport legs, meal plans, included and
 * not-included services, additional options, Mina/Arafat/Muzdalifah
 * arrangements and standard notes — along with journey (itinerary) templates
 * and whole-package templates.
 *
 * Picking a library record COPIES its values into the package's own row and
 * records which record it came from (the nullable `*_id` link columns added
 * below). The package rows stay the single source the public page, the
 * presenter and the AI assistant read, exactly as before, so none of those
 * consumers change. The link is what gives the library its usage counts, its
 * "where is this used" list, and an explicit "update the packages that use
 * this" action — editing a library record never silently rewrites a live
 * package's published content. See
 * docs/architecture/ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md.
 *
 * Every change here is additive: new tables, and nullable columns on existing
 * ones. Nothing existing is renamed, narrowed or dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('location', 20)->default('makkah')->after('city')
                ->comment('makkah | medinah | aziziya | mina | arafat | other');
            $table->string('address')->nullable()->after('location');
            $table->string('website_url', 500)->nullable()->after('description');
            $table->string('map_url', 500)->nullable()->after('website_url');
            $table->text('notes')->nullable()->after('map_url');
        });

        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('includes_breakfast')->default(false);
            $table->boolean('includes_lunch')->default(false);
            $table->boolean('includes_dinner')->default(false);
            $table->boolean('is_included')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transport_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transport_type', 50);
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_included')->default(true);
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('price_basis')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('inclusion | exclusion');
            $table->string('title');
            $table->text('description');
            $table->string('category', 50)->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('upgrade_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('price_basis')->nullable();
            $table->text('conditions')->nullable();
            $table->boolean('is_included')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mashaer_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location', 20)->comment('mina | arafat | muzdalifah');
            $table->string('maktab')->nullable();
            $table->string('category')->nullable();
            $table->string('zone')->nullable();
            $table->string('tent_type')->nullable();
            $table->string('accommodation_type')->nullable();
            $table->string('meal_plan')->nullable();
            $table->string('bathroom')->nullable();
            $table->string('air_conditioning')->nullable();
            $table->string('transportation')->nullable();
            $table->text('other_services')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('note_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Admin-facing name used to find the note');
            $table->string('heading')->nullable()->comment('Optional heading customers see above the note');
            $table->string('category', 50)->default('general');
            $table->string('note_type', 20)->default('general')
                ->comment('Copied into package_notes.note_type, so it uses that column\'s values');
            $table->text('content');
            $table->boolean('is_important')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('itinerary_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('days');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('package_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('payload');
            $table->foreignId('source_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('meta_description');
            $table->string('social_image')->nullable()->after('cover_image');
            $table->timestamp('archived_at')->nullable()->after('published_at');
        });

        Schema::table('package_accommodations', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->after('variant_id')->constrained('hotels')->nullOnDelete();
            $table->foreignId('meal_plan_id')->nullable()->after('meal_plan')->constrained('meal_plans')->nullOnDelete();
        });

        Schema::table('package_transportation', function (Blueprint $table) {
            $table->foreignId('transport_option_id')->nullable()->after('package_id')->constrained('transport_options')->nullOnDelete();
        });

        Schema::table('package_features', function (Blueprint $table) {
            $table->foreignId('service_item_id')->nullable()->after('package_id')->constrained('service_items')->nullOnDelete();
        });

        Schema::table('package_upgrades', function (Blueprint $table) {
            $table->foreignId('upgrade_option_id')->nullable()->after('package_id')->constrained('upgrade_options')->nullOnDelete();
        });

        Schema::table('package_notes', function (Blueprint $table) {
            $table->foreignId('note_template_id')->nullable()->after('package_id')->constrained('note_templates')->nullOnDelete();
        });

        Schema::table('package_mashaer_details', function (Blueprint $table) {
            $table->foreignId('mashaer_location_id')->nullable()->after('package_id')->constrained('mashaer_locations')->nullOnDelete();
        });

        // Muzdalifah is part of every Hajj journey and the public page already
        // orders and renders it (HajjPackagePresenter::mashaerOrdered), but the
        // column's enum only ever allowed Mina and Arafat.
        $this->setMashaerLocations(['mina', 'arafat', 'muzdalifah']);
    }

    public function down(): void
    {
        DB::table('package_mashaer_details')->where('location', 'muzdalifah')->delete();
        $this->setMashaerLocations(['mina', 'arafat']);

        foreach ([
            'package_mashaer_details' => 'mashaer_location_id',
            'package_notes' => 'note_template_id',
            'package_upgrades' => 'upgrade_option_id',
            'package_features' => 'service_item_id',
            'package_transportation' => 'transport_option_id',
        ] as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropConstrainedForeignId($column);
            });
        }

        Schema::table('package_accommodations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meal_plan_id');
            $table->dropConstrainedForeignId('hotel_id');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['internal_notes', 'social_image', 'archived_at']);
        });

        Schema::dropIfExists('admin_activities');
        Schema::dropIfExists('package_templates');
        Schema::dropIfExists('itinerary_templates');
        Schema::dropIfExists('note_templates');
        Schema::dropIfExists('mashaer_locations');
        Schema::dropIfExists('upgrade_options');
        Schema::dropIfExists('service_items');
        Schema::dropIfExists('transport_options');
        Schema::dropIfExists('meal_plans');

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['location', 'address', 'website_url', 'map_url', 'notes']);
        });
    }

    /**
     * Same approach as the currency enum widening in
     * 2026_08_29_201422_add_hajj_fields_to_packages_table: SQLite rebuilds the
     * table through `change()`, MySQL alters the column in place. This table
     * has no children, so the SQLite rebuild cannot cascade into other data.
     */
    private function setMashaerLocations(array $values): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('package_mashaer_details', function (Blueprint $table) use ($values) {
                $table->enum('location', $values)->change();
            });

            return;
        }

        $list = implode(',', array_map(fn ($value) => "'{$value}'", $values));
        DB::statement("ALTER TABLE `package_mashaer_details` MODIFY `location` ENUM({$list}) NOT NULL");
    }
};
