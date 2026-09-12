# Hajj Package Detail — Design System

**Scope:** the public `/hajj/{slug}` detail page.
**Rule this document exists to enforce:** there is ONE page design for every Hajj package. No template, component, stylesheet or controller branches on a package slug, code, or id. A thirteenth package added through the admin tomorrow renders correctly with no code change.

---

## 1. Why one hard-coded layout was never an option

A structure matrix of all 12 published packages (`.visual-audit/matrix.php`) shows the catalogue does not share a shape:

| Variation | Reality |
|---|---|
| Variant count | 9 packages have 2 variants; **3 have none** (UB011, UB013, UB024) — room options hang directly off the package |
| What the variants differ on | **Not always Makkah.** UB001/UB003 vary the **Makkah** hotel and share one Madinah hotel. UB004/UB006/UB008/UB010 do the opposite — they vary the **Madinah** hotel and share Makkah |
| Sharing types | quad / triple / double everywhere, plus `sharing_room` on UB008 and UB010, whose `occupancy` is **null** |
| Room availability | Some rows exist but are `is_available = false`; 4 rows carry no price at all |
| Aziziya | `optional` on 8, `included` on 4. UB023's **only** accommodation row is the Aziziya one |
| Currencies | **63 USD prices, 0 SAR, 0 PKR** across the whole catalogue |
| Upgrades | **36 priced, 36 unpriced** — "Price on request" is the normal state for half the rows |

A hard-coded heading like "Choose Your Makkah Hotel" would be **factually wrong on a third of the catalogue**. A layout that assumed two variants would break on three packages. That is why every decision is derived, not assumed.

---

## 2. Where the decisions live

```
app/Support/HajjPackagePresenter.php     ← WHAT to show (all derivation)
resources/views/components/hajj/*        ← HOW to show it (all presentation)
resources/views/packages/show-hajj.blade ← the order of the sections, nothing else
resources/scss/_hajj-detail.scss         ← the whole visual system
resources/js/app.js  (initHajjDetail)    ← currency switching, option hand-off
```

`PackageController::showHajj()` eager-loads the package graph and hands the view one `$hajj` presenter. The template contains no `@php` logic beyond passing it around.

### Key presenter methods

| Method | Derives |
|---|---|
| `optionGroups()` | Room options grouped under the variant they belong to, plus an unscoped group when variant-less rooms also exist (a package can legitimately have both) |
| `hasChoice()` | Whether the customer actually has a decision to make |
| `choiceScope()` / `choiceHeading()` | "Makkah Hotel" vs "Madinah Hotel", read from the *location* of the variant-linked accommodation rows. Falls back to the neutral "Package Option" when variants span cities or carry no hotel |
| `sharedAccommodation()` | Hotels that apply whichever option is chosen. Holds back Aziziya rows when a `package_aziziya` record exists, because four packages store the same hotel in both tables |
| `startingFrom()` | `starting_price` when the admin has set one, otherwise the cheapest **available, actually priced** room. Never a zero or a guess |
| `currencyAvailability()` | Which of USD/SAR/PKR this package genuinely holds prices in |
| `journeyStops()` | Ordered stops taken strictly from the itinerary's own `city` column; "To Medinah" collapses into the stay that follows. Nothing is added |
| `quickFacts()` | Up to 8 tiles, each only if the underlying field exists |
| `itineraryColumnLabels()` | Maps `accommodation_a` / `accommodation_b` onto the package's **real variant codes** |
| `mashaerTitle()` | "Mina & Arafat" or whatever the package actually records |
| `sections()` | The in-page nav, built from the same conditions the template uses — so it can never link to a section that was skipped |

---

## 3. The information architecture

The order follows the decision a customer actually makes.

