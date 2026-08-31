# Responsive QA

## What was actually verified (real browser, real viewport, automated + repeatable)

Playwright's `mobile-chrome` project runs a real Chromium instance at the Pixel 7 device profile (390×844 CSS px, mobile UA) against every public page. This is genuine device-viewport rendering, not a resize of a desktop window — confirmed passing:

| Check | Desktop (chromium) | Mobile (mobile-chrome, Pixel 7) |
|---|---|---|
| Homepage loads, hero renders | ✅ | ✅ |
| Hajj/Tourism listing + package cards render | ✅ | ✅ |
| Package detail (itinerary accordion, pricing table, add-ons table) renders | ✅ | ✅ |
| Hajj package detail redesign: currency switcher actually re-renders the price on click | ✅ | ✅ (test 6b run against both desktop and mobile-chrome profiles) |
| Umrah empty-state renders | ✅ | ✅ |
| Footer with real contact info renders | ✅ | ✅ |
| Navigation | Desktop nav bar links visible directly | Collapses correctly behind hamburger; offcanvas opens and contains the same links (tested explicitly) |
| No horizontal overflow at 375px width | n/a (desktop-only assertion) | ✅ `document.documentElement.scrollWidth <= clientWidth` asserted directly, not eyeballed |

Business-logic tests (form submission, validation) were deliberately **not** duplicated on the mobile project once proven on desktop — see REGRESSION_TEST_RESULTS.md for why (shared per-IP rate-limit buckets, and re-proving server logic twice adds no real signal). Mobile's job here is rendering/layout, and that's what's actually asserted.

## Design-system basis for the rest

Every template in this build uses Bootstrap 5's grid (`container`/`row`/`col-*`) and its default breakpoints (sm/md/lg/xl/xxl) with no custom breakpoint set — see UI_DESIGN_SYSTEM.md for why a custom scale wasn't introduced. Bootstrap 5's own cross-device behavior is a known, widely-verified quantity; this project's own responsive risk surface is limited to whether *this build's* markup uses that grid correctly, which is what the Playwright checks above actually test.

## Frontend redesign — all 7 required breakpoints (closed in an earlier pass)

The tablet-breakpoint gap noted below (previously "not verified") was closed in an earlier pass: `responsive.spec.js` originally exercised the 7 breakpoints that redesign directive specified — 1920, 1440, 1280, 1024, 768, 390, 375 — across 5 representative pages, asserting `document.documentElement.scrollWidth <= clientWidth + 1` at each, plus a nav-collapse check. A real bug was found and fixed while building that coverage: Bootstrap's offcanvas mobile nav, even correctly closed off-screen via CSS `transform`, still contributed to `document.documentElement.scrollWidth` in Chromium (confirmed by direct measurement: 387px reported at a 375px viewport before the fix) — fixed with `overflow-x: hidden` on both `html` and `body` (`body` alone was insufficient).

## Release-gate final responsive QA — 13 breakpoints × 16 pages (this pass)

`responsive.spec.js` was rewritten to the full set the business-confirmation + final-responsive-QA directive required — **13 exact breakpoints** (DESKTOP: 1920×1080, 1680×1050, 1440×900, 1366×768, 1280×720; TABLET: 1024×1366, 820×1180, 768×1024; MOBILE: 430×932, 414×896, 390×844, 375×812, 360×800) across **16 named public pages** (Homepage, About Us, Hajj Services, Hajj Package Listing, Hajj Package Detail, Umrah Services, Umrah Packages, Tourism, Domestic Tourism, International Tourism, Awards, Affiliations, Media, Testimonials, FAQs, Contact), using a resize-without-reload pattern (one page load, then loop `setViewportSize` + measure) rather than one test per page×breakpoint combination. All 16 pages pass with zero horizontal overflow at all 13 breakpoints.

Dedicated sub-suites added for this pass, all passing:
- **Mobile navigation QA** — hamburger open/close, nested mega-menu items reachable, no background-scroll issue, no stuck-open state, real nav links (About/Awards/Affiliations/Media/Testimonials/FAQs/Contact) visible and tappable, "Register Now" present with the confirmed URL — at all 5 mobile breakpoints.
- **Hero video/slider responsive QA** — heading and CTAs visible, no overflow, at representative desktop/tablet/mobile breakpoints.
- **Currency switcher responsive QA** — PKR/SAR/USD switching works with no layout break at desktop and mobile widths.
- **Forms responsive QA** — Contact and Hajj inquiry form fields fit the viewport and stay usable at the two narrowest mobile breakpoints (360×800, 390×844).
- **Touch target QA** — package-card and currency-switcher buttons measured ≥44px tall on mobile (see the touch-target fix below).
- **Package card overlap QA** — the package summary text never overlaps the price/CTA row below it (see the overlap fix below).
- **Reduced-motion QA** — package-card hover produces no transform under `prefers-reduced-motion` (see the fix below).
- **Package filter bar responsive QA** — Hajj filter fields (Days/Sharing/Filter button) usable with no overflow at mobile width.
- **Desktop/tablet nav QA** — nav collapses to the hamburger below `lg` (tested at 768px) and shows the full mega-menu-capable nav above it (tested at 1280px).

