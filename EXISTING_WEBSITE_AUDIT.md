# Existing Website Audit

Produced from a live audit of the three real, currently-hosted Universal Brothers domains, performed via direct HTTP fetch (WebFetch was blocked by network policy for one domain, worked around with raw `curl` + HTML inspection so findings are still first-hand, not guessed). Every fact below was actually observed on a live response; nothing here is inferred.

## 1. `universalbrothers.com` (root domain)

Not a real site — a 5 KB static Bootstrap 3 "splash/chooser" page with two big buttons: **"Hajj & Umrah Ziarat → ENTER"** (links to the Hajj subdomain) and **"International & Domestic Tourism → ENTER"** (links to the Tourism subdomain, currently over plain `http://`, not `https://`). No nav, no content, no footer.

**Decision this resolves:** the approved proposal's own Homepage Requirements (§E) already list Featured Umrah + Featured Hajj + Featured Tourism packages together on *one* homepage — so the rebuild unifies what is currently a two-site-plus-splash-gate structure into a single site on one domain. This is not a new design choice being introduced here; it's what the proposal already specified. The splash-gate pattern is retired, not preserved.

## 2. `hajjumrah.universalbrothers.com` (Hajj/Umrah site)

**Platform:** WordPress + Elementor + the "tripgo" theme, Yoast SEO active (sitemap at `/sitemap_index.xml`).

**Navigation:** Home / About Us / Umrah (dropdown → Ramadan 1–15, Last 10 Days of Ramadan, Eid in Madina, Eid in Makkah, Shawal Package — every one of these links straight to a PDF) / Hajj (dropdown → Hajj Packages 2027 SAR/USD/Overseas PDFs, Hajj Booking Form) / Media (Affiliation, Awards, Merits & Credits, Hajj Gallery, Hajj Orientation) / Tourism (cross-link to the sister site) / Blog / Contact / a standing "Hajj Registration 2027" CTA.

**Confirmed real business facts to carry forward verbatim:**
- Office: **Maxims House, A-9, 1st Floor, Hassan Homes, FL-3/8, Opposite Nehr-e-Khayyam, Block-5, Clifton, Karachi, Pakistan** (matches the brochure address).
- Phones: **(92-21) 111-102-786**, **(92-21) 111-106-786**, **0322-2102786** (WhatsApp) — matches the brochure exactly.
- Email: **info@maximsgroup.org**.
- Socials: Facebook `Universalbrotherstravel`, Instagram, YouTube, X, TikTok.
- Company history: **20+ years in operation, 50,000+ pilgrims served**, licensed/affiliated with Ministry of Religious Affairs, IATA, TAAP, HOAP, DTS, SECP, KCCI.
- Named leadership: **Furqan Abdul Qadir and Junaid Abdul Qadir**, described as sons of Maxim's Group founder Abdul Qadir — consistent with the brochure's CEO/Director credits.
- Hajj-2027-specific policy facts already published: visa issue date quoted as **20 Sha'ban 1448 / 28 Jan 2027**, no under-12s, ladies may travel without Mehram (these are policy claims from the live site — flag to the client for reconfirmation before publishing verbatim, since Mehram rules are religiously/legally sensitive and worth a human sign-off rather than an unreviewed carry-over).
- 20+ real policy/legal pages already exist (privacy, T&Cs, refund/cancellation, Hajj-specific T&Cs, FAQ) — genuine content to adapt, not filler.

**Weakness — content is PDF-locked.** No hotel names, room rates, or itineraries exist as crawlable HTML anywhere on the site; everything pricing-related lives inside PDFs. Bad for SEO and mobile. **This is exactly the gap the new Package Management System (digitized hotels/pricing/itinerary as real DB rows, per the Hajj 2027 brochure data already captured in PROJECT_REQUIREMENTS.md §F) fixes.**

**Weakness — stale metadata.** Page `<title>` reads "Best Hajj Packages in Karachi | Leading Hajj Operators" and the meta description literally says **"Book your short, VIP, and Executive 2023 Hajj packages"** — three years stale against on-page content that already talks about Hajj 2027. Concrete argument for admin-editable SEO fields with visible "last updated" so this can't silently rot again.

## 3. `tourism.universalbrothers.com` (Tourism site)

**Platform:** Same WordPress/Elementor/"tripgo" theme, but running **WooCommerce** — tours are WooCommerce products with cart/checkout, not simple inquiry forms. No Yoast/RankMath — only the default `wp-sitemap.xml`; homepage has **no meta description tag at all**.

