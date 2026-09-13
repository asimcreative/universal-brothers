# AI Assistant — Test Report

**Date:** 13 September 2026
**Feature:** AI project assistant (public chat, retrieval layer, admin panel)

## How the provider is handled in tests

Every automated test fakes the provider. That is not a way of avoiding the real thing — it is the only way to assert on **what we send**, which is where the risk lives:

- does the context actually contain the right package's *live* prices?
- does the API key ever reach the browser?
- can one visitor reach another's conversation?

A live call would make those assertions weaker, not stronger, and would tie the suite to a paid third party being reachable. The live provider was exercised separately, by hand — see "Live provider verification" below.

`phpunit.xml` pins an obviously-fake key and an `api.openai.invalid` base URL, so a future test that forgets `Http::fake()` fails against an unreachable host rather than quietly spending the client's credit.

## Backend — PHPUnit

**Full project suite: 250 passed, 860 assertions.** (Before this feature: 183 / 637.)

### `AiAssistantTest` — 44 tests

| Area | Covers |
|---|---|
| Availability | Disabled assistant, public access off, missing key, widget rendered/not rendered |
| Validation | Empty message, over-long message, CSRF membership of the `web` group, POST-only routes |
| Retrieval | Exact package code, currency-specific rendering, live prices, "not published" for a missing currency, variant separation, unpublished package excluded |
| Parsing | Code normalisation (`ub 1` → `UB001`), currency detection not firing on substrings |
| Language | Roman Urdu detection, Arabic/Urdu/Hindi/Bengali scripts, English not misread as Roman Urdu |
| Prompting | Anti-hallucination rules present, injection attempt handled |
| Secrets | Key absent from response and page, sent only as a Bearer header, never in URL or body |
| Links | Real internal URLs with reasons; suppressed when the setting is off |
| Errors | Auth failure, empty reply, malformed body, failure recorded, **no-credit distinguished from throttling** |
| Limits | Per-minute rate limit, conversation length cap, history trimming, lead endpoint shares the inquiry throttle |
| Privacy | Session-bound conversations, IP stored only as a hash, reset |
| Leads | Inquiry created in the existing inbox, consent required, disabled state, not offered on the opening message, offered on intent |

### `AiAssistantAdminTest` — 23 tests

| Area | Covers |
|---|---|
| Authorization | Guests blocked from every screen and every write |
| The key | Encrypted at rest, masked on screen, blank field keeps it, explicit clear, environment beats database, hidden from serialisation, scrubbed before logging |
| Configuration | Validation, plaintext base URL rejected, localhost allowed, toggles, model change |
| Knowledge | Reindex, and the index-is-for-matching-only guarantee |
| Test panel | Working connection, failure reported without leaking the key or the provider body, retrieved context shown |
| Analytics | Most-requested packages counted from visitors' own messages |
| Conversations | Transcript rendering, stored content escaped |

## Browser — Playwright

**15 AI tests × 2 projects (chromium, mobile-chrome) — 30 passing.**

**Full project suite: 238 passed, 0 failed, 0 flaky, 4 skipped** (11.6m, exit 0), re-run after every change to confirm nothing else regressed.

| Test | Proves |
|---|---|
| Launcher opens/closes | `aria-expanded` tracks state |
| Escape closes | Focus returns to the launcher |
| Welcome + quick actions | 7 chips, welcome message |
| Send and receive | User bubble, assistant bubble, list formatting, source card with a real href |
| Enter / Shift+Enter | Enter sends, Shift+Enter makes a newline |
| Quick action | Sends its predefined question; chips step aside |
| Busy state | Typing indicator, send and input both disabled, restored after |
| Error + retry | Error bubble, retry re-sends and succeeds |
| Clear | Back to the welcome message, chips restored |
| Lead form | Absent from the DOM until offered; appears on offer; removed on cancel |
| **No name collisions** | 11 public form labels × 2 pages × panel open/closed, plus the "Send Message" button |
| Injection | `<img onerror>` / `<script>` render as text, `window.__xss` undefined, off-site source produces no card |
| Secrets | Key absent from page HTML, inline scripts and every request payload |
| Phone width | Bottom sheet fits 390px, no horizontal overflow, still usable |
| Accessibility | `role="dialog"`, `aria-labelledby`, `aria-live="polite"`, keyboard-reachable |

## Regressions found and fixed during testing

