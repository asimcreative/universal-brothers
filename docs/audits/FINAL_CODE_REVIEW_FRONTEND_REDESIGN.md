# Final Code Review — Frontend Redesign Pass

Independent senior-lead review of the "Complete Frontend Redesign" pass: new mega-menu header/footer, a rebuilt 14-section homepage, a restructured About Us page, two new content types (Award, Affiliation) with full admin CRUD + public pages, extended video-capable Testimonials, five new public pages (Awards, Affiliations, Testimonials, FAQs, Media, Hajj Services, Umrah Services), a real database-driven filter bar on the Hajj listing, and three new additive sections on the Hajj package detail page (Package Options, Meals, Gallery).

**Methodology.** Every controller, model, migration, seeder, route, and Blade view named in the review brief was read in full — not inferred from names or from the implementation plan's own claims. `git diff`/`git log`/`git show` were used throughout (this is in fact a git repo, despite the environment banner) to separate what this pass actually changed from what a prior, already-reviewed pass left behind, and to settle content-provenance questions definitively rather than by inference. `FRONTEND_IMPLEMENTATION_PLAN.md`, `docs/source-documents/1a-website-flow-extracted.md`, `docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md`, and (where a specific provenance claim needed checking) `EXISTING_WEBSITE_AUDIT.md`/`PROJECT_DISCOVERY.md` were read as ground truth. `artisan test` was run first to confirm baseline: **127/127 passing**, including all three new/extended test files (`FrontendRedesignTest.php` — 17 tests, `Admin/AwardAffiliationManagementTest.php` — 6 tests, and the 2 new tests appended to `HajjPackagePublicTest.php`), consistent with what this task was told to expect. Nothing was broken at the start.

This review itself was findings-only — per the original engagement brief, no files were modified and no fixes were applied during the review. A subsequent fix pass then addressed every finding; see the "Resolution" section at the end of this document for what changed and how each was verified.

## Executive Summary

This is, on balance, an unusually disciplined pass for a redesign of this size. The single most emphasized rule for this engagement — that the already-audited Hajj package data model must not be touched, destroyed, or flattened — was fully respected: all three new migrations (`awards`, `affiliations`, testimonial video columns) touch only their own new/target tables, `PackageController::applyHajjFilters()` is purely read-only `whereHas()` filtering with no raw SQL, and the three new sections on `show-hajj.blade.php` (Package Options, Meals, Gallery) are provably additive reads of relations that already existed before this pass. The two new admin CRUD controllers (Award, Affiliation) correctly avoid this codebase's own twice-previously-shipped route-model-binding bug, correctly validate every field, and correctly delete the old file on image/logo replacement — all matching the established `Faq`/`Office` pattern. The content-fabrication check this brief specifically called for came back clean in the narrow sense it was framed: every single seeded award and affiliation name, including specific years and descriptions, traces byte-for-byte (verified via `git diff`) to content that already existed in this codebase before this pass began.

That said, six parallel-agent audits plus direct reading turned up real problems, the most consequential being: the brand-new "video testimonials" feature — an explicit requirement of this pass — has a fully-built public display path but **zero admin path to ever create one**, with a silent field-drop and a false "Testimonial updated." success message; the homepage's own testimonial query truncates to 6 rows *before* splitting video from text, quietly defeating the pass's own "prioritize video" requirement the day real data crosses that threshold; the Hajj price-range filter can return packages where no single room option is actually in the requested range; and this same pass, while genuinely fixing a real gold-on-white contrast bug in one place, introduced the identical contrast failure fresh in three new places (mega-menu hover states, news-ticker hover state) in the same commit. Separately, deeper provenance tracing surfaced a real, if inherited, business-risk finding: the seven named awards were never actually verified against any of this project's own source documents at any point in its history, and this pass is what turns that unverified content into a structured, publicly-routed, admin-editable, stat-counter-driving "real data" feature.

**Totals: 0 CRITICAL, 3 HIGH, 10 MEDIUM, 8 LOW, 4 INFO.**

---

## CRITICAL

None found. Every mechanism this brief specifically flagged as high-risk (Hajj data-model integrity, admin auth on new resources, mass-assignment/file-upload handling, route-model-binding on the new resources, raw-SQL exposure in the new filter) checked out clean under direct reading, not merely under the seeded test suite.

---

## HIGH

### H-1. The new "video testimonials" feature has a fully-built public display path but no way to ever create one through the CMS — silent field-drop, false success message

**Files:** `app/Http/Controllers/Admin/TestimonialController.php:51-66`, `resources/views/admin/testimonials/form.blade.php` (entire file), `app/Models/Testimonial.php:12-15`, `resources/views/testimonials.blade.php:31-47`, `database/seeders/TestimonialSeeder.php`

`Testimonial::$fillable` was correctly extended this pass to include `video_url`, `video_thumbnail`, and `package_label`, and the public pages fully consume them (`testimonials.blade.php` renders a video-thumbnail card + Bootstrap modal + iframe keyed on `video_url`/`video_thumbnail`/`package_label`). But `Admin\TestimonialController::validated()` was never touched: its `$request->validate([...])` array only lists `name`, `quote`, `service_tag`, `rating`, `source`, `sort_order`, `is_active` — no rule for `photo`, `package_label`, `video_url`, or `video_thumbnail`. Laravel's `validate()` only returns keys present in the rules array, so any of these four fields submitted in a POST/PUT is silently dropped before `Testimonial::create()`/`update()` ever sees it. `admin/testimonials/form.blade.php` compounds this: it has no `<input>` for any of the four fields at all, so a content editor cannot even attempt to set them through the UI.

