# AI Assistant — Architecture

A retrieval-grounded chat assistant for the public Universal Brothers website. It answers questions about Hajj, Umrah and Tourism services in the visitor's own language, using the project's own database as the source of every fact.

## The problem it has to solve

This company sells a once-in-a-lifetime religious journey costing five figures in US dollars. An assistant that invents a price, a hotel or an availability claim is a commercial and reputational problem, not a cosmetic bug. Every design decision below follows from that.

The site also already shipped superseded prices once (issue #5), so "the figure the assistant states must be the figure in the database right now" is treated as a hard invariant rather than an aspiration.

## Request flow

```
Browser  ──POST /ai/chat──▶  AiChatController
                              │  session → conversation   (never a client-supplied id)
                              │  rate limit               (session key + IP key)
                              ▼
                            ChatService
                              ├─▶ KnowledgeRetriever ──▶ ai_knowledge_entries   (which records?)
                              │                     └──▶ PackageContext ──▶ live package tables
                              ├─▶ LanguageDetector
                              ├─▶ PromptBuilder      (rules + retrieved context)
                              └─▶ AiClient ──HTTPS──▶ OpenAI-compatible /chat/completions
                              ▼
                            ai_messages (+ sources)
                              ▼
Browser  ◀──{reply, sources, offer_lead}──
```

The browser never talks to the provider. It only ever talks to this application.

## The two-layer knowledge model

This is the most important part of the design.

**Layer 1 — the index (`ai_knowledge_entries`), for finding.** Rebuilt by `php artisan ai:index` from published packages, categories, series, FAQs, pages, news, awards, affiliations, testimonials, offices, hotels and media. It holds the text you would *search* by: names, codes, hotel names, itinerary cities, FAQ answers.

**Layer 2 — the live render (`PackageContext`), for answering.** Once the retriever knows *which* package is relevant, every fact the model sees — prices, hotels, room types, itinerary, inclusions, Aziziya, upgrades — is read from the package tables at that moment and formatted fresh.

`KnowledgeRetriever` excludes `source_type = 'package'` when it gathers text excerpts, so a package index entry can only ever influence *which* package is chosen, never *what is said about it*. A stale index therefore cannot produce a stale price. `AiAssistantTest::test_prices_in_the_context_come_from_the_database` proves this directly: it changes a price after indexing and asserts the new figure reaches the prompt.

This is also why there is no second source of truth. The index is a search aid over data that already exists; it is deleted and rebuilt wholesale, and nothing reads a business fact from it.

### Retrieval, in order of trust

1. **Exact.** A package code anywhere in the question (`UB001`, `ub 010`, `UB-023`) resolves straight to that package, bypassing scoring entirely. Asking about UB010 can never return UB011.
2. **Structured.** Otherwise, packages are matched by scored keyword search over the index, then rendered live.
3. **Text.** Non-package entries (FAQs, pages, offices, awards) are scored the same way and included as excerpts.

Scoring is `LIKE`-based rather than full-text. This project runs SQLite locally and in the test suite and MySQL in production, and `MATCH ... AGAINST` does not exist in SQLite — one code path means the behaviour under test is the behaviour in production.

### Currency handling

`KnowledgeRetriever::currencies()` detects PKR / SAR / USD from the question using word-boundary matching, so `sr` inside "sharing" and `us` inside "used" do not select a currency. `PackageContext` then renders only the currencies asked for.

A currency with no value in the database is rendered as **"not published"** rather than omitted. A gap the model cannot see is a gap it will fill in; a gap it is told about, it reports.

## Files

| Path | Role |
|---|---|
| `config/ai.php` | Defaults, read from the environment |
| `app/Support/Ai/AiConfig.php` | Effective configuration; the only route to the API key |
| `app/Support/Ai/KnowledgeIndexer.php` | Builds the searchable index |
| `app/Support/Ai/KnowledgeRetriever.php` | Question → context + source links |
| `app/Support/Ai/PackageContext.php` | Renders one package live, currency-aware |
| `app/Support/Ai/PromptBuilder.php` | The system prompt and its rules |
| `app/Support/Ai/LanguageDetector.php` | Script and Roman Urdu detection |
| `app/Support/Ai/AiClient.php` | HTTP to the provider; typed failures |
| `app/Support/Ai/ChatService.php` | One turn, end to end |
| `app/Http/Controllers/AiChatController.php` | Public endpoints |
| `app/Http/Controllers/Admin/AiAssistantController.php` | Settings, test panel, analytics |
| `app/Console/Commands/RebuildAiKnowledgeIndex.php` | `ai:index` |
| `app/Console/Commands/PruneAiConversations.php` | `ai:prune` |
| `resources/views/components/ai-assistant.blade.php` | The widget |
| `resources/js/ai-assistant.js` | Widget behaviour |
| `resources/scss/_ai-assistant.scss` | Widget styling |

## Database

| Table | Purpose |
|---|---|
| `ai_settings` | One row. Admin configuration; `api_key` is encrypted and `$hidden`. |
| `ai_knowledge_entries` | The searchable index. Deleted and rebuilt by `ai:index`. |
| `ai_conversations` | One per visitor session. IP stored only as an HMAC. |
| `ai_messages` | Turns, with sources, token counts, latency and failure reason. |

Typed columns rather than the key/value shape `site_settings` uses: every field has a validation rule, and a typed column is the difference between `temperature` being a float in range and a string nobody noticed until the provider rejected the request.

## Provider

Any OpenAI-compatible `/chat/completions` endpoint: OpenAI, Azure OpenAI, Groq, OpenRouter, or a self-hosted server. Change the base URL and model in the admin panel; no code change.

`AiClient` returns typed failures (`missing_api_key`, `invalid_api_key`, `provider_rate_limited`, `provider_timeout`, `provider_rejected_request`, `provider_unavailable`, `empty_response`, `malformed_response`) which the controller maps to the visitor-facing fallback message, and which the admin test panel reports with a specific remedy.

Retries happen once, and only on a connection-level failure. Retrying a 4xx would repeat a rejected request; retrying a 429 would make the rate limit worse.

## Conversations and privacy

- A conversation is resolved **from the session**, never from anything the client sends. There is no conversation id in the request body by design — accept one and any visitor could read another's chat by guessing a uuid.
- IP addresses are stored only as `hash_hmac('sha256', ip, app.key)`. The system only ever needs to tell one visitor from another.
- Only the last `ai.history.max_turns_sent` turns are sent to the provider. The system prompt already carries context retrieved for *this* question; replaying the whole conversation adds cost and can pull the model back to an earlier, wrong package.
- Conversations that produced no enquiry are deleted after `AI_RETENTION_DAYS` by `php artisan ai:prune`. Conversations that produced an enquiry are kept, because that enquiry is a business record.

## Leads

When a visitor signals booking or contact intent — and not on their opening message, and not twice — the assistant offers an enquiry form. Submissions create a normal `Inquiry` with `source_page = 'ai-assistant'`, so AI leads land in the inbox the team already works rather than in a second place nobody checks.

## Rendering safety

The assistant's reply is never treated as HTML. `resources/js/ai-assistant.js` escapes it first, then a small allow-list formatter re-introduces line breaks, `**bold**` and `- ` bullets. **Links are not parsed out of the reply at all** — they come from the `sources` array the backend built with `route()`, and the widget additionally refuses to render any source whose URL is not same-origin.

So a model talked into emitting `<img onerror=…>` produces inert text, and a model talked into emitting an off-site link produces nothing.

## Performance

- Settings are cached for an hour (`ai:settings`), invalidated on save. The Blade component reads only that cache, so the widget costs no query on a public page.
- Retrieval is bounded: at most 40 candidate package rows and 60 candidate entries scanned, at most 3 packages and 6 entries used, context truncated to `ai.retrieval.max_context_chars`.
- **Relations are eager-loaded across the whole package collection**, not per package. `PackageContext::RELATIONS` is the shared list; `KnowledgeRetriever` passes it to `->with()` and `render()`'s own `loadMissing()` is then a no-op that remains only as a safety net for a direct caller.

  Measured, not assumed — the first implementation loaded per package and was a textbook N+1:

  | Question | Before | After |
  |---|---|---|
  | One package, one currency | 17 queries, 12ms | 17 queries, 12ms |
  | Three packages compared | **45 queries, 17.5ms** | **17 queries, 6.5ms** |
  | Keyword match, two packages | 46 queries | 18 queries |
  | No package, text entries only | 40 queries | 16 queries |

  The query count is now flat in the number of packages retrieved.

## Deliberate limitations

- **No live inventory.** The assistant has no access to bookings, seats, rooms, visas, flights or payments, and the system prompt forbids it from confirming any of them.
- **No summarisation of long conversations.** History is trimmed instead. Summarising would mean a second model call per turn for a pre-sales assistant where the last few turns carry nearly all the context.
- **No streaming.** Replies arrive whole. Streaming would complicate the escaping guarantee above for a marginal perceived-latency gain.
- **Roman Urdu detection is a heuristic**, requiring two distinct marker words. It nudges the prompt; the model still does the real language matching.
