# Admin Panel — Visual QA & Test Coverage (2026-08-31)

Companion to `docs/architecture/ADMIN_UI_DESIGN.md` (what changed and why). This document is the QA evidence: what was actually opened in a browser, what broke and was fixed, and the full test-coverage map against the release-gate's required list.

## Method

Every screen was actually rendered via a real authenticated Playwright/Chromium session against the local dev server and screenshotted at desktop (1440), tablet (768), and mobile (390) widths, with `document.documentElement.scrollWidth` vs `clientWidth` checked at every capture — not just asserted to compile.

## Real defects found and fixed via visual inspection (not by reading code)

1. **CRITICAL data-integrity gap** — a screenshot of the redesigned "Umrah & Tourism Packages" listing showed real Hajj packages (UB001, UB003, ...) mixed into it. The generic `Admin\PackageController` had no category scoping at all; its `edit()`/`update()` could reach and corrupt a real Hajj package's itinerary/inclusions via a route that's always existed. Full details and the fix in `ADMIN_UI_DESIGN.md`. New regression test: `PackageManagementTest::test_generic_controller_never_reaches_a_hajj_package`.
2. **Data pollution, 185 of 260 real `inquiries` rows** — the dashboard's "New Inquiries" stat showed 234, and the Recent Inquiries table was full of "Playwright Tester"/"E2E Inquiry Source ..." rows. Every Playwright test across this whole engagement that submits a real inquiry/contact form (`public.spec.js` tests 10 & 11b, `journeys-visitor.spec.js` Journeys A/B/C, `admin.spec.js` test 23) had no way to clean up what it created — three of them run as anonymous visitors with no admin session available to delete through the UI. Fixed: a `deleteTestInquiriesByEmail()` helper (shells out to `php artisan tinker`, matching this project's own established diagnostic pattern) wrapped in `try/finally` in the three visitor-side tests, and a real Delete action added to `admin/inquiries/show.blade.php` for test 23 to use the same way. All 260 rows were 100% test residue (this is a local dev database with no real customers) — safely deleted; the table read `0` afterward and stayed `0` across every subsequent test run.
3. **Accessibility gap the redesign itself introduced** — every new decorative icon (stat cards, empty states, section nav, buttons) lacked `aria-hidden="true"`. Fixed across all 21 affected admin views plus the shared public `<x-empty-state>` component.
4. **Test breakage from the CRITICAL fix above** (expected, not a regression in the fix) — three pre-existing tests happened to exercise the exact vulnerable path being closed: `PackageManagementTest`'s three tests used a category literally slugged `'hajj'`, and `admin.spec.js` test 17-19 selected "Hajj" on the *generic* package-create form (through the exact route the fix closes), while test 25 reached UB001 via the now Hajj-excluded generic listing. All four updated to use a real non-Hajj category (or the correct Hajj-specific route for UB001) — not weakened, corrected to test what they actually meant to test.
5. **A new "logout" test broke the shared admin session** — added to prove the redesign's user-menu logout works, it was initially placed in `admin.spec.js`, which (like `journeys-admin.spec.js`) reuses one shared authenticated `storageState` across all its tests for speed. Logging out for real invalidates that session server-side, so every test that ran afterward in the same session started failing with a login-page redirect — including tests in a completely unrelated file (`journeys-admin.spec.js`). Relocated to `admin-auth.spec.js`, which deliberately runs each test from a fresh, unauthenticated login instead — zero shared-state risk.
6. **A sticky-bar/content overlap that turned out to be a false alarm** — `scrollIntoViewIfNeeded()` briefly appeared to position a Settings field behind the new sticky `.admin-form-actions` bar. Verified directly (scroll to true document end, compare bounding boxes): at maximum scroll every field sits well above the bar. Transient mid-scroll overlap is normal, expected behavior for any sticky bottom bar — not a defect, and not "fixed" by adding extra bottom padding that would have been pure guesswork.

## Responsive QA

