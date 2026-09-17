# Homepage Layout Proposal

**Implementation note (2026-09-16).** Built as described below with two
deliberate differences, both to avoid deleting approved content:

- The page now carries **12 sections, not 10**. "Trusted by Pilgrims Around the
  World" is an approved homepage block from the source flow, so it stayed as its
  own section rather than being folded into the credibility strip.
- The two absorbed sections kept their words: the pilgrims-served paragraph
  moved into "Experience That Inspires Confidence", and the "Personalized Care"
  paragraph into "Why Universal Brothers". Nothing was dropped, only merged.

The repeated counters are gone: the years and pilgrims figures now appear once,
in the credibility strip, where they keep their count-up behaviour.

**Date:** 2026-09-16 · **Status:** proposal, awaiting approval
**Reads with:** [DESIGN_DIRECTION_ANALYSIS.md](DESIGN_DIRECTION_ANALYSIS.md) · [UNIVERSAL_BROTHERS_DESIGN_SYSTEM.md](UNIVERSAL_BROTHERS_DESIGN_SYSTEM.md)

The homepage today has **14 sections**. Four of them argue for trust in different words, the same three statistics appear up to three times, and several calls to action compete. Nothing needs deleting from the site — this is about what the *homepage* carries, and where the detail lives instead.

Proposal: **10 sections**, each with one job. Every section still draws from the same controller data (`sliders`, `hajjPackages`, `umrahPackages`, `awards`, `affiliations`, `testimonials`, `news`, `stats`, `counters`, `filterDurations`) and stays CMS-driven.

---

## 1. Today → proposed

| # | Today | Proposed | What happens |
|---|---|---|---|
| 1 | Hero (slider or editorial) + trust panel | **1. Hero** | Keep. Simplify to one message + two CTAs; the stats panel moves out (see 2). |
| — | Trust ticker strip | **2. Credibility strip** | Keep, directly under the hero: 20+ years · 10,000+ pilgrims · Zone 1 Category A Mina camp · IATA registered · Hajj Licence 2014. |
| 2 | "Experience That Inspires Confidence" (photo + counters) | merged into 2 and 6 | Its counters are the same figures as the hero panel. |
| 3 | "Thousands of Journeys. One Enduring Trust." (photo + counters) | merged into 6 | Same three numbers again. |
| 4 | "Trusted by Pilgrims Around the World" (global reach) | merged into 6 | Becomes one line of the credibility block. |
| 5 | Awards | **7. Recognition** | Keep as its own section, tightened. |
| 6 | "Three Services. One Standard of Care." | **3. Services** | Keep, promoted up the page — it answers "what do you do?" early. |
| 7 | Package finder | **4. Hajj 2027** | Becomes part of the Hajj feature band, which is our commercial priority. |
| 8 | "Because No Two Sacred Journeys Are the Same" | merged into 6 | Third "why us" section. |
| 9 | "A Name Built on Trust…" (why us) | **6. Why Universal Brothers** | The one "why us" section, with the real proof points. |
| 10 | Hajj 2027 band + package cards | **4. Hajj 2027** | Keep as the strongest band: intro + finder entry + 3 featured packages + "See all 12". |
| 11 | Umrah + tourism | **5. Umrah & Tourism** | Keep, condensed to two cards leading to their own pages. |
| 12 | Testimonials | **8. Pilgrim voices** | Keep, text + video. |
| 13 | Affiliations | merged into 7 | Logos sit under the awards as one recognition block. |
| 14 | Final CTA | **9. Talk to us** | Keep: enquiry + phone + WhatsApp. |
| — | Footer, AI assistant | **10. Footer** + assistant launcher | Unchanged in substance. |

Net: 14 → 10, with the three repeated counters shown **once**, and four trust sections becoming one strip plus one section.

---

## 2. Proposed page

