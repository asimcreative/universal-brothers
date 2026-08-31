# Performance Audit

Findings are measured, not estimated — actual query counts via `DB::enableQueryLog()` against the real seeded dataset (12 Hajj packages, 35 Tourism packages), not a guess from reading code.

## N+1 query bug found and fixed

**Before:** homepage = 58 queries. Root cause: `HomeController` eager-loaded each category's packages (`PackageCategory::with('packages')`), but `package-card.blade.php` also reads `$package->category->slug` and `$package->series->name` to build each card's link and badge. Eloquent does not back-fill the inverse `belongsTo` relation just because a record was reached through the forward `hasMany` side, so every rendered card triggered a fresh lazy-loaded query for its own category (and, where not eager-loaded, its series) — 12 rendered cards → 24 extra queries, confirmed by literally seeing `select * from package_categories where id = ?` and `select * from package_series where id = ?` repeated 12 times each in the query log.

**Fix:** `HomeController`, `PackageController::category()`, and `PackageController::show()` (for its "related packages" list) now call `$package->setRelation('category', $category)` on every package pulled through a category/tourism-listing context they were already loaded via — zero extra queries, versus even eager-loading `packages.category` which would still cost one more batched query. `series` is eager-loaded properly (`->with('series')`) since, unlike category, it isn't already sitting in memory from the parent query.

**After (measured):**
| Page | Before | After |
|---|---|---|
| Homepage | 58 | 23 |
| Hajj category listing | — (not measured before fix, same bug present) | 18 |
| Hajj package detail | — (same bug present) | 24 |

**Verification:** re-ran the full PHPUnit suite (51/51) and full Playwright suite (46/50, 4 correctly skipped) after the fix — no regression.

## Second redundant-query bug found and fixed (FINAL_CODE_REVIEW.md M-6)

