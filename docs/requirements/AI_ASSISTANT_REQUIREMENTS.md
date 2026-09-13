# AI Assistant — Requirements and Operations

What the assistant must do, how it is configured, and how to run it day to day.

## Functional requirements

| # | Requirement | Status |
|---|---|---|
| 1 | Answer questions about Universal Brothers' Hajj, Umrah and Tourism services | Done |
| 2 | Understand and reply in the visitor's own language (English, Urdu, Roman Urdu, Arabic, Hindi, Bengali, Malay, others) | Done |
| 3 | Answer from the project's real database, never from model memory | Done |
| 4 | Currency-aware answers in PKR, SAR and USD | Done |
| 5 | Never confuse Package A, B and C | Done |
| 6 | Provide links to the exact relevant website page | Done |
| 7 | Say clearly when information is unavailable | Done |
| 8 | Never guarantee availability, visas, flights, bookings or payments | Done |
| 9 | Offer an enquiry form when the visitor shows intent | Done |
| 10 | Full admin control over provider, model, key, prompt, limits | Done |
| 11 | Admin test panel showing the answer, sources, timing and errors | Done |
| 12 | API key never reaches the browser | Done |

## Environment variables

Set on the server only. Add to `.env`; the names (never the values) are in `.env.example`.

| Variable | Default | Notes |
|---|---|---|
| `OPENAI_API_KEY` | — | **The credential.** Blank disables the assistant. Takes priority over any key saved in the admin. |
| `OPENAI_BASE_URL` | `https://api.openai.com/v1` | Any OpenAI-compatible endpoint. |
| `OPENAI_MODEL` | `gpt-4o-mini` | |
| `AI_PROVIDER` | `openai` | Label only. |
| `AI_ASSISTANT_ENABLED` | `true` | Hard kill switch, above the admin toggles. |
| `AI_TEMPERATURE` | `0.3` | Keep low. This assistant states prices. |
| `AI_MAX_TOKENS` | `700` | |
| `AI_TIMEOUT` | `30` | Seconds. |
| `AI_RATE_LIMIT_PER_MINUTE` | `12` | Per session and per IP. |
| `AI_DAILY_MESSAGE_LIMIT` | `0` | 0 = no daily cap. |
| `AI_RETENTION_DAYS` | `90` | Conversations without an enquiry are pruned after this. 0 keeps them. |

**`.env` is git-ignored** (`.gitignore:3`). Never commit a real key, and never paste one into documentation, an issue or a commit message.

## Admin settings

**Admin → Assistant → AI Assistant** (`/admin/ai`).

- **General** — enable/disable, show on public site, assistant name, icon, welcome message, fallback message.
- **Provider** — provider, base URL, model, API key, temperature, max tokens, timeout.
- **Prompt & tone** — brand tone, supported languages, additional instructions. The anti-hallucination, currency, variant, link and escalation rules are built in and always applied; these fields add to them.
- **Knowledge** — database retrieval, page retrieval, source links, lead capture, and a "Rebuild knowledge index" button showing when it last ran and how many records it holds.
- **Safety & limits** — messages per minute, max message length, max messages per chat, daily limit, logging level.

**Admin → Assistant → AI Test Panel** (`/admin/ai/test`) — send a question through the live configuration and see the answer, the retrieved sources, the exact context the model was given, the model name, the latency and the token counts. Errors are shown with a specific remedy. The key is never displayed.

**Admin → Assistant → AI Conversations** (`/admin/ai/conversations`) — every conversation, its message count, language, whether it produced a lead, and a full transcript.

## Day-to-day operations

### Replace or rotate the API key

Preferred — on the server:

```bash
# edit .env, set OPENAI_API_KEY=...
php artisan config:clear
```

If the key had previously been saved in the admin, remove it so the environment is unambiguous: **Admin → AI Assistant → "Remove the stored key"**.

Alternative, with no shell access: **Admin → AI Assistant → API key**, paste, save. It is encrypted at rest and only ever shown masked. Leaving the field blank never clears the saved key — use the explicit remove link for that.

Verify either way in the **Test Panel**: it should report *Connection OK*.

### Change the model

**Admin → AI Assistant → Provider → Model**, save, then confirm in the Test Panel. A wrong model name surfaces as `provider_rejected_request`.

### Disable the assistant

Any one of these, in increasing severity:

1. **Admin → AI Assistant → "Show on the public website"** off — hidden from visitors, still usable in the Test Panel.
2. **Admin → AI Assistant → "Assistant enabled"** off — off everywhere.
3. `AI_ASSISTANT_ENABLED=false` in `.env` — off regardless of the database, for when the admin is unreachable.
4. Remove `OPENAI_API_KEY` and clear the stored key — no credential, so `publiclyAvailable()` is false.

The widget renders nothing at all when unavailable; it does not render a disabled button.

### Refresh the knowledge index

After adding or editing packages, pages, FAQs, offices or awards:

```bash
php artisan ai:index
```

or **Admin → AI Assistant → Rebuild knowledge index**.

Prices, hotels and itineraries do **not** need a reindex — they are read live. A reindex is only needed when a record is added, renamed, published or unpublished.

### Prune old conversations

```bash
php artisan ai:prune            # uses AI_RETENTION_DAYS
php artisan ai:prune --days=30
```

Worth a scheduled task. Conversations that produced an enquiry are never pruned.

## Deployment

1. Set `OPENAI_API_KEY` (and any overrides) in the production `.env`.
2. Deploy. `php artisan migrate --force` creates the four tables.
3. Run `php artisan ai:index` once — **required**; the assistant cannot match anything against an empty index.
4. In the admin, enable the assistant and public access, and confirm the Test Panel reports *Connection OK*.
5. Open the public site and send one real question.

> This project's `deploy.php` runs `migrate --force` but **never `db:seed`** and never `ai:index`. The index build is a deliberate manual step (or an admin button click) after the first deploy — see `docs/audits/IMAGE_ASSET_AUDIT.md` and issue #5 for what happens when data steps are assumed to run automatically here.

## Cost control

- `AI_DAILY_MESSAGE_LIMIT` caps messages per visitor per day.
- `AI_RATE_LIMIT_PER_MINUTE` caps burst usage per session and per IP.
- `max_conversation_messages` caps a single conversation.
- `ai.history.max_turns_sent` caps how much history is re-sent each turn, which is the main driver of prompt-token growth.
- Token usage to date is shown on **Admin → AI Conversations**. It is a usage count, not a billing figure — rates depend on the provider.

## Known limitations

- No live inventory, booking, payment or visa status. By design.
- Answers are not streamed.
- Roman Urdu detection is a two-marker heuristic; it hints the prompt rather than deciding the reply language.
- The knowledge index is rebuilt wholesale rather than incrementally. At this content volume (~95 records) that takes well under a second; it would need revisiting at a much larger scale.
- Quick-action labels and the widget's built-in strings go through `__()` but no translation files ship with this change, so they render in English until translations are added.
