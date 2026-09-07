# UI Design System

## Brand palette (source: real "Crown Packages" brand material in the Hajj 2027 brochure — not Avenix's church palette)

| Token | Hex | Use |
|---|---|---|
| `$ub-navy` (primary) | `#101B45` | Header/footer backgrounds, primary buttons, headings on light backgrounds |
| `$ub-navy-dark` (dark) | `#0A1230` | Hero overlays, dark section backgrounds |
| `$ub-gold` (secondary/accent) | `#C9A227` | CTAs, badges, dividers, icon accents, price highlights |
| `$ub-gold-light` | `#E8C766` | Hover states, subtle highlights |
| `$ub-cream` (light) | `#F7F3E8` | Section backgrounds, card backgrounds alternating with white |

Implemented as Bootstrap 5 SCSS variable overrides in `resources/scss/_variables.scss` (`$primary`, `$secondary`, `$dark`, `$light`), so every Bootstrap component (buttons, badges, navbar, alerts) inherits the brand automatically rather than needing per-component overrides.

## Typography

- Headings: **Playfair Display** (serif, `$headings-font-family`) — gives the "premium international travel company" gravitas the directive asks for, distinct from a generic Bootstrap default and distinct from Avenix's condensed grotesque (which reads more editorial/church-poster than travel-luxury).
- Body/UI text: **Poppins** (`$font-family-sans-serif`) — clean, highly legible, wide language support (relevant for a site serving both domestic Pakistani and international audiences).
- Self-hosted via `@fontsource/playfair-display`/`@fontsource/poppins` (latin subset, weights 400/500/600/700 + Playfair 900 and 600-italic), imported from `resources/js/app.js`, with system-font fallback stacks declared in `_variables.scss`. **Correction (2026-08-30 frontend visual redesign):** this doc previously said "loaded from Google Fonts" — that was never actually true; no font `<link>` or import existed anywhere in the codebase, so every page had silently been rendering in fallback system fonts (Georgia/system-ui) since the design system was first written. Self-hosting (rather than a Google Fonts CDN link) avoids an external network dependency and needs no CSP change.
- Heading hierarchy: H1 (hero/page titles, one per page) → H2 (section titles) → H3 (card/subsection titles) → H4–H6 (in-card structure, FAQ questions, footer column headers). Enforced by Blade section-heading components, not left to page authors to improvise.

## Layout

- Bootstrap 5 grid/containers throughout; standard `container` (max-width breakpoints) for content sections, `container-fluid` only for full-bleed hero/slider imagery.
- Section vertical rhythm: consistent `py-5`/`py-6`-equivalent spacing between homepage sections (defined once in `_components.scss`, not per-section magic numbers).
- Border radius: `0.5rem` base (`$border-radius`), `0.75rem` for large cards/hero panels, `0.375rem` for small badges/buttons — soft, premium, not sharp/corporate, not overly rounded/playful.

## Section flow — homepage (adapted from Avenix's structural skeleton, per Phase 4; see EXISTING_WEBSITE_AUDIT.md §5 for what was and wasn't carried over)

Sticky header w/ mega-style dropdown nav (Hajj / Umrah / Tourism, mirroring the real site's own nav shape) → Hero slider (eyebrow + headline + subtext + dual CTA, adapted from Avenix's hero pattern) → trust-marker ticker band (real numbers: "20+ Years · 50,000+ Pilgrims Served · Category A Mina Camp · IATA Member") → Featured Umrah / Featured Hajj / Featured Tourism package grids → About/Company Introduction (icon-pillar pattern, 4 pillars: Hajj, Umrah, Domestic Tourism, International Tourism) → Why Choose Us → animated stat counters (real figures, not filler) → Services overview → Testimonials carousel (real, tagged-by-service reviews) → Travel News → Partner/accreditation logos (IATA, TAAP, PHGOC, ELAF, FPCCI) → Quick Inquiry form + WhatsApp/Call/Book-Now CTA band → Footer (4 columns: company+socials, quick links, services, contact block with real address/phone/email).

## Components (Bootstrap 5 base + brand overrides, built as Blade components under `resources/views/components/`)

