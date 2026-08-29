# Project Requirements — Universal Brothers Website Redesign

Source priority for everything below: (1) Website Redesign Proposal.pdf, (2) HAJJ 2027 Packages overseas.pdf for package/business facts, (3) existing live sites for content/SEO continuity, (4) Avenix template for visual direction only, (5) general best practice. Where the source documents are silent, the requirement is marked **[CMS-configurable / client-supplied]** rather than invented.

## A. Business Requirements

- Redesign and redevelop the existing Universal Brothers website(s) (Hajj + Tourism, currently on separate subdomains) into one unified, modern, conversion-focused platform serving both domestic (Pakistani) and international customers.
- Preserve existing SEO value while improving visibility, speed, and security.
- Give non-technical staff full control of content (packages, pages, media, testimonials, news, FAQs, contact info) via a custom admin panel — no code changes needed for routine updates.
- Increase inquiries/bookings through clearer package presentation and stronger CTAs (Book Now, WhatsApp Us, Get Quote, Call Now, Request Callback).
- Present Universal Brothers' real accreditations and track record (IATA membership, Hajj Registration No. 4143, Government License No. 2014, FPCCI/Brands-of-the-Year/Best-Hajj-Operator awards, ELAF/TAAP/PHGOC affiliations) as trust signals.

## B. Functional Requirements

