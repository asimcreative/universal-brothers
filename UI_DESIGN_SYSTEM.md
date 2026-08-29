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
- Both loaded from Google Fonts (`fonts.googleapis.com`) with system-font fallback stacks already declared in `_variables.scss`.
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
