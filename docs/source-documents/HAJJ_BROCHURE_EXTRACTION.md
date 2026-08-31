# Hajj 2027 Brochure — Full Structured Extraction

Produced by directly viewing every page of `HAJJ 2027 Packages overseas.pdf` as rendered images (poppler/pdftoppm unavailable in this environment; rendered via PyMuPDF at 200dpi instead — see FINAL_GAP_ANALYSIS.md's MySQL section for the same class of environment-workaround precedent). This supersedes the previous, "too generic" package structure. Every fact below was read directly off a brochure page; nothing is inferred or guessed. Brochure-internal inconsistencies are recorded as found, not silently corrected.

**Cover page confirms: "US$ PACKAGES (20 AUG 2026)" — this brochure is USD-only.** No PKR or SAR package price appears anywhere in the 37 pages. The only PKR figures anywhere are an *airfare* estimate (§Shared Services below) — explicitly excluded from the package price — not a package price in any currency. The schema must support PKR/SAR as first-class currencies (per directive), but no real PKR/SAR *values* exist to seed; those fields stay null/CMS-editable until the client supplies them.

## Table of contents (brochure page 3) — ground truth for which 12 packages exist

Only 12 real Hajj packages exist in this document. Codes UB002/005/007/009/012/014 do not appear anywhere — presumably reserved for a companion PKR/SAR brochure not present in this project's source documents. Do not invent packages for the missing codes.

## Master package list (verified against each package's own page, not just the TOC — the TOC contains at least one proven error, see UB013)

| Code | Title on its own page | Duration (header) | Itinerary rows | Medinah/Makkah First (header) | Shifting | Aziziya | Variants (A/B)? |
|---|---|---|---|---|---|---|---|
| UB001 | Executive Platinum Intercon/Fairmont – Medinah First | "13 Days Package" | 13 | Medinah First | Non Shifting | Non Aziziya | Yes (A=Intercon, B=Fairmont) |
| UB003 | Executive Platinum Intercon/Fairmont – Medinah First | "10 Days Package" | 10 | Medinah First | Non Shifting | Non Aziziya | Yes (A=Intercon, B=Fairmont) |
| UB004 | Executive Platinum Swissotel – Medinah First | "14 Days Package" | 13 (header says 14, itinerary table has 13 rows — brochure inconsistency, recorded not corrected) | Medinah First | Non Shifting | Non Aziziya | Yes (Medinah: A=Dar Al Taqwa/Hilton 5★, B=Taibah Front 3★; Makkah shared: Swissotel Makkah 5★ for both) |
| UB006 | Executive Platinum Swissotel – Medinah First | "Short Package — 10 Days Package" | 10 | Medinah First | Non Shifting | Non Aziziya | Yes (same pattern as UB004) |
| UB008 | Executive Platinum Makkah Tower – Medinah First | "14 Days Package" | 13 (same header/itinerary mismatch as UB004) | Medinah First | Non Shifting | Non Aziziya | Yes (Medinah: A=Dar Al Taqwa/Hilton 5★, B=Taibah Front 3★; Makkah shared: Makkah Tower 4★ for both) |
| UB010 | Executive Platinum Makkah Tower – Medinah First | "10 Days Package" | 10 | Medinah First | Non Shifting | Non Aziziya | Yes (same pattern as UB008) |
| UB011 | Executive Platinum Voco By IHG – Medinah First | "14 Days Package" | 13 (same mismatch) | Medinah First | Non Shifting | Non Aziziya | **No** — single accommodation/price column throughout |
| UB013 | Executive Platinum Voco By IHG – **Makkah First** | "Short Package — 10 Days Package" | 10 | **Makkah First per header, but itinerary starts "To Medinah" identically to UB011 — real brochure contradiction, flagged, not resolved by us** | Non Shifting | Non Aziziya | No |
| UB015 | Executive Platinum Flex 14 – Medinah First | "Flex 14 Days" | 14 | Medinah First | **Shifting** | **With Aziziya** | Yes (Medinah split A/B; Makkah shared Abraj Tower/Swiss Maqam 5★ for both; days 12-13 shift to shared "AZIZIYA Accommodation - A Class") |
| UB016 | Executive Platinum Flex 10 – Medinah First | "Flex 10 Days" | 10 | Medinah First | Shifting | With Aziziya | Yes (same shifting pattern as UB015) |
| UB023 | Executive Platinum Value 14 – Medinah First | "Value 14 Days" | 14 | Medinah First | Non Shifting | With Aziziya | Yes (Medinah split A/B; **entire Makkah stay is Aziziya**, days 4-7 and 12-13) |
| UB024 | Executive Platinum Value 10 – Medinah First | "Value 10 Days" | 10 | Medinah First | Non Shifting | With Aziziya | **No** — single column; Medinah hotel here is "Al Aqeeq / Dallah Taibah / Similar ★★★" (a third Medinah hotel name, distinct from Dar Al Taqwa and Taibah Front) |

**TOC error confirmed:** TOC lists UB013 as "VOCO MEDINAH FIRST (14 DAYS)" at brochure page 21 — the actual page 21 says "VOCO BY IHG — MAKKAH FIRST", "Short Package — 10 Days". Trust the package's own page, not the TOC, per instruction; both are recorded here so the discrepancy itself isn't lost.

**UB013 vs UB011 price anomaly:** identical QUAD/TRIPLE/DOUBLE prices ($10450/$11500/$13425) despite different stated duration (14 vs 10 days) and Medinah/Makkah-first header. Recorded as found; not corrected or explained.

## Itinerary detail (per package, day-by-day)

All 12 packages share the same real-world date range (7/10 May 2027 → 19/20 May 2027, 01–14 Zil Hajj) and the same Mina/Arafat block structure. Full day-by-day rows (date AD, date Hijri, city, Package A accommodation, Package B accommodation) were transcribed directly from each package's own itinerary table during this session and will be seeded verbatim via the rebuilt `HajjPackageSeeder` — not repeated in full here to avoid duplicating ~150 rows of tabular data in prose; the seeder itself is the source of truth for exact values, cross-checked against this document's package table above for duration/day-count.

Recurring accommodation labels used across packages (verbatim, including brochure's own "Similar" hedge and star ratings):
- Medinah: "Dar Al Taqwa ★★★★★", "Dar Al Taqwa/Hilton ★★★★★" (Package A slot), "Taibah Front / Similar ★★★" (Package B slot), "Al Aqeeq / Dallah Taibah / Similar ★★★" (UB024 only)
- Makkah: "Dar Al Tawhid Intercontinental ★★★★★", "Fairmont Clock Tower ★★★★★", "Swissotel Makkah ★★★★★", "Makkah Tower ★★★★", "Voco Makkah By IHG ★★★★", "Abraj Tower / Swiss Maqam ★★★★★", "AZIZIYA Accommodation - A CLASS"
- Mina: "Zone 1 near to Jamarat A Category (Exclusive Services)"
- Arafat: "Arafat Air Conditioned Marquee (Exclusive Services)"
- Terminal rows: "DEPARTURE TO AIRPORT"

Glossary note (brochure pg 22/26/29 + T&C #25): **"Abraaj Tower" / "Makkah (similar)" means**: Swiss Maqam, Hajar Tower, Swissotel, Safwa Orchid, Al Marwa etc., and the Jabal e Omar project means Hayat Regency, Address Hotel, Jumeirah Hotel, Hilton Convention, Double Tree, Marriott Hotel etc. — i.e. "could be any five-star hotel by Saudi standards" in that cluster. Store as a package_note, do not resolve to one specific hotel.

## Main package room/sharing pricing (USD, exactly as printed)

| Code | Variant | Quad | Triple | Double | Sharing Room |
|---|---|---|---|---|---|
| UB001 | A | NA | $22450 | $26850 | — |
| UB001 | B | $16300 | $18100 | $20850 | — |
| UB003 | A | NA | $21650 | $26050 | — |
| UB003 | B | $15890 | $17250 | $20000 | — |
| UB004 | A | $14950 | $16300 | $19300 | — |
| UB004 | B | $14200 | $15590 | $18350 | — |
| UB006 | A | $15350 | $15750 | $18500 | — |
| UB006 | B | $18850 | $15200 | $17950 | — |
| UB008 | A | $13850 | $15200 | $18220 | NA |
| UB008 | B | $13000 | $14650 | $17400 | $13000 |
| UB010 | A | $13550 | $14650 | $17400 | NA |
| UB010 | B | $12750 | $14390 | $16850 | $12750 |
| UB011 | (none) | $10450 | $11500 | $13425 | — |
| UB013 | (none) | $10450 | $11500 | $13425 | — |
| UB015 | A | $11650 | $12750 | $14520 | — |
| UB015 | B | $11100 | $12200 | $13560 | — |
| UB016 | A | $11100 | $11920 | $12880 | — |
| UB016 | B | $10685 | $11390 | $12330 | — |
| UB023 | A | $10410 | $10685 | $11230 | — |
| UB023 | B | $9725 | $10000 | $10275 | — |
| UB024 | (none) | $9175 | $9315 | $9450 | — |

"Sharing Room" (UB008/UB010 only): T&C #28 clarifies "Makkah tower rooms have stairs & sharing rooms mean 4 to 5 person in a room" — occupancy recorded as a range (4–5), not a single guessed number.

"NA" cells are real brochure content (that variant/room-type combination is not offered), not missing data — seed as `is_available = false`, not a null row.

## Aziziya accommodation (brochure pg 23/23A — canonical description, applies to every Aziziya-inclusive package)

- Tagline: "Walk Less, Pray More – Ultimate Convenience Near Jamarat & Mina"
- Location: directly opposite the Jamarat escalator, near Mina camps
- Average 4 persons per room (family rooms available for supplement)
- Fully air-conditioned rooms, mini fridge, attached bathroom
- Separate prayer areas for men/women; daily religious talks by Moulana
- 2 lifts with wheelchair access
- Large dining area for buffet meals (separate men/women sections)
- Proper beds, high-quality mattresses/linens
- Three buffet meals daily (breakfast, lunch, dinner) + 24hr hot/cold drinks
- Shuttle to Haram 4x/day (4th–6th Zil Hajj) per pg23; "two times a day for drop to Haram till 07 Zil hajj" per pg26/29 services page — both recorded verbatim, not reconciled (different sub-pages, possibly describing different aspects)
- 30–50 minute walk to Mina camp and back
- Free WiFi in lobby, valuables lockers, complimentary washing machine, daily bathroom cleaning, LCD screen with Haram live telecast, water coolers/tea/coffee/iron per floor
- Explicit disclaimer (repeated in T&C #24 too): "Aziziya Accommodation services are not comparable to hotel services."

## Aziziya-specific pricing found

- **Optional Aziziya upgrade on non-Aziziya packages** (UB001/003/004/006/008/010/011/013, i.e. every "Non Aziziya" package): "Family Rooms available in our Aziziya Building, duration of 05 days of Hajj, with Supplement US$ 5500" (per-package note, identical value across all 8 non-Aziziya packages — this is a real, brochure-stated *optional* upgrade even on packages whose base Aziziya status is "not included").
- **Family Room supplement on Aziziya-inclusive packages** (UB023/UB024, "Medinah Series"): Double US$1100/person, Triple US$550/person. (UB015/UB016, "Makkah & Medinah series", do not carry this breakdown — they only say "Aziziya family room included," no supplement given, meaning it's bundled at no extra charge for those two.)
- **Kaba view supplement**: US$2200/person on non-Aziziya packages (pg22 "Important Notes" #3; also individually repeated on UB003/UB004/UB006/UB010's own notes, absent from UB001/UB008/UB011/UB013's own page but present on the canonical services page — treat as applicable to all 8 non-Aziziya packages). **US$1050/person on Aziziya packages** (pg26/29 "Important Notes" #4) — a genuinely different value; do not conflate the two.
- **Additional night in Medinah** (non-Aziziya packages only, pg22 note #2): Double US$850/night/person, Triple US$600/night/person, Quad US$600/night/person.

## Mina (all 12 packages, identical — "Zone 1, Maktab A-Category" box)

- Best location in Mina, avg 16 people/tent, sofa-cum-bed 50–55cm each (tent may be combined, per Saudi Talimaat)
- Full board buffet meal in Mina & Arafat (for Group Maktab A category Hujjaj)
- Private bathroom in Mina & Arafat for UB Group
- Bullet train Makkah↔Medinah OR private luxury buses (2025 model) for Mashaer days with bathroom
- Services page detail: "(Mashaer days Services) Pillow, Bed sheet, blanket, Air conditioned tent, buffet meal and Hot & Coldrink. Avg 16 People to a tent (Tent may be combined) (Services by Saudi Company)"
- Upgrade options mentioned (no price given — "on request"): "Upgrade your Hajj from Platinum to Deluxe Family Tent — Private Deluxe Family Tent with Attached Bathroom Available"; "5-Person Sharing Tent with Attached Bathroom"; "8-Person Sharing Tent with Attached Bathroom"

## Arafat (all 12 packages, identical)

- "Tent in Arafat with meals and Hot & Coldrink. Floor Mat & snack box in Muzdalfa. (Services by Saudi Company). Mic and Speaker installed for religious speeches/guidance."
- Accommodation label in itinerary: "Arafat Air Conditioned Marquee (Exclusive Services)"

## Transportation (company-wide, applies to all 12 packages)

- Group arrival transfer by bus, airport→hotel, provided by NAQABA/Saudi Moallim/Moullem (included)
- Private Special Luxury Busses with bathroom: Mina–Arafat–Muzdalfa–Mina (included)
- Transfer Makkah↔Medinah by Bullet Train or Bus (included)
- Family Car/Taxi: Jeddah Airport→Makkah Hotel, US$165/person (optional, priced)
- Family Car/Taxi: Medinah Airport→Medinah Hotel, US$40/person (optional, priced)
- VIP GMC transport (Land Cruiser, max 6 persons), 5 days Hajj (08–13 Zil Hajj) Mina-Arafat-Muzdalfa and back, Urdu/English chauffeur with mobile phone: US$9600 per GMC (optional, priced)

## Meals

- Makkah hotel: half board (breakfast + dinner), "by Saudi Stars Standard"
- Medinah hotel: half board (breakfast + dinner), 1 night may be reduced per final itinerary
- Mina & Arafat: full board buffet (08–12 Zil Hajj)
- Aziziya: full board buffet (breakfast, lunch, dinner) + 24hr hot/cold drinks, except Hajj days

## Inclusions (shared, non-Aziziya packages — brochure pg 22)

Meet & assist at Jeddah/Medinah Hajj Terminal (subject to approval handling); group arrival transfer by bus; Makkah accommodation with breakfast+dinner (Saudi Stars standard) 4–8 & 12–14 Zil Hajj except Hajj days; Medinah accommodation with breakfast+dinner (1 night may be reduced); private luxury buses Mina-Arafat-Muzdalfa-Mina; Makkah↔Medinah transfer by train; best-location Maktab A in Mina with sofa-cum-bed + private toilet; Mashaer days services (pillow/bedsheet/blanket/AC tent/buffet/hot-cold drinks); Arafat tent with meals; Ziyarat in Medinah with guidance; Hajj training program in Pakistan/Saudia; religious guide book; assistance with Qurbani (approx US$200, not included in package price) and Tawaf-e-Ziyara.

## Inclusions (shared, Aziziya packages — brochure pg 26/29)

Same as above, plus: "Average 04 person sharing Accommodation in Aziziya with air condition A class building with proper beds (pillow, bed sheet, blanket)"; "Fullboard meal (breakfast, lunch, dinner) with hot & coldrink to serve in Aziziya building except Hajj Days"; Makkah/Medinah hotel nights still apply where the itinerary includes a real hotel stay (UB015/016 only — UB023/024 stay in Aziziya the whole Makkah portion).

## Exclusions

Airline ticket **not included** — approx PKR 335,000 from Karachi, PKR 345,000 from North Pakistan (different fares for int'l-origin Hajjis); airlines: Saudia, Emirates, Oman, PIA, Qatar, SereneAir, Flynas, Turkish, Fly Dubai (PSF inclusive); business-class upgrade available subject to availability/supplement (no price given). **Qurbani not included** (~US$200 approx assistance charge). This PKR figure is airfare, categorically not a package-price value — never seed it into `package_room_options`.

## Package-wide notes (from T&C, pg32 — apply to every Hajj package, not repeated per-package in the seeder beyond a shared note set)

"Makkah and Madinah hotels only have double rooms; an extra bed is provided for triple/quad." "Makkah (similar) means Abraj Tower/Jabal e Omar cluster hotels, any 5-star by Saudi standards." "Makkah tower rooms have stairs & sharing rooms mean 4 to 5 person in a room" (UB008/UB010-specific). "No of days of stay in Makkah can be reduced but price remains the same." "Rates & hotels subject to change (currency difference); prices subject to change even after booking / Saudi Talimaat changes." "Book Early, Prices and Packages Subject to Change" (printed on every single package page).

## Payment plan / booking-form / general T&C (pg 30–32)

Out of scope for the package data model itself (applies company-wide to the booking process, not to any individual package's structured data) — not modeled as new package tables, consistent with the instruction not to create unnecessary tables. Recorded here for completeness only: 50% at booking / 25% by 15 Sep 2026 / 25% by 15 Dec 2026; US$350/person cancellation or substitution service charge; required documents (passport copy, 2 photos, ID card, next-of-kin ID+contact, blood group, children B-form).