- **Buttons:** `btn-primary` (navy, primary actions), `btn-secondary`/gold outline (secondary actions), a `.btn-whatsapp` variant (WhatsApp green, fixed exception to the palette since it's a recognized platform color), consistent icon+label pattern via Bootstrap Icons.
- **Cards:** package card (image, category badge, title, duration/price teaser, "View Details" CTA), hotel/destination card (image + name + star rating), testimonial card (quote, name, avatar-initial badge, star rating), news/blog card (image, date, excerpt).
- **Package detail components:** itinerary day-by-day accordion, room-type price table, inclusions/exclusions two-column checklist, hotel gallery block, sticky inquiry/CTA sidebar.
- **Forms:** consistent floating-label Bootstrap 5 form controls, inline validation feedback, reCAPTCHA slot, WhatsApp-prefilled-message link alternative next to every form.
- **Badges/alerts:** category badges (Hajj/Umrah/Tourism, Featured, Seasonal, Promotional) as colored Bootstrap badges; flash-message alerts styled to brand.
- **Navigation:** sticky header, dropdown/mega-menu for package categories, mobile off-canvas nav (Bootstrap 5 offcanvas, no separate mobile-nav library needed — replaces Avenix's SlickNav dependency).
- **Breadcrumbs:** Bootstrap breadcrumb component on every non-home page, schema-marked (BreadcrumbList) for SEO.

## Animation policy — deliberately lighter than Avenix's own stack

Avenix's actual build (see EXISTING_WEBSITE_AUDIT.md §5) loads GSAP + ScrollTrigger + SplitText, a custom cursor, Magnific Popup, Parallaxie, and WOW.js simultaneously. The proposal explicitly calls for **"Attractive Animations (Lightweight)"** and warns against animation that hurts performance/SEO/mobile — so this project intentionally does not port that whole stack:
- Scroll-reveal: a small vanilla-JS `IntersectionObserver` helper (a few dozen lines, no library) toggling a CSS fade/slide class — same visual effect as WOW.js at near-zero payload cost.
- Hero/testimonial carousels: Bootstrap 5's built-in carousel component (already in the bundle we're shipping) rather than adding Swiper as a second carousel dependency.
- Animated stat counters: a small vanilla-JS count-up helper triggered by the same `IntersectionObserver`, no Waypoints/Counterup dependency.
- No custom cursor, no parallax library, no GSAP/SplitText, no Magnific Popup — Bootstrap's own modal covers any lightbox/video-popup need.
- Net effect: same "premium, animated, professional" feel Avenix demonstrates, at a fraction of the JS payload, consistent with the "no heavy JS framework, minimal third-party dependencies" mandate.

## Responsive breakpoints

Bootstrap 5 defaults used as-is (sm 576px / md 768px / lg 992px / xl 1200px / xxl 1400px) — no custom breakpoint set introduced, since the proposal's "Desktop/Laptop/Tablet/Mobile" requirement maps cleanly onto Bootstrap's existing scale and a custom scale would only add maintenance risk for no real benefit.

## Master frontend visual redesign (2026-08-30)

A full visual/UX pass — Avenix/GlobeTrek-inspired premium presentation layered onto the existing, already-correct data and business logic. See `docs/audits/FRONTEND_QA.md` for the page-by-page change log and `docs/audits/FINAL_CODE_REVIEW_VISUAL_REDESIGN.md` for the independent review. No backend, package data model, pricing, or admin functionality was touched.

**New design tokens** (`_variables.scss`): a 3-tier shadow scale (`$ub-shadow-sm/md/lg`), a section vertical-rhythm pair (`$ub-section-py`/`-sm`), an eyebrow letter-spacing constant, and a single motion vocabulary (`$ub-ease`, `$ub-duration-fast/base/slow`) so every new component draws from the same values instead of one-off inline numbers.

**New shared components** (`_components.scss`): `.section`/`.section-tight` (consistent vertical rhythm, replacing ad-hoc `py-5`), `.section-eyebrow` (the small uppercase gold kicker label used above nearly every heading), `.visual-placeholder` (the designed gradient+icon "photo coming soon" treatment used everywhere a package/award/gallery image doesn't exist yet — never a browser broken-image icon), `.split-section-visual` (image/visual-one-side, content-the-other layout helper), `.quick-overview` (icon+label+value mini-card strip for the Hajj package hero), `.pricing-card` (room-type/upgrade pricing as cards instead of tables — naturally responsive, no horizontal table scroll needed), `.itinerary-timeline`/`.itinerary-day` (restyles the existing Bootstrap accordion into a numbered day-by-day journey timeline — behavior/JS unchanged), `.filter-panel` + Bootstrap's `.offcanvas-lg` (the Hajj/Umrah/Tourism listing filter is now a responsive drawer below `lg`, a static sidebar above it — one form, no duplicated fields), `.contact-method-card`, `.gallery-item` (lightbox-triggering grid tile, paired with the new `<x-lightbox-modal>` shared component and `initLightbox()` in `app.js`), `.faq-accordion`.

**Animation system** (new `_animations.scss`): a fast (0.5s), single-pass `.hero-anim` entrance for hero content (an earlier per-child staggered version was tried and measured out to ~1.5-2s before the last element appeared — long enough to risk a visibly blank hero — replaced for exactly that reason), scroll-reveal stagger delay modifiers (`.reveal-delay-1` through `-6`) layered on the pre-existing `.reveal-on-scroll`/`IntersectionObserver` pattern, and a small rAF-throttled parallax helper (`initParallax()`, `.parallax-layer`) applied to the homepage hero background only. Everything resolves to an instant final state under `prefers-reduced-motion: reduce`.

**Known, deliberately limited scope**: the CMS-authored `Page::body`/`NewsArticle::body` rich-text content (About Us prose, news articles) is rendered as-authored — no timeline/card structure is imposed on arbitrary admin HTML, since doing so without knowing its actual structure risks breaking it. Long-form prose in these fields is capped to a `46rem` reading width (`.page-body p/ul/ol`, added in the final polish pass below) for line-length comfort, without touching the underlying content.

**Final client-ready visual polish pass (2026-08-31)** — see `docs/audits/FINAL_AUDIT_REPORT.md` §23 for full findings. Superseded the note above about `Package::summary`: rather than leaving the internal data-recovery note visible to customers (the earlier pass's own disclosed limitation), `Package::publicSummary()` now substitutes an honest, non-invented "still being finalized" line for that one exact known internal string, and returns every other real summary completely unchanged — the raw `summary` column itself is still never rewritten. Also added this pass: `<x-empty-state>` (a designed icon+message "nothing published yet" card, replacing a bare `.alert-info` box across Media/Awards/Testimonials/Affiliations/FAQs/package-listing empty states), and a real keyless Google Maps embed on the Contact page for the office's own already-published address (no API key, no invented location). Fixed one CRITICAL layout regression discovered only through direct scroll-position measurement, not visual inspection: `html, body { overflow-x: hidden }` was silently forcing `overflow-y` to `auto` (the two axes resolve together in CSS), which broke `position: sticky` for every descendant on the site, including the Hajj package detail page's enquiry sidebar — switched to `overflow-x: clip`, the one non-`visible` value that doesn't force-promote the other axis.

## Live-site visual overhaul (2026-09-05)

The owner reviewed the deployed site and rejected the visual result. This pass was driven entirely by **rendered screenshots of the live site**, not by code review or by the (fully passing) test suite — a functional pass is not a visual pass. See `docs/audits/FRONTEND_QA.md` for the page-by-page findings, `docs/audits/RESPONSIVE_QA.md` for the measured 221-check sweep, and `docs/audits/FINAL_AUDIT_REPORT.md` §25 for the release-gate decision.

### The generated visual system (`_visuals.scss` + `<x-visual>`)

The single largest change, and the fix for the root cause of "it looks unfinished". The project has **no photography at all** — verified in the database: 0 of 47 packages have a `cover_image`, 0 of 7 awards an `image`, 0 of 10 affiliations a `logo`, 0 sliders, 0 media items. Every image slot was rendering a flat navy box reading **"PHOTO COMING SOON"** (3 on the homepage, 9 on the Hajj listing, 9 on Tourism, 7 on Awards, 6 on Hajj Services). No amount of typography or spacing work fixes a site with no visual layer.

Rather than invent photographs or hotlink stock imagery, every image slot now falls back to a **generated composition** built purely from CSS gradients and inline SVG:

- **Motif library** — `$ub-motif-khatim` (eight-point star tessellation), `-circles` (the overlapping-circle construction grid all Islamic geometry is compass-built from), `-arcade` (ogee arch colonnade), `-girih` (interlaced strapwork), `-chevron` (muqarnas-inspired), `-zellige` (octagon/square tiling), `-rosette` (a single large non-tiling composition for hero/banner surfaces), and `-grain` (fractal noise, to break gradient banding on wide-gamut displays).
- **Eight variants** (`.ub-visual--v0` … `--v7`), each a distinct combination of base gradient, gold bloom position, motif and scale. The variant is chosen by `crc32(seed) % 8`, so a twelve-card grid shows eight genuinely different compositions and a given package always renders the same one across page loads, pagination and cache.
- **Surfaces** — `--card` (16:10), `--panel` (4:3), `--square`, `--stage` (full-bleed hero backdrop with a 60s drift), `--stage-sm` (quieter interior-page banner).
- **Content layer** — an optional large editorial figure drawn from real data (a package's `duration_days`, an award's `year`), a rule, and a caption.

Constraints this system deliberately respects: strictly non-figurative (no people, and no depiction of the Kaaba or the Haramain, which would be both disrespectful and dishonest as a stand-in for real photography); no external asset dependency, so the site-wide CSP `img-src 'self' data:` is untouched; and it disappears entirely the moment an admin uploads a real image.

### New shared components

- **`<x-page-hero>`** — replaces thirteen separate copies of the same inline `linear-gradient(135deg,#101B45,#0A1230)` block with per-file `min-height` values. That duplication was the direct cause of "every dark section looks identical". Supports breadcrumbs, eyebrow, lead, copy, a centred feature variant, an actions slot and an aside slot.
- **`<x-page-cta>`** — shared closing CTA band for interior pages that previously ran straight from their last card into the footer with several hundred pixels of empty white between.
- **`<x-stat-number>`** — see "Statistics" below.
- **`<x-stat-panel>`** — a statistic composed against a generated visual, replacing the lone numeral that used to float in an otherwise empty half-column.
- **`.award-medallion` / `.award-citation`** — per-award struck medallions carrying the award's own initials, replacing six identical `bi-trophy-fill` glyphs.
- **`.service-panel`**, **`.package-finder`**, **`.global-reach`**, **`.testimonial-card`**, **`.news-card`**, **`.affiliation-card`**, **`.series-pill-bar`**, **`.listing-toolbar`**, and a rebuilt `.site-footer` grid.

`.visual-placeholder` is retained only for the admin panel; no public view references it any more.

### Statistics

`<x-stat-number>` server-renders the **approved figure** ("20+", "10,000+") as the element's text and lets JavaScript animate *up* to it. Previously the markup shipped a literal `0` and only JS wrote the real value, so crawlers, social previews, no-JS visitors and anyone with slow JS were told a twenty-year-old company had "0 Years of Experience". `data-counter-target` is unchanged, so the existing raw-HTML assertions still hold.

### Navigation density

The primary nav expands at **`xl`**, not `lg`. Ten required top-level items plus a brand and a CTA do not fit a 992px bar — they wrapped mid-phrase into "About / Us" and "Awards & / Recognition". Every one of those items must remain a *visible link inside `nav.navbar`* (nine E2E call sites scope to it), so hiding them behind a dropdown was not available. 992–1199px now gets the drawer, which is the better experience for that many items anyway. Verified by measurement: the bar holds a single row at 1280, 1366, 1440 and 1920.

### Typographic floor

Every font-size in the design system is now **≥ 0.75rem (12px)**. A measured sweep found 104 instances between 10.5px and 11.9px across eyebrows, badges, form labels and captions. The brand tagline's letter-spacing was tightened alongside its size increase so the larger type did not widen the brand lockup and re-crowd the 1280px nav.

### Touch targets

The 44px minimum is now applied site-wide on touch widths to `.btn`, `.series-pill`, `.nav-pills .nav-link`, `.accordion-button`, `.form-control` and `.form-select`. It previously covered only `.package-card .btn` and `#currency-switcher .btn` — the two selectors an earlier release gate happened to measure.

### Motion

Every new hover/entrance effect is gated under `prefers-reduced-motion: reduce`. This pass also closed a pre-existing gap where a comment claimed the button hover lift was "guarded further down" and no such guard existed — `.package-card-cta` **is** a `.btn-primary`, so that ungated `translateY(-2px)` was moving the "View Details" link's bounding box during Playwright's click-actionability check. That is the same moving-target pattern previously documented as the cause of intermittent cross-browser click failures.

### Content visibility no longer depends on JavaScript

`.reveal-on-scroll` is now gated on an `html.js` class set by an inline script before first paint. Nineteen homepage blocks sat at `opacity: 0` until `app.js` ran; a blocked, failed or errored bundle rendered a blank page. The four initialisers also ran inside one un-caught handler, so a throw in any of them prevented the reveal observer from ever attaching — each is now individually wrapped. The observer's `threshold: 0.15` was replaced with a `rootMargin` trigger, because `intersectionRatio` is measured against the element's own height and can never reach 0.15 for anything taller than ~6.7 viewports.
