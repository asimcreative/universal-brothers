# Requirements Traceability Matrix

Maps each major requirement from PROJECT_REQUIREMENTS.md / the approved proposal to where it's actually implemented, its DB component, its UI component, its test coverage, and current status. "Verified" means a test actually asserts the behavior and was actually run (see REGRESSION_TEST_RESULTS.md); "Built, untested" is flagged honestly rather than hidden.

| Req ID | Requirement | Implementation | DB Component | UI Component | Test Coverage | Status |
|---|---|---|---|---|---|---|
| F-1 | 12 real Hajj 2027 packages, faithful to brochure | `HajjPackageSeeder` | `packages`, `package_itinerary_days`, `package_price_tiers`, `package_room_prices`, `package_features` | `packages/show.blade.php` | `HajjSeedDataTest` (PHPUnit), Playwright test 6 | ✅ Verified |
| F-2 | Package add-ons (Kaba view, extra nights, VIP GMC, transfers) | `HajjPackageSeeder::seedAddons()` | `package_addons` | Not yet surfaced in any public view | None | ⚠️ Data seeded, not displayed anywhere — real gap, not previously flagged |
| G-1 | Umrah category CMS-ready | `PackageCategorySeeder`, `PackageSeries` | `package_categories`, `package_series` | `packages/category.blade.php` (empty-state) | `PackageBrowsingTest`, Playwright test 7 & Journey B | ✅ Verified (architecture); ❌ zero real content (documented gap, not a defect) |
| H-1 | Tourism real names/prices | `TourismPackageSeeder` | `packages` | `packages/category.blade.php`, `package-card` | `HajjSeedDataTest::test_tourism_category_has_no_invented_itinerary_data`, Playwright test 8 & Journey C | ✅ Verified |
| I-1 | Package CRUD (admin) | `Admin\PackageController` | all `package_*` tables | `admin/packages/{index,form}.blade.php` | `PackageManagementTest` (5 tests), Playwright tests 17-25 | ✅ Verified |
| J-1 | Public inquiry form | `InquiryController`, `InquiryRequest` | `inquiries` | `components/inquiry-form.blade.php` | `InquiryTest` (3 tests), Playwright test 10, Journeys A/C | ✅ Verified |
| J-2 | Contact form | `ContactController`, `ContactRequest` | `inquiries` | `contact.blade.php` | `ContactTest` (3 tests), Playwright tests 11/11b/11c | ✅ Verified |
| J-3 | Admin inquiry management | `Admin\InquiryController` | `inquiries` | `admin/inquiries/*.blade.php` | `InquiryManagementTest`, Playwright test 23 | ✅ Verified |
| K-1 | Pages/About Us CMS | `Admin\PageController`, `PageController` | `pages` | `admin/pages/*.blade.php`, `page.blade.php` | `PageManagementTest` (5 tests), `StaticPageTest` (3 tests), Playwright tests 24 & Journey E | ✅ Verified (closed this pass — previously schema-only) |
| K-2 | Testimonials CRUD | `Admin\TestimonialController` | `testimonials` | `admin/testimonials/*.blade.php` | `ContentManagementTest`, Playwright test 21 | ✅ Verified |
| K-3 | FAQs CRUD | `Admin\FaqController` | `faqs` | `admin/faqs/*.blade.php` | `ContentManagementTest`, Playwright test 22 | ✅ Verified |
| K-4 | Sliders CRUD | `Admin\SliderController` | `sliders` | `admin/sliders/*.blade.php` | `ContentManagementTest` | ✅ Verified (no dedicated Playwright test — PHPUnit only) |
| K-5 | Offices CRUD | `Admin\OfficeController` | `offices` | `admin/offices/*.blade.php` | `ContentManagementTest` | ✅ Verified — **this controller was completely broken for real users until this pass** (see REGRESSION_TEST_RESULTS.md run 8) |
| K-6 | News CRUD | `Admin\NewsArticleController` | `news_articles` | `admin/news/*.blade.php` | None | ⚠️ Built, untested — no PHPUnit or Playwright coverage exists for News; real gap, newly identified in this traceability pass |
| K-7 | Media Gallery | `MediaItem` model + migration only | `media_items` | None | None | ❌ Schema-only, no admin controller/views ever built — same class of gap as Pages was before this pass, not yet closed |
| K-8 | Categories & Series management | `Admin\PackageCategoryController` | `package_categories`, `package_series` | `admin/categories/*.blade.php` | None at PHPUnit/Playwright level | ⚠️ Built, untested — real gap |
| K-9 | Site Settings | `Admin\SiteSettingController` | `site_settings` | `admin/settings/index.blade.php` | None | ⚠️ Built, untested — real gap |
| L-1 | Sitemap/robots/meta/schema | `SitemapController`, layout partials | n/a | `sitemap.blade.php` | `SitemapTest` (added this pass) | ✅ Verified — sitemap was missing Pages until this pass, now fixed and tested |
| M-1 | Security controls | See SECURITY_AUDIT.md | n/a | n/a | `SecurityHeadersTest`, auth tests throughout | ✅ Verified, re-audited this pass |
| N-1 | No N+1 queries | `HomeController`, `PackageController` | n/a | n/a | Measured via `DB::enableQueryLog()`, see PERFORMANCE_AUDIT.md | ✅ Verified — real N+1 found and fixed this pass (58→23 queries on homepage) |
| Q-1 | Full test suite | see above | n/a | n/a | 52 PHPUnit + 46 Playwright | ✅ Verified, actually executed |

## Gaps this traceability pass surfaced that weren't previously in FINAL_GAP_ANALYSIS.md

Cross-referencing every admin module against its actual test coverage (not just "does the controller exist") surfaced three items missed by earlier passes:
1. **Package add-ons are seeded but never displayed anywhere on the public site** — real Hajj brochure data (Kaba view supplement, extra-night pricing, VIP GMC transport) sits in the `package_addons` table with no view ever querying it.
2. **News, Categories/Series admin, and Site Settings admin have zero automated test coverage** — they work (manually verified via earlier curl smoke-tests in this engagement), but nothing guards them against regression.
3. **Media Gallery module is schema-only** — same situation Pages was in before this pass, not yet addressed.

These are added to FINAL_GAP_ANALYSIS.md's tracking rather than silently left out of this matrix.
