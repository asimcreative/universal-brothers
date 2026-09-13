<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row settings. Explicit columns rather than the key/value
        // shape SiteSetting uses: every field here has a real type and its own
        // validation rule, and a typed column is the difference between
        // `temperature` being a float between 0 and 2 and it being the string
        // "abc" nobody noticed until the provider rejected the request.
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_enabled')->default(false);
            $table->boolean('public_enabled')->default(false);
            $table->string('assistant_name')->default('Universal Brothers Assistant');
            $table->string('assistant_icon')->default('bi-stars');
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();

            $table->string('provider')->default('openai');
            $table->string('base_url')->nullable();
            $table->string('model')->nullable();
            // Encrypted at rest by the model's `encrypted` cast. Nullable
            // because the environment variable is the preferred home for it.
            $table->text('api_key')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.30);
            $table->unsignedSmallInteger('max_tokens')->default(700);
            $table->unsignedSmallInteger('timeout_seconds')->default(30);

            $table->text('system_prompt')->nullable();
            $table->text('brand_tone')->nullable();
            $table->string('supported_languages')->nullable();

            $table->boolean('use_database_retrieval')->default(true);
            $table->boolean('use_page_retrieval')->default(true);
            $table->timestamp('indexed_at')->nullable();
            $table->unsignedInteger('indexed_records')->default(0);

            $table->boolean('show_source_links')->default(true);
            $table->boolean('lead_capture_enabled')->default(true);

            $table->unsignedSmallInteger('rate_limit_per_minute')->default(12);
            $table->unsignedSmallInteger('max_message_length')->default(1000);
            $table->unsignedSmallInteger('max_conversation_messages')->default(60);
            $table->unsignedInteger('daily_message_limit')->default(0);
            $table->string('log_level')->default('errors');

            $table->timestamps();
        });

        // The searchable text index. Built by `ai:index` from the models, and
        // deliberately holds only what is useful for FINDING a record — never
        // a price. Prices are read live from package_room_options at answer
        // time, so a stale index can never put a wrong figure in front of a
        // visitor. See AI_ASSISTANT_ARCHITECTURE.md.
        Schema::create('ai_knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference')->nullable()->comment('Package code or slug, for exact-match lookups');
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('category')->nullable();
            $table->text('body');
            $table->text('keywords')->nullable();
            $table->unsignedSmallInteger('weight')->default(10);
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('reference');
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Hashed, not stored raw: the only thing the rate limiter and the
            // abuse counters need is "same visitor or not".
            $table->string('ip_hash', 64)->nullable();
            $table->string('locale', 12)->nullable();
            $table->string('source_page')->nullable();
            $table->unsignedSmallInteger('message_count')->default(0);
            $table->boolean('lead_captured')->default(false);
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('last_activity_at');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->json('sources')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_knowledge_entries');
        Schema::dropIfExists('ai_settings');
    }
};