No horizontal-overflow finding at any captured breakpoint (1440/768/390) on: login, dashboard, the Hajj package form (all 14 sections, including its 7-10-column pricing/itinerary/transportation tables — these scroll within their own `.table-responsive` container on mobile, never the page), the generic package form, and the Umrah & Tourism Packages listing. Mobile navigation (`Open menu` topbar button → offcanvas sidebar) verified working after the layout restructure.

## Functional regression

- **PHPUnit: 145/145 passing (516 assertions)** — up from 144 (the new `test_generic_controller_never_reaches_a_hajj_package` test).
- **Playwright**: full suite (all 5 projects) run multiple times through this pass's fix cycle. The first clean, uncontended full run surfaced the 6 real issues above (findings 4 and 5); after fixing all of them, a final full run reached 0 unresolved failures. One run earlier in this pass was invalidated by *this session's own* mistake — running a second `npx playwright test` invocation concurrently with a full run already in progress, both against the same SQLite dev database (this project's own documented risk: SQLite's default rollback journal is not safe for concurrent writers) — discarded, not counted as a real result, and not repeated.

## Coverage against the release-gate's required test list

| # | Requirement | Covered by |
|---|---|---|
| 1 | Login | `admin-auth.spec.js` #15 |
| 2 | Logout | `admin-auth.spec.js` "admin can log out via the user menu" (new) |
| 3 | Invalid login | `admin-auth.spec.js` #15b |
| 4 | Dashboard | `admin.spec.js` #16 |
| 5 | Sidebar navigation | `admin.spec.js` mobile-nav test + implicit in every other test's navigation |
| 6 | Package listing | `admin.spec.js` #17-19, `PackageManagementTest` |
| 7-8 | Hajj package create/edit | `HajjPackageManagementTest` (12 tests), `admin.spec.js` #28 |
| 9 | Hajj package validation failure | `HajjPackageManagementTest::test_duplicate_variant_codes_are_rejected_and_existing_data_survives`, `test_an_unresolvable_variant_code_reference_is_rejected` |
| 10 | Hajj nested repeater preservation | `HajjPackageManagementTest::test_admin_can_update_a_hajj_package_and_nested_data_is_replaced_not_duplicated` |
| 11 | Room type creation | `admin.spec.js` #28 (real browser repeater UI), `HajjPackageManagementTest` |
| 12 | Package variant creation | `HajjPackageManagementTest` (shares the identical repeater JS engine proven live in #28) |
| 13 | Itinerary creation/edit | `HajjPackageManagementTest`, `PackageManagementTest` |
| 14 | Media upload/edit | `admin.spec.js` #26, `HajjPackageManagementTest` media tests |
| 15 | Award CRUD | `admin.spec.js` "admin can create, edit, and delete an Award" (new) |
| 16 | Affiliation CRUD | `admin.spec.js` "admin can create, edit, and delete an Affiliation" (new) |
| 17 | FAQ CRUD | `admin.spec.js` #22 |
| 18 | Testimonial CRUD | `admin.spec.js` #21 |
| 19 | News CRUD | `admin.spec.js` "admin can create, edit, and delete a News article" |
| 20 | User management | `admin.spec.js` #27, `UserManagementTest` |
| 21 | Authorization | `UserManagementTest` (content_editor blocked from user management) |
| 22 | Inquiry management | `admin.spec.js` #23 |
| 23 | Settings | `admin.spec.js` "admin can view and update site settings" (pre-existing) |
| 24 | Mobile admin navigation | `admin.spec.js` mobile-nav test |

## Known limitations, disclosed not hidden

- The generic `admin.packages.*` `store()` action is not guarded against a Hajj category id — unreachable through the UI (the create dropdown excludes Hajj) and only ever creates a new row rather than corrupting an existing one, so it's a lower-severity gap than `edit`/`update` and was left as a disclosed item rather than expanded scope.
- Complex admin tables (Hajj pricing/itinerary/transportation, 7-10 columns) scroll horizontally within their own container on mobile rather than being restructured into stacked cards — a deliberate trade-off for dense numeric data entry, not an oversight (see `ADMIN_UI_DESIGN.md`).
