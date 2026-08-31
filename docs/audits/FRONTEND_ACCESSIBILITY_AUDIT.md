# Frontend Accessibility Audit — Complete Redesign

Findings from a manual review of the redesigned frontend, cross-checked with real computed-style measurements (not assumed from reading the SCSS source), plus the existing Playwright a11y-adjacent coverage (label associations, keyboard-reachable nav, focus-visible defaults from Bootstrap 5).

## Real issue found and fixed: gold text on light backgrounds (WCAG AA contrast failure)

**Finding:** this theme redefines Bootstrap's `$secondary` SASS variable to the brand gold (`#c9a227`) so that `.btn-secondary`/`.badge.bg-secondary` render correctly (Bootstrap auto-picks a readable foreground for those components). But Bootstrap's `.text-secondary` utility just paints literal text in that color, and this project — including code that predates this redesign pass (`package-card.blade.php`'s summary text) and every new page written this pass (home, Hajj/Umrah Services, Awards, Affiliations, FAQs) — used `.text-secondary` extensively for ordinary body paragraphs on white/light-cream backgrounds.

**Measured, not assumed:** computed the actual contrast ratio for `#c9a227` on `#ffffff` — **2.42:1**, against a WCAG AA requirement of 4.5:1 (normal text) or 3:1 (large text). Confirmed via a real headless-Chromium render (`getComputedStyle`) that this color was in fact being applied to body paragraphs across every new page.

**Fix:** added a `.text-secondary { color: rgba(33,37,41,.75) !important; }` override in `_components.scss` (matches Bootstrap's own `!important` specificity so it actually takes effect) — repaints all `.text-secondary` body text to a safe, readable dark gray (~13.6:1 against white) without touching `$secondary` itself, so buttons/badges keep their intended gold. The footer sits on a dark navy background where gold text was already correct (~7.6:1, verified) — added a second, more specific `.site-footer .text-secondary` rule restoring gold there, so the fix doesn't invert into a dark-on-dark failure in the one place gold text was already right.

**Also fixed:** the `.stat-number` counter widget (`.stat-tile`) defaulted to gold everywhere, including the several new light-background sections this pass added (Experience, Pilgrims-Served, Awards, and the restructured About Us "Experiences" section) — same underlying contrast problem, same measured ~2.42:1. Changed the default to navy (safe on light backgrounds, used in 3 of the page's 4 stat-tile placements) and added a `.bg-primary .stat-number` override restoring gold-light specifically for the one navy-background placement (the Hajj Feature section) — verified via computed style on a real render: navy text on the three light sections, gold-light (`~10:1` on navy) on the one dark section.

## Follow-up: two more contrast gaps found by an independent code review

An independent Senior-Team-Lead review of this pass (`FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md` M-4/M-5) found two more instances of the same underlying issue documented above:

- **M-4**: the `.text-secondary` fix only had a dark-background restoration rule scoped to `.site-footer` — any future `.text-secondary` usage nested inside a `.bg-primary`/`.bg-dark`/`.text-white` section elsewhere (including raw admin-authored CMS body HTML rendered under a `bg-primary` banner in `page.blade.php`) would have silently repainted as dark-gray-on-navy, a *worse* failure than the original light-background bug. Fixed by broadening the restoration rule to `.bg-primary .text-secondary, .bg-dark .text-secondary, .text-white .text-secondary` as well.
- **M-5**: this same pass introduced the identical gold-on-white failure fresh, in new code written alongside the fix — three new `:hover` states (`.mega-menu-title`, `.mega-menu-links a`, `.news-ticker-track a`) used raw `$ub-gold` (~2.4:1) as a hover text color on a white/cream background. Fixed with a new `$ub-gold-dark` token (`#7a5f14`, ~6:1 contrast on white) defined in `_variables.scss` specifically for gold text/links on light backgrounds, distinct from `$ub-gold`/`$ub-gold-light` which remain correct for dark backgrounds and non-text uses (badges, buttons, icons).

## Heading hierarchy

- Every public page has exactly one `<h1>` (in its hero) — confirmed across home, Awards, Affiliations, Testimonials, FAQs, Media, Hajj/Umrah Services, and the existing package listing/detail/About Us pages.
- Fixed a pre-existing (not introduced this pass) hierarchy defect in the Hajj package detail's itinerary accordion: each "Day N" accordion header was an `<h2>`, a sibling of the section's own "Day-by-Day Itinerary" `<h2>` rather than a subsection of it. Changed to `<h3>` (opening and closing tag) to correctly nest under the section heading, matching the pattern already used for the FAQ accordions this pass built (`<h3 class="accordion-header">`).
- New FAQ-category headings (`<h2 id="{category}">`) and accordion question headers (`<h3>`) follow the same pattern on `/faqs`, `/hajj-services#faqs`, and `/umrah-services#faqs`.

## Labels, landmarks, and keyboard access

- The new Hajj filter bar (`packages/category.blade.php`) gives every input an explicit `<label for>` — no bare inputs, consistent with the project's established pattern (an earlier pass's Playwright run had already caught and fixed this class of defect elsewhere in the codebase).
- The mega-menu trigger (`Hajj & Umrah`) and the Tourism dropdown trigger are marked `role="button"` (not left as the implicit `link` role) since they only toggle a panel and never navigate — matches WAI-ARIA authoring practice for a disclosure trigger, and is exactly what a screen reader user needs to not expect a page change. Verified directly via Playwright's accessibility-tree snapshot (`role="button"`, not `"link"`, in the captured a11y tree) rather than assumed from the markup.
- The mobile offcanvas's "Hajj Services"/"Umrah Services"/"Tourism" collapse triggers are the same `role="button"` pattern for the same reason.
- All accordions (FAQ pages, Hajj/Umrah FAQ sections, itinerary) use Bootstrap's native `data-bs-toggle="collapse"` + `aria-expanded`/`aria-controls` pairing — keyboard-operable and screen-reader-announced by default, not a custom implementation.
- The testimonial video modal sets `aria-labelledby` to a (visually-hidden) heading naming whose story is playing, and the close button carries an explicit `aria-label="Close video"` (an icon-only button otherwise has no accessible name).
- All new images (`Award`/`Affiliation` uploads, package gallery, media gallery) have `alt` attributes — falling back to the record's own name/title/package name when no dedicated alt text field is filled in, never left blank.

## Reduced motion

- `.reveal-on-scroll`'s CSS transition and the JS scroll-reveal/counter-animation logic (`app.js`) both now check `prefers-reduced-motion: reduce` — elements render immediately visible and counters jump straight to their target value, rather than animating, when the user has that OS/browser preference set. This was a real, pre-existing gap (not present before this pass) now closed for every one of this session's new animated elements (which all reuse `.reveal-on-scroll` and the shared counter widget) as well as the original ones.

## Responsive keyboard/visual behavior

- Verified via the new `tests/e2e/responsive.spec.js` (37 tests) that the primary nav correctly collapses to the accessible hamburger/offcanvas pattern below the `lg` breakpoint and shows the full mega-menu-capable nav above it — not a half-collapsed or overlapping state at any of the 7 required breakpoints.

## Not yet measured

No automated axe-core/Lighthouse accessibility scan was run in this pass (no such tool is wired into this project's toolchain) — the above is a manual review backed by real computed-style/accessibility-tree measurements at every point a genuine issue was suspected, not a substitute for a full automated audit once a staging URL exists.