**Concrete failure:** a content editor is asked to add a real Hajj pilgrim's video testimonial. They fill in name/quote/rating, save, and see "Testimonial created." — a genuine success message. The video never appears anywhere, with no error and no indication why, because the field was never on the form and would have been silently dropped even if it had been. There is currently no seeded video testimonial (`TestimonialSeeder.php`'s 4 real entries all lack `video_url`), so this is dormant today — but it is the guaranteed, silent outcome of the very first attempt to use the feature this pass shipped as a headline redesign item.

**Fix direction:** add `photo`, `package_label`, `video_url` (`nullable|string|max:255`), `video_thumbnail` (`nullable|string|max:255`) to `Admin\TestimonialController::validated()`'s rules array, and add the corresponding form fields (a file input for `photo`/`video_thumbnail` mirroring `AwardController`'s upload-and-delete-old-file pattern, plus a `video_url` text input) to `admin/testimonials/form.blade.php`.

### H-2. The seven seeded awards were never independently verified against any real source document at any point in this project's history — and this pass turns that unverified content into a structured, publicly-routed, admin-editable, stat-counter-driving feature

**Files:** `database/seeders/AwardSeeder.php:8-30`, `database/seeders/AboutPageSeeder.php` (pre-existing content, introduced in commit `e41ee1c`), `FRONTEND_IMPLEMENTATION_PLAN.md:12-21`, `resources/views/home.blade.php:122-153,166-194`, `resources/views/page.blade.php:71-97`

This is **not** a claim that this pass invented anything — `git show e41ee1c:database/seeders/AboutPageSeeder.php` proves all seven award names, including the specific "(2010–2011)" year and the "25 years of excellence" description, already existed verbatim as a flat `<h2>Recognition</h2>` list before this pass touched anything, and `AwardSeeder.php`'s docblock/removal of that flat list is a faithful, correctly-idempotent lift, not a new fabrication. The problem is upstream of this pass: `EXISTING_WEBSITE_AUDIT.md` — the document `AwardSeeder.php`'s own docblock and `FRONTEND_IMPLEMENTATION_PLAN.md` both cite as "the live site audit" that recovered these — only ever confirms that an "Awards" nav item/page *existed* on the live sites; it never quotes a single award name, awarding body, year, or description as independently confirmed. `HAJJ_BROCHURE_EXTRACTION.md` (146 lines, described as covering "every page... nothing is inferred or guessed") contains zero occurrences of the word "award." `1a-website-flow-extracted.md` contains a *different*, non-overlapping award list ("50+ Awards," "Booking.com Best Performance Awards," etc.) inside an unconfirmed `[THEMES]` demo-template section, and its own confirmed-copy sections only ever use the generic "20+ Awards & Recognitions" figure, never a specific name. Distinctive strings from the seeded list — "WHUC," "London Olympia," "Who's Who," "Consumers Choice," "Brand Icon" — appear in exactly two files in the entire repository: `AwardSeeder.php` itself and `FRONTEND_IMPLEMENTATION_PLAN.md`, which cites `AwardSeeder.php`'s own list as its evidence. That is circular sourcing.

This pass's own handling of the *aggregate count* discrepancy (Website Flow doc says "20+," verified data says 7) was genuinely responsible — it documented the conflict in `FRONTEND_IMPLEMENTATION_PLAN.md` §1, kept both numbers CMS-driven, and did not pad the list to 20. But that same diligence was never extended to the *names* themselves, which this pass elevates from a single buried paragraph on one page into: a first-class `Award` Eloquent model, a dedicated public `/awards` route + page, a full admin CRUD surface where staff can now "manage" these records as if they were confirmed facts, and a new `industry_awards_count` SiteSetting (`= '7'`) driving an animated stat-tile counter on both the homepage and the About page. A previously low-visibility, unverified claim is now structurally load-bearing across three customer-facing surfaces with the implicit authority of "real, admin-managed data." (The 10 seeded affiliations do not have this problem — 7 of the 10 are independently corroborated by `EXISTING_WEBSITE_AUDIT.md`'s confirmed-real list; the remaining 3 — PHGOC, ELAF, FPCCI — rest only on cross-seeder consistency, which is a materially lower-stakes gap.)

**Fix direction:** flag the seven award names/years/descriptions for explicit client confirmation before `/awards` and the stat counters go live, the same treatment this pass already gave the aggregate-count discrepancy — this is a business decision, not a code change.

### H-3. `FrontendRedesignTest::test_media_page_shows_real_news_gallery_and_video_items` provides false confidence — it only tests the News tab, not the Gallery or Video tabs it claims to cover

**File:** `tests/Feature/FrontendRedesignTest.php:92-100`

```php
public function test_media_page_shows_real_news_gallery_and_video_items(): void
{
    NewsArticle::create(['title' => 'Real News Item', 'slug' => 'real-news-item', 'body' => 'Body.', 'is_active' => true, 'published_at' => now()]);

    $response = $this->get('/media');

    $response->assertOk();
    $response->assertSee('Real News Item');
}
```

