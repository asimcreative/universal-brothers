# Hajj 2027 brochure gap analysis

**Status:** scope only — nothing here has been built. Raised 12 September 2026.

The site publishes **12** Hajj packages. The client's current brochures carry **26** (UB001–UB026). This document says exactly what is missing, what it would take to add, and what has to be settled before any of it can be built.

## Where the numbers come from

| Document | Cover date | Packages | Notes |
|---|---|---|---|
| `HAJJ 2027 Packages overseas.pdf` | 20 Aug 2026 | 12 | What the site was seeded from |
| `HAJJ 2027 Packages overseas (1).pdf` | 20 Aug 2026 | 12 | A **corrected re-export** — same cover date, different content |
| `HAJJ 2027 Packages US$.pdf` | 25 Aug 2026 | **26** | Supersedes the overseas deck |
| `HAJJ 2027 Packages Riyal.pdf` | 25 Aug 2026 | 26 | SAR |
| `HAJJ 2027 Packages PKR.pdf` | 7 Sep 2026 | 26 | Newest document of the five |

All five are image-only PowerPoint exports with no text layer, so every figure has to be read off rendered page images. There is no way to parse them.

> **The cover date cannot be trusted to tell two versions apart.** The corrected overseas re-export carries the same "20 AUG 2026" stamp as the original while differing on four pages. Any future brochure drop needs a page-by-page diff, not a date check.

## The 14 missing packages

Page numbers are the brochure's own, from the table of contents on page 1 of the US$ deck.

### Makkah-first mirrors of packages already on the site (6)

These pair with an existing package — same hotels and tier, arriving in Makkah first instead of Medinah.

| Code | Title | Days | Page | Pairs with |
|---|---|---|---|---|
| UB002 | Executive Platinum Intercon — Makkah First | 14 | 15 | UB001 |
| UB005 | Executive Platinum Swissotel — Makkah First | 14 | 18 | UB004 |
| UB007 | Executive Platinum Swissotel — Makkah First | 09 | 20 | UB006 |
| UB009 | Executive Platinum Makkah Tower — Makkah First | 14 | 22 | UB008 |
| UB012 | Executive Platinum Voco — Makkah First | 14 | 25 | UB011 |
| UB014 | Executive Platinum Flex 14 — Makkah First | 14 | 29 | UB015 |

### Further Flex and Value variants (4)

| Code | Title | Days | Page |
|---|---|---|---|
| UB017 | Executive Platinum Flex 09 — Makkah First (shifting) | 09 | 32 |
| UB022 | Executive Platinum Value — Makkah First | 13 | 38 |
| UB025 | Executive Platinum Value — Makkah First | 9–10 | 41 |
| UB026 | Executive Platinum Value — Makkah First (shifting) | 20 | 42 |

### A whole tier the site does not have at all (4)

**"Comfort" does not exist anywhere on the site today** — not as packages, not as a series, not in the filters or navigation. This is a new product line, not four more rows.

| Code | Title | Days | Page |
|---|---|---|---|
| UB018 | Executive Platinum Comfort — Medinah First (shifting) | 17 | 33 |
| UB019 | Executive Platinum Comfort — Makkah First (shifting) | 17 | 34 |
| UB020 | Executive Platinum Comfort — Medinah First (shifting) | 14 | 35 |
| UB021 | Executive Platinum Comfort — Makkah First (shifting) | 14 | 36 |

UB018 and UB019 are **17-day** packages — longer than anything currently on the site, whose maximum is 14.

## What each package needs

Per package, read off three decks (USD, PKR, SAR) and transcribed by hand:

1. Header facts — code, title, duration, Makkah/Medinah first, shifting/non-shifting, Aziziya or not.
2. Day-by-day itinerary — 9 to 20 rows, each with Gregorian date, Hijri date, city, and accommodation for variant A and B.
3. Accommodation rows — hotel, star rating, city, nights, per variant.
4. Room prices — 3 to 4 sharing types × up to 2 variants **× 3 currencies**.
5. Aziziya supplements where the package has them.
6. Package notes, upgrades, transport and Mashaer detail, following the existing per-package pattern.

Roughly **80–120 transcribed values per package**, and every one is a price or a factual claim about a real trip. The existing 12 took a full working day to transcribe and verify.

## Decisions needed before any of this is built

1. **Which price basis governs.** The PKR deck (7 Sep) and Riyal deck (25 Aug) still print the older basis for UB006, UB008 and UB010, where the US$ deck (25 Aug) has been corrected. Adding 14 more packages across three decks multiplies that problem. The client should re-issue PKR and Riyal from the same basis as USD, or state which one is authoritative.

2. **Does the Comfort tier belong on the public site at all,** or is it quoted on request? It needs its own series, filter entry and navigation, not just package rows.

3. **Makkah-first mirrors: separate pages or a toggle?** Six of the fourteen differ from an existing package only in arrival order. Twelve near-identical pages competing with each other is bad for search and bad for the visitor. A "Medinah first / Makkah first" switch on one page may serve both better — that is a product decision, not a technical one.

4. **UB013's identity.** The database says "Makkah First (Short Package)"; the current brochure says MEDINAH FIRST and its own itinerary starts "To Medinah". The page contradicts itself today. Correcting it changes a published URL, so it needs a redirect and a decision.

## Recommended order

1. Settle decisions 1 and 3 — both change what gets built.
2. Fix UB013 (small, and it is currently wrong on a live page).
3. Add the four Value and Flex variants — they reuse existing series and patterns.
4. Add the Makkah-first mirrors, in whichever shape decision 3 produces.
5. Add the Comfort tier last, as its own piece of work with its own series and navigation.

## What is already true

- All 12 published packages carry USD, PKR and SAR.
- USD for all 12 has been audited against the 25 Aug deck; 9 matched, 3 were corrected (issue #5).
- The two renamed hotels are live.
- Seeders are idempotent and never overwrite an admin edit, so new packages can be added without disturbing what is there.