Five things surfaced. Three were defects in this feature, one was an existing
test that had to become more specific because the page legitimately changed,
and one was nothing to do with the assistant at all.

### 1. The lead form appeared on the visitor's opening message

`shouldOfferLead()` guarded on `message_count`, which counts assistant replies too and therefore already reads 2 after a single exchange. The intent was "not on the first message". Fixed to count the visitor's own messages.

### 2. The lead form competed with every page's contact form

The widget renders on every page, and its lead form was rendered hidden but still present in the DOM. A hidden field is still real DOM with a real accessible name, so `Email` on the assistant and `Email` on the page's own form became two matches **on every page of the site**. This broke 17 existing browser tests.

Not only a test problem: a form the visitor was never offered was sitting in every page, competing for autofill and for assistive technology.

Fixed structurally — the lead form now lives in a `<template>` and is cloned in only when offered, then removed. `<template>` content is inert: not in the document tree, not in the accessibility tree, not matched by a query.

### 3. The send button's label collided with the contact form

`aria-label="Send message"` matched both `getByLabel('Message')` (the textarea) and `getByRole('button', { name: 'Send Message' })` (the submit button), because accessible-name matching is substring-based. Renamed to "Send to the assistant", and a rule written into the Blade comment: nothing added to the widget may carry an accessible name containing a label the public forms use.

A permanent test now checks the full public label vocabulary on two pages, with the panel both closed and open.

### 4. `locator('form')` in the contact-validation test became ambiguous

`public.spec.js` test 11c did `page.locator('form').evaluate(...)`. That was unambiguous when `/contact` had exactly one form. The assistant's composer is a second, legitimate form on every page — a `<form>` is the correct element for it, since that is what gives Enter-to-submit its semantics — so the test now says which form it means.

Fixed by scoping the test rather than by changing the widget: the widget is right, and a bare `locator('form')` was always going to break on the first page that grew a second form.

### 5. The currency-switcher test was asserting data that no longer exists — nothing to do with the assistant

Test 6b asserted that UB001 Package B Quad shows **N/A** when switched to SAR, with a comment explaining why: *"no PKR/SAR value exists in the brochure"*. That was true when it was written.

**It is not true any more, because of yesterday's work.** The client supplied the PKR and Riyal brochures and the backfill landed (issue #5), so that row now holds SAR 59,500 and PKR 4,640,000. The test was asserting the absence of data that the client has since provided.

This failure was present before the assistant existed; it surfaced now only because this is the first full browser run since the backfill reached the testing database.

Updated to assert **all three currencies** — a stronger test of the switcher than USD-and-a-blank ever was — and the N/A path moved to where it is still true: UB001 Package A Quad has no price in any currency, so it reads N/A whichever is selected.

**Lesson recorded separately:** the first full run reported `17 failed` four lines above `219 passed`. Reading the tail of a long log is not reading the result.

## Live provider verification

Run by hand against the real OpenAI API with the client's key.

| Check | Result |
|---|---|
| Key authenticates | **Yes** — HTTP 429, not 401 |
| Request correctly formed and accepted | **Yes** |
| Structured error parsed and mapped | **Yes** |
| Fallback message shown to the visitor | **Yes** |
| A completion generated | **No** — see below |

The provider returned: *"You have no credits remaining. Add credits to continue using the API."*

**The integration is proven end to end up to the point of generation. No answer can be produced until the OpenAI account is topped up.** That is a billing action only the client can take, and it is reported here rather than worked around.

The live run earned its keep regardless: it exposed that OpenAI returns **429 for an exhausted balance as well as for genuine throttling**, and the client was reporting both as "rate limited". Those need opposite responses — *top up* versus *wait and retry* — so this would have sent whoever is on support down the wrong path. Now a distinct `provider_no_credit` failure with its own remedy in the admin test panel, and two tests pinning the cases apart.

## Not covered by automated tests

- **Answer quality.** No test asserts that the model's prose is good, because that is not something a unit test can judge. What *is* tested is that the model is given correct, current, complete facts and the rules to use them.
- **Real provider latency and cost** under production load.
- **Languages beyond detection.** Script detection is tested; whether the model's Bengali is idiomatic is not.
- **Firefox and WebKit** for the AI widget specifically. The existing cross-browser projects cover the rest of the public site; the assistant runs on Chromium and mobile Chrome, matching how `public.spec.js` scopes its own coverage.
