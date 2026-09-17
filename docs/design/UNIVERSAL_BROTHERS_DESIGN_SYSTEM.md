# Universal Brothers Design System

**Date:** 2026-09-16 · **Status:** proposal, awaiting approval · **Stack:** Laravel 12 · Blade · Bootstrap 5 (unchanged)

This refines what the site already has rather than replacing it. Existing tokens in `resources/scss/_variables.scss` stay; the additions below fill the gaps the design analysis identified — a display type scale, a warm surface tier, a neutral ink scale, chips, and a documented focus/motion contract.

Character we are designing for: **premium, trustworthy, calm, spiritual without theatre, professional, international, spacious.**

---

## 1. Colour

### 1.1 Brand (unchanged — taken from the real Hajj 2027 brochure)

| Token | Hex | Use |
|---|---|---|
| `$ub-navy` | `#101B45` | Primary. Header, footer, primary buttons, headings on light |
| `$ub-navy-dark` | `#0A1230` | Dark sections, hero scrims |
| `$ub-gold` | `#C9A227` | Accent. CTAs, eyebrows, dividers, price emphasis |
| `$ub-gold-light` | `#E8C766` | Hover, highlights on dark |
| `$ub-gold-dark` | `#7A5F14` | Gold *text* on light (≈6:1). `$ub-gold` on white is 2.4:1 — never for text |
| `$ub-cream` | `#F7F3E8` | Alternating section background |

### 1.2 New — surfaces

Today every section is white, cream or navy. One warm tier in between gives rhythm without a new hue.

| Token | Hex | Use |
|---|---|---|
| `$ub-surface` | `#FFFFFF` | Default page surface |
| `$ub-surface-warm` | `#FBF9F4` | A half-step warmer than white, for large reading sections |
| `$ub-surface-sand` | `#F1EADB` | Cards on cream, quiet panels, table header rows |
| `$ub-surface-navy` | `#0D1738` | Feature bands (Hajj 2027, final CTA) |

Rule: **no more than two dark bands per page**, and never two adjacent.

### 1.3 New — ink (text) scale

| Token | Hex | Contrast on white | Use |
|---|---|---|---|
| `$ub-ink` | `#16203D` | 14.8:1 | Headings and primary text on light |
| `$ub-ink-body` | `#333B52` | 10.4:1 | Paragraphs |
| `$ub-ink-muted` | `#5A6076` | 6.3:1 | Captions, meta, helper text (replaces `rgba(33,37,41,.7)`) |
| `$ub-on-dark` | `#F4F1EA` | 14.6:1 on navy | Text on navy/dark bands |
| `$ub-on-dark-muted` | `#C6CBDD` | 8.1:1 on navy | Secondary text on dark |

Minimum kept: **4.5:1 body, 3:1 large text**, verified per change. Our current sample is 7.6:1 and above — that is the floor, not the target.

### 1.4 Borders

| Token | Value | Use |
|---|---|---|
| `$ub-border` | `rgba(16,27,69,.10)` | Card and panel edges on light |
| `$ub-border-strong` | `rgba(16,27,69,.18)` | Inputs, table rules |
| `$ub-border-dark` | `rgba(255,255,255,.14)` | Edges on navy |

---

## 2. Typography

Families stay: **Playfair Display** (headings) and **Poppins** (body/UI), both self-hosted. The change is scale and rhythm.

### 2.1 Display scale

| Step | Size | Line height | Weight | Use |
|---|---|---|---|---|
| `display-1` | `clamp(2.75rem, 5.4vw, 4.5rem)` | 1.05 | 700 | Homepage hero h1 |
| `display-2` | `clamp(2.25rem, 4vw, 3.5rem)` | 1.1 | 700 | Section h2 on feature bands |
| `h1` | `clamp(2.1rem, 3.4vw, 3.15rem)` | 1.15 | 700 | Inner page titles |
| `h2` | `clamp(1.8rem, 2.8vw, 2.6rem)` | 1.2 | 700 | Section headings |
| `h3` | `clamp(1.35rem, 1.9vw, 1.75rem)` | 1.3 | 700 | Card and subsection titles |
| `h4` | `1.15rem` | 1.35 | 600 | In-card structure, FAQ questions |

Today's largest heading is `clamp(2.5rem, 5.2vw, 4.15rem)`; the step up is deliberate but moderate — a pilgrimage operator should not shout.

### 2.2 Body

| Style | Size | Line height | Notes |
|---|---|---|---|
| `lead` | `1.125rem` | 1.7 | Section intros, max **65ch** |
| `body` | `1rem` | 1.7 | Default |
| `small` | `0.875rem` | 1.6 | Meta, captions |
| `eyebrow` | `0.78rem` | 1.2 | Uppercase, `$ub-eyebrow-letter-spacing` (0.12em), gold-dark on light / gold on dark |

Rules: paragraphs capped at 65–72 characters; **uppercase only** for eyebrows, chips and buttons — never paragraphs; italic Playfair reserved for the hero's second line, where it already carries brand meaning.

---

## 3. Spacing and layout

| Token | Value | Use |
|---|---|---|
| `$ub-section-py` | `6rem` (existing) | Standard section |
| `$ub-section-py-lg` | `7.5rem` (new) | Feature bands (hero-adjacent, Hajj 2027, final CTA) |
| `$ub-section-py-sm` | `3.5rem` (existing) | Compact strips (trust bar, logo row) |
| `$ub-gap-card` | `1.5rem` | Grid gaps |
| `$ub-measure` | `65ch` | Reading column cap |

Containers stay Bootstrap's. Content sections use `container`; hero, feature bands and galleries may go full-bleed.

**Section header pattern** (one component, used everywhere): eyebrow → heading → optional lead, centred or left-aligned per section, with `margin-bottom: 3rem`.

