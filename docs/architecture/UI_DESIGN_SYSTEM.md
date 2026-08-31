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
