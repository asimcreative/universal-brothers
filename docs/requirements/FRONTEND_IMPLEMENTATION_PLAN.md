# Frontend Implementation Plan — Universal Brothers Redesign

Phase 1 deliverable per the "Complete Frontend Redesign Master Directive." Documents CURRENT → REQUIRED → CHANGE for every area before any frontend code is written, per the directive's own "do not start blindly" instruction.

## 0. Source documents actually read for this plan

- `docs/source-documents/1a-website-flow-extracted.md` — the pre-extracted markdown of **"1A. Universal Website Flow.docx"**, the file the directive explicitly named. Read in full this pass.
- `docs/source-documents/website-flow-extracted.md` — the plain `Universal Website Flow.docx` extraction, also read in full this pass for cross-check.
- **Finding:** the two documents are not in conflict. The "1A." version is a superset — it adds the `[THEMES]` reference section (Avenix/GlobeTrek links), the `[DATABASE]` section (package fields, sharing prices, notes), and one extra IA level of detail on the Hajj/Umrah mega-menu (`UB Hajj Services` / `UB Umrah Services` sub-items). Every homepage/About/Hajj-Services/Umrah/Tourism/Awards/Affiliations/Contact/Footer copy block is **byte-for-byte identical** between the two. The "1A." file is treated as authoritative per the directive; the plain file is retained only as the earlier draft it evidently is.
- `docs/source-documents/HAJJ_BROCHURE_EXTRACTION.md` — the ground-truth Hajj 2027 brochure extraction already produced this session from a direct, page-by-page re-read of `HAJJ 2027 Packages overseas.pdf` (all 67 room-option prices cross-verified against the seeded data with zero discrepancies — see `HAJJ_PACKAGE_DATA_AUDIT.md`). This satisfies "read the Hajj brochure directly" for this phase; the raw PDF was not re-opened again since nothing about the frontend redesign requires new brochure facts beyond what's already extracted and seeded.

## 1. CRITICAL — real data discrepancies found (flagging, not resolving)

The Website Flow document's homepage copy and the site's own previously-verified, real data disagree on two headline numbers. Per the standing "never invent, never guess" rule, neither is silently overwritten — both are recorded here for the client to resolve, and the architecture keeps both numbers **CMS-driven** so whichever the boss confirms takes effect with no code change.