---

## 4. Radius, shadow, elevation

| Token | Value | Use |
|---|---|---|
| `$border-radius-sm` | `0.375rem` | Badges, chips, inputs |
| `$border-radius` | `0.5rem` | Buttons, small cards |
| `$border-radius-lg` | `0.75rem` | Cards, panels |
| `$ub-radius-xl` (new) | `1.25rem` | Feature cards, media frames |
| `$ub-radius-pill` (new) | `999px` | CTA buttons, chips |
| `$ub-shadow-sm` | existing | Resting cards |
| `$ub-shadow-md` | existing | Hover elevation |
| `$ub-shadow-lg` | existing | Hero panels, modals |

Elevation rule: a card rests on `sm`, lifts to `md` on hover/focus, and nothing on a page uses `lg` except one hero or modal.

---

## 5. Components

### 5.1 Buttons

| Variant | Look | Use |
|---|---|---|
| `btn-primary` | Navy fill, white text, pill | Primary action on light |
| `btn-gold` (existing `btn-secondary`) | Gold fill, navy text, pill | Primary action on dark; the single strongest CTA per screen |
| `btn-outline-light` | Transparent, white border | Secondary on dark |
| `btn-outline-navy` | Transparent, navy border | Secondary on light |
| `btn-whatsapp` | WhatsApp green (platform exception) | Contact |
| `btn-quiet` (new) | Text + arrow, no fill | Tertiary ("View all awards") |

Rules: one primary per section. Sizes `sm` 40 px / base 48 px / `lg` 56 px, minimum 44 px touch target. "Go" actions carry a trailing arrow (adopted from the reference, drawn with Bootstrap Icons, `aria-hidden`).

### 5.2 Chips (new)

Small uppercase labels carrying one fact each: `13 Days`, `Madinah First`, `Non-Shifting`, `Aziziya`, `5 Star`, `Half Board`. Sand background, navy text, `sm` radius, 0.72rem. Always generated from real package fields — never typed into markup. Gold variant reserved for `Featured`.

### 5.3 Badges

Category badge (Hajj / Umrah / Tourism), status badge (Featured, Limited seats) — existing Bootstrap badges restyled to the palette, never more than two on a card.

### 5.4 Cards

| Card | Contents |
|---|---|
| Package card | Photo (16:9), code badge, featured pill, title, 3–5 chips, "from" price + currency, `View Full Package` |
| Service card | Image, title, one sentence, arrow link |
| Award badge | Logo/plate, award name, year if approved |
| Testimonial | Quote, name, service tag, rating; video variant keeps its play affordance |
| Stat panel | Figure, label, optional supporting line |

All cards: `lg` radius, `$ub-border`, `sm` shadow, `md` on hover, **the whole card is not a link** — the title and the CTA are, so text stays selectable.

### 5.5 Forms

Existing Bootstrap controls with: 48 px height, `sm` radius, `$ub-border-strong`, label above field (never placeholder-as-label), helper text in `$ub-ink-muted`, errors in `$danger` with text (not colour alone), and `autocomplete` on name/email/phone (audit U-7).

### 5.6 Tables (package pricing)

Sand header row, zebra-free, `$ub-border` rules, right-aligned figures, `scope` on headers, caption naming the option and currency, horizontal scroll container on small screens with a visible hint.

---

## 6. Focus, hover, motion

**Focus** (fixed earlier today, now the contract): every interactive element shows a **3 px solid outline with 2 px offset** on `:focus-visible` — white on dark surfaces, navy on light. Never removed; never replaced by colour change alone.

**Hover:** cards lift 2 px and raise their shadow; buttons darken/lighten one step; links gain underline or gold. Never a layout-shifting transform on a clickable target.

**Motion vocabulary** (existing tokens): `$ub-ease`, `$ub-duration-fast/base/slow`.

| Interaction | Motion |
|---|---|
| Scroll reveal | Fade + 12 px rise, `base`, once |
| Counters | Count up on first view only |
| Card hover | Shadow + 2 px lift, `fast` |
| Accordion | Height, `fast` |
| Hero slides | Cross-fade, `slow` |

Rules: `prefers-reduced-motion: reduce` disables reveals, counters (final value shown immediately), parallax and slide autoplay. **Nothing is invisible before JavaScript runs** — reveal classes apply only after the observer is registered. No parallax on mobile.

---

## 7. Icons

Bootstrap Icons only, 1em, `aria-hidden` where decorative, always paired with text. Gold on dark, navy on light, muted for meta.

---

## 8. Breakpoints

Bootstrap defaults unchanged: sm 576 · md 768 · lg 992 · xl 1200 · xxl 1400. Layout intent: 1 column below md, 2 at md, 3 at lg for cards; hero text never exceeds 640 px measure; sticky sidebars only at lg and up, using the measured `--ub-header-h` offset that already exists.

---

## 9. Dark-context rules

The site is light-first with dark bands (it has no dark mode). On any dark band: `$ub-on-dark` text, gold accents, white focus rings, and images carry a scrim so text keeps 4.5:1 — measured, not assumed.

---

## 10. What this changes in code

| File | Change |
|---|---|
| `resources/scss/_variables.scss` | Add surface, ink, border, radius-pill/xl, `$ub-section-py-lg`, `$ub-measure` |
| `resources/scss/_components.scss` | Section header pattern, chips, button pill/arrow, card revisions, table styling |
| `resources/views/components/` | New: `section-header`, `chip`, `cta-button`, `trust-strip`. Revised: `package-card`, `stat-panel`, `award-badge` |
| Existing page views | Use the new components instead of repeated markup (brief Phase 11) |

No new dependency, no framework change, no database change.