- Dynamic package management for Hajj, Umrah, and Tourism categories (create/edit/publish/unpublish, no redeploy needed).
- Homepage with hero/slider, featured packages per category, company intro, testimonials, news, promotions, quick inquiry form.
- Package listing → package detail → inquiry flow for each of Hajj, Umrah, Tourism.
- Contact/inquiry form(s) with validation, spam protection, and admin-side inquiry management (view/respond/track status).
- Media center: image gallery, video gallery, event gallery, news updates.
- Policy pages: Privacy Policy, Refund Policy, Cancellation Policy, Booking Policy, Visa Policy, Terms & Conditions (content sourced from the real Hajj brochure's terms where applicable — see §F — plus client-supplied content for general policies not covered by the Hajj-specific terms).
- Admin authentication with roles/permissions (at minimum: Super Admin, Content Editor).

## C. Non-Functional Requirements

- Fast page loads (lazy loading, WebP images, minified/compressed assets, browser caching, CDN-ready architecture).
- Secure by default: CSRF, XSS and SQL-injection protection, validated input, hashed passwords, secure file uploads, security headers, SSL-ready.
- Maintainable: Laravel MVC conventions, no unnecessary abstraction layers, Blade partials/components for reuse.
- Scalable: architecture must support the "Optional Future Enhancements" in §R without a rebuild.
- Accessible and SEO-friendly server-rendered HTML (no SPA/heavy client framework — see tech stack below).

**Technology stack (mandated by the proposal, not a choice to revisit):**
- Backend: Laravel 12, PHP 8.3+, MVC, RESTful conventions.
- Frontend: HTML5, CSS3, Bootstrap 5, Blade templates, vanilla JS, jQuery only where genuinely needed, AJAX only for genuinely dynamic bits. Explicitly **no** React/Vue/Angular/Next.js.
- Database: MySQL 8.x.

## D. Website Pages (initial information architecture)

Home · Hajj (landing → category/series → package detail) · Umrah (landing → package detail) · Tourism (landing → destination/package detail) · About Us · Why Choose Us · Services · Media Center (gallery/video/news) · Testimonials · FAQs · Contact Us · Policy pages (Privacy, Refund, Cancellation, Booking, Visa, Terms) · Inquiry/Quote form (shared component, contextualized per package).

## E. Homepage Requirements

Hero banner/dynamic slider → Featured Umrah Packages → Featured Hajj Packages → Featured Tourism Packages → Company Introduction → Why Choose Us → Services Overview → Popular Destinations/Promotions → Customer Testimonials → Travel News → Partner/accreditation logos → Quick Inquiry form → WhatsApp CTA, Call CTA, Book Now CTA. (See ARCHITECTURE/UI_DESIGN_SYSTEM docs for exact section ordering, adapted from Avenix — pending that audit's completion.)

## F. Hajj Requirements

This is the one product line with real, complete source data (HAJJ 2027 Packages overseas.pdf). Requirements here are data facts to preserve exactly, not aspirations:

- **9 real packages for Hajj 2027 / 1448 AH**, organized into 3 series:
  - *Platinum, Non-Aziziya, in front of Haram* (Makkah & Medinah series): UB001 (13 days), UB003 (10 days), UB004 (14 days), UB006 (10 days), UB008 (14 days), UB010 (10 days), UB011 (14 days), UB013 (10 days).
  - *Platinum, With Aziziya* (Makkah & Medinah series, shifting): UB015 (Flex 14), UB016 (Flex 10).
  - *Platinum Value, With Aziziya* (Medinah series, non-shifting): UB023 (Value 14), UB024 (Value 10).
- Each package carries: package code, duration (10/13/14 days), Hijri+Gregorian day-by-day itinerary table, named hotels per city/night (Dar Al Tawhid Intercontinental, Fairmont Clock Tower, Swissotel Makkah, Makkah Tower, Voco Makkah by IHG, Abraaj Tower/Swiss Maqam, Dar Al Taqwa, Taibah Front Medinah, Al Aqeeq/Dallah Taibah — real names, not invented), a Mina Zone 1 Category A tent stay, an Arafat AC marquee stay, and (where applicable) an Aziziya accommodation block.
- Per-package room-type pricing in USD (Quad/Triple/Double, and Package A/B hotel-tier variants where the brochure splits them) must be stored and displayed exactly as printed — these are real, dated (20 Aug 2026) prices, explicitly "subject to change," and must be editable by admin without a code change.
- Shared inclusions (meet & assist, group transfer, half-board hotel meals, Mina/Arafat full-board buffet, private luxury buses for Mashaer days, bullet train option Makkah↔Medinah, Ziyarat guidance, Hajj training program, religious guide book) and shared exclusions (airline ticket — approx. PKR 335,000 from Karachi / PKR 345,000 from North Pakistan, Qurbani ~US$200) must be presented per package, sourced from the brochure's "Platinum Packages Services" pages, not reworded into generic marketing copy that loses the specifics.
- Add-ons found in the source and must remain configurable: Kaba view supplement (US$2,200 non-Aziziya / US$1,050 Aziziya series), extra Medinah nights (Double $850, Triple/Quad $600 per night per person), Aziziya family room supplement, 5-/8-person private tent upgrades, VIP GMC transport (US$9,600 for 5 Mashaer days), airport transfer add-ons ($165/person Jeddah, $40/person Medinah).
- Payment plan (50% booking / 25% by 15 Sep 2026 / 25% by 15 Dec 2026) and the 28-point Terms & Conditions from the brochure must be reproduced faithfully on the Hajj policy/terms page, not paraphrased into something looser.
- Hajj Application and Hajj Booking Form field sets from the brochure define the minimum fields the Hajj inquiry/booking form must capture (name, CNIC/passport, DOB, blood group, next-of-kin, Mehram details, room type, package code, etc.) — full online payment/booking is out of scope for this phase (see §V); the form captures a structured **inquiry**, not a transaction.
- **2027 packages are seasonal.** The schema must support future Hajj years (2028, 2029...) as new package batches without structural change — do not hardcode "2027" into the data model, only into this year's seeded content.

## G. Umrah Requirements

No package-level source data exists for Umrah in either source document (the proposal only says to "merge the existing Umrah menu with Winter Packages" and "improve category structure"). Requirements:
- Umrah must be a first-class package category with the same CMS structure as Hajj (packages, itineraries, hotels, pricing, inclusions/exclusions) so the client can populate it.
- No specific Umrah packages, prices, or hotel names are to be invented. Ship the category empty/CMS-ready, **[client-supplied content pending]**.
- Preserve/reorganize whatever real Umrah content exists on the current live sites per the existing-website audit (in progress) rather than fabricating replacements.

## H. Tourism Requirements

Same situation as Umrah: proposal references a "Featured Tourism Packages" homepage section and a Tourism destination structure, but no tourism package/pricing/destination data exists in either source PDF.
- Tourism must be a first-class package category, same CMS structure as Hajj/Umrah.
- No destinations, prices, or itineraries are to be invented. **[client-supplied content pending]** — carry forward whatever is recoverable from the live tourism.universalbrothers.com audit.

## I. Package Requirements (cross-cutting: Hajj/Umrah/Tourism)

Every package, regardless of category, supports: category (Hajj/Umrah/Tourism), optional sub-classification (seasonal/featured/promotional), image(s), duration, one or more room-type prices, itinerary (day-by-day where applicable), named hotels, flight/airline notes, inclusions, exclusions, a booking/inquiry CTA, and SEO fields (slug, meta title/description). Admin can feature/unfeature and publish/unpublish any package independently.

## J. Booking/Inquiry Requirements

- Inquiry form(s) capture contact details + package context (category, package code if applicable) + message.
- Server-side validation on every field; CSRF-protected; spam-guarded (Google reCAPTCHA per proposal's integration list).
- Every submission is stored and visible in the admin Inquiries module with a status (new/contacted/closed) an admin can update.
- Hajj-specific inquiries additionally capture the brochure's Hajj Application fields (see §F) as optional structured fields, without turning the public form into the full multi-page paper application — that level of detail is captured once a human follows up.
- This phase delivers **inquiry capture, not online payment/checkout** — see §V (Out of Scope) and §R (Future Enhancements) for the online booking system.

## K. CMS/Admin Requirements

Modules: Dashboard, Pages, Packages (with category/series structure), Sliders, Media Gallery, News, Testimonials, FAQs, Contact Information, Inquiries, SEO Settings, Users & Roles, Website Settings. Admin UI built with the same Blade/Bootstrap 5 stack (no separate frontend framework), protected by Laravel auth + policy-based authorization.

## L. SEO Requirements

Meta titles/descriptions per page, friendly slugs, canonical URLs, Open Graph + Twitter Card tags, image ALT text, correct H1–H6 structure, internal linking, XML sitemap, robots.txt, schema markup (Organization, TravelAgency/Product where applicable), all editable/manageable from the admin SEO module. Preserve existing URL equity from the live sites — see EXISTING_WEBSITE_AUDIT.md for the redirect map once that audit lands.

## M. Security Requirements

CSRF protection, XSS output escaping, parameterized queries (Eloquent/query builder — no raw string-concatenated SQL), server-side validation via Form Requests, hashed passwords (bcrypt/argon2 via Laravel defaults), secure file upload handling (type/size validation, non-executable storage path), rate limiting on auth and public form endpoints, security headers, no secrets in version control, mass-assignment protection on every model.

## N. Performance Requirements

Eager-loaded queries (no N+1), lazy-loaded below-the-fold images, WebP where practical, minified CSS/JS for production, browser caching headers, GZIP/Brotli-ready, optimized web fonts, CDN-ready static asset paths, minimal JS payload (no framework runtime).

## O. Integration Requirements

Google Analytics 4, Google Search Console, Google Maps (contact page), WhatsApp click-to-chat, SMTP email, Google reCAPTCHA, social media links. **Payment gateway integration** is listed in the proposal's tech stack but no specific provider is named anywhere in either source document — this is a genuine open decision requiring the client to choose a provider (and is largely moot until the online-booking future-enhancement is greenlit, since this phase is inquiry-based, not transactional). Flagged, not blocking: the architecture will leave a clean integration point but will not wire a specific gateway without that decision.

## P. Responsive Requirements

Fully responsive: desktop, laptop, tablet, mobile. Cross-browser: Chrome, Edge, Firefox, Safari.

## Q. Testing Requirements

Functional, responsive, cross-browser, performance, security, SEO, form, and link-validation testing before any "done" claim — detailed in TESTING_STRATEGY.md, PLAYWRIGHT_TEST_PLAN.md, and REGRESSION_TEST_PLAN.md (to be produced alongside the corresponding implementation, not speculatively upfront).

## R. Future Enhancement Requirements (explicitly out of this phase's scope, architecture should not block them)

Online booking/payment system, customer portal, agent portal, online visa application, multi-language support, multi-currency support, native mobile apps, CRM integration, AI chatbot, live chat, push notifications.

## S. Assumptions

- The client (Universal Brothers) will supply real Umrah and Tourism package content during or after development; the CMS must make adding it trivial.
- "MySQL 8.x" and "Laravel 12 / PHP 8.3+" in the proposal are binding technical constraints, not suggestions.
- The proposal's 8-week timeline is a client-facing estimate; this delivery is being executed by an AI agent under continuous autonomous execution rather than a human team, so the actual working cadence will differ, but the deliverable scope is the same.
- Where the proposal says "if required" (e.g., mega menu, documentation), the feature is implemented only if the actual content/navigation depth ends up needing it.

## T. Dependencies

- Existing live websites (hajjumrah.universalbrothers.com, tourism.universalbrothers.com) for content/SEO continuity — audit in progress.
- Client-supplied Umrah/Tourism content (not yet available).
- A payment gateway decision (not yet available, not currently blocking since this phase is inquiry-based).
- Production hosting/domain/SSL credentials for actual deployment (not available in this environment — deployment steps will be documented and deployment-ready, but cannot be executed against a real production target without credentials).

## U. Risks

- Package pricing/hotel data is explicitly "subject to change" per the source brochure (dated 20 Aug 2026) — the CMS must make re-pricing trivial so the shipped site doesn't calcify stale 2027 prices.
- Two source-data gaps (Umrah, Tourism) create a real risk of an under-populated site at launch if the client doesn't supply content — mitigated by making those sections fully CMS-ready but not by inventing filler content.
- No production/staging server access in this environment — deployment phase deliverables will be scripts/docs/configuration, verified locally, not a live deployment.
- Building without poppler installed required a PyMuPDF-based workaround to read the image-only Hajj brochure — noted in PROJECT_DISCOVERY.md so the workaround is auditable.

## V. Out-of-Scope Items (this phase)

Online payment/checkout, customer/agent portals, visa application processing, multi-language/multi-currency UI, native mobile apps, CRM/chatbot/live-chat/push-notification integrations — all explicitly deferred to §R.