The paragraph below originally accepted the header/footer composer's 2 queries as "not worth fixing" — but a subsequent independent code review found a **third**, uncounted, ad-hoc `Office::where(...)->first()` query living directly in `layouts/app.blade.php`'s JSON-LD block (outside the composer's `layouts.partials.*` pattern, so never counted above), bringing the real total to 3 identical `Office` queries and 2 identical `PackageCategory` (nav) queries per page. With a third call site added, "not worth a request-level cache for a 2-query saving" no longer held — this pass bound both lookups as request-scoped singletons (`$this->app->singleton(...)` in `AppServiceProvider::boot()`) that the composer reads from, so `layouts.app`, `header`, and `footer` share one query each instead of querying again per view. **Confirmed via a real query-log run** (not assumed): `select * from "offices" where "is_active" = ?` and `select * from "package_categories" where "is_active" = ?` (the nav-specific query, distinct from the featured-packages query) each now appear **exactly once** in the homepage's query log, down from 3 and 2 respectively.

## Remaining query counts — assessed, not further optimized

22–23 queries per page (homepage now measures 22, down from 23, after the M-6 fix above) is not zero, but every remaining query in the log is a genuinely distinct piece of data (itinerary days, price tiers, room prices, inclusions, exclusions — each legitimately a separate eager-loaded relation for a package detail page). None of the remaining queries scale with the amount of content on the page (they don't repeat per package/itinerary-day/etc.), so there is no remaining N+1 pattern — confirmed by inspecting the full query list, not assumed from the count alone.

## Hajj admin edit page — real N+1 found and fixed (FINAL_CODE_REVIEW_HAJJ_REDESIGN.md H-3)

An independent code review found `HajjPackageController::edit()`'s eager-loading was missing `.variant` on three relations (`accommodations`, `roomOptions`, `aziziya.roomOptions`) that the public `showHajj()` controller correctly had — the admin form's repeater pre-fill reads `$row->variant?->code` once per row, so for UB001 (3 accommodations + 6 room options + 1 Aziziya room option) that was 10 avoidable lazy-loaded queries on every single edit-page load. Fixed by matching the admin controller's `load()` call to the public one exactly. **Measured directly** (rendering `admin/hajj-packages/form.blade.php` with query logging enabled around just the render call, isolated from request-pipeline overhead): **0 additional queries fire during render** after the fix, confirming every relation the view touches is now actually eager-loaded rather than merely requested.

## Hajj package detail page (redesigned data model)

**Measured after the Hajj package data model redesign** (11 new tables, much deeper eager-loading required for variants/accommodations/room-options/Aziziya/Mina-Arafat/transportation/notes/upgrades/media): UB001's detail page — the richest real package (2 variants, 3 accommodations, 6 room options, an optional-Aziziya row, 13 itinerary days) — measures **35 queries**, none scaling with content (confirmed by grouping the query log and checking for repeats: the only duplicates found were 2 identical `sessions`/`cache` framework-internal queries, 2 `package_features` queries that are genuinely different data — inclusions vs. exclusions, same SQL shape but different bound `type` value — and one small, real redundancy: `package_variants` is queried twice (once via `accommodations.variant`, once via `roomOptions.variant` — Eloquent doesn't share a variant lookup across two independent eager-load paths on the same parent). Not yet fixed as of this measurement — a 1-query saving, tracked alongside the FINAL_CODE_REVIEW_HAJJ_REDESIGN.md findings rather than fixed in isolation, to batch it with whatever else that independent review surfaces.

## Asset pipeline

`npm run build` produces `app.css` (~313 KB, ~46 KB gzipped) and `app.js` (~223 KB, ~76 KB gzipped) — reasonable for a Bootstrap 5 + jQuery site with icon fonts bundled, no code-splitting attempted (not warranted at this page count). Images: cover images/slider images/featured images are validated `image`+`max:4096` on upload but **not** processed into multiple sizes or WebP on upload — see FINAL_GAP_ANALYSIS.md's "real asset handling" section for what's in place vs. deferred.

## Frontend redesign — new/rebuilt page query counts

**Measured directly** via a warmed-up HTTP kernel with `DB::enableQueryLog()` around each route (not estimated from reading the controllers):

| Page | Queries |
|---|---|
| Homepage (rebuilt, 14 sections) | 22–23 (unchanged from the pre-redesign baseline — new Award/Affiliation queries offset by removing the old generic per-category loop) |
| `/awards` | 8 |
| `/affiliations` | 7 |
| `/testimonials` | 7 |
| `/faqs` | 7 |
| `/media` | 9 |
| `/hajj-services` | 13 (see N+1 fix below — was 18) |
| `/umrah-services` | 9 |
| `/hajj` listing, no filters | 14 |
| `/hajj` listing, filtered (`days`+`sharing`) | 14 (filters add `whereHas` clauses to the existing query, not new queries) |
| `/about-us` (restructured, 4 sections) | 12 |

None of these scale with content — each is a small, fixed set of eager-loaded/simple queries per new content type, with no N+1 pattern (every new controller follows the same `setRelation`/eager-load discipline established earlier in this project rather than looping a lazy relation per row).

**A real N+1 was found and fixed in the frontend-redesign's own code review** (`FINAL_CODE_REVIEW_FRONTEND_REDESIGN.md` M-3): `HomeController`, `HajjServicesController`, and `UmrahServicesController` all fetched package collections rendered through `<x-package-card>` (which reads `$package->series->name`) without `->with('series')` — the exact same class of bug this file's first entry above documents, reintroduced in three new controllers that sat right next to `PackageController::category()`'s correct version of the same pattern. **Measured, not assumed:** `/hajj-services` dropped from 18 to 13 total queries after adding the eager-load; a direct query-log filter confirmed `package_series` now fires exactly once per page across all three fixed routes, versus once per rendered card before.

## Not measured in this pass

Real network waterfall / Lighthouse score / actual page-load timing under production-like conditions (no production or staging target exists to measure against — see FINAL_AUDIT_REPORT.md's production-readiness section). The query-count measurements above are the honest, verifiable substitute available in this environment: proof the backend isn't doing needless work, not a substitute for a real Lighthouse run once a live URL exists.