**Navigation:** Home / About Us / Domestic (Domestic Tours, Domestic Customized Tour) / International (International Tours, International Customized Tour) / Awards / Contact / Hajj & Umrah (cross-link). Footer adds Reviews, T&Cs, Refund & Cancellation, FAQ.

**Real testimonials recovered from the homepage carousel — genuine named reviewers, reusable content for the Testimonials module (subject to the client re-confirming permission to reuse, standard practice for testimonials):**
- *Haseeb Jawed* — "Universal Brothers are top quality tour operators, we have had a fabulous experience with them performing Umrah. We stayed in 5 Star Luxurious hotels..."
- *Mustafa Aslam* — "We went on Hajj in 2018 and I will say that I was impressed with their services..."
- *Rehan Ahmed* — "...experience of more than a decade, they are immensely experienced religious tour operator..."
- *Ali Naviwala* — "I performed Hajj for the first time and was told about the quality of their service by my friend..."

(Note: all four are about Hajj/Umrah, not tourism, despite living on the tourism homepage — a real content-assignment mismatch on the current site. The rebuild should tag testimonials by which service they actually describe rather than by which page they happened to be pasted onto.)

**Real package catalog recovered from listing pages (names + some real PKR prices — this is genuine client business data, not invented):**
- *Domestic*: Eid & Spring Hunza Tour, Eid & Spring Skardu Tour, Winter Malam Jabba, **"The Splendid Skardu Tour Direct from Dubai" (₨197,500)**, Bhurban, Gilgit, Kashmir, Malam Jabba, Skardu 4N/5D (Standard & Economy variants), Kaghan Valley 5N/6D (Standard & Economy variants), Skardu/Hunza/Gilgit 8N/9D (Standard & Economy variants).
- *International*: Europe, Maldives, Jordan, Bintan Island (Indonesia), Egypt, Thailand, Sri Lanka, Singapore, Turkey, Indonesia, Dubai, Malaysia/Singapore/Thailand combo, "Europe Holidays," "Road To Turkey," Dubai 5D/4N, South Africa, Maldives Honeymoon, Malaysia, Baku+Dubai, Hong Kong, "The Great China Package."

**Critical confirmed bug — every individual tour page is broken (HTTP 500), site-wide, right now:** `/product/gilgit-tour-package/`, `/product/the-splendid-skardu-tour-direct-from-dubai/`, `/product/kashmir-tour-package/`, `/product/skardu-tour-standard-package/`, `/product/bhurban-tour-package/`, `/product/winter-malam-jabba-tour-package/` all return WordPress's generic fatal-error page. Listing pages/homepage grid still show titles/images/prices, but **no itinerary, inclusion, or detail-page content is recoverable from the live site for any Tourism package** — that content simply isn't viewable anywhere right now, live-site or cached.

**Practical consequence for §H of PROJECT_REQUIREMENTS.md (updated there too):** Tourism is not a *pure* data void after all — real package **names, categories (Domestic/International), and some prices** are recoverable and will be seeded as real starting content. Full itineraries/inclusions/hotel names for those packages are genuinely unavailable (not merely unfetched) and remain **[client-supplied content pending]** exactly as originally flagged — the CMS must make adding that detail trivial once the client supplies it or the WooCommerce DB is exported.

**Same shared contact info as the Hajj site** (same address, same two landlines, same WhatsApp, same email) — confirms one shared business entity across both properties, consistent with a single unified rebuild.

## 4. URL / redirect-mapping note

The two live sites don't share a URL convention (e.g. Hajj site: `/refund-and-cancellation-policy/`, `/frequently-asked-question/` singular; Tourism site: `/cancellation_refund/` underscored, `/frequently-asked-questions/` plural). A full 301-redirect map should be drawn from `sitemap_index.xml` (Hajj/Yoast) and `wp-sitemap.xml` (Tourism/WooCommerce default) before go-live, once the new site's URL structure is finalized in ARCHITECTURE.md — tracked as a pending SEO task, not done here since it depends on the new routes existing first.

## 5. Avenix template — see UI_DESIGN_SYSTEM.md

Full section-by-section and visual-system breakdown of the Avenix "index-slider" church template lives in UI_DESIGN_SYSTEM.md, since it's a design-direction input rather than an existing-website fact. Headline finding worth flagging here: Avenix's actual color system (`#000000` / `#FFF4F1` cream / `#FE6035` orange-red accent, "Fira Sans Condensed" throughout) is being adapted structurally (its section flow, hero pattern, card grids, sticky nav, animated counters) but **not** copied color-for-color — Universal Brothers' own navy/gold "Crown Packages" brand identity (verified from the real brochure) is the actual brand palette, per the directive's Phase 28 priority order (business requirements/existing brand outrank a reference template's literal styling).