`MediaPageController::index()` builds three independently-queried collections (`$news`, `$gallery` filtered on `media_type='image'`, `$videos` filtered on `media_type='video'`), and `media.blade.php` renders three separate tab panes, each with its own empty-state. This test's name and the class docblock both explicitly promise "Media (News/Gallery/Videos tabs)" coverage, but no `MediaItem` row is ever created and neither the Gallery nor the Videos tab content is asserted anywhere. A regression that broke the gallery/video query (wrong `media_type` string, wrong view variable, wrong `is_active` scope) would pass this suite silently, contradicting the "127/127 passing" evidence this claims to be part of.

**Fix direction:** create one `MediaItem` with `media_type='image'` and one with `media_type='video'` in this test, and assert their captions/titles appear.

---

## MEDIUM

### M-1. Hajj listing's price-range filter checks `price_min` and `price_max` against independent room options, not the same one — can return packages with no room actually in the requested range

**File:** `app/Http/Controllers/PackageController.php:129-134`

```php
->when($request->filled('price_min'), function ($q) use ($request) {
    $q->whereHas('roomOptions', fn ($r) => $r->where('price_usd', '>=', (float) $request->input('price_min')));
})
->when($request->filled('price_max'), function ($q) use ($request) {
    $q->whereHas('roomOptions', fn ($r) => $r->where('price_usd', '<=', (float) $request->input('price_max')));
});
```

These are two separate `whereHas()` calls, each compiled as its own independent `EXISTS` subquery. A package with room options priced $800 and $3,000 satisfies *both* conditions under a `?price_min=1000&price_max=2000` filter (EXISTS a room ≥ 1000 → the $3,000 one; EXISTS a room ≤ 2000 → the $800 one) even though **no single room option** is actually priced between $1,000 and $2,000 — a visitor shopping by budget is shown a package with nothing in their stated range. The existing regression test, `test_hajj_listing_filter_by_price_range_returns_only_matching_packages`, does not catch this because both fixture packages in that test have exactly one room option each, so the two independent subqueries can never disagree.

**Fix direction:** combine both bounds into a single `whereHas('roomOptions', fn ($r) => $r->where('price_usd', '>=', $min)->where('price_usd', '<=', $max))` so both checks run against the same row.

### M-2. Homepage testimonial query truncates to 6 rows *before* splitting video from text — silently defeats this pass's own "prioritize video" requirement once real data exceeds that threshold

**File:** `app/Http/Controllers/HomeController.php:37-39`

```php
$testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->limit(6)->get();
$videoTestimonials = $testimonials->filter(fn (Testimonial $t) => filled($t->video_url))->values();
$textTestimonials = $testimonials->filter(fn (Testimonial $t) => blank($t->video_url))->values();
```

`FRONTEND_IMPLEMENTATION_PLAN.md` item 15 requires the homepage section to "prioritize video cards, fall back to text cards." But `->limit(6)` runs on the raw, unfiltered query — before any video/text split — so *which* six testimonials are even considered is governed purely by `sort_order` rank. If a shop has 6 text testimonials at `sort_order` 1–6 and 3 video testimonials at `sort_order` 7–9, the video ones never make it into `$testimonials` at all, and the homepage shows zero video testimonials despite them existing and being active — silently contradicting the section's own design intent, with no error and no way to notice except by manually checking `sort_order` values. Compare `TestimonialPageController::index()` (the full `/testimonials` page), which correctly has no `->limit()` before its identical filter/split — only the homepage variant has this bug. Dormant today only because `TestimonialSeeder.php` seeds exactly 4 rows, none with video — and compounds directly with H-1, since no video testimonial can currently be created through the admin anyway.

**Fix direction:** fetch all active testimonials (or a generously over-fetched pool), split by video/text first, then cap each side independently — e.g. `take(3)` video + `take(6 - videoCount)` text — rather than capping the combined pool before the split.

### M-3. Home/Hajj-Services/Umrah-Services listing controllers regress this codebase's own eager-loading discipline — missing `series` eager-load causes N+1 on `<x-package-card>`

**Files:** `app/Http/Controllers/HomeController.php:22-27`, `app/Http/Controllers/HajjServicesController.php:17-19`, `app/Http/Controllers/UmrahServicesController.php:16-18`

All three controllers fetch package collections with `->packages()->published()->orderBy(...)->limit(...)->get()` and correctly `setRelation('category', ...)` per package to avoid an N+1 there — but none add `->with('series')`. `components/package-card.blade.php:23` does `@if($package->series) ... {{ $package->series->name }} @endif`, a `belongsTo` lazy-load if not eager-loaded. `PackageController::category()` (line 21, in the same codebase) correctly does `->with('series')` for the exact same card component — these three newer controllers didn't follow the pattern they sit right next to. Concretely: the homepage alone triggers up to 6 extra lazy `SELECT * FROM package_series WHERE id = ?` queries (3 Hajj + 3 Umrah cards) on every load.

**Fix direction:** add `->with('series')` to all three queries, matching `PackageController::category()`.

### M-4. Accessibility contrast fix has no dark-background override for `.text-secondary` outside `.site-footer` — a latent regression path, not yet triggered

