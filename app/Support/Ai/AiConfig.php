<?php

namespace App\Support\Ai;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the effective AI configuration from the admin settings row, falling
 * back to config/ai.php (which reads the environment).
 *
 * Two different precedence rules, deliberately:
 *
 * - For everything editable — model, wording, limits — the DATABASE wins, so
 *   the client can change the assistant without a deploy.
 * - For the API key, the ENVIRONMENT wins. A server-side secret belongs in the
 *   server's environment; rotating it should be a .env edit and a restart, not
 *   a database write. The encrypted database column exists so the client *can*
 *   set a key from the admin when they have no shell access, but it is the
 *   fallback, not the primary.
 *
 * apiKey() is the only way to reach the plaintext. Nothing else in the
 * application should touch AiSetting::api_key directly.
 */
class AiConfig
{
    private const CACHE_KEY = 'ai:settings';

    public static function settings(): AiSetting
    {
        // Cached because every chat turn, and the Blade widget on every public
        // page, needs to know whether the assistant is switched on. Invalidated
        // by AiSetting::booted().
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => AiSetting::current());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The provider credential. Environment first — see the class docblock.
     *
     * Never log, never serialise, never return through a response.
     */
    public static function apiKey(): ?string
    {
        $fromEnvironment = config('ai.api_key');

        if (filled($fromEnvironment)) {
            return $fromEnvironment;
        }

        $stored = self::settings()->api_key;

        return filled($stored) ? $stored : null;
    }

    public static function hasApiKey(): bool
    {
        return filled(self::apiKey());
    }

    /**
     * Where the key is actually coming from, for the admin screen. Reports the
     * source, never the value.
     */
    public static function apiKeySource(): string
    {
        if (filled(config('ai.api_key'))) {
            return 'environment';
        }

        if (filled(self::settings()->api_key)) {
            return 'database';
        }

        return 'missing';
    }

    public static function baseUrl(): string
    {
        return rtrim(self::settings()->base_url ?: config('ai.base_url'), '/');
    }

    public static function model(): string
    {
        return self::settings()->model ?: config('ai.model');
    }

    public static function provider(): string
    {
        return self::settings()->provider ?: config('ai.provider');
    }

    public static function temperature(): float
    {
        return (float) (self::settings()->temperature ?? config('ai.temperature'));
    }

    public static function maxTokens(): int
    {
        return (int) (self::settings()->max_tokens ?: config('ai.max_tokens'));
    }

    public static function timeout(): int
    {
        return (int) (self::settings()->timeout_seconds ?: config('ai.timeout'));
    }

    /**
     * Whether a visitor may use the assistant at all. Both switches must be on,
     * and there must be a key — an enabled assistant with no credential would
     * only ever show visitors an error, so it counts as unavailable.
     */
    public static function publiclyAvailable(): bool
    {
        $settings = self::settings();

        return $settings->is_enabled
            && $settings->public_enabled
            && config('ai.enabled')
            && self::hasApiKey();
    }

    public static function assistantName(): string
    {
        return self::settings()->assistant_name ?: 'Universal Brothers Assistant';
    }

    public static function welcomeMessage(): string
    {
        return self::settings()->welcome_message ?: PromptBuilder::DEFAULT_WELCOME;
    }

    public static function fallbackMessage(): string
    {
        return self::settings()->fallback_message ?: PromptBuilder::DEFAULT_FALLBACK;
    }

    public static function maxMessageLength(): int
    {
        return (int) (self::settings()->max_message_length ?: config('ai.limits.max_message_length'));
    }

    public static function rateLimitPerMinute(): int
    {
        return (int) (self::settings()->rate_limit_per_minute ?: config('ai.limits.per_minute'));
    }

    public static function dailyMessageLimit(): int
    {
        return (int) (self::settings()->daily_message_limit ?: config('ai.limits.daily_messages'));
    }

    public static function maxConversationMessages(): int
    {
        return (int) (self::settings()->max_conversation_messages ?: config('ai.history.max_messages_per_conversation'));
    }

    public static function showSourceLinks(): bool
    {
        return (bool) self::settings()->show_source_links;
    }

    public static function leadCaptureEnabled(): bool
    {
        return (bool) self::settings()->lead_capture_enabled;
    }
}
