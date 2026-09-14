<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\Inquiry;
use App\Models\Package;
use App\Support\Ai\AiConfig;
use App\Support\Ai\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * The visitor-facing chat endpoint.
 *
 * The single most important property here: the conversation is resolved from
 * the SESSION, never from anything the client sends. There is no conversation
 * id in the request body by design — accept one and any visitor could read
 * another visitor's chat by guessing or replaying a uuid. The session cookie
 * is the only thing that binds a browser to a conversation.
 */
class AiChatController extends Controller
{
    private const SESSION_KEY = 'ai_conversation_uuid';

    public function __construct(private readonly ChatService $chat) {}

    public function send(Request $request): JsonResponse
    {
        if (! AiConfig::publiclyAvailable()) {
            return response()->json([
                'ok' => false,
                'error' => 'unavailable',
                'reply' => AiConfig::fallbackMessage(),
            ], 503);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:'.AiConfig::maxMessageLength()],
        ]);

        $message = trim($validated['message']);

        if ($message === '') {
            return response()->json(['ok' => false, 'error' => 'empty_message'], 422);
        }

        if ($response = $this->enforceRateLimit($request)) {
            return $response;
        }

        $conversation = $this->conversation($request);

        if ($conversation->message_count >= AiConfig::maxConversationMessages()) {
            return response()->json([
                'ok' => false,
                'error' => 'conversation_too_long',
                'reply' => __('This conversation has reached its limit. Please start a new chat, or contact our team directly.'),
            ], 429);
        }

        $result = $this->chat->answer($conversation, $message);

        return response()->json([
            'ok' => $result['ok'],
            'reply' => $result['reply'],
            'sources' => $result['sources'],
            'offer_lead' => $result['offer_lead'],
        ], $result['ok'] ? 200 : 502);
    }

    /**
     * Turn a chat into a real enquiry in the existing inbox, so AI-generated
     * leads land where the team already looks rather than in a second place
     * nobody checks.
     */
    public function lead(Request $request): JsonResponse
    {
        if (! AiConfig::publiclyAvailable() || ! AiConfig::leadCaptureEnabled()) {
            return response()->json(['ok' => false, 'error' => 'unavailable'], 503);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'service' => ['nullable', 'string', 'max:60'],
            'package_code' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'string', 'in:PKR,SAR,USD'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ]);

        $conversation = $this->conversation($request);

        $package = filled($validated['package_code'] ?? null)
            ? Package::published()->where('code', $validated['package_code'])->first()
            : null;

        $inquiry = Inquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'package_id' => $package?->id,
            'package_category_id' => $package?->package_category_id,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            // Marked so the team can tell at a glance where a lead came from,
            // and so the admin list can filter them.
            'source_page' => 'ai-assistant',
            'ip_address' => $request->ip(),
            'hajj_details' => array_filter([
                'source' => 'AI assistant',
                'interested_service' => $validated['service'] ?? null,
                'preferred_package' => $validated['package_code'] ?? null,
                'preferred_currency' => $validated['currency'] ?? null,
                'conversation_reference' => $conversation->uuid,
            ]),
        ]);

        $conversation->forceFill([
            'lead_captured' => true,
            'inquiry_id' => $inquiry->id,
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => __('Thank you. Our team will contact you shortly.'),
        ]);
    }

    /**
     * Start a fresh conversation. The old one is detached from the session
     * rather than deleted, so the client keeps their own history for retention
     * handling while the visitor gets a clean slate.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }

    private function conversation(Request $request): AiConversation
    {
        $uuid = $request->session()->get(self::SESSION_KEY);

        if ($uuid) {
            $existing = AiConversation::where('uuid', $uuid)->first();

            if ($existing) {
                return $existing;
            }
        }

        $conversation = AiConversation::create([
            'uuid' => (string) Str::uuid(),
            // Hashed with the app key so the table never holds raw visitor IP
            // addresses; it only ever needs to tell one visitor from another.
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'source_page' => Str::limit((string) $request->headers->get('referer'), 190, ''),
            'last_activity_at' => now(),
        ]);

        $request->session()->put(self::SESSION_KEY, $conversation->uuid);

        return $conversation;
    }

    private function enforceRateLimit(Request $request): ?JsonResponse
    {
        $session = $request->session()->getId();
        $address = $this->publicAddress($request);

        // [key, max attempts, decay seconds, error code]
        $limits = [];

        $perMinute = AiConfig::rateLimitPerMinute();

        // Per minute: the session stops one browser hammering the endpoint; the
        // address stops someone clearing cookies between requests.
        $limits[] = ['ai-chat:session:'.$session, $perMinute, 60, 'rate_limited'];

        if ($address !== null) {
            $limits[] = ['ai-chat:ip:'.sha1($address), $perMinute, 60, 'rate_limited'];
        }

        $daily = AiConfig::dailyMessageLimit();

        if ($daily > 0) {
            // Per visitor — the number the client sets in the admin.
            $limits[] = ['ai-chat:daily:session:'.$session, $daily, 86400, 'daily_limit'];

            // Per network address, at a multiple. See
            // AiConfig::SHARED_ADDRESS_MULTIPLIER for why not the same number.
            if ($address !== null) {
                $limits[] = ['ai-chat:daily:ip:'.sha1($address), $daily * AiConfig::SHARED_ADDRESS_MULTIPLIER, 86400, 'daily_limit'];
            }
        }

        // Check every limit before counting against any of them, so a message
        // refused by one limit does not also use up another.
        foreach ($limits as [$key, $max, , $error]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return response()->json([
                    'ok' => false,
                    'error' => $error,
                    'retry_after' => RateLimiter::availableIn($key),
                    'reply' => $error === 'daily_limit'
                        ? __('You have reached today\'s message limit. Please contact our team directly and they will be glad to help.')
                        : __('You are sending messages very quickly. Please wait a moment and try again.'),
                ], 429);
            }
        }

        foreach ($limits as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    /**
     * The visitor's public IP address, or null if the request did not arrive
     * with one.
     *
     * Production runs nginx in front of Apache. When the proxy passes the
     * visitor's address on correctly, this is that address. If it ever does
     * not — a server rebuild, a changed nginx or mod_remoteip config — every
     * request would appear to come from 127.0.0.1, and per-address limits would
     * silently become per-SITE limits: fifty messages a day for everyone put
     * together. Rather than risk that, an internal address is treated as "no
     * address" and only the per-visitor limits apply, and the condition is
     * logged once a day so it can be noticed and fixed.
     */
    private function publicAddress(Request $request): ?string
    {
        $ip = (string) $request->ip();

        $public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        // Logged at ERROR, once a day, and never with the address itself.
        // Production runs LOG_LEVEL=error, so a warning here would never be
        // written — and a degraded limit is an operational fault someone needs
        // to see. The healthy case is not logged; the admin AI screen shows it.
        if (! $public && Cache::add('ai-chat:address-check', true, now()->addDay())) {
            Log::error('AI assistant: requests arrive from an internal address, so the proxy is not passing visitor IPs. Per-address limits are disabled and only per-visitor limits apply.');
        }

        return $public ? $ip : null;
    }
}