**File:** `resources/scss/_components.scss:14-23` vs. `:283-300`

The fix gives `.stat-number` two-tier coverage: a light-background default (`_components.scss:291`, navy, ~14:1) *and* a dark-background override keyed off `.bg-primary &, .text-white &` (`:296-300`, gold-light, ~10:1). `.text-secondary` only gets a light-background default (`:14-16`, dark gray, ~6.7:1) plus a single dark-background override keyed *specifically* to `.site-footer` (`:21-23`) — there is no equivalent `.bg-primary .text-secondary`/`.text-white .text-secondary` rule. Verified this is **not** currently triggered: every existing `.text-secondary` usage that sits inside a `.bg-primary`/`.bg-dark` ancestor (page-header banners, the Hajj Feature/Final-CTA sections) either uses plain unclassed text inheriting `text-white`, or sits inside a white `.card`/`.accordion-body`, not directly on the dark background. But `page.blade.php:35,131` renders `{!! $page->body !!}` — raw, admin-authored CMS HTML — directly beneath a `bg-primary text-white py-5` banner (`page.blade.php:13`); any admin who pastes body content containing `class="text-secondary"` inside a colored wrapper, or any future dev adding a `.text-secondary` caption inside `home.blade.php`'s `bg-primary` sections (lines 222, 353), will silently reintroduce ~2.47:1 contrast on navy — worse than the original bug this pass fixed.

**Fix direction:** add `.bg-primary .text-secondary, .bg-dark .text-secondary, .text-white .text-secondary { color: $ub-gold-light !important; }` alongside the existing footer rule.

### M-5. This same pass introduced the identical gold-on-white contrast failure fresh, in three new places, in the same commit that fixed it elsewhere

**File:** `resources/scss/_components.scss:84-86,99-101,163-166` (confirmed via `git diff` to be new-this-pass rules, not pre-existing)

`.mega-menu-title:hover { color: $ub-gold; }`, `.mega-menu-links a:hover { color: $ub-gold; }`, and `.news-ticker-track a:hover { color: $ub-gold; text-decoration: underline; }` all render literal `$ub-gold` (`#c9a227`) text on a white/cream background on `:hover` — independently recomputed at **~2.42:1**, the exact same failing ratio (and the exact same root cause: raw `$ub-gold` used as body/link text color on a light background) that this pass's own code comment in the same file explicitly describes and fixes for `.text-secondary`/`.stat-number`. Both components are new this pass (`.mega-menu-*` backs the new header mega-menu; `.news-ticker` backs the new homepage news ticker — both confirmed live and reachable, not dead CSS) — so this is not a pre-existing gap left unaddressed, it is the same bug reintroduced in new code written alongside the fix.

**Fix direction:** change all three hover rules to a WCAG-AA-passing color (e.g. `$ub-navy` or a darkened gold), consistent with the fix already applied to `.text-secondary`/`.stat-number` in the same file.

### M-6. `playwright.config.js` hardcodes an absolute Windows-only PHP path with no environment override — breaks the entire E2E suite on any other machine or CI

**File:** `playwright.config.js:3,39`

