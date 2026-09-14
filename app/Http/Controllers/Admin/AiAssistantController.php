<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiKnowledgeEntry;
use App\Models\AiMessage;
use App\Models\AiSetting;
use App\Support\Ai\AiClient;
use App\Support\Ai\AiConfig;
use App\Support\Ai\KnowledgeIndexer;
use App\Support\Ai\KnowledgeRetriever;
use App\Support\Ai\LanguageDetector;
use App\Support\Ai\PromptBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request): View
    {
        $settings = AiSetting::current();

        return view('admin.ai.index', [
            'settings' => $settings,
            // The admin's own request goes through the same proxy as a
            // visitor's, so this is a live check that visitor addresses reach
            // the application — the thing per-address limits depend on.
            'addressReachesApp' => filter_var((string) $request->ip(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false,
            'maskedKey' => $settings->maskedApiKey(),
            'keySource' => AiConfig::apiKeySource(),
            'defaults' => [
                'welcome' => PromptBuilder::DEFAULT_WELCOME,
                'fallback' => PromptBuilder::DEFAULT_FALLBACK,
                'tone' => PromptBuilder::DEFAULT_TONE,
                'languages' => PromptBuilder::DEFAULT_LANGUAGES,
            ],
            'stats' => $this->stats(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'public_enabled' => ['nullable', 'boolean'],
            'assistant_name' => ['required', 'string', 'max:80'],
            'assistant_icon' => ['nullable', 'string', 'max:60'],
            'welcome_message' => ['nullable', 'string', 'max:2000'],
            'fallback_message' => ['nullable', 'string', 'max:2000'],

            'provider' => ['required', 'string', 'max:40'],
            // Enforcing https here is not cosmetic: the API key travels on this
            // request, so a plaintext base URL would put the credential on the
            // wire. Localhost is allowed so a self-hosted model can be used.
            'base_url' => ['nullable', 'string', 'max:190', 'url', 'starts_with:https://,http://localhost,http://127.0.0.1'],
            'model' => ['required', 'string', 'max:80'],
            'api_key' => ['nullable', 'string', 'max:300'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:64', 'max:8000'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],

            'system_prompt' => ['nullable', 'string', 'max:6000'],
            'brand_tone' => ['nullable', 'string', 'max:2000'],
            'supported_languages' => ['nullable', 'string', 'max:300'],

            'use_database_retrieval' => ['nullable', 'boolean'],
            'use_page_retrieval' => ['nullable', 'boolean'],
            'show_source_links' => ['nullable', 'boolean'],
            'lead_capture_enabled' => ['nullable', 'boolean'],

            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:120'],
            'max_message_length' => ['required', 'integer', 'min:50', 'max:4000'],
            'max_conversation_messages' => ['required', 'integer', 'min:4', 'max:500'],
            'daily_message_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'log_level' => ['required', 'in:none,errors,all'],
        ]);

        $settings = AiSetting::current();

        // An empty key field means "leave the saved key alone", never "delete
        // it". The form cannot show the real value, so submitting the form
        // must not be able to silently wipe the credential.
        $apiKey = $request->input('api_key');

        unset($validated['api_key']);

        foreach (['is_enabled', 'public_enabled', 'use_database_retrieval', 'use_page_retrieval', 'show_source_links', 'lead_capture_enabled'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        $settings->fill($validated);

        if (filled($apiKey)) {
            $settings->api_key = trim($apiKey);
        }

        $settings->save();

        AiConfig::flush();

        return back()->with('status', 'AI assistant settings updated.');
    }

    /**
     * Remove the stored key without touching anything else, for when the
     * client wants to move the credential into the server environment.
     */
    public function clearKey(): RedirectResponse
    {
        $settings = AiSetting::current();
        $settings->api_key = null;
        $settings->save();

        AiConfig::flush();

        return back()->with('status', 'Stored API key removed. The environment variable is now the only source.');
    }

    public function reindex(KnowledgeIndexer $indexer): RedirectResponse
    {
        $count = $indexer->rebuild();

        return back()->with('status', "Knowledge index rebuilt — {$count} records.");
    }

    /**
     * Admin-only connection test. Returns the answer, the retrieved sources
     * and the timing, and never the key.
     */
    public function test(Request $request, KnowledgeRetriever $retriever, AiClient $client): View
    {
        $question = trim((string) $request->input('question'));
        $result = null;

        if ($question !== '') {
            $retrieved = $retriever->retrieve($question);

            $response = $client->chat([
                ['role' => 'system', 'content' => PromptBuilder::system(
                    AiSetting::current()->brand_tone ?: PromptBuilder::DEFAULT_TONE,
                    AiSetting::current()->supported_languages ?: PromptBuilder::DEFAULT_LANGUAGES,
                    AiSetting::current()->system_prompt,
                    $retrieved['context'],
                    LanguageDetector::detect($question),
                )],
                ['role' => 'user', 'content' => $question],
            ]);

            $result = [
                'question' => $question,
                'ok' => $response['ok'],
                'answer' => $response['content'] ?? null,
                // Scrubbed a second time even though AiClient already produces
                // a safe string — this one is rendered in a browser.
                'failure' => $response['failure'] ?? null,
                'detail' => AiClient::scrub($response['detail'] ?? null),
                'latency_ms' => $response['latency_ms'] ?? null,
                'model' => $response['model'] ?? AiConfig::model(),
                'prompt_tokens' => $response['prompt_tokens'] ?? null,
                'completion_tokens' => $response['completion_tokens'] ?? null,
                'sources' => $retrieved['sources'],
                'packages' => $retrieved['packages'],
                'currencies' => $retrieved['currencies'],
                'context_chars' => mb_strlen($retrieved['context']),
                'context_preview' => mb_substr($retrieved['context'], 0, 4000),
            ];
        }

        return view('admin.ai.test', [
            'settings' => AiSetting::current(),
            'keySource' => AiConfig::apiKeySource(),
            'maskedKey' => AiSetting::current()->maskedApiKey(),
            'result' => $result,
        ]);
    }

    public function conversations(Request $request): View
    {
        $conversations = AiConversation::query()
            ->withCount('messages')
            ->with('inquiry:id,name,email')
            ->latest('last_activity_at')
            ->paginate(20);

        return view('admin.ai.conversations', [
            'conversations' => $conversations,
            'stats' => $this->stats(),
        ]);
    }

    public function conversation(AiConversation $conversation): View
    {
        $conversation->load(['messages' => fn ($q) => $q->orderBy('id')]);

        return view('admin.ai.conversation', compact('conversation'));
    }

    /** @return array<string, mixed> */
    private function stats(): array
    {
        return [
            'conversations' => AiConversation::count(),
            'messages' => AiMessage::count(),
            'failures' => AiMessage::whereNotNull('failure_reason')->count(),
            'leads' => AiConversation::where('lead_captured', true)->count(),
            'indexed' => AiKnowledgeEntry::count(),
            'prompt_tokens' => (int) AiMessage::sum('prompt_tokens'),
            'completion_tokens' => (int) AiMessage::sum('completion_tokens'),
            'top_questions' => AiMessage::where('role', 'user')
                ->latest('id')
                ->limit(200)
                ->pluck('content')
                ->map(fn (string $c) => mb_strtolower(trim($c)))
                ->countBy()
                ->sortDesc()
                ->take(10),
            // Which packages visitors actually ask about — read off the codes
            // in their own messages rather than off what the assistant
            // answered, so it measures demand rather than retrieval.
            'top_packages' => AiMessage::where('role', 'user')
                ->latest('id')
                ->limit(500)
                ->pluck('content')
                ->flatMap(function (string $content) {
                    preg_match_all('/\bub[\s\-_]?(\d{1,3})\b/i', $content, $matches);

                    return collect($matches[1] ?? [])
                        ->map(fn (string $digits) => 'UB'.str_pad($digits, 3, '0', STR_PAD_LEFT));
                })
                ->countBy()
                ->sortDesc()
                ->take(10),
        ];
    }
}