```
┌──────────────────────────────────────────────────────────────┐
│ Announcement line (one message, CMS, dismissible)            │  ← replaces the busy utility bar
│ Header: logo · 5 nav items · Register Now                    │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  1. HERO — photo, scrim                                      │
│     eyebrow: HAJJ · UMRAH · TOURISM                          │
│     h1: A Sacred Journey. A Trusted Name.                    │
│     one sentence of support copy (max 2 lines)               │
│     [ Explore Hajj 2027 → ]  [ Plan Your Umrah ]             │
│     scroll cue                                               │
├──────────────────────────────────────────────────────────────┤
│  2. CREDIBILITY STRIP  (navy, compact)                       │
│     20+ Years · 10,000+ Pilgrims · Zone 1 Category A Mina    │
│     Camp · IATA Registered · Hajj Licence 2014               │
├──────────────────────────────────────────────────────────────┤
│  3. SERVICES  (white)                                        │
│     ┌─ Hajj ──────┐ ┌─ Umrah ─────┐ ┌─ Tourism ───┐          │
│     │ photo       │ │ photo       │ │ photo       │          │
│     │ 1 sentence  │ │ 1 sentence  │ │ 1 sentence  │          │
│     │ View →      │ │ View →      │ │ View →      │          │
│     └─────────────┘ └─────────────┘ └─────────────┘          │
│     Hajj card is wider / first — primary service             │
├──────────────────────────────────────────────────────────────┤
│  4. HAJJ 2027  (navy feature band, the page's centrepiece)   │
│     h2 + one paragraph                                       │
│     Finder entry: duration · type · arrival  [ Find → ]      │
│     3 featured package cards (code, days, chips, from-price) │
│     [ See all 12 Hajj packages → ]                           │
├──────────────────────────────────────────────────────────────┤
│  5. UMRAH & TOURISM  (cream)                                 │
│     two cards, each: photo, one sentence, CTA                │
├──────────────────────────────────────────────────────────────┤
│  6. WHY UNIVERSAL BROTHERS  (white)                          │
│     4 proof points (care, planning, on-ground support,       │
│     accommodation choice) + the three counters, shown once   │
├──────────────────────────────────────────────────────────────┤
│  7. RECOGNITION  (warm surface)                              │
│     award badges grid + affiliation logo row beneath         │
│     [ View all awards → ]                                    │
├──────────────────────────────────────────────────────────────┤
│  8. PILGRIM VOICES  (cream)                                  │
│     3 testimonials (video where available) + [ Read all → ]  │
├──────────────────────────────────────────────────────────────┤
│  9. TALK TO US  (navy band)                                  │
│     short enquiry form │ phone · WhatsApp · office hours     │
├──────────────────────────────────────────────────────────────┤
│ 10. FOOTER — 4 columns, real contact block, licence line     │
└──────────────────────────────────────────────────────────────┘
        AI assistant launcher, bottom-right (unchanged)
```

Mobile order is the same, single column. The hero keeps its two CTAs stacked full width; the credibility strip becomes a 2×3 grid; the finder entry collapses to a single "Find your package" button that opens the full filter on `/hajj`.

---

## 3. Hero specification