1. **Hero** — identity, from-price, and the two actions (Enquire / See Prices). Badges carry identity chips only; the itinerary facts live in the strip below, where they are labelled.
2. **At a glance** — up to 8 fact tiles, only for fields the package has.
3. **Jump nav** — only the sections that exist.
4. **Your Journey** — the stops, from the itinerary.
5. **Room Type Pricing** — *the* section. See §4.
6. **Your Stay** — the hotels that do not change between options.
7. **Aziziya** — its own section, because "included" and "optional at extra cost" are different offers.
8. **Mina & Arafat** — a card per location.
9. **Day-by-Day Itinerary** — an always-visible timeline.
10. **Transportation · Meals · What's Included · Upgrades · Notes · Gallery**
11. **Related packages** (full width) and the closing CTA.

Sidebar: price summary + phone + enquiry form, sticky. Below `lg`, a fixed action bar.

---

## 4. The pricing section — the problem this rebuild exists for

**Before.** Room prices were one flat two-column grid. Each card showed the hotel name as a small gold eyebrow, so Package A and Package B rows **alternated down the page**, and a customer had to read every caption to work out which hotel a price belonged to. The block that explained what A and B meant sat **below** the prices. A bare "N/A" floated alone in a card. The hotel names then appeared a third time under "Accommodation".

**After.** Each option is one self-contained column:

```
┌─ PACKAGE A ───────── from US$22,450 ─┐  ┌─ PACKAGE B ───────── from US$16,300 ─┐
│ Dar Al Tawhid Intercontinental       │  │ Fairmont Clock Tower                 │
│ MAKKAH  ★★★★★  4 nights              │  │ MAKKAH  ★★★★★  4 nights              │
├──────────────────────────────────────┤  ├──────────────────────────────────────┤
│ Quad Sharing              N/A        │  │ Quad Sharing          US$16,300      │
│ Triple Sharing       US$22,450       │  │ Triple Sharing        US$18,100      │
│ Double Sharing       US$26,850       │  │ Double Sharing        US$20,850      │
├──────────────────────────────────────┤  ├──────────────────────────────────────┤
│ [ Enquire about Package A ]          │  │ [ Enquire about Package B ]          │
└──────────────────────────────────────┘  └──────────────────────────────────────┘
```

A price can no longer be read apart from the option it is for.

**Side by side, not tabs.** The task here is to *compare*. A tab hides exactly the half the customer needs to compare against, and hides real published prices from anyone without JS. Options collapse to one column below `md`, where two columns would each be unreadably narrow.

**N/A.** A row marked `is_available = false` is kept — dropping it would make two options look like they offer the same room types — and shown muted as "N/A / not offered on this option". Its cell is set at the **same size as a real price** on purpose: a smaller cell made its row shorter than the equivalent row in the next column, and the drift accumulated until the two price lists no longer lined up.

---

## 5. Currency

`package_room_options` and `package_aziziya_room_options` carry a **column per currency**, so the page ships all three real values at once and the switcher only changes which is displayed. Nothing is ever converted.

Three behaviours worth recording:

1. **Prices are server-rendered.** The old template emitted an empty `<span>` and let JS fill it, so a visitor with JS blocked, a crawler, or anyone reading before the bundle executed saw a package with **no prices at all**. `x-hajj.price` renders the default currency into the markup; JS only swaps a value that is already correct.
2. **Single-currency amounts do not participate.** `package_transportation`, `package_upgrades` and `package_aziziya_services` each store ONE `price` and ONE `currency` — there is no per-currency column. The old template fed them through the switcher anyway, so choosing SAR blanked every transport fare and every upgrade on the page to "N/A" even though the price was perfectly well known. They are now printed in the currency they are sold in, via `HajjPackagePresenter::money()`.
3. **The page says what it does not have.** Every one of the catalogue's 63 room prices is in USD; none is in SAR or PKR. The switcher still renders all three (the admin can add a column at any time, and the page must not need a template change when they do), but a note states which are unpublished before the visitor clicks.

---

## 6. Sticky offsets — one source of truth

Four sticky elements each hard-coded their own offset: the Hajj sidebar (`100px`), the Umrah/Tourism sidebar (`100px`), the Hajj listing's filter panel (`6.5rem`) and the About aside (`6.5rem`). Measured (`.visual-audit/header-h.mjs`), the site header is:

| Width | Header height |
|---|---|
| 360–1024px | 63.97px |
| **1200px** | **125.75px** (the nav wraps to two lines at exactly this width) |
| 1366–1920px | 107.75px |

So **all four guesses left their element sitting under the header** on at least one desktop width.

There is no CSS-only way to read an element's height, so `initHeaderOffset()` measures the real header (via `ResizeObserver`, which also catches font-load reflow) and publishes `--ub-header-h`. `_variables.scss` carries a `126px` fallback that already clears the tallest case, so the layout is correct before JS runs and merely gets tighter afterwards. Everything derives from it:

```scss
:root {
    --ub-header-h: 126px;                                   // JS overwrites with the real value
    --ub-sticky-top: calc(var(--ub-header-h) + 1.25rem);
}
[id] { scroll-margin-top: var(--ub-sticky-top); }
```

**Do not reintroduce a literal pixel offset for a sticky element or an anchor target.**

### The related z-index defect

Bootstrap's `.sticky-top` sets `z-index: 1020` — the **same value `.site-header` uses**. On a z-index tie, paint order falls back to document order, and the sidebar comes after the header, so the enquiry card rendered **on top of the navigation**: visible form, unclickable nav. `.ub-sticky-aside` drops it to `z-index: 1`.

### Sticky height

The old sidebar stacked the enquiry form **and** the related-package list into one sticky element, measuring **830px** — taller than the usable viewport of any 768px-high laptop, so part of it was always cut off. Related packages moved to a full-width band at the foot of the page; the sticky element now measures **714px** and carries a `max-height` / `overflow-y: auto` safety net, the same treatment the Hajj listing's filter panel already uses.

---

## 7. What the page must never do

- Branch on a slug, code or id. There is no `if ($package->code === 'UB001')` anywhere and there must not be.
- Invent a fact. Every sentence on the page is either static copy about how the page works, or a field from the database.
- Show a heading over an empty section. Each component returns nothing when its data is absent — including the Gallery, whose heading is omitted entirely rather than sitting over an empty grid.
- Print a raw enum key. `transport_type`, `price_basis` and `pricing_type` are stored snake_case and were being rendered to visitors as "airport_transfer" and "vip_gmc". `pricing_type` stays lower-case **in the markup** (so the stored value is never misrepresented) and is capitalised in CSS.
- Depend on JS for content. Prices, itinerary days behind `<details>`, and every option's rooms are all in the served HTML.

---

## 8. Verification

| Harness | What it proves |
|---|---|
| `.visual-audit/matrix.php` | How the 12 packages genuinely differ |
| `.visual-audit/hajjcheck.php` | Renders all 12 through the real route; asserts HTTP 200, no raw enum keys, no placeholder text, no Gallery heading without media, correct render count per room row, no duplicated hotel name, every published price present **server-side**, every variant named |
| `.visual-audit/hajjshots.mjs` | Slice screenshots of the four structurally different shapes at 1600/1366/390px, with a horizontal-overflow check |
| `.visual-audit/sticky.mjs` | The sidebar's real position against the real header at seven scroll positions |
| `.visual-audit/header-h.mjs` | The header's real height at 14 widths |
| `tests/Feature/HajjPackagePublicTest.php` | Variants, sharing types, all three currencies, every Aziziya status, and the invariant that switching currency changes only prices |
| `tests/e2e/public.spec.js`, `responsive.spec.js` | Headings, the live currency switch, 13 breakpoints, touch targets, the sticky sidebar |

The four validation shapes to check when changing anything here:

- **UB001** — two variants, varies **Makkah**, Aziziya optional
- **UB008** — two variants, varies **Madinah**, adds `sharing_room` with null occupancy
- **UB011** — **no variants at all**
- **UB023** — Aziziya `included` and the only accommodation row

If a change works on all four, it works on the catalogue.
