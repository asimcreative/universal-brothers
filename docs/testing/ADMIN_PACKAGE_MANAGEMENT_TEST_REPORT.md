# Admin Package Management — Test Report

**Date:** 2026-09-14 · **Issue:** [#10](https://github.com/asimcreative/universal-brothers/issues/10) · **Environment:** local only.

- PHPUnit uses in-memory SQLite (`phpunit.xml`).
- Playwright uses `database/testing.sqlite` through `php artisan serve --env=testing`.
- **No test touched the production database.**

Related: [architecture](../architecture/ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md) · [requirements and traceability](../requirements/ADMIN_PACKAGE_MANAGEMENT_REQUIREMENTS.md) · [UX audit](../audits/ADMIN_UX_AUDIT.md)

---

## 1. Results

| Suite | Result |
|---|---|
| PHPUnit, full suite | **335 passed** (1,929 assertions), 0 failed |
| Playwright, full suite (all five projects), final run | **250 passed**, 0 failed, 0 flaky, 4 skipped (the existing mobile-only skips in `public.spec.js`), 11.3 min |
| Playwright, first full run (before the fixes in §4) | 246 passed, 4 failed — all four traced and fixed |
| Playwright, `package-builder.spec.js` alone, no retries | 8 passed (+ login setup) |
| Migration cycle on a copy of the local database (migrate → rollback → migrate) | Passed; moved remarks restored on rollback and moved again on re-migrate |
| Round trip: every seeded brochure package saved back through the builder route | 12 of 12 unchanged |

Before this work the suite stood at 279 PHPUnit tests. **56 new PHPUnit tests** were added, and 3 existing ones were updated for intended behaviour changes (§3).

## 2. New automated tests

### 2.1 `tests/Feature/Admin/PackageBuilderTest.php` (31 tests, HTTP routes)

| Area | Tests |
|---|---|
| **Real data** | All 12 brochure packages meet the publishing rules. All 12 open in the builder and save back through `PUT` unchanged — every option, price, hotel, day, note, service, leg, upgrade and library link — and stay published. |
| **Saving** | Incomplete draft saves and gets a web address from its title. Publishing incomplete is refused with `publish.basics`, `publish.hotels` and `publish.pricing`, and nothing is created. The error summary links to the step and keeps what was typed. Every option needs a price. Publish makes the package live and records the user. Save keeps a live package live, and continue opens the next step. The draft intent unpublishes. The preview intent saves as a draft and redirects to a signed URL. A field error shows on the row that caused it, even with gaps in the row numbers. |
| **Options and content** | A, B and C keep their own hotels and prices; option letters are stored in capitals; empty currencies stay empty; the public page shows Option C. A room named only by its label is kept. A saved hotel is linked and filled, and a package-specific rename leaves the saved hotel untouched. Saved transport, notes (with heading), services, upgrades and Mina are linked and copied. The same service twice is saved once. Muzdalifah is saved and rendered publicly. |
| **Privacy** | Internal notes: shown in the admin; absent from the public page, the listing, `PackageContext`, `toJson()` and the rebuilt AI knowledge index. After seeding, UB004, UB008, UB011 and UB013 have no audit remark in their description, notes or public page, and the remark is in `internal_notes`. |
| **Duplicate** | Copy is a draft, not featured, with a new code and slug. All 13 content sections are identical, including library links. Option rows point at the copy's own options. Cover and gallery images are new files. The original is unchanged and still published. The copy returns 404 publicly. Two copies get `-COPY` and `-COPY-2` with distinct slugs. |
| **Quick actions** | Publishing an incomplete draft is refused. Publish → feature → unpublish → publish → archive (off the site, un-featured, only in the Archived tab) → restore (draft). The audit log holds exactly those six actions. Actions on a non-Hajj package return 404. |
| **Listing** | Search by code; filters by arrival, Aziziya, featured, days and status. |
| **Preview** | A signed link works for an admin, shows the draft banner and sends the noindex header. Unsigned → 403. Expired → 403. Guest → login. The draft is still 404 publicly. |
| **Templates** | Saving as a template keeps content and never code, slug, name, status, featured, internal notes, media or SEO. Create-from-template prefills. A template is built and edited in the builder. Apply to a draft replaces content and keeps identity. Apply to a published package is refused and nothing changes. The content JSON leaves out identity and internal notes, and requires login. New pages require login. |

### 2.2 `tests/Feature/Admin/PackageLibraryTest.php` (9 methods, 17 cases)

- **All nine sections through a data provider:** list, create form, store, list shows it, edit, update, duplicate, archive (leaves the Active tab, appears in Archived), restore, delete.
- **Scoping:** an inclusion route cannot open or delete an exclusion.
- **Validation:** name, location and URL are validated; nothing is saved on failure.
- **Hotel photo:** stored; `slug` and `city` are derived.
- **Usage protection (seeded data):** the transport leg used by 12 packages shows "12 packages", its usage page lists UB001 and offers "Update 12 packages", delete is refused, archiving removes it from the builder, and the packages still use it.
- **Explicit update:** editing Swissotel does not change the 2 linked package rows and the message says so. "Update" changes only the linked rows, is recorded, and the public UB004 page shows the new name.
- **JSON quick-add:** hotel from the builder returns 201; a blank name returns 422.
- **Journey template:** empty days dropped, days numbered, order kept.
- **Menu:** every section appears in it.

### 2.3 `tests/Feature/PackageLibraryBackfillTest.php` (5 tests)

- **Links:** after seeding, every live Hajj row in six tables is linked. Record counts: transport 6, Mashaer 2, upgrades 8, notes 8, meal plans 4.
- **Hotel matching:**
  - "Dar Al Tawhid Intercontinental" links to the seeded "… Makkah" hotel, with no duplicate.
  - "Makkah Tower" links to "Makkah Tower (Hajar Tower)".
  - Aziziya and Madinah locations are set.
  - Superseded hotels are archived.
- **Idempotent:** a second run returns nothing, and counts and every link are identical; a full re-seed changes nothing.
- **Legacy data** with the remark in both description and a note: moved once, word for word, even when run twice; customer notes untouched.
- **Public pages** still show UB001's hotels, prices, transport and "Makkah (similar)" note.

### 2.4 `tests/Feature/Admin/DashboardTest.php` (3 tests)

- Exact counts for Hajj total, published, draft, featured and archived, other packages, inquiries total and new, FAQs, awards and affiliations.
- A live package made incomplete appears under "Needs attention", with a link to the pricing step.
- Recent activity names the user.

### 2.5 `tests/e2e/package-builder.spec.js` (8 tests, `admin-chromium`)

1. **Full journey in a real browser:**
   - Basics: the length label fills itself and the step is ticked.
   - Setup, then "Yes" to options: A and B appear.
   - A box per option; "Add Quad, Triple & Double".
   - Saved hotel picked: name and stars filled.
   - Journey: the next day gets the next date; the second stay column appears.
   - Saved transport added from the dialog.
   - A saved service is greyed out once added.
   - Internal note; the unsaved flag shows.
   - Save draft returns to the edit page, and the page is 404 publicly.
   - Publish through the dialog: the public page shows the title and price, and not the internal note.
   - Cleanup through the listing.
2. **Publishing incomplete:** the summary names the problem, the Room prices step is marked, and its link opens the step.
3. **New hotel from inside the builder:** selected in the row, listed in the library as "0 packages", then deleted.
4. **Copy from another package:** journey copied from UB001 (13 days); "Fill dates" sets day 13 to the correct date.
5. **Duplicate a live package:** lands on the copy (draft, `UB003-COPY…`); the original is still published; the copy is deleted.
6. **Unsaved changes:** a reload offers restore, restoring brings the text back, and discard clears it.
7. **Preview:** a link without its signature returns 403; the signed link shows the banner.
8. **Phone (390 px):** no sideways scroll on Room prices, Hotels, Journey or Transport; the save button is visible.

## 3. Existing tests updated, and why

| Test | Change | Reason |
|---|---|---|
| `HajjPackageManagementTest::test_admin_can_create_a_full_hajj_package` and `…update…` | Redirect expected to the package's edit page, not the list | Intended: the builder returns to what was saved |
| `HajjPackageManagementTest::test_admin_can_delete_a_hajj_package` | Split into "delete a draft" and "a published package cannot be deleted" | Intended: live packages are protected |
| `admin.spec.js` tests 25 and 28 | Rewritten for the builder (SEO in its step; add a room type to Option A's box); cleanup in `finally` | The old form markup no longer exists |
| 8 delete steps in `admin.spec.js`, 1 in `journeys-admin.spec.js` | `page.once('dialog')` replaced by `clickAndConfirm()` | Native `confirm()` replaced by the admin dialog |

No assertion was removed or weakened. Every other existing test — including all 12 remaining `HajjPackageManagementTest` cases (media persistence, option-code validation, zero-price currency) — passes unchanged against the new writer and request.

## 4. Defects the tests found during the build (all fixed)

| Found by | Defect | Fix |
|---|---|---|
| PHPUnit | `URL` facade not aliased in Blade: the listing returned 500 | `PackagePreview::url()` |
| E2E journey | Step ticks did not update while typing | Updates on input (debounced) |
| E2E journey | "Add day" repeated the date. `toISOString()` is UTC, and Pakistan is UTC+5 | Local-date formatting |
| E2E full run | The user-menu `aria-label` dropped the user's name (a screen-reader and test-locator regression) | Label includes the name |
| E2E full run | **A delete pressed before the admin script finished loading went through with no confirmation.** Trace showed `POST …/affiliations/37` with no dialog | Inline `<head>` fallback to the browser's confirm until the styled dialog is ready; the test helper waits for readiness |
| E2E full run | Dashboard hint "Published packages" duplicated the "Published Packages" card text | Hint reworded |
| Screenshots | Layout defects (sideways overflow at 1440 px, stacked rows at every width, gold low-contrast buttons, three-line phone save bar, shrinking sidebar) | See [UX audit §5](../audits/ADMIN_UX_AUDIT.md#5-defects-found-and-fixed-during-the-build) |

## 5. How to reproduce

```bash
php artisan test                              # PHPUnit, in-memory database
php artisan migrate --env=testing             # bring database/testing.sqlite up to date
npm run build
npx playwright test                           # all projects
npx playwright test tests/e2e/package-builder.spec.js --project=admin-chromium --retries=0
```

## 6. Not covered by automation

- Real file uploads through the browser for the new main/social photo inputs. The server side is covered by the existing media tests and the duplicate test.
- MySQL-specific migration SQL (`MODIFY` enum). Covered by reading; the SQLite path is exercised by every test run.
- Screen-reader walkthrough. Labels, `aria-current`, `role=status` and alert roles were added and checked in markup; no assistive-technology session was run.
