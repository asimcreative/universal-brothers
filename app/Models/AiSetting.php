<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The single row of AI assistant configuration the admin edits.
 *
 * `api_key` uses the `encrypted` cast, so it is ciphertext at rest and the
 * plaintext exists only in memory on the way to the provider. It is also in
 * $hidden, which means a stray `->toJson()` or a model dumped into a log or an
 * API response cannot leak it — the kind of accident no amount of care in the
 * controller protects against on its own.
 */
class AiSetting extends Model
{
    protected $fillable = [
        'is_enabled', 'public_enabled', 'assistant_name', 'assistant_icon',
        'welcome_message', 'fallback_message',
        'provider', 'base_url', 'model', 'api_key', 'temperature', 'max_tokens', 'timeout_seconds',
        'system_prompt', 'brand_tone', 'supported_languages',
        'use_database_retrieval', 'use_page_retrieval', 'indexed_at', 'indexed_records',
        'show_source_links', 'lead_capture_enabled',
        'rate_limit_per_minute', 'max_message_length', 'max_conversation_messages',
        'daily_message_limit', 'log_level',
    ];

    /**
     * Belt and braces against the secret ever reaching a response body, a log
     * line, or a queued job payload.
     */
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_enabled' => 'boolean',
            'use_database_retrieval' => 'boolean',
            'use_page_retrieval' => 'boolean',
            'show_source_links' => 'boolean',
            'lead_capture_enabled' => 'boolean',
            'api_key' => 'encrypted',
            'temperature' => 'float',
            'max_tokens' => 'integer',
            'timeout_seconds' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'max_message_length' => 'integer',
            'max_conversation_messages' => 'integer',
            'daily_message_limit' => 'integer',
            'indexed_records' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    /**
     * The one settings row, created on first access so no environment can be
     * in a half-configured state where the admin screen 500s.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], []);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('ai:settings'));
        static::deleted(fn () => Cache::forget('ai:settings'));
    }

    /**
     * Never render the key itself. Enough characters to recognise which key is
     * installed, not enough to be of any use if the screenshot leaks.
     */
    public function maskedApiKey(): ?string
    {
        $key = $this->api_key;

        if (blank($key)) {
            return null;
        }

        $length = mb_strlen($key);

        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return mb_substr($key, 0, 3).str_repeat('•', 12).mb_substr($key, -4);
    }
}
