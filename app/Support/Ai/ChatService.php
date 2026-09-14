<?php

namespace App\Support\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Str;

/**
 * One chat turn, end to end: retrieve, prompt, call, persist, return.
 *
 * The controller stays thin; everything that decides what the visitor sees
 * lives here so it can be tested without an HTTP request.
 */
class ChatService
{
    public function __construct(
        private readonly KnowledgeRetriever $retriever,
        private readonly AiClient $client,
    ) {}

    /**
     * @return array{ok: bool, reply: string, sources: array<int, array<string, string>>, offer_lead: bool, failure?: string}
     */
    public function answer(AiConversation $conversation, string $question): array
    {
        $settings = AiConfig::settings();

        $retrieved = $settings->use_database_retrieval
            ? $this->retriever->retrieve($question)
            : ['context' => 'Knowledge retrieval is disabled.', 'sources' => [], 'packages' => [], 'currencies' => []];

        $language = LanguageDetector::detect($question);

        $system = PromptBuilder::system(
            $settings->brand_tone ?: PromptBuilder::DEFAULT_TONE,
            $settings->supported_languages ?: PromptBuilder::DEFAULT_LANGUAGES,
            $settings->system_prompt,
            $retrieved['context'],
            $language,
        );

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $this->history($conversation),
            [['role' => 'user', 'content' => $question]],
        );

        // Recorded before the provider call so the visitor's question survives
        // a timeout or a crash — the history of what was asked is worth more
        // than the reply that never arrived.
        $this->record($conversation, 'user', $question);

        $result = $this->client->chat($messages);

        if (! $result['ok']) {
            $this->record($conversation, 'assistant', AiConfig::fallbackMessage(), [
                'failure_reason' => $result['failure'],
                'latency_ms' => $result['latency_ms'],
            ]);

            return [
                'ok' => false,
                'reply' => AiConfig::fallbackMessage(),
                'sources' => [],
                'offer_lead' => false,
                'failure' => $result['failure'],
            ];
        }

        $sources = AiConfig::showSourceLinks() ? $retrieved['sources'] : [];

        // Only surface a link the assistant actually drew on. A reply that
        // says "I don't have that" should not be followed by three confident
        // looking package cards.
        $sources = $this->relevantSources($sources, $result['content'], $question);

        $this->record($conversation, 'assistant', $result['content'], [
            'sources' => $sources,
            'prompt_tokens' => $result['prompt_tokens'] ?? null,
            'completion_tokens' => $result['completion_tokens'] ?? null,
            'latency_ms' => $result['latency_ms'],
        ]);

        $conversation->forceFill([
            'locale' => $language ? Str::limit($language, 12, '') : $conversation->locale,
            'last_activity_at' => now(),
        ])->save();

