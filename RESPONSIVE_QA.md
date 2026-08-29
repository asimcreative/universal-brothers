# Responsive QA

## What was actually verified (real browser, real viewport, automated + repeatable)

Playwright's `mobile-chrome` project runs a real Chromium instance at the Pixel 7 device profile (390×844 CSS px, mobile UA) against every public page. This is genuine device-viewport rendering, not a resize of a desktop window — confirmed passing:

| Check | Desktop (chromium) | Mobile (mobile-chrome, Pixel 7) |
|---|---|---|
| Homepage loads, hero renders | ✅ | ✅ |
| Hajj/Tourism listing + package cards render | ✅ | ✅ |
| Package detail (itinerary accordion, pricing table, add-ons table) renders | ✅ | ✅ |
| Umrah empty-state renders | ✅ | ✅ |
| Footer with real contact info renders | ✅ | ✅ |
| Navigation | Desktop nav bar links visible directly | Collapses correctly behind hamburger; offcanvas opens and contains the same links (tested explicitly) |
| No horizontal overflow at 375px width | n/a (desktop-only assertion) | ✅ `document.documentElement.scrollWidth <= clientWidth` asserted directly, not eyeballed |

Business-logic tests (form submission, validation) were deliberately **not** duplicated on the mobile project once proven on desktop — see REGRESSION_TEST_RESULTS.md for why (shared per-IP rate-limit buckets, and re-proving server logic twice adds no real signal). Mobile's job here is rendering/layout, and that's what's actually asserted.

## Design-system basis for the rest

Every template in this build uses Bootstrap 5's grid (`container`/`row`/`col-*`) and its default breakpoints (sm/md/lg/xl/xxl) with no custom breakpoint set — see UI_DESIGN_SYSTEM.md for why a custom scale wasn't introduced. Bootstrap 5's own cross-device behavior is a known, widely-verified quantity; this project's own responsive risk surface is limited to whether *this build's* markup uses that grid correctly, which is what the Playwright checks above actually test.

## Not verified in this pass

- **Tablet viewport** (e.g. iPad breakpoint, ~768–1024px) has no dedicated Playwright project — only "mobile" (390px) and "desktop" (1280px, Playwright's `Desktop Chrome` default) were exercised. Bootstrap's `md`/`lg` breakpoints sit in that gap; nothing here confirms them beyond code review.
- **The admin panel** has no mobile/tablet Playwright coverage — the admin sidebar layout (`layouts/admin.blade.php`) hides the sidebar nav below `d-md-block`, meaning on a real mobile device an admin currently has **no visible way to navigate between admin sections** (the nav is simply `d-none` below `md`, with no offcanvas fallback built for it, unlike the public header). This is a genuine, newly-identified gap from writing this document honestly rather than just re-asserting what was already tested — logged in FINAL_GAP_ANALYSIS.md.
- **Real device testing** (an actual phone/tablet, not a viewport emulation) — not possible in this environment; Playwright's device profiles emulate viewport/UA/touch but are not a substitute for a real device pass before launch.