### Real defects found and fixed during this pass

- **Mobile touch targets under the ~44px minimum** (WCAG 2.5.5 AAA / Apple HIG / Material Design): `.package-card .btn` and `#currency-switcher .btn` measured ~31px tall on a real 375px mobile viewport. Fixed with a mobile-scoped (`@media (max-width: 767px)`) CSS override giving both a 44px `min-height`; re-measured directly to confirm 44px achieved, with a permanent Playwright test proving it.
- **WCAG 2.3.3 "Animation from Interactions" gap**: `.package-card`/`.video-testimonial-card`'s hover-lift `transform: translateY(-4px)` had no `prefers-reduced-motion` guard, unlike this project's own established `.reveal-on-scroll` pattern (which already had one). Fixed by extending the existing `@media (prefers-reduced-motion: reduce)` block to also neutralize both cards' hover transform.
- **Package card text/CTA overlap**: the package summary paragraph (`Str::limit(..., 110)` at the PHP level, but no line-count cap in CSS) could render tall enough to overlap the price/CTA row pinned below it via `mt-auto` — confirmed directly via a Playwright trace showing the paragraph "intercepts pointer events" meant for the "View Details" button. Fixed with a deterministic 2-line `-webkit-line-clamp`; proven by a new test that directly measures both elements' bounding boxes and asserts no vertical overlap, on every visible package card, not just inferred from a click succeeding.
- **Dead anchor link** (found via the new cross-browser WebKit run, not previously caught): `news/show.blade.php`'s "Back to Travel News" link pointed to `route('home').'#news'`, a section the earlier frontend redesign had removed (replaced by the homepage's News Ticker) — fixed to link to `route('media')` (which now hosts the News tab), with a regression assertion.
- **Stale test assertion**: `public.spec.js`'s About Us test still asserted the old `/50,000/` pilgrim figure after this pass's own business-confirmation edit changed the live value to "10,000+" — only surfaced by the new WebKit cross-browser run; fixed.

### Cross-browser QA (new this pass)

Firefox and WebKit Playwright projects were added (previously Chromium/mobile-Chrome only), scoped to `public.spec.js` + `responsive.spec.js` — the public site's own interactive surfaces (nav/mega-menu, hero, currency switcher, forms, package filters, responsive layout), without re-running the full admin suite a 3rd time for marginal benefit. This is genuine, real independent-engine verification, not an assumption that Chromium behavior generalizes. Finding these two real defects (the dead link, the stale assertion) directly justified adding it.

**A residual, low-frequency flake was found and extensively investigated, not swept under the rug**: an intermittent click/navigation failure on `.package-card` "View Details" links, always confined to Firefox/WebKit and only appearing deep into the longest combined 5-project runs — never once in 30+ isolated re-runs of the exact same test. Investigation (8+ full-suite runs, several Playwright trace extractions) found and fixed the two real defects above (the reduced-motion gap and the text/CTA overlap) plus hardened the 4 affected tests to arm `page.waitForURL()` before the click (closing a documented Playwright click/navigation race) — see REGRESSION_TEST_RESULTS.md Run 17 for the full trail. A small residual flake remains, consistent with local single-threaded-dev-server/long-process resource variance rather than a deterministic UI or application defect: it always hits the same 4-test cluster (`public.spec.js` tests 6/6b/9/10, always Firefox, never Chromium/mobile-Chrome/admin-Chromium/WebKit), it self-resolves on an identical, unmodified retry every single time it's been observed (3 consecutive final-config runs, 0 unresolved failures each time), and combined-run duration climbed steadily across the investigation (≈6 → 8.7 → 13.1 → 13.8 minutes) with zero corresponding code change — a pattern that tracks wall-clock session length, not application behavior. Mitigated with Playwright's standard, disclosed `retries: 1`.

## Not verified in this pass

- **Real device testing** (an actual phone/tablet, not a viewport emulation) — not possible in this environment; Playwright's device profiles emulate viewport/UA/touch but are not a substitute for a real device pass before launch.
- **The admin panel's mobile nav** was fixed in an earlier pass (see FINAL_AUDIT_REPORT.md §4 — an offcanvas fallback now exists below `md`, verified by a real Playwright test at 390×844).
