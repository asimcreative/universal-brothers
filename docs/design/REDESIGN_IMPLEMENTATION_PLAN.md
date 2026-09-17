# Redesign Implementation Plan

**Date:** 2026-09-16 · **Status:** plan, awaiting approval before any implementation
**Reads with:** [DESIGN_DIRECTION_ANALYSIS.md](DESIGN_DIRECTION_ANALYSIS.md) · [UNIVERSAL_BROTHERS_DESIGN_SYSTEM.md](UNIVERSAL_BROTHERS_DESIGN_SYSTEM.md) · [HOMEPAGE_LAYOUT_PROPOSAL.md](HOMEPAGE_LAYOUT_PROPOSAL.md)

Nothing below has been built. No file has been changed for the redesign, and no commit, push or deployment will happen without separate approval at each step.

---

## 1. Principles for the work

1. **Presentation only.** No controller logic, model, migration, package data, price, contact value or company claim changes.
2. **Components before pages.** Shared Blade components first, then each page adopts them, so markup is not duplicated (brief Phase 11).
3. **Additive CSS.** New tokens and component classes are added; existing classes are not renamed until everything that uses them is moved, so no page is left half-styled.
4. **One page at a time, reviewable.** Each stage ends with tests, browser checks and screenshots for review.
5. **Nothing is invisible without JavaScript.** Reveal classes are applied by the observer, never in the server-rendered HTML.
6. **Accessibility is a gate, not a phase.** Each stage is checked for heading order, contrast, focus and reduced motion before it is called done.

---

## 2. Stages

### Stage 0 — Approval and baseline *(no code)*
- Approve the three design documents, and answer the five questions in the homepage proposal (§7).
- Capture "before" screenshots of every public page at 1440 / 768 / 390 px, and record current CSS/JS weight, so the change can be judged.

### Stage 1 — Design tokens *(low risk)*
- `resources/scss/_variables.scss`: surface tier, ink scale, borders, `$ub-radius-pill`, `$ub-radius-xl`, `$ub-section-py-lg`, `$ub-measure`.
- No visual change yet — tokens only.
- **Check:** build succeeds, full suite green, site pixel-identical.

### Stage 2 — Shared components *(the foundation)*
| Component | Purpose |
|---|---|
| `x-section-header` | eyebrow + heading + optional lead, one rhythm everywhere |
| `x-chip` | one package fact, from real fields |
| `x-cta` | pill button with optional arrow, variants and sizes |
| `x-trust-strip` | the credibility facts row |
| `x-feature-card` | service / Umrah / tourism card |
| revise `x-package-card` | photo, code, chips, from-price, CTA |
| revise `x-stat-panel`, `x-award-badge` | new spacing and type scale |

- **Check:** component tests render each one; existing pages unchanged until they adopt them.

### Stage 3 — Header, navigation, footer *(every page)*
- 10 nav items → 5 with dropdowns; announcement line replacing the utility bar (subject to Q-1); offcanvas rebuilt from the same five groups.
- Keyboard: dropdowns operable, focus visible, no layout shift on scroll, `aria-current` on the active item.
- **Check:** E2E for desktop nav, mobile offcanvas, keyboard traversal; every page still reachable.

### Stage 4 — Homepage
- Rebuild `home.blade.php` to the 10-section order, reusing Stage 2 components.
- Hero simplified; counters appear once; Hajj 2027 becomes the feature band.
- **Check:** all controller data still rendered; no section silently dropped; screenshots at three widths.

### Stage 5 — Service pages (Hajj, Umrah, Tourism)
- Apply the section header, cards and spacing; Hajj page gets the strongest treatment.
- **Check:** existing content preserved, FAQ accordions keep their new focus rings.

### Stage 6 — Package listing `/hajj`, `/tourism`
- Restyle the "Refine Packages" rail (sticky, quieter labels, active filters as chips, visible reset) and the cards (chips + from-price).
- Filters, series tabs, paging and routes unchanged.
- **Check:** every existing filter still returns the same results; 12 Hajj packages all listed.

### Stage 7 — Hajj package detail *(the most important page)*
- Regroup into the 21 blocks the brief lists, with: sticky summary (lg+), clear Package A/B switch, currency switch, accessible price tables, accordions for long secondary detail, and a print-friendly fallback.
- **Nothing removed**: options, room types, all three currencies, itinerary, Mashaer detail, transport, meals, inclusions, exclusions, upgrades, notes.
- **Check:** field-by-field comparison against the live page for all 12 packages, so no value is lost or crossed between packages.

