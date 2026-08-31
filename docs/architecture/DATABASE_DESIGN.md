# Database Design

Reference for the schema actually implemented (20 migrations, verified via `migrate:fresh --seed` and the PHPUnit suite). See ARCHITECTURE.md for the *why* behind the less obvious modeling choices.

| Table | Key columns | Relationships | Notes |
|---|---|---|---|
| `package_categories` | name, slug (unique), icon, sort_order, is_active | hasMany series, packages | Fixed taxonomy: Hajj, Umrah, Tourism |
| `package_series` | package_category_id, name, slug, sort_order, is_active | belongsTo category, hasMany packages | slug unique per category (composite unique) |
| `packages` | package_category_id, package_series_id (nullable), code (nullable unique), name, slug (unique), duration_days, duration_label, is_shifting, has_aziziya, season_year, season_label, currency (USD/PKR), starting_price, cover_image, gallery (json), is_featured/is_seasonal/is_promotional, status (draft/published), published_at, meta_title, meta_description | belongsTo category/series; hasMany priceTiers, itineraryDays, features (inclusions/exclusions scoped), addons, inquiries | Soft-deletes enabled |
| `package_price_tiers` | package_id, label (nullable), hotel_note, sort_order | belongsTo package; hasMany roomPrices | Nullable label = single-tier package (no A/B split) |
| `package_room_prices` | package_price_tier_id, room_type (enum: sharing/quad/triple/double), price (nullable decimal), currency | belongsTo priceTier | `price` NULL = brochure's "N/A", not zero |
| `package_itinerary_days` | package_id, day_number, date_gregorian (nullable date), date_hijri_label, city, accommodation_a, accommodation_b (nullable), notes | belongsTo package | Two accommodation columns mirror the brochure's own A/B tier columns |
| `package_features` | package_id, type (inclusion/exclusion), description, sort_order | belongsTo package | One table, discriminated by `type` |
| `package_addons` | package_id (nullable), package_category_id (nullable), name, price (nullable), currency, unit, notes, sort_order, is_active | belongsTo package (optional), category (optional) | Dual-nullable FK models category-wide vs package-specific add-ons |
| `hotels` | name, slug (unique), city, star_rating, description, cover_image, sort_order, is_active | none (standalone showcase entity) | Real hotel names only — see HotelSeeder |
| `sliders` | title, subtitle, image, cta/secondary_cta label+url, page_context (home/hajj/umrah/tourism), sort_order, is_active | none | |
| `media_items` | media_type (image/video), gallery_type (gallery/event/promo), title, file_path, video_url, sort_order, is_active | none | |
| `news_articles` | title, slug (unique), excerpt, body, cover_image, is_active, published_at, meta_title, meta_description | none | |
| `testimonials` | name, quote, service_tag (hajj/umrah/tourism/general), rating, photo, source, sort_order, is_active | none | `source` is a provenance note (e.g. which live page it was recovered from) |
| `faqs` | category (general/hajj/umrah/tourism), question, answer, sort_order, is_active | none | |
| `offices` | label, address, phone_primary/secondary, whatsapp, email, google_maps_embed, is_domestic, sort_order, is_active | none | |
| `site_settings` | key (unique), value, group | none | Simple key-value store, cached via `SiteSetting::get()` with per-key cache invalidation on save |
| `pages` | title, slug (unique), body, template, is_active, meta_title, meta_description | none | For static CMS pages (About, policies) — not yet populated with content, ready for it |
| `inquiries` | name, email, phone, package_id (nullable FK, null-on-delete), package_category_id (nullable FK, null-on-delete), message, hajj_details (json, nullable), status (new/contacted/closed), source_page, ip_address | belongsTo package (optional), category (optional) | `hajj_details` holds the optional structured Hajj-application fields (CNIC, blood group, next-of-kin, etc.) without a dedicated table |
| `users` | name, email, password, role (super_admin/content_editor), is_active | hasMany (implicit, none currently) | Laravel's default table + 2 added columns; admin-only, no public registration |

## Constraints and integrity

- Every foreign key that points at content admins can delete uses `cascadeOnDelete` (e.g. deleting a category cascades to its series/packages) or `nullOnDelete` where the child record should survive its parent's deletion (e.g. an `inquiry` outlives a deleted `package`, so the historical lead isn't lost).
- `packages.slug`, `packages.code`, `hotels.slug`, `news_articles.slug`, `pages.slug`, `site_settings.key` are all unique — enforced at the DB level, not just in validation, so a race condition can't create two published packages at the same URL.
- `packages` uses soft deletes; every other content table uses hard deletes (they're simple content, not audited financial/booking records — soft-deleting them would only add unused `deleted_at` columns).

## What's deliberately not modeled yet

No `bookings`/`payments` tables (this phase is inquiry-based, not transactional — see PROJECT_REQUIREMENTS.md §V). No multi-language columns (`_ms`-suffix style or otherwise) — out of scope for this phase, and if added later, the project's own lessons on localisation (same-record translation, field-level fallback, never separate rows per language) apply directly to `packages`/`news_articles`/`pages`.
