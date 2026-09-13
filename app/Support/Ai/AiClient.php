<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to an OpenAI-compatible /chat/completions endpoint.
 *
 * Any provider exposing that shape works — OpenAI, Azure OpenAI, Groq,
 * OpenRouter, a local llama.cpp server — by changing the base URL and model in
 * the admin panel. That is the whole reason the base URL is configurable
 * rather than hardcoded.
 *
 * Nothing here ever returns or logs the API key. Failures come back as a
 * typed reason the caller can map to a visitor-safe message, with the
 * technical detail written to the log instead.
 */
class AiClient
{
    public const FAILURE_NO_KEY = 'missing_api_key';

    public const FAILURE_AUTH = 'invalid_api_key';

    public const FAILURE_RATE_LIMIT = 'provider_rate_limited';

    public const FAILURE_NO_CREDIT = 'provider_no_credit';

    public const FAILURE_TIMEOUT = 'provider_timeout';

    public const FAILURE_BAD_REQUEST = 'provider_rejected_request';

    public const FAILURE_SERVER = 'provider_unavailable';

    public const FAILURE_EMPTY = 'empty_response';

    public const FAILURE_MALFORMED = 'malformed_response';

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, content?: string, failure?: string, detail?: string, prompt_tokens?: int, completion_tokens?: int, model?: string, latency_ms: int}
     */
    public function chat(array $messages): array
    {
        $startedAt = microtime(true);

        $apiKey = AiConfig::apiKey();

        if (blank($apiKey)) {
            return $this->failure(self::FAILURE_NO_KEY, 'No API key is configured.', $startedAt);
        }

        $model = AiConfig::model();

        try {
            $response = Http::withToken($apiKey)
                ->timeout(AiConfig::timeout())
                // One retry, and only on a connection-level failure. Retrying a
                // 4xx would just repeat a rejected request, and retrying a 429
                // would make the rate limit worse.
                ->retry(2, 300, fn (Throwable $e) => $e instanceof ConnectionException, throw: false)
                ->acceptJson()
                ->asJson()
                ->post(AiConfig::baseUrl().'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => AiConfig::temperature(),
                    'max_tokens' => AiConfig::maxTokens(),
                ]);
        } catch (ConnectionException $e) {
            $this->log('AI provider connection failed', $e->getMessage());

            return $this->failure(self::FAILURE_TIMEOUT, 'Could not reach the AI provider.', $startedAt);
        } catch (Throwable $e) {
            $this->log('AI provider request threw', $e->getMessage());

            return $this->failure(self::FAILURE_SERVER, 'Unexpected error contacting the AI provider.', $startedAt);
        }

        if ($response->failed()) {
            return $this->failureFromStatus($response->status(), $response->json('error.message') ?: $response->body(), $startedAt);
        }

        $data = $response->json();

        if (! is_array($data)) {
            $this->log('AI provider returned a non-JSON body', substr($response->body(), 0, 500));

            return $this->failure(self::FAILURE_MALFORMED, 'Provider returned an unreadable response.', $startedAt);
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            $this->log('AI provider returned no message content', json_encode(array_keys($data)));

            return $this->failure(self::FAILURE_EMPTY, 'Provider returned an empty message.', $startedAt);
        }

        return [
            'ok' => true,
            'content' => trim($content),
            'prompt_tokens' => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'model' => (string) ($data['model'] ?? $model),
            'latency_ms' => $this->elapsed($startedAt),
        ];
    }

    /** @return array{ok: false, failure: string, detail: string, latency_ms: int} */
    private function failureFromStatus(int $status, string $body, float $startedAt): array
    {
        $detail = substr(trim($body), 0, 500);

        [$failure, $message] = match (true) {
            $status === 401 || $status === 403 => [self::FAILURE_AUTH, 'The AI provider rejected the API key.'],
            // OpenAI returns 429 for an exhausted balance as well as for real
            // rate limiting, and the two need completely different actions —
            // "wait and retry" versus "top up the account". Reporting an empty
            // balance as throttling sends whoever is on support down the wrong
            // path entirely. Found by testing against the live API with a key
            // whose account had no credit.
            $status === 429 && $this->mentionsBilling($detail) => [self::FAILURE_NO_CREDIT, 'The AI provider account has no credit remaining.'],
            $status === 429 => [self::FAILURE_RATE_LIMIT, 'The AI provider is rate limiting requests.'],
            $status === 400 || $status === 404 || $status === 422 => [self::FAILURE_BAD_REQUEST, 'The AI provider rejected the request (often an unknown model name).'],
            $status >= 500 => [self::FAILURE_SERVER, 'The AI provider is unavailable.'],
            default => [self::FAILURE_SERVER, "The AI provider returned HTTP {$status}."],
        };

        $this->log("AI provider returned HTTP {$status}", $detail);

        return $this->failure($failure, $message, $startedAt);
    }

    /** @return array{ok: false, failure: string, detail: string, latency_ms: int} */
    private function failure(string $failure, string $detail, float $startedAt): array
    {
        return [
            'ok' => false,
            'failure' => $failure,
            'detail' => $detail,
            'latency_ms' => $this->elapsed($startedAt),
        ];
    }

    /**
     * Whether a 429 is really "your balance is empty" rather than "slow down".
     * Matched on the provider's own wording — OpenAI says "no credits
     * remaining" / "insufficient_quota" / links to billing.
     */
    private function mentionsBilling(string $detail): bool
    {
        $haystack = mb_strtolower($detail);

        foreach (['credit', 'quota', 'billing', 'insufficient_quota', 'payment'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function elapsed(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /**
     * Provider error bodies occasionally echo back part of the request. Scrub
     * anything that looks like a credential before it reaches the log, because
     * a log file is exactly where a leaked key sits unnoticed for months.
     */
    private function log(string $message, string $detail): void
    {
        Log::warning($message, ['detail' => self::scrub($detail)]);
    }

    public static function scrub(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        return (string) preg_replace(
            ['/\b(sk|rk|pk)-[A-Za-z0-9_\-]{8,}/', '/Bearer\s+[A-Za-z0-9._\-]{8,}/i'],
            ['[redacted]', 'Bearer [redacted]'],
            $text
        );
    }
}
