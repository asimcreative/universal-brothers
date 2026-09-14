<?php

/**
 * AI assistant defaults.
 *
 * These are the *fallbacks*. The admin panel writes to the `ai_settings` row,
 * and anything set there wins — see App\Support\Ai\AiSettings. The split lets
 * a fresh install work from the environment alone, and lets the client change
 * the model or the wording later without a deploy.
 *
 * The API key is the exception to "the database wins": it is read from the
 * environment FIRST and only falls back to the encrypted database column. A
 * server-side secret belongs in the server's environment, and a deploy that
 * rotates the key should not need a database write. The key is never sent to
 * the browser under either arrangement.
 */
return [

    'api_key' => env('OPENAI_API_KEY'),

    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'provider' => env('AI_PROVIDER', 'openai'),

    'enabled' => env('AI_ASSISTANT_ENABLED', true),

    'temperature' => (float) env('AI_TEMPERATURE', 0.3),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 700),

    'timeout' => (int) env('AI_TIMEOUT', 30),

    /*
     * Retrieval sizing. These bound the work done per message so a chat turn
     * can never turn into a full-table scan or an unbounded prompt.
     */
    'retrieval' => [
        'max_entries' => 6,
        'max_packages' => 3,
        'entry_excerpt_chars' => 900,
        'max_context_chars' => 12000,
    ],

    /*
     * Conversation handling. History is trimmed rather than summarised: a
     * summarisation pass would be a second model call per turn, and for a
     * pre-sales assistant the last few turns carry essentially all the
     * context that matters.
     */
    'history' => [
        'max_turns_sent' => 8,
        'max_messages_per_conversation' => 60,
        'retention_days' => (int) env('AI_RETENTION_DAYS', 90),
    ],

    'limits' => [
        'per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 12),
        'max_message_length' => 1000,
        // Initial value only — once the settings row exists the admin value
        // wins. 50 rather than 0 so a fresh install is not open-ended spend.
        'daily_messages' => (int) env('AI_DAILY_MESSAGE_LIMIT', 50),
    ],
];