| Figure | Website Flow doc says | Already-seeded, previously-verified real figure | Source of the verified figure |
|---|---|---|---|
| Pilgrims served | "10,000+ Hajis Served" / "10,000+ Pilgrims Served" (appears 4×) | `pilgrims_served` = **"10,000+"** | **RESOLVED (2026-09-05).** This row previously recorded "50,000+", recovered from the old live site. The owner has since confirmed **10,000+** as the approved public figure (`PROJECT_REQUIREMENTS.md` § Approved public statistics), and `SiteSettingSeeder.php:35` now seeds that value. The source-document copy and the setting agree. |
| Awards count | "20+ Awards & Recognitions" (appears 3×, incl. a homepage stat tile) | `industry_awards_count` = **"20+"** (the approved public claim), while **7 real, named** award records are on file (FPCCI Achievement Award, Who's Who Pakistan Award, Quality Standard Award, Best Hajj Operator — WHUC London Olympia, Consumers Choice Award, Brand Icon of Pakistan, Brands of the Year) | **RESOLVED (2026-09-05).** The public count is deliberately **decoupled** from `Award::count()`: the company claims 20+ recognitions but only 7 have been documented with names so far. `SiteSettingSeeder.php:36` seeds "20+"; the 7 records stay as the only named ones, and none are invented to close the gap. |

**Decision for this pass:** both values stay exactly as currently seeded (`SiteSetting` rows, unchanged) and the new Award model is seeded with only the **7 real named awards** — not 20. Every new template pulls these numbers from `SiteSetting::get(...)` / `Award::count()`, never hardcodes "10,000+" or "20+" from the copy doc. This is noted again in `FRONTEND_QA.md` as an open client item.

## 2. Existing codebase inventory (CURRENT)

### Stack
Laravel 12 (routes/web.php, no API layer), Blade + Bootstrap 5.3 + jQuery 3.7 + vanilla JS (no frontend framework — confirmed, matches the directive's "preserve stack" instruction), Vite build (`resources/scss/app.scss` → compiled via `sass-embedded`), SQLite locally.

### Design system already in place (`resources/scss/_variables.scss`, `_components.scss`)
Brand tokens already exist and are **real**, not placeholder: navy `#101b45` + gold `#c9a227` ("Crown Packages" seal colors, per the brochure), Playfair Display headings + Poppins body, `border-radius` tokens, a `.reveal-on-scroll` IntersectionObserver fade-up utility, a `.stat-tile` counter component, `.package-card`, `.hero-slide`, `.trust-ticker`. **Gap:** `.reveal-on-scroll` and the counter animation in `app.js` do not check `prefers-reduced-motion` — required fix, not a rebuild.

### Routes (`routes/web.php`)
`/` (home), `/contact`, `/inquiries` (POST), `/news/{slug}`, `/{category}` where category∈{hajj,umrah,tourism} (listing), `/{category}/{package:slug}` (detail — branches to `show-hajj` view for Hajj), `/admin/*` (full CRUD suite), and a catch-all `/{slug}` → `Page` model for CMS pages (About Us currently lives here). **Gap:** no dedicated routes yet for a Hajj Services landing page, Awards page, Affiliations page, Media page, Testimonials page, or FAQs page as *pages* (FAQs/Testimonials currently only render as homepage sections; Awards/Affiliations don't exist as content types at all).

### Existing CMS content types (models) and what they cover
| Model | Covers | Gap vs. directive |
|---|---|---|
| `Slider` | Homepage hero slides (title/subtitle/CTA×2/image/page_context) | None — directive's hero slider maps directly onto this |
| `Testimonial` | name/quote/service_tag/rating/photo/source | **No video fields.** Directive requires video-first testimonials (thumbnail/video/name/package/description/modal) |
| `Faq` | category/question/answer/sort_order/is_active | Already supports categories — sufficient as-is |
| `Office` | address/phones/whatsapp/email/domestic flag | Sufficient |
| `MediaItem` | media_type/gallery_type/title/file_path/video_url | Already supports gallery+video+"press"-style items generically — check `gallery_type` enum values before reuse for the Media page |
| `NewsArticle` | title/slug/body/excerpt/cover_image/published_at | Already has its own public `/news/{slug}` page — reuse directly for the Media page's "News" tab |
| `Page` | title/slug/body(HTML)/template/meta fields | Powers About Us today as one freeform HTML blob (Leadership/Recognition/Why-Choose-Us as `<h2>` sections inside `body`) |
| `SiteSetting` | key/value/group, cached, admin-editable | Powers every "must be CMS-driven number" requirement already |
| **(none)** | **Awards** as a structured, orderable content type (award name/organization/year/image/description) | **Must build** — directive explicitly wants an Awards page + homepage Awards section driven by real records, not a text blob |
| **(none)** | **Affiliations** as a structured content type (org/logo/description/year/link) | **Must build** — same reasoning |

### Existing public pages
`home.blade.php` (generic hero/trust-ticker/featured-packages-per-category/4 static icon pillars/stat counters/testimonials/news/inquiry form — **not** the 14-section structure the directive specifies), `packages/category.blade.php` (listing, no filter UI beyond a series dropdown), `packages/show.blade.php` (Tourism/Umrah detail), `packages/show-hajj.blade.php` (Hajj detail — **already redesigned this session** to the rich data model; section order needs to be checked against the directive's exact required order), `page.blade.php` (generic CMS page, used for About Us), `news/show.blade.php`, `contact.blade.php`, `sitemap.blade.php`.

### Existing header/footer (`layouts/partials/header.blade.php`, `footer.blade.php`)
Flat nav: Home / About Us / (one link per `PackageCategory`, i.e. Hajj/Umrah/Tourism as single links, no mega-menu) / Contact. **No mega-menu at all** — this is the single biggest structural gap versus the directive's IA. Footer has 4 columns (brand+social, Quick Links, Services, Contact) — directive wants 5 columns (Hajj/Umrah/Tourism/Company/Support) with specific named links, several of which don't have a page yet (Hajj Process, Hajj Guidance, Accommodation & Transport, Next Flight Date, Register Now, Careers).

## 3. Required state (REQUIRED) and the resulting change list (CHANGE)

Organized in the directive's own 22-step order. Each row is a unit of work, not a guess at scope — "New" means no existing mechanism covers it, "Extend" means an existing model/view is modified, "Reuse" means no backend change is needed.

| # | Area | Action | Detail |
|---|---|---|---|
| 1 | Design system | Extend | Add `prefers-reduced-motion` guards to `.reveal-on-scroll`/counters; add premium section-transition/card/button utility classes; keep existing brand tokens (already correct, do not replace) |
| 2 | Header / mega-menu | Rebuild | Bootstrap dropdown-based mega-menu for Hajj & Umrah (each with the exact sub-item list from §0), Tourism (Domestic/International), plus the 6 more nav items (About Us, Awards & Recognition, Affiliations, Media, Testimonials, FAQs, Contact) — all real routes, no placeholder links |
| 3 | Footer | Rebuild | 5-column layout; only link to pages that exist after this pass (Hajj Process/Guidance/Accommodation&Transport/Next-Flight-Date/Register-Now become real routes in step 8; Careers has no content anywhere in any source doc — **left out, not stubbed with a fake link**, per "do not create broken links") |
| 4 | Homepage | Rebuild | 14 sections per the directive's literal copy blocks (§ home.blade.php below) |
| 5 | About Us | Extend | Restructure `page.blade.php`'s `template === 'about'` variant into Beginning/Experiences/Affiliations/Awards visual sections, pulling Awards/Affiliations from the new models instead of the flat HTML list currently inside `AboutPageSeeder`'s body |
| 6 | Hajj Services landing | New | `/hajj` becomes a real landing page (not just the package-listing view it is today) — Introduction/How-to-Apply/Hajj-Process-timeline/Guidance/Accommodation&Transport/Next-Flight-Date/link-to-Packages/FAQs/Register-Now |
| 7 | Hajj Process timeline | New | Visual component (Registration→Documentation→Orientation→Departure→Arrival→Makkah→Days of Hajj→Madinah→Return Home), reused on both the Hajj landing and Hajj Services page |
| 8 | Hajj Package Listing | Extend | Real filter bar (Days/Package A-B-C/Arrival/Aziziya/5-Star/Price/Sharing) querying `package_variants`, `package_accommodations.location`, `package_aziziya.status`, `package_accommodations.star_rating`, `package_room_options.sharing_type` — **no hardcoded category list** |
| 9 | Hajj Package Detail | Verify/reorder | Confirm `show-hajj.blade.php`'s section order matches the directive's exact spec; already has Currency Selector, Room/Sharing pricing, Accommodation, Aziziya, Mina/Arafat, Itinerary, Transportation, Meals/Inclusions/Exclusions, Upgrades, Notes, Media, Inquiry — reorder/restyle only, data layer untouched (directive's #1 rule: do not touch this schema) |
| 10 | Umrah | Extend | Same treatment as Hajj Services at a smaller scope (Umrah has no Package A/B/Aziziya concept — existing generic `packages/show.blade.php` mechanism is correct and stays) |
| 11 | Tourism | Extend | Domestic/International split via `PackageSeries` (already the mechanism — verify series exist/seed if not) |
| 12 | Awards | New | `awards` table + model + admin CRUD + public `/awards` page |
| 13 | Affiliations | New | `affiliations` table + model + admin CRUD + public `/affiliations` page |
| 14 | Media | Extend | `/media` page tabbing News (existing `NewsArticle`) / Videos+Gallery+Press (existing `MediaItem`, verify `gallery_type` values) |
| 15 | Testimonials | Extend | Add `video_url`/`thumbnail` nullable columns to `testimonials`; public `/testimonials` page; homepage section prioritizes video cards, falls back to text cards when no video exists (none do yet — real, not invented) |
| 16 | FAQs | Extend | Public `/faqs` page with accordion, grouped by existing `category` column (already populated: hajj/general — extend as needed) |
| 17 | Contact | Reuse | Existing `ContactController`/`ContactRequest` already validates/throttles/CSRFs/stores — no backend change, template restyle only |
| 18 | SEO | Extend | Add breadcrumbs + `schema.org` per new page type; existing per-page title/meta/canonical/OG mechanism already present in `layouts/app.blade.php`, extend to new pages |
| 19 | Responsive | Test | 1920/1440/1280/1024/768/390/375 via Playwright viewport tests |
| 20 | Accessibility | Test | Semantic landmarks, mega-menu keyboard nav, accordion/modal a11y, contrast |
| 21 | Performance | Measure | Query-count discipline already established this session (`PERFORMANCE_AUDIT.md`) — apply the same `setRelation`/eager-load pattern to every new controller |
| 22 | Full regression | Run | PHPUnit + Playwright + the 16-item Hajj package regression list, after each major step, not just at the end |

## 4. What will NOT be invented

Per the directive's explicit rule, the following remain CMS-editable placeholders rather than fabricated content until the client supplies them: company "Beginning" narrative beyond what's already verified in `AboutPageSeeder`, any award/affiliation beyond the 7/10 already-verified real ones, a "Careers" page (no source doc mentions any careers content), video testimonial files (architecture ships, content stays empty until supplied), Register-Now behavior beyond linking out to the existing external HUMS URL (`https://hums.akhg.com.pk/HajiReg/HajiLead`) already named in the Website Flow doc itself.

## 5. Implementation order for this session

Design system fixes → header/footer → homepage → About Us → Awards/Affiliations (new backends, needed by both About Us and their own pages) → Media/Testimonials/FAQ pages → Hajj Services landing + process timeline → Hajj package listing filters → Hajj package detail reorder → Umrah/Tourism polish → SEO pass → responsive/accessibility/performance pass → full regression → code review → fix → re-test → update audit docs.
