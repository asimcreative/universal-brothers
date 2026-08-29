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

## Remaining query counts — assessed, not further optimized

23–24 queries per page is not zero, but every remaining query in the log is either (a) a genuinely distinct piece of data (itinerary days, price tiers, room prices, inclusions, exclusions — each legitimately a separate eager-loaded relation for a package detail page) or (b) the site-wide layout composer running once per included partial (header + footer each independently query `PackageCategory`/`Office` for nav/contact data — a small, real, non-scaling duplication worth noting but not worth the added complexity of a request-level cache for a a 2-query saving). None of the remaining queries scale with the amount of content on the page (they don't repeat per package/itinerary-day/etc.), so there is no remaining N+1 pattern — confirmed by inspecting the full query list, not assumed from the count alone.

## Asset pipeline

`npm run build` produces `app.css` (~313 KB, ~46 KB gzipped) and `app.js` (~223 KB, ~76 KB gzipped) — reasonable for a Bootstrap 5 + jQuery site with icon fonts bundled, no code-splitting attempted (not warranted at this page count). Images: cover images/slider images/featured images are validated `image`+`max:4096` on upload but **not** processed into multiple sizes or WebP on upload — see FINAL_GAP_ANALYSIS.md's "real asset handling" section for what's in place vs. deferred.

## Not measured in this pass

Real network waterfall / Lighthouse score / actual page-load timing under production-like conditions (no production or staging target exists to measure against — see FINAL_AUDIT_REPORT.md's production-readiness section). The query-count measurements above are the honest, verifiable substitute available in this environment: proof the backend isn't doing needless work, not a substitute for a real Lighthouse run once a live URL exists.
