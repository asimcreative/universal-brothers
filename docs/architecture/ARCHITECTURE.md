# Architecture

Written after implementation, describing what was actually built (not a speculative pre-build plan) — see PROJECT_DISCOVERY.md for why that ordering was chosen.

## Stack

Laravel 12 / PHP 8.3, MySQL 8.x target (SQLite for local dev — see PROJECT_DISCOVERY.md for why), Blade + Bootstrap 5 + vanilla JS/jQuery via Vite, no frontend framework. Matches the proposal exactly; no stack deviation.

## Application structure

Standard Laravel MVC, no repository/service-layer abstraction — the domain is CRUD-shaped (packages, content, inquiries) and Eloquent models carry the relationships directly. Adding a repository layer here would be indirection without a corresponding benefit, per the "don't over-engineer" mandate.

- **Models** (`app/Models`): `PackageCategory` → `PackageSeries` → `Package` → (`PackagePriceTier` → `PackageRoomPrice`), `PackageItineraryDay`, `PackageFeature` (inclusion/exclusion), `PackageAddon`; plus `Hotel`, `Slider`, `MediaItem`, `NewsArticle`, `Testimonial`, `Faq`, `Office`, `SiteSetting`, `Page`, `Inquiry`, `User` (admin-only, `role` enum).
- **Controllers**: split into `App\Http\Controllers` (public site) and `App\Http\Controllers\Admin` (CMS), mirroring the two audiences with zero shared route namespace — `/admin/*` is entirely gated by `auth` + a custom `admin` middleware (`EnsureUserIsAdmin`, checks `is_active`).
- **Form Requests**: validation lives in `App\Http\Requests` (public: `ContactRequest`, `InquiryRequest`) and `App\Http\Requests\Admin` (`PackageRequest`) — controllers never call `$request->all()` unvalidated.
- **View composers**: `AppServiceProvider` registers one composer on `layouts.partials.*` supplying `$navCategories`/`$primaryOffice` site-wide, avoiding repeated queries per view.

## The Package data model — why it looks the way it does

The Hajj 2027 brochure itself organizes packages as: category → series (e.g. "Platinum, Non-Aziziya") → package (e.g. UB001) → per-day itinerary row with up to two hotel-tier columns ("Package A"/"Package B") → per-tier room-type pricing (Sharing/Quad/Triple/Double). The schema mirrors that structure directly rather than flattening it:

- `package_categories` (Hajj/Umrah/Tourism) and `package_series` (nullable grouping under a category) are separate lookup tables because the brochure's own series distinctions (shifting vs non-shifting, with/without Aziziya) are real filtering/display groupings, not incidental labels — and the same shape reused cleanly for Tourism's Domestic/International split.
- `package_itinerary_days` has both `accommodation_a` and `accommodation_b` text columns on the same row (not a separate pivot/tier-per-day table) because that's exactly how the source table is laid out, and most days only ever populate `accommodation_a` — a normalized day↔tier↔hotel junction table would have added a join for no real benefit at this scale (max 14 rows per package).
- `package_price_tiers` → `package_room_prices` is a real one-to-many split (not columns on one row) because a package can have 1 or 2 tiers and each tier independently may or may not price out Sharing/Quad/Triple/Double — some cells are legitimately `NULL` ("N/A" in the brochure), which the schema preserves as `NULL`, not `0`.
- `package_features` uses one table with a `type` enum (`inclusion`/`exclusion`) rather than two tables — same shape, same lifecycle, no reason to split.
- `package_addons` is deliberately *not* nested under a single package in most cases — it has both a nullable `package_id` and a nullable `package_category_id` because the brochure's own add-ons (Kaba view supplement, extra Medinah nights, VIP GMC transport) apply per-series or per-category, not per-package, and forcing them onto every individual package row would have meant either duplicating the same add-on 8 times or inventing a series-level table just for this — the nullable dual-FK is the smaller, more honest model of the actual business rule.

SEO fields (`meta_title`, `meta_description`) live directly on `packages`, `news_articles`, and `pages` rather than behind a generic polymorphic `seo_meta` table — only three models ever need them, known upfront, so the generic-polymorphic indirection wasn't earned.

## Admin CMS

No third-party admin panel package (Filament/Nova/Backpack) — proposal explicitly calls for a *custom* Laravel admin panel, and the actual surface area (10 CRUD modules, one with a genuinely complex nested form) is small enough that hand-rolled Blade + Bootstrap forms are less overhead than learning/working around a framework's conventions. The one complex form (package edit — itinerary days + price tiers as repeatable rows) uses plain `<template>` cloning in vanilla JS, submitting indexed array fields (`itinerary[0][city]`, `tiers[1][prices][double]`) that the controller loops over positionally — no client-side framework, matching the "no heavy JS" mandate.

Authorization is a single `role` enum column (`super_admin`/`content_editor`) plus one middleware check, not a permissions package — two roles with no granular per-module permission matrix in the requirements didn't justify pulling in `spatie/laravel-permission`.

## Request flow for the one non-trivial write path (package save)

`PackageController@store`/`@update` → validate via `PackageRequest` → persist scalar package fields → **fully replace** (delete-then-recreate) the itinerary days, price tiers + room prices, inclusions, and exclusions from the submitted arrays/textareas → recompute `starting_price` as the minimum non-null room price across all tiers. Delete-then-recreate (rather than diffing/upserting individual rows) was chosen because these child collections are small (≤14 itinerary rows, ≤2 tiers) and always submitted in full from the form — diffing would add complexity with no correctness benefit at this scale.

## What's intentionally not here yet

Payment gateway integration, multi-language/currency, customer/agent portals, native apps, CRM/chatbot — see PROJECT_REQUIREMENTS.md §R/§V. The schema doesn't block any of them (e.g., `Package.currency` already supports multi-currency display; adding a `bookings` table with a FK to `packages` later is additive, not a migration of existing data).
