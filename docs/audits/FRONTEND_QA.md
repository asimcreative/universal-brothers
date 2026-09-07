# Frontend QA — Complete Redesign

Functional QA log for the "Complete Frontend Redesign" directive. See `FRONTEND_IMPLEMENTATION_PLAN.md` for the CURRENT→REQUIRED→CHANGE plan this work followed, `FRONTEND_ACCESSIBILITY_AUDIT.md` and `PERFORMANCE_AUDIT.md` for those dimensions specifically.

## What was built

- **Design system**: existing brand tokens (navy/gold, Playfair Display/Poppins, `.reveal-on-scroll`, `.stat-tile`) kept and extended — `prefers-reduced-motion` guards added to both the CSS transition and the JS counter/reveal logic (previously missing).
- **Header**: Bootstrap dropdown-based mega-menu for "Hajj & Umrah" (two columns, one per service, matching `docs/source-documents/1a-website-flow-extracted.md`'s exact IA) and a simple dropdown for Tourism (Domestic/International). Full 10-item primary nav per the directive.
- **Footer**: rebuilt to 5 columns (Hajj/Umrah/Tourism/Company/Support) — every link resolves to a real route; no stubbed/dead links (Careers and a few source-doc items with no backing page were deliberately left out rather than linked to nowhere).
- **Homepage**: rebuilt to the directive's 13 content sections (+ footer = 14), using the literal copy blocks from the Website Flow document verbatim. All numeric claims (years/pilgrims/awards) render from `SiteSetting`/`Award::count()`, never hardcoded.
- **About Us**: restructured into Beginning / Experiences / Awards / Affiliations sections — Awards and Affiliations now pull from real, structured `Award`/`Affiliation` records instead of a flat HTML list baked into the page body.
- **New content types**: `Award` and `Affiliation` — full admin CRUD (index/create/edit/delete, image upload), public `/awards` and `/affiliations` pages, seeded with the same real, previously-verified data that used to live only in `SiteSettingSeeder`/`AboutPageSeeder` (7 real named awards, 10 real named affiliations — nothing invented to hit the source document's aspirational "20+ awards" figure, see the discrepancy note below).
- **Testimonials**: `video_url`/`video_thumbnail`/`package_label` columns added; public `/testimonials` page and the homepage section both show video testimonials first (modal player) with text-quote cards as the fallback — no fake video content was invented, so today every testimonial renders as a text card until the client supplies real videos.
- **FAQs**: public `/faqs` page grouping by the existing `category` column, Bootstrap accordion.
- **Media**: public `/media` page with News / Gallery / Videos tabs, reusing the existing `NewsArticle` and `MediaItem` models — no "Press" tab was added, since no content type or real data for press coverage exists yet (per "only show links/sections that actually exist").
- **Hajj Services landing** (`/hajj-services`) and **Umrah Services landing** (`/umrah-services`): hero, introduction, How to Apply, Hajj Process (visual 9-stage timeline component), Hajj Guidance, Accommodation & Transport, Next Flight Date (CMS-driven `SiteSetting`, shows an honest "to be announced" placeholder — no date was invented), Packages preview, FAQs, Register Now (links to the real external HUMS registration URL already named in the source document).
- **Hajj Package Listing filters**: real, DB-derived filter bar (Days / Package variant / Arrival / Aziziya / 5-Star / Sharing / Price range) — every option list is queried from actual seeded data (`distinct()` on `duration_days`, `package_variants.code`, `package_room_options.sharing_type`), so a package with a 4th sharing type or a new variant code appears automatically with no template change.
- **Hajj Package Detail**: reordered/extended to match the directive's exact section order — added three previously-missing sections that had real backing data but no UI: **Package Options** (Package A/B), a consolidated **Meals** section (pulled from real `meal_plan` values already on `package_accommodations`/`package_mashaer_details`), and a **Gallery** section (`package_media`, gracefully hidden when empty since no real photos exist yet).

## Business decisions confirmed by the project owner (2026-08-30)

- **Pilgrim count: "10,000+ Hajis Served"** — approved public-facing figure, confirmed by the project owner. `site_settings.pilgrims_served` updated from "50,000+" (the previously-verified live-site-audit figure) to "10,000+"; every hardcoded fallback default across `HomeController`, `PageController`, and the seeders updated to match. No longer a discrepancy/blocker.
- **Awards count: "20+ Awards & Recognitions"** — approved public-facing statistic, confirmed by the project owner. `site_settings.industry_awards_count` updated from "7" to "20+". **The 7 real, named award records remain exactly as seeded** (`AwardSeeder.php`) — explicitly confirmed provisional by the owner; no award names were invented to reach 20. The public statistic (`industry_awards_count`) is deliberately decoupled from the `Award` model's actual row count for exactly this reason. Individual award names/details: **PENDING FINAL OWNER CONTENT**.
- **"Register Now" URL confirmed**: the project owner has confirmed `https://hums.akhg.com.pk/HajiReg/HajiLead` **is** the official Universal Brothers Hajj registration system. No longer a blocker. Re-verified every "Register Now" CTA (header mega-menu, mobile offcanvas nav, Hajj Services landing page) points to exactly this URL.

## Known, documented content gaps (not invented, left honest)

- **Video testimonials**: architecture is fully built and tested, but zero real testimonial videos exist yet — the page/section correctly falls back to text cards for every testimonial today.
- **Careers**: no source document has any careers content — no page, no footer link.
- **Next Flight Date**: no real Hajj 2027 flight schedule has been supplied — both landing pages show an honest placeholder linking to Contact instead of a guessed date.
- **Individual award names/details — PENDING FINAL OWNER CONTENT**: the 7 seeded award names/years/descriptions were never independently verified against any source document, at any point in this project's history (found by an earlier independent code review, `FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md` H-2). The project owner has since confirmed the *aggregate count* ("20+") but not yet supplied the individual award names — the existing 7 records are kept as provisional placeholders per explicit instruction, not replaced or padded to 20.

## Testing performed

- **PHPUnit**: 131/131 passing (up from the pre-redesign 102/102 — 29 new tests across the initial build and the subsequent code-review fix pass: `FrontendRedesignTest` (18), `AwardAffiliationManagementTest` (7), 2 new Hajj-detail-page tests, 1 new video-testimonial admin test, 1 new homepage video-priority test). See `REGRESSION_TEST_RESULTS.md` Runs 14–15.
- **Playwright**: 90/94 passing, 4 correctly skipped — full suite (public, admin, both business journeys, and the new dedicated `responsive.spec.js`), run twice back-to-back for idempotency, both before and again after the code-review fix pass.
- **Independent code review**: a Senior-Team-Lead review scoped specifically to this pass (`FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md`) found 0 CRITICAL, 3 HIGH, 10 MEDIUM, 8 LOW, 4 INFO findings. All HIGH/MEDIUM findings were fixed and regression-tested except one (award-name provenance, a business decision — see below); 7/8 LOW and 2/4 INFO were also fixed, with the remainder disclosed as deliberate judgment calls rather than changed unilaterally.
- **Responsive**: 37 dedicated tests across the 7 required breakpoints (1920/1440/1280/1024/768/390/375) on 5 representative pages (home, Hajj listing, Hajj Services, Awards, Testimonials) — zero horizontal overflow at any breakpoint, plus an explicit nav-collapse check (hamburger below `lg`, mega-menu above it).
- **Manual browser verification**: every new route hit directly (curl + a real Chromium instance via Playwright) to confirm 200 status and real content before writing automated coverage.

## Master frontend visual redesign (2026-08-30)

A dedicated visual/UX pass on top of the already-correct, already-tested structure above — the Avenix/GlobeTrek-inspired premium design direction, applied without touching backend, package data, pricing, or admin functionality. See `docs/architecture/UI_DESIGN_SYSTEM.md`'s "Master frontend visual redesign" section for the full design-system token/component inventory.

**Pages redesigned**: header (visible Register Now CTA added alongside WhatsApp), footer (gold top accent), homepage (hero entrance animation, all 12 content sections re-composed with eyebrow labels/generous spacing, a genuine image/content split added to Personalized Services and Umrah Feature — previously text-only), Hajj/Umrah/Tourism listing (hero band, `.offcanvas-lg` responsive filter drawer, premium package cards), Hajj package detail (quick-overview strip, card-based room pricing, day-by-day itinerary timeline, package notes, all data preserved), the generic Umrah/Tourism package detail page, Hajj Services/Umrah Services landing pages, About Us, Awards, Affiliations, Media, Testimonials, FAQs, Contact.

**Package cards** (shared `<x-package-card>` component, used everywhere packages render): now show duration/arrival/shifting/Aziziya as icon-labeled metadata chips (only the fields the package actually has), a designed gradient placeholder instead of no image or a plain icon, and a clearer price/CTA row.

**Package detail**: room-type/upgrade pricing changed from an HTML `<table>` to a card grid (naturally responsive, no horizontal scroll needed on mobile); the itinerary accordion (behavior unchanged) is now visually a numbered, connected day-by-day timeline; a new "quick overview" strip surfaces duration/arrival/Aziziya/shifting/transport/meals at a glance.

**Real defects found and fixed during this pass** (via a systematic screenshot-based visual-quality-gate review, not just "looks fine"):
- **Fonts never actually loaded**: `Playfair Display`/`Poppins` were declared in `_variables.scss` since the design system was first written, but no font file or `<link>` existed anywhere — every page silently rendered in fallback system fonts the whole time. Fixed by self-hosting via `@fontsource`.
- **Hero entrance animation risked a blank hero**: an initial per-child staggered reveal measured out to ~1.5-2s before the last element appeared. Replaced with a single fast (0.5s) reveal on the whole hero block.
- **`.text-secondary` gold/low-contrast on package-card summaries**: a pre-existing dark-background contrast fix (`.text-white .text-secondary { color: gold }`) incorrectly applied inside package cards nested in a dark section (e.g. the homepage's Hajj Feature block), since the card itself is always a white-background component regardless of its section. Fixed with a more specific override.
- **A runaway `height: 100%` layout bug**: `.pricing-card`'s base CSS set `height: 100%` for equal-height grid rows, but the same class was also used standalone (the Aziziya summary card) — a percentage height resolving against an auto-height ancestor measured out to 5000+px in real testing. Fixed by removing the blanket `height: 100%` and adding Bootstrap's own `.h-100` only where a grid context needs it.
- **Header nav overflow at normal desktop widths**: adding the new "Register Now" button (plus an unrelated earlier `nav-link` padding increase) pushed the 10-item nav + 3 buttons past the viewport edge at 1440px and below — real content was literally unreachable. Fixed by reverting the padding increase and removing a redundant third button ("Get a Quote", not explicitly required and duplicative of the existing "Contact" nav item).
- **Broken/duplicated pagination** (pre-existing, not introduced by this pass, but caught by this pass's visual review): Laravel's paginator defaults to its Tailwind view; this project only ever loads Bootstrap, so the Tailwind responsive-visibility classes did nothing and both the "mobile" and "desktop" pagination variants — including oversized, unstyled SVG arrows — rendered simultaneously on every paginated listing. Fixed via `Paginator::defaultView('pagination::bootstrap-5')`.
- Two duplicate CSS rule blocks (`.stat-tile`, `.icon-pillar`, each defined twice during incremental work) consolidated into one definition apiece; one fully unused rule block (`.about-timeline`, written but never applied since imposing a timeline on arbitrary admin-authored body HTML was deliberately not done) removed.

**Accessibility**: heading hierarchy/alt-text/focus states preserved; the new filter drawer and lightbox both use Bootstrap's own accessible offcanvas/modal patterns (focus trap, `aria-*`, Escape-to-close) rather than custom JS; all new animation is gated by `prefers-reduced-motion`.

**Performance**: no new npm runtime dependencies beyond the two self-hosted font packages (~250KB across all weights, only the weights actually referenced in a given page's CSS are fetched by the browser); no new JS libraries — the lightbox/parallax/reveal-stagger are all small vanilla-JS additions to the existing `app.js`, consistent with this project's "no heavy JS framework" policy.

**Testing**: PHPUnit 141/141 (unchanged — this was a visual/frontend pass, no backend logic touched). Playwright: full 5-project suite run repeatedly through this pass's own investigation and fix cycle, converging on 0 unresolved failures (a small residual Firefox/WebKit click-race flake — already documented and mitigated with `retries: 1` before this pass began — continued to self-resolve identically). Two test updates were needed and made: the Hajj filter test now opens the new "Filters" drawer before asserting field visibility (matching the new responsive-drawer UX), and one admin E2E assertion was scoped with `.first()` since a duration value now legitimately appears twice (hero badge + quick-overview strip) by design.

## Final client-ready visual polish pass (2026-08-30, second pass)

A follow-up UI/UX quality pass explicitly built around *not* trusting the previous pass's passing tests as proof the site was visually finished — every required page was actually rendered in a real headless browser, scrolled through so scroll-reveal animations had genuinely fired, and screenshotted at a wide spread of breakpoints (desktop 1920/1600/1440/1366/1280, tablet 1024/900/768, mobile 430/414/390/375/360) before any further code change. Two real, previously-undetected defects were found this way — neither visible from source code or from the previous pass's passing test suite.

**1. Real business data was leaking into the live catalog.** The Hajj package listing screenshot at mobile width showed two cards titled "Playwright E2E Test Package …" mixed in among the real 12 Hajj packages, both marked `published`. Root cause: an admin E2E test (`admin.spec.js` "17-19…") had, earlier in this engagement's own test-fixing work, failed on a strict-mode assertion violation *before* reaching its own cleanup step twice in a row (its first attempt, then Playwright's automatic retry, since the retry hit the identical then-unfixed bug) — each attempt had already created and *published* a real package by that point, so both were left permanently orphaned in the dev database, visible to any real visitor of `/hajj`. Fixed in two parts: (a) the two orphaned records were deleted (`Package::where('name', 'like', 'Playwright E2E Test Package%')->delete()`, confirmed via direct query — Hajj package count back to the correct 12); (b) the test itself was hardened with a `try/finally` so the delete-cleanup step always runs even if an earlier assertion throws, closing off this entire failure class rather than just the one instance found.

**2. The Hajj package detail page's sticky enquiry sidebar had never actually been sticking.** It looked plausible in a static screenshot (a `.sticky-top` element with `top: 100px` sitting where expected on initial load), but direct scroll-position measurement showed its viewport-relative position tracked the scroll offset 1:1 at every depth tested — i.e., it behaved as a completely ordinary, non-sticky element that just scrolls away with the page. Root cause: `html, body { overflow-x: hidden }` (added earlier in this engagement to fix a real horizontal-overflow bug from the mobile offcanvas nav) silently forces the browser's computed `overflow-y` to `auto` too — CSS requires the two axes to resolve together, so even an explicit `overflow-y: visible` declared alongside it gets overridden back to `auto` (verified directly via `getComputedStyle`). Any ancestor with non-`visible` overflow breaks `position: sticky` for every descendant, sidebar included. Fixed by switching to `overflow-x: clip` — verified in an isolated test case that `clip` (unlike `hidden`/`auto`/`scroll`) does *not* force-promote the other axis, then confirmed on the real page that the sidebar now correctly pins at `top: 100px` through a full scroll test and un-pins near the page bottom as expected, while the original mobile-offcanvas horizontal-overflow fix still holds at all mobile widths. A permanent regression test (`responsive.spec.js` "Sticky sidebar QA") now asserts the sidebar's on-screen position directly after a scroll, rather than only checking that it renders.

**Everything else reviewed** (homepage at all 13 breakpoints, Hajj listing at all 13, Hajj detail at all 13, plus every other public page at desktop+mobile): no additional horizontal overflow, no broken cards, no unreadable pricing, no broken accordions found. The mobile filter drawer, itinerary timeline, quick-overview strip, and currency switcher were all re-verified as both visually clean and functionally correct (including confirming the Hajj brochure genuinely contains USD-only pricing — zero of the 67 seeded room options have any SAR/PKR value — so the currency switcher's SAR/PKR views correctly and consistently show "N/A" rather than a fabricated conversion; disclosed as a real, pre-existing content characteristic, not a defect).

**Regression**: PHPUnit 141/141 unchanged; package/business data integrity directly re-verified (12 Hajj packages, 47 total packages, 67 room options, 7 awards, 10 affiliations, 0 orphaned test records). Full 5-project Playwright suite run twice back-to-back after both fixes: **run 1 — 205 passed, 4 skipped, 0 failed, 0 flaky; run 2 — see `REGRESSION_TEST_RESULTS.md` for the exact figure.**

---

# Live-site visual audit and overhaul — 2026-09-05

The owner reviewed the deployed site at `https://universal-brothers.iisol.co` and rejected the visual result. This audit was performed against the **live rendered pages**, not the source and not the test suite. 15 pages were captured at desktop (1440) and mobile (390) and reviewed as images; the automated pass alongside it measured overflow, broken images, console errors and placeholder text per page.

## What actually looked bad

| # | Finding | Evidence |
|---|---|---|
| 1 | **No photography anywhere.** Every image slot rendered a dark box reading "PHOTO COMING SOON" — 3 on the homepage, 9 on the Hajj listing, 9 on Tourism, 7 on Awards, 6 on Hajj Services. | DB: 0/47 packages with `cover_image`, 0/7 awards with `image`, 0/10 affiliations with `logo`, 0 sliders, 0 media items |
| 2 | **Hero was a flat navy rectangle** — no media, centred text, ~900px of empty colour. | `home.blade.php` fallback branch used `background-image: linear-gradient(...)` |
| 3 | **All 13 interior pages inlined the identical flat gradient hero** with per-file `min-height` of 38/42/48/55vh. | The direct cause of "every dark section looks the same" |
| 4 | **Split sections had one empty half** — a lone numeral in several hundred pixels of blank white/cream. | Experience, Pilgrims-served, Hajj-feature sections |
| 5 | **Header nav wrapped mid-phrase** — "About / Us", "Awards & / Recognition", "WhatsApp / Us". | 10 items + brand + 2 CTAs in a 992px bar |
| 6 | **Awards were six identical trophy glyphs**; the Awards page grid went ragged because only some awards carry an issuing organisation. | `award-badge.blade.php` fell back to `bi-trophy-fill` for every record |
| 7 | **Package cards read as Bootstrap admin rows** — five stacked lines of tiny icon+text metadata. | |
| 8 | **Series filter pills wrapped onto three ragged rows.** | Real series names are 30–45 characters |
| 9 | **Trust strip silently clipped its last item** at every width below desktop. | `white-space: nowrap` + `overflow: hidden` |
| 10 | Brand was plain text with no mark; favicon was a deliberately blank `data:,`. | |

## Genuine defects found that the passing test suite had not caught

1. **CRITICAL — statistics server-rendered as `0`.** The HTML delivered to every visitor and every crawler read "0 Years of Experience / 0 Pilgrims Served / 0 Awards & Recognitions". Only JavaScript ever wrote the real value. Confirmed by curling the live HTML.
2. **CRITICAL — desktop visitors could not filter Hajj packages at all.** The filter panel carried both `offcanvas` and `offcanvas-lg`; Bootstrap's plain `.offcanvas` sets `position:fixed; visibility:hidden; transform:translateX(100%)` unconditionally and `.offcanvas-lg`'s ≥992px block resets neither. Verified live via `getComputedStyle`: `visibility: hidden`, parked 400px off-screen, while its trigger button was `display:none`. A dead 25% column was left behind. The existing Playwright coverage only exercised 375px.
3. **Raw database enum keys shown to visitors** — the Hajj Transportation section printed `airport_transfer`, `mashaer`, `train_or_bus`, `car_taxi`, `vip_gmc`. Fixed with a `transportLabel()` accessor on the model so every consumer resolves the same label.
4. **Content visibility depended on JavaScript** — 19 homepage blocks sat at `opacity: 0` until `app.js` ran, with all four initialisers in one un-caught handler.
5. **`IntersectionObserver` threshold unreachable** for tall elements — `threshold: 0.15` cannot be met by anything taller than ~6.7 viewports.
6. **Ungated button hover lift** — a comment claimed it was "guarded further down"; no guard existed. `.package-card-cta` is a `.btn-primary`, so the "View Details" link was moving during click-actionability checks — the documented cause of the intermittent cross-browser click flake.
7. Empty `<img src="">` in the lightbox modal (flagged as a broken image on every page), and a double-escaping bug that rendered "News &middot; Gallery &middot; Videos" literally on the Media page.

## What changed, page by page

- **Homepage** — cinematic composed hero with a credentials panel; three approved-but-never-built sections added ("Trusted by Pilgrims Around the World" with both approved sub-lines, the Servicing tri-panel including the previously-absent Tourism third, and the "Filter My Packages" widget); stat sections rebuilt as composed panels; awards row rebuilt as differentiated medallions.
- **Package listings** — composed banner, working desktop filter sidebar (sticky), single scrollable series-pill row with a fade affordance, result count suppressed at zero, premium cards.
- **Hajj package detail** — composed hero carrying the real cover photo when one exists, proper badge pills, prominent per-person price. **Every field verified still present** — 30-point checklist including Package A/B variants, all four room types, Aziziya pricing, Kaba View supplement, Qurbani, Mina/Arafat detail, day-by-day itinerary with Gregorian and Hijri dates, transportation, meals, inclusions and exclusions.
- **Awards** — citation list with per-award medallions.
- **Affiliations** — institutional roster cards with geometric seals; organisation name kept as accessible text in both the logo and no-logo branches.
- **Media** — news cards gained an image fallback they never had.
- **Testimonials** — quote-mark cards, gold ratings, clamped quotes so a row cannot go ragged.
- **About Us** — two-column editorial with a credentials aside, replacing a narrow centred column that left the right third of the page permanently blank.
- **Footer** — one balanced grid with credential badges and a legal bar, replacing a 4-column row followed by a half-empty second row.
- **Contact / FAQs / Services pages** — shared banner, closing CTA.

## Deliberately not changed

- No business fact was invented. Every figure, credential, price and package field traces to `SiteSetting` or the real package tables.
- No currency conversion was introduced. All Hajj room options carry `price_usd` only; the brochure is USD-only by design and a converted figure would be a fabricated commercial number.
- Honest empty states ("No umrah packages are published yet…", "No news articles have been published yet.") keep their exact meaning — only their presentation improved.
- The 7 award records remain provisional pending owner confirmation; the public "20+" figure is CMS-driven and deliberately decoupled from `Award::count()`.