        return [
            'ok' => true,
            'reply' => $result['content'],
            'sources' => $sources,
            'offer_lead' => $this->shouldOfferLead($conversation, $question),
        ];
    }

    /**
     * Recent turns only. The system prompt already carries freshly retrieved
     * context for THIS question, so replaying the whole conversation adds cost
     * and, worse, stale context from an earlier question that can pull the
     * model back to the wrong package.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(AiConversation $conversation): array
    {
        $limit = (int) config('ai.history.max_turns_sent') * 2;

        return $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->whereNull('failure_reason')
            ->latest('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (AiMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $attributes */
    private function record(AiConversation $conversation, string $role, string $content, array $attributes = []): AiMessage
    {
        $message = $conversation->messages()->create(array_merge([
            'role' => $role,
            'content' => $content,
        ], $attributes));

        $conversation->increment('message_count');
        $conversation->forceFill(['last_activity_at' => now()])->save();

        return $message;
    }

    /**
     * Offer the enquiry form when the visitor is signalling intent to act —
     * booking, registering, wanting a person. Never on the opening message,
     * and never twice, because a form that appears unprompted at the top of a
     * conversation reads as a lead-capture wall rather than help.
     */
    private function shouldOfferLead(AiConversation $conversation, string $question): bool
    {
        if (! AiConfig::leadCaptureEnabled() || $conversation->lead_captured) {
            return false;
        }

        // Counted on the visitor's OWN messages, not message_count: that
        // counter includes the assistant's replies, so it already reads 2
        // after a single exchange and the form would appear on the opening
        // message — the exact lead-capture wall this is meant to avoid.
        if ($conversation->messages()->where('role', 'user')->count() < 2) {
            return false;
        }

        $intent = [
            'book', 'booking', 'register', 'registration', 'sign up', 'enrol', 'enroll',
            'apply', 'reserve', 'seat', 'confirm', 'deposit', 'installment', 'instalment',
            'contact', 'call me', 'whatsapp', 'talk to', 'speak to', 'agent', 'representative',
            'recommend', 'suggest', 'best package', 'which package', 'advice', 'consult', 'quote',
            'booking karna', 'karwana', 'registration karni', 'rabta', 'baat karni', 'raabta',
        ];

        $haystack = mb_strtolower($question);

        foreach ($intent as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keep a source if the reply plausibly refers to it — by URL, by package
     * code, or by a distinctive word from its title. Falls back to keeping the
     * first source so a helpful answer is not left with no way to read more.
     *
     * @param  array<int, array<string, string>>  $sources
     * @return array<int, array<string, string>>
     */
    /**
     * Words that appear in almost every title and almost every reply on this
     * site, and so say nothing about whether a particular page is relevant.
     */
    private const GENERIC_WORDS = [
        'price', 'prices', 'package', 'packages', 'hajj', 'umrah', 'tour', 'tours', 'tourism',
        'makkah', 'mecca', 'medinah', 'madinah', 'medina', 'hotel', 'hotels', 'sharing', 'room',
        'rooms', 'first', 'included', 'include', 'executive', 'platinum', 'series', 'short',
        'universal', 'brothers', 'days', 'nights', 'double', 'triple', 'quad',
    ];

    private function relevantSources(array $sources, string $reply, string $question = ''): array
    {
        if (! $sources) {
            return [];
        }

        $haystack = mb_strtolower($reply);
        $asked = mb_strtolower($question);

        $kept = array_values(array_filter($sources, function (array $source) use ($haystack, $asked) {
            if (str_contains($haystack, mb_strtolower($source['url']))) {
                return true;
            }

            // A package code in the title that appears in the REPLY or in the
            // QUESTION. The question matters as much as the reply: asked
            // "What is the price of UB010 in PKR?", the live assistant answered
            // correctly in terms of "Package A" and "Package B" without ever
            // repeating "UB010" — and the one link the visitor most obviously
            // needed, to the package they named, was filtered out.
            if (preg_match('/\bUB\d{3}\b/', $source['title'], $matches)) {
                $code = mb_strtolower($matches[0]);

                if (str_contains($haystack, $code) || preg_match('/\bub[\s\-_]?0*'.preg_quote(ltrim(substr($code, 2), '0'), '/').'\b/', $asked)) {
                    return true;
                }
            }

            // Distinctive words only. With generic words allowed, the same
            // UB010 reply kept an unrelated airline-ticket FAQ purely because
            // its title and the reply both contain "price".
            foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($source['title']), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if (mb_strlen($word) >= 5
                    && ! in_array($word, self::GENERIC_WORDS, true)
                    && str_contains($haystack, $word)) {
                    return true;
                }
            }

            return false;
        }));

        // No fallback. This used to return the first retrieved source when
        // nothing matched, "so a helpful answer is not left with no way to read
        // more". On the live site that attached "What happens if I need to
        // cancel my Hajj booking?" to an answer about visa guarantees. A link
        // the visitor has no reason to open is worse than no link — it implies
        // the assistant thinks it is relevant. The reply can carry its own
        // inline link when one genuinely helps.
        return $kept;
    }
}