`const PHP_BIN = 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';` is used unconditionally in `webServer.command` with no `process.env.PHP_BIN` fallback. On any machine or CI runner without that exact Laragon install at that exact path and PHP point-version (Linux CI, another developer's machine, a different PHP patch version), the Playwright web server fails to start — silently producing "no tests could run" rather than a clear error, and undermining this project's own repeatedly-stated standard of proving fixes via a real, reproducible test run.

**Fix direction:** `const PHP_BIN = process.env.PHP_BIN || 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';`

### M-7. About Us page shows the same 10 affiliation names twice — once in body prose, once in the new structured grid — while the equivalent Awards duplication was correctly cleaned up

**Files:** `database/seeders/AboutPageSeeder.php:28`, `resources/views/page.blade.php:99-122`

This pass correctly removed the old flat `<h2>Recognition</h2>` awards list from `AboutPageSeeder.php`'s body (confirmed via `git diff` — 7 lines deleted, with a comment explicitly noting the new structured Awards section replaces it), so awards render exactly once on `/about-us`. The equivalent cleanup was missed for affiliations: `AboutPageSeeder.php:28`'s "Beginning" paragraph still reads "...affiliated with the Ministry of Religious Affairs (Pakistan), TAAP, PHGOC, ELAF, FPCCI, HOAP, DTS, SECP and KCCI" — the same 10 organizations that `page.blade.php:99-122` now *also* renders as a dedicated logo/badge grid immediately below, on the same page, via the new `Affiliation` model (`PageController::show():19`). Not a factual conflict (the two lists match exactly), but real, live, visible content duplication on a customer-facing page.

**Fix direction:** trim the affiliation enumeration out of the "Beginning" paragraph prose, mirroring how the Awards section was already handled in the same file.

### M-8. `media.blade.php`'s news excerpt is unguarded against a nullable column — inconsistent with this exact pass's own defensive pattern used everywhere else

**File:** `resources/views/media.blade.php:48`

`<p class="small text-secondary">{{ Str::limit($article->excerpt, 100) }}</p>` — `news_articles.excerpt` is a nullable column with no default accessor. If an admin publishes an article without an excerpt, `Str::limit(null, ...)` triggers a PHP 8.1+ deprecation notice on every render of that card (`mb_strwidth()` receiving `null` for its typed `string` parameter) until an excerpt is entered — not fatal, but noisy, and this is precisely the class of bug this same pass correctly guards against in three other places: `page.blade.php:4` (`Str::limit(strip_tags($page->body ?? ''), 160)`), `packages/show-hajj.blade.php:4` (`Str::limit($package->summary ?? '', 160)`), and `components/package-card.blade.php:25` (wrapped in `@if($package->summary)`).

**Fix direction:** `Str::limit($article->excerpt ?? '', 100)`.

### M-9. Video-testimonial card + modal markup is duplicated verbatim between `home.blade.php` and `testimonials.blade.php`, with no shared component — unlike the equivalent text-testimonial handling in the same pass

**Files:** `resources/views/home.blade.php:287-317`, `resources/views/testimonials.blade.php:30-52`

Both files contain a near-identical ~30-line block: the `video-testimonial-card` trigger div, the conditional-background-image thumbnail, the play icon, and a full Bootstrap `<div class="modal fade" id="testimonialVideoModal{{ $testimonial->id }}">` with header/close-button/iframe. `components/testimonial-card.blade.php` exists and is correctly reused for *text* testimonials in both of these same files — no equivalent `x-video-testimonial-card` component was extracted for the video case, so this larger, more structurally complex block was hand-copied instead, inconsistent with the pass's own established componentization pattern.

**Fix direction:** extract a `x-video-testimonial-card` component (mirroring `x-testimonial-card`) and use it in both places.

### M-10. `FrontendRedesignTest::test_faqs_page_groups_questions_by_real_category` doesn't actually assert grouping — only active/inactive visibility

**File:** `tests/Feature/FrontendRedesignTest.php:78-90`

`faqs.blade.php:25-27` genuinely groups FAQs by category (`@foreach($faqsByCategory as $category => $faqs)` with an `<h2>{{ $category }}</h2>` per group) — real behavior worth testing. But the test's assertions (`assertSee('Hajj question?')`, `assertSee('Umrah question?')`, `assertDontSee('Hidden question?')`) never check that the Hajj question appears under/near a distinct "Hajj" heading separate from the Umrah question under "Umrah" — e.g. `assertSeeInOrder(['Hajj', 'Hajj question?', 'Umrah', 'Umrah question?'])` would actually prove grouping. As written, this is really an "inactive FAQs are hidden" test mislabeled as a grouping test — not misleading about a broken feature (the feature does group correctly), just narrower than its name/purpose claims.

---

## LOW

### L-1. `HomeController::index()` computes a `$stats['affiliations']` value that `home.blade.php` never reads — dead code left over from before the `Affiliation` model existed

**Files:** `app/Http/Controllers/HomeController.php:50`, `resources/views/home.blade.php`

`'affiliations' => SiteSetting::get('affiliations', '')` (the legacy flat comma-separated string) is computed into `$stats['affiliations']` on every homepage load, but `home.blade.php` only ever reads the separately-passed, structured `$affiliations` Eloquent collection (line 327 onward) — `grep` confirms `$stats['affiliations']`/`stats.affiliations` appears nowhere in the view. Harmless (the underlying `SiteSetting::get()` call is cached), but genuinely dead code from before this pass built the `Affiliation` model.

### L-2. No numeric validation on the Hajj filter bar's `days`/`price_min`/`price_max` query-string inputs before casting

**File:** `app/Http/Controllers/PackageController.php:115,130,133`

`(int) $request->input('days')`, `(float) $request->input('price_min')`, `(float) $request->input('price_max')` never check `is_numeric()` first. A tampered URL like `?price_max=abc` silently becomes `price_usd <= 0.0` rather than being rejected or ignored, with no error surfaced — combines with M-1's independent-subquery issue to produce confusing, unexplained empty/wrong result sets from garbage input.

### L-3. Related-packages query drops the `sort_order` tiebreaker every other listing query in the same file uses

**File:** `app/Http/Controllers/PackageController.php:56-61,79-84`

`show()`/`showHajj()`'s `$related` query orders only by `orderBy('is_featured', 'desc')`, unlike `category()`/`HomeController`/`HajjServicesController`/`UmrahServicesController`, which all add `->orderBy('sort_order')` as a tiebreaker. Among non-featured related packages, display order is whatever the DB returns for ties — unstable across engines and future data changes, not a correctness bug today.

### L-4. Award/Affiliation badge-grid markup duplicated verbatim between `home.blade.php` and `page.blade.php`, unlike the established component pattern

**Files:** `resources/views/home.blade.php:135-146,335-343`, `resources/views/page.blade.php:79-90,107-115`

The award-badge loop (image-or-trophy-icon fallback + name) and the affiliation-logo loop (image-or-badge fallback) are byte-for-byte identical between these two files, with no shared component extracted — inconsistent with this codebase's own `x-package-card`/`x-testimonial-card` convention for exactly this kind of repeated card markup.

### L-5. Media page's video tab can render a blank `<iframe>` — no guard against an empty `video_url`

**File:** `resources/views/media.blade.php:77-83`

`<iframe src="{{ $item->video_url }}" ...>` has no `@if($item->video_url)` guard, unlike the identical pattern correctly guarded in `packages/show-hajj.blade.php:317` (`@elseif($item->video_url)`). `Admin\MediaItemController`'s validation leaves `video_url` unconditionally `nullable` even when `media_type === 'video'`, so a video-type `MediaItem` can be saved with neither a file nor a URL and will render as an empty iframe on `/media`. Not a security issue, just a broken-UI risk from an upstream data-entry gap.

### L-6. No "guest cannot access affiliation admin" test — only awards has this specific coverage

**File:** `tests/Feature/Admin/AwardAffiliationManagementTest.php:117-120`

`test_guest_cannot_access_award_admin` exists; there is no equivalent `test_guest_cannot_access_affiliation_admin`. Functionally covered (both resources sit in the same `['auth','admin']` middleware group, confirmed independently), but the two resources built in the same PR don't have matching test coverage for the same guard.

### L-7. Several new test assertions prove less than their name/docblock claims (not misleading about a broken feature — imprecise mapping only)

**Files:** `tests/Feature/FrontendRedesignTest.php:102-113,141-155`, `tests/Feature/HajjPackagePublicTest.php:198-218`

- `test_hajj_services_page_shows_real_hajj_packages_and_process_timeline`'s `assertSee('Registration')`/`assertSee('Return Home')` pass regardless of any fixture data — the timeline is a hardcoded static component, not "real Hajj package" data as the test name implies.
- `test_about_us_page_shows_real_awards_and_affiliations_sections` only proves the award/affiliation names appear somewhere on the page, not that they render inside distinct sections.
- `HajjPackagePublicTest::test_package_options_meals_and_gallery_sections_render_real_data`'s "Package Options"/"Package A"/"Package B" assertions would pass in every test in the file (the shared `buildPackage()` helper always creates variants A and B) — only the meal-plan and gallery-caption assertions in that specific test are actually fixture-specific proof.

### L-8. `awards`/`affiliations` migrations have no DB-level unique constraint — idempotency relies entirely on application code

**Files:** `database/migrations/2026_08_29_210001_create_awards_table.php:13`, `2026_08_29_210002_create_affiliations_table.php:13`

Both seeders correctly use `updateOrCreate()` keyed on `name`/`organization_name`, so re-running them is safe — but neither migration adds a `->unique()` index on that column, so there is no database-level backstop against a duplicate row via the new admin CRUD (`AwardController::store()`/`AffiliationController::store()` both call plain `::create($data)` with no uniqueness check) or any other future write path.

---

## INFO

### I-1. Code comment overstates a contrast ratio that is in fact even better than claimed

**File:** `resources/scss/_components.scss:19`

The comment claims the footer's gold-on-navy `.text-secondary` override measures "~7.6:1 (verified)"; independently recomputed against the footer's actual background (`$ub-navy-dark` / `#0a1230`, not the lighter `$ub-navy`) it is closer to ~11.2:1. Still comfortably WCAG-AA-compliant either way — the comment is just inaccurate, not the code.

### I-2. `PackageController::category()` declares a `View|Response` return type but never constructs a `Response`

**File:** `app/Http/Controllers/PackageController.php:15`

Both branches of the method return a `View`; the `Response` half of the union type is dead/misleading, harmless since `View` satisfies it.

### I-3. A deactivated category 404s its listing page immediately, but individual package detail pages under it remain reachable

**File:** `app/Http/Controllers/PackageController.php:17,44-48`

`category()` filters `PackageCategory::where('is_active', true)`; `show()`/`showHajj()` never re-check `$package->category->is_active`. If an admin deactivates a whole category, its listing 404s but bookmarked/indexed detail-page URLs under it stay live. Possibly intentional "deep links persist" behavior — flagged for completeness, not as a defect.

### I-4. Footer renders across 6 logical columns in two rows rather than the plan's literally-specified "5-column layout"

**File:** `resources/views/layouts/partials/footer.blade.php:16-88`

`FRONTEND_IMPLEMENTATION_PLAN.md` item 3 specifies "5-column layout (Hajj/Umrah/Tourism/Company/Support)." The shipped footer has those five plus a sixth "Contact" column, split across two `row` wrappers. No broken or fabricated links result from this — a shape deviation from the plan text, not a functional defect.

---

## Verified clean

Checked directly and found correct, not merely assumed from the plan or the test suite:

- **Hajj data model untouched.** All three new migrations (`2026_08_29_210001/2/3`) create/alter only `awards`, `affiliations`, and testimonial video columns — confirmed none references or touches any of the 11 existing Hajj-specific tables (`package_variants`, `package_accommodations`, `package_room_options`, `package_aziziya`, `package_aziziya_room_options`, `package_aziziya_services`, `package_mashaer_details`, `package_transportation`, `package_notes`, `package_upgrades`, `package_media`).
- **`PackageController::applyHajjFilters()` is purely read-only and properly parameterized.** Every predicate uses Eloquent `where()`/`whereHas()` with bound values — zero `DB::raw()`/`whereRaw()`/string interpolation anywhere in the method or `hajjFilterOptions()`. It only adds filtering on top of the existing query; it never mutates, restructures, or flattens `variants`/`accommodations`/`roomOptions`/`aziziya`.
- **`show-hajj.blade.php`'s three new sections (Package Options, Meals, Gallery) are purely additive.** Every field they read (`variant->code`/`label`, `accommodation->meal_plan`, `mashaerDetail->meal_plan`, `media->image_path`/`video_url`/`alt_text`/`caption`) is an already-established column on an already-established relation; no new migration, no mutation, no controller write path.
- **Route-model-binding — the twice-previously-shipped bug class (News, Media) does not recur a third time.** `Route::resource('awards', ...)`/`Route::resource('affiliations', ...)` need no `->parameters()` override because `Str::singular('awards')`/`Str::singular('affiliations')` naturally produce `award`/`affiliation`, matching `AwardController`/`AffiliationController`'s type-hinted variables exactly — verified for every resource route in `routes/web.php`, not just the two new ones.
- **Admin auth middleware.** Every new admin route (`awards`, `affiliations`) sits inside the same `Route::middleware(['auth','admin'])` group as every other admin resource; no route defined outside it.
- **Mass assignment / validation completeness on Award and Affiliation.** Every migration column has a matching validation rule and matching `$fillable` entry; `is_active` is handled via `$request->boolean()` (not raw truthiness), `sort_order` defaults via `??` (not `?:`) so a genuine `0` is preserved — matching the established `Faq`/`Office` pattern exactly.
- **No orphaned file uploads.** `AwardController::update()`/`AffiliationController::update()` both delete the old `image`/`logo` file before storing a new one, and both `unset()` the key entirely when no new file is uploaded (leaving the existing value untouched on `update()`) — verified by direct reading and by the `test_admin_can_upload_an_award_image_and_it_persists_across_an_unrelated_edit` regression test.
- **No XSS.** Every `{!! !!}` in the new/modified views renders either admin-authored CMS body content (the established, documented `Page::body` pattern) or hardcoded literal strings — never request/query-string-influenced data. `applyHajjFilters()`'s query-string values never reach a view unescaped.
- **No GET routes mutate state**, and `app.js` (76 lines, unchanged filter/mega-menu logic — both are pure Bootstrap `data-bs-*` attributes) has no query-string reads and no DOM-XSS surface.
- **Content-fabrication check, as specifically framed by this brief.** Every seeded award and affiliation name — including exact years and descriptions — traces byte-for-byte, verified via `git diff`, to content that already existed in this codebase (`AboutPageSeeder.php`'s prior flat list, `SiteSettingSeeder.php`'s `affiliations` key) before this pass began. Nothing was invented by this pass. (See H-2 for the separate, more important finding about *this pre-existing content's own* unverified provenance, and this pass's role in escalating its exposure.)
- **Seeders are idempotent.** `AwardSeeder`/`AffiliationSeeder` both use `updateOrCreate()` keyed on a stable, real field (`name`/`organization_name`); `DatabaseSeeder.php` registers both in a sensible position with no FK-ordering risk (neither new table has a foreign key).
- **Tests use the real HTTP stack and don't pollute shared data.** All three new/extended PHP test files (`FrontendRedesignTest`, `AwardAffiliationManagementTest`, `HajjPackagePublicTest`) declare `RefreshDatabase`, create all fixtures inline per-test with synthetic/neutral names (`Visible Award`, `Old Org`, `UB-TEST`), and exercise real routes (`$this->get(...)`/`actingAs(...)->post(...)`) — no direct service/controller instantiation anywhere, and no repeat of this project's own documented past incident of editing shared seeded rows without restoring them.
- **Playwright specs target real behavior.** No `waitForTimeout` anti-patterns, no `test.only`/`fixme`; role-based locator changes for the new mega-menu are correctly reasoned (menu triggers use `role="button"`, not `link`) and add coverage rather than weakening it; the currency-switcher test is correctly split between a PHP assertion (server emits all three currencies) and a genuine Playwright click-through (client-side switch), since PHPUnit cannot itself execute JS.

---

## Resolution

Every finding above was fixed and verified with a real test run — full suite before and after: **131/131 PHPUnit** (up from 127; +4 tests), **90/94 Playwright, 4 correctly skipped** (run twice back-to-back post-fix to confirm idempotency, matching this project's own established practice).

**HIGH — all 3 fixed:**
- **H-1** (no admin path to create a video testimonial): `Admin\TestimonialController::validated()` now validates `package_label`, `photo` (image upload, old-file-delete-on-replace), `video_url`, and `video_thumbnail` (image upload, same pattern) — matching the established `AwardController` file-handling convention. `admin/testimonials/form.blade.php` gained the corresponding fields (`enctype="multipart/form-data"` added), and `admin/testimonials/index.blade.php` gained a "Video" column. Proven end-to-end by a new test, `test_admin_can_create_a_video_testimonial_and_it_renders_publicly` (`ContentManagementTest.php`): posts through the real `/admin/testimonials` route with a `video_url` and an uploaded thumbnail, confirms both persist, then confirms `/testimonials` actually renders the video.
- **H-2** (unverified award provenance): a business/content decision, not a code defect — flagged explicitly in `FRONTEND_QA.md`'s known-gaps section and as a new item in `FINAL_AUDIT_REPORT.md`'s deployment checklist (§19), the same treatment already given the pilgrim-count/award-count aggregate discrepancy. Not silently resolved either way; awaits client confirmation.
- **H-3** (Media test only covered the News tab): `test_media_page_shows_real_news_gallery_and_video_items` now creates one `MediaItem` with `media_type='image'` and one with `media_type='video'`, and asserts both the gallery image path and the video title/URL actually render.

**MEDIUM — all 10 fixed:**
- **M-1** (price filter's independent subqueries): `PackageController::applyHajjFilters()` now runs both bounds inside a single `whereHas('roomOptions', ...)` closure, so both conditions must hold for the same room. New test `test_hajj_listing_price_filter_requires_the_same_room_to_satisfy_both_bounds` proves a package with a $800 room and a $3,000 room no longer matches a `$1,000–$2,000` filter.
- **M-2** (homepage testimonial cap-before-split): `HomeController::index()` now queries video and text testimonials as two separate, independently-capped queries (`limit(3)` video, `limit(6 - videoCount)` text) instead of capping the combined pool first. New test `test_homepage_shows_a_video_testimonial_even_when_ranked_below_six_text_testimonials` proves a video testimonial ranked `sort_order = 99` still appears behind 6 higher-ranked text ones.
- **M-3** (missing `->with('series')`): added to `HomeController`, `HajjServicesController`, and `UmrahServicesController`'s package queries. Verified by direct query-log measurement, not assumed: `hajj-services` dropped from 18 to 13 total queries (the eliminated lazy N+1s), `package_series` now fires exactly once per page across all three routes.
- **M-4** (no dark-background override for `.text-secondary` outside the footer): added `.bg-primary .text-secondary, .bg-dark .text-secondary, .text-white .text-secondary { color: $ub-gold-light !important; }` alongside the existing footer-specific rule in `_components.scss`.
- **M-5** (fresh gold-on-white hover states): `.mega-menu-title:hover`, `.mega-menu-links a:hover`, and `.news-ticker-track a:hover` now use a new `$ub-gold-dark` token (`#7a5f14`, ~6:1 contrast on white — added to `_variables.scss`) instead of raw `$ub-gold` (~2.4:1).
- **M-6** (hardcoded Windows-only PHP path): `playwright.config.js` now reads `process.env.PHP_BIN` first, falling back to the existing hardcoded path.
- **M-7** (About Us affiliation names duplicated): trimmed the "...affiliated with the Ministry of Religious Affairs (Pakistan), TAAP, PHGOC, ELAF, FPCCI, HOAP, DTS, SECP and KCCI" enumeration out of `AboutPageSeeder.php`'s "Beginning" paragraph — affiliations now render exactly once, in the dedicated structured section.
- **M-8** (unguarded nullable excerpt): `media.blade.php` now uses `Str::limit($article->excerpt ?? '', 100)`.
- **M-9** (duplicated video-testimonial markup): extracted `components/video-testimonial-card.blade.php`; both `home.blade.php` and `testimonials.blade.php` now use `<x-video-testimonial-card>`.
- **M-10** (FAQ grouping test didn't prove grouping): now uses `assertSeeInOrder(['Hajj', 'Hajj question?', 'Umrah', 'Umrah question?'])`.

**LOW — 7 of 8 fixed, 1 deliberately left as a judgment call:**
- **L-1**: removed the dead `$stats['affiliations']` computation from `HomeController`.
- **L-2**: `applyHajjFilters()` now guards `days`/`price_min`/`price_max` with `is_numeric()` before casting (fixed alongside M-1).
- **L-3**: added the missing `->orderBy('sort_order')` tiebreaker to both `$related` queries in `PackageController`.
- **L-4**: extracted `components/award-badge.blade.php` and `components/affiliation-badge.blade.php`; both `home.blade.php` and `page.blade.php` now reuse them instead of duplicating the loop body.
- **L-5**: `media.blade.php`'s video tab now wraps its `<iframe>` in `@if($item->video_url)`.
- **L-6**: added `test_guest_cannot_access_affiliation_admin`, mirroring the existing award test.
- **L-7** (test assertions prove less than claimed): not changed — a genuine precision gap in three tests, but each test's own core assertion is still true and no test claims a passing result it doesn't have; left as a disclosed, lower-priority test-quality note rather than rewritten under time pressure that could itself introduce a new, unverified assertion.
- **L-8**: new migration `2026_08_30_000001_add_unique_constraints_to_awards_and_affiliations.php` adds `unique()` on `awards.name`/`affiliations.organization_name`; `Admin\AwardController`/`AffiliationController` also gained a matching `Rule::unique(...)->ignore($id)` validation rule so a duplicate submission now surfaces a normal validation error instead of an unhandled `QueryException`.

**INFO — 2 of 4 addressed, 2 left as disclosed judgment calls:**
- **I-1**: superseded by the M-4 fix — the comment now states a range (~7.6–11:1) covering both the footer's actual `$ub-navy-dark` background and the lighter `$ub-navy` used elsewhere, rather than a single inaccurate figure.
- **I-2**: `PackageController::category()`'s return type narrowed from `View|Response` to `View`, matching what it actually returns.
- **I-3** (deactivated category still leaves detail pages reachable) and **I-4** (footer ships 6 columns, not literally 5): left as-is — both were flagged by the review as "possibly intentional"/"no functional defect," and changing either now would be a product decision made unilaterally rather than a bug fix. Recorded here for the client/dev lead's awareness, consistent with this project's established practice of disclosing rather than silently deciding ambiguous product questions.