### Stage 8 — Remaining pages
- About, Awards, Affiliations, Testimonials, Media, FAQs, Contact, policy pages, and the CMS `page-sections` blocks so builder pages inherit the same system.

### Stage 9 — Motion and polish
- Reveal, counters, hover and accordion timings from the one motion vocabulary; reduced-motion verified.

### Stage 10 — Performance and images
- Audit image sizes and `sizes` attributes; confirm lazy loading below the fold; look at the 477 KB CSS (unused Bootstrap components) — **reported, not removed, without approval**.

### Stage 11 — Full verification
- PHPUnit, the Playwright suites, responsive checks at 390 / 768 / 1440, accessibility pass, and a link/route sweep.

### Stage 12 — Review, then (separately) commit, push, deploy
- Changed files, screenshots and test output presented for approval.
- Commit only on approval; push only on approval; deploy only on approval.

---

## 3. Files expected to change

| Area | Files |
|---|---|
| Tokens / styles | `resources/scss/_variables.scss`, `_components.scss`, `_animations.scss` |
| New components | `resources/views/components/{section-header,chip,cta,trust-strip,feature-card}.blade.php` |
| Revised components | `package-card`, `stat-panel`, `award-badge`, `testimonial-card`, `inquiry-form`, `page-hero` |
| Layout | `resources/views/layouts/app.blade.php`, `partials/nav.blade.php`, `partials/footer.blade.php` |
| Pages | `home`, `hajj-services`, `umrah-services`, package listing and detail views, `about`, `awards`, `affiliations`, `testimonials`, `media`, `faqs`, `contact`, `page-sections` |
| Public blocks | `resources/views/pages/blocks/*` (so CMS-built pages match) |
| Tests | new component and layout tests; E2E for nav, homepage, listing, detail |

**Not touched:** controllers, models, migrations, seeders, admin views, `app/Support/**`, package data, `.env`, production.

## 4. Tests

| Type | What |
|---|---|
| Existing suites | 470 PHPUnit tests and the Playwright suites must stay green throughout |
| Open Graph regression | `tests/Feature/SharingTagsTest.php` (11 tests, added today) already guards the fixed metadata bug, including a directive-leak guard on every public page — kept and extended to any new layout |
| New feature tests | Homepage renders every data set (sliders, packages, awards, affiliations, testimonials, stats); nav contains every route it used to; package detail still shows every field for a fixture package |
| New E2E | Desktop and mobile navigation, hero CTAs, package filtering, Package A/B and currency switching, accordion keyboard use, focus visibility |
| Responsive | No horizontal overflow at 390 / 768 / 1440 on every public page |
| Accessibility | One h1, heading order, contrast sampling, focus rings, reduced-motion behaviour |

## 5. Risks

| Risk | Handling |
|---|---|
| A section is dropped silently during the homepage rebuild | Feature test asserting each data set appears; before/after screenshots |
| Package detail loses a field, or mixes two packages | Field-by-field comparison for all 12 packages against the live page |
| CMS-built pages left on the old styling | `pages/blocks/*` included in Stage 8 |
| Nav grouping hides a page people use | Every page stays in the footer and sitemap; grouping is reversible |
| CSS grows instead of shrinking | Measure before and after; additive classes replace older ones only when unused |
| Scope creep into content | Any wording change is listed for approval, never made silently |

## 6. Effort

| Stage | Estimate |
|---|---|
| 1–2 tokens and components | 2–3 h |
| 3 header/nav/footer | 2 h |
| 4 homepage | 3–4 h |
| 5–6 service pages and listing | 3 h |
| 7 package detail | 4–5 h |
| 8 remaining pages | 3 h |
| 9–11 motion, performance, verification | 3 h |
| **Total** | **≈ 20–23 h**, reviewable stage by stage |

## 7. Approval gates

1. **Now:** approve these documents and answer the homepage questions.
2. After Stage 2: review components.
3. After Stage 4: review the homepage.
4. After Stage 7: review the package detail page.
5. After Stage 11: review everything, then approve commit → push → deploy as three separate decisions.