| Requirement | Decision |
|---|---|
| Communicates all three services | Eyebrow reads `HAJJ · UMRAH · TOURISM`; the headline carries the promise |
| Approved messaging | Existing approved lines only: *"A Sacred Journey. A Trusted Name."* and *"Serving the Guests of Allah with Experience, Care & Commitment."* No new claims |
| Typography | `display-1`, Playfair, second line italic gold (existing brand treatment) |
| Contrast | Navy scrim `ub-navy-overlay()`, text measured at 4.5:1 or better against the darkest and lightest parts of the photo |
| Primary CTA | **Explore Hajj 2027** → `/hajj` (commercial priority) |
| Secondary CTA | **Plan Your Umrah** → `/umrah-services` |
| Support copy | One sentence, ≤ 2 lines, ≤ 140 characters (today's is 4 lines) |
| Stats panel | **Removed from the hero** → becomes the credibility strip below it |
| Slider | Keep the CMS slider; each slide obeys the same one-message rule. Autoplay pauses on hover/focus and is off under reduced motion. Static editorial hero remains the fallback when no slides are published |
| Imagery | Existing licensed library only; credits stay on the page. No AI-generated or misleading imagery |
| Height | `min(88vh, 900px)` desktop; content-height + comfortable padding on phones — never a 100vh trap |
| Mobile | Headline `clamp` floor 2.75rem, CTAs stacked, no parallax |

---

## 4. Section-by-section notes

**2. Credibility strip.** Five facts, icon + label, one row on desktop and a 2-column grid on phones. All from `SiteSetting` (`years_in_operation`, `pilgrims_served`, `mina_camp_location`, `iata_registered`) plus the licence number already in the header strip — no new figures.

**3. Services.** Hajj gets a wider card and appears first. One sentence each, drawn from existing page content, with a photo from the library.

**4. Hajj 2027.** The only place on the homepage that shows package cards. Three featured packages with the new chips (days · Madinah/Makkah first · shifting · Aziziya · hotel rating) and a "from" price with currency. The finder entry keeps the real filters (`filterDurations`, type, arrival) and submits to `/hajj` — the full experience stays on the listing page.

**6. Why Universal Brothers.** Four proof points, each one line, plus the three counters (`years`, `pilgrims`, `awards_count`) shown once on the page.

**7. Recognition.** Award badges in a grid (`awards`), affiliation logos beneath (`affiliations`), one CTA to `/awards`. Logos keep their empty `alt` beside a visible name, as today.

**9. Talk to us.** Existing `x-inquiry-form` with labels above fields and `autocomplete`, beside the real phone, WhatsApp and office details. No new contact values.

---

## 5. What is deliberately *not* changing

- Every section stays driven by the existing controller and CMS records.
- No package data, price, hotel name, licence detail or contact value is altered.
- The AI assistant, page builder, sliders, awards, affiliations, testimonials and news modules keep their current admin behaviour.
- `/hajj`, `/umrah-services`, `/tourism`, `/awards`, `/affiliations`, `/testimonials`, `/media`, `/faqs`, `/contact` keep their routes and their content; some of it becomes *more* important, since the homepage now points to it instead of repeating it.

## 6. Navigation proposal (affects every page)

| Today (10 items) | Proposed (5 items) |
|---|---|
| Home · About Us · Hajj & Umrah · Tourism · Awards & Recognition · Affiliations · Media · Testimonials · FAQs · Contact | **Home** · **Hajj & Umrah** (dropdown: Hajj 2027 packages, Hajj services, Umrah services, FAQs) · **Tourism** · **About** (dropdown: About us, Awards, Affiliations, Testimonials, Media) · **Contact** — plus the gold **Register Now** |

Nothing is removed from the site; the same pages sit one level down. The mobile menu shrinks from 27 links to the same five groups, expandable.

## 7. Open questions for the owner

| # | Question | Why it matters |
|---|---|---|
| Q-1 | Is the announcement line wanted, and what should it say? | It replaces the busy utility bar. Needs one approved sentence, or we leave it off. |
| Q-2 | Should the hero primary CTA be **Explore Hajj 2027** or **Register Now**? | Register Now is the stronger commercial action but a bigger commitment for a first-time visitor. |
| Q-3 | Confirm the wording of the five credibility facts | They are your approved claims; the redesign only re-presents them. |
| Q-4 | Are award *years* approved for display on the badges? | Only if the data already holds them; otherwise names only. |
| Q-5 | Sharing image (issue #17) | Still needed for social previews; unrelated to layout but part of the same polish pass. |

## 8. Success criteria

1. Homepage sections: 14 → 10, with no repeated statistic and one primary CTA per screen.
2. First screen states one message and one main action.
3. Package facts readable in about a second (chips).
4. Top-level nav: 10 → 5.
5. No loss of any content, route or CMS capability.
6. Accessibility holds or improves: one h1, 4.5:1 text, visible focus, reduced-motion respected.
7. No horizontal overflow at 390 / 768 / 1440 px; homepage CSS/JS no heavier than today.
