# Admin UX Audit — Hajj Package Management

**Date:** 2026-09-14 · **Issue:** [#10](https://github.com/asimcreative/universal-brothers/issues/10) · **Scope:** the whole admin shell, the dashboard, and above all the Hajj package create/edit workflow.

This records what the admin panel was like **before** the package-management redesign, with the evidence, and what was done about each finding. Architecture: [`ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md`](../architecture/ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md). Requirements: [`ADMIN_PACKAGE_MANAGEMENT_REQUIREMENTS.md`](../requirements/ADMIN_PACKAGE_MANAGEMENT_REQUIREMENTS.md). Tests: [`ADMIN_PACKAGE_MANAGEMENT_TEST_REPORT.md`](../testing/ADMIN_PACKAGE_MANAGEMENT_TEST_REPORT.md).

---

## 1. Method

**1. Code read in full**
- `resources/views/admin/hajj-packages/form.blade.php` (808 lines)
- `resources/views/admin/hajj-packages/index.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `Admin\HajjPackageController` (423 lines)
- `HajjPackageRequest`
- All 17 package models
- All package migrations
- `HajjPackagePresenter` and the `x-hajj.*` public components
- The AI assistant's `PackageContext` and `KnowledgeIndexer`
- Every test touching Hajj packages (PHPUnit and Playwright)

**2. Data queried**
- **Local database**, grouped by distinct values, to see how much content the 12 live Hajj packages repeat.
- **Live production site**, fetched over HTTP to confirm the privacy finding in §3.1.

**3. Screenshots**
- Every builder step and the new pages, taken with Playwright at 1440 px and 390 px after the redesign.
- They surfaced four layout defects, fixed before completion (§5).

---

## 2. Findings in the package workflow (before)

| # | Finding | Evidence | Severity |
|---|---|---|---|
| F1 | **One 808-line form with 14 stacked sections.** No sense of progress, no way to tell what was missing. | `form.blade.php` | High |
| F2 | **Package A/B linked by typing a letter** into a free-text "Variant Code" box on every hotel, room and Aziziya row. A typo was only caught by server validation after submitting. Nothing showed which prices belonged to which hotel. | `accommodations[][variant_code]`, `room_options[][variant_code]` | High |
| F3 | **Technical values shown as labels.** Pricing type showed `on_request` / `supplement`, note type `disclaimer`, media type `accommodation`. Price basis was free text defaulting to `per_person`. Transport type was a free-text box expecting `airport_transfer`. Room type needed a code (`quad`), a number (`4`) and a label (`Quad Sharing`) typed separately and kept in agreement by hand. | Form markup | High |
| F4 | **The same content re-typed in every package.** Across the 12 live Hajj packages, see the table below. The `hotels` table (9 rows) was linked to nothing. | Local database query | High |
| F5 | **No duplicate, template, preview, archive, publish or feature action.** Adding the 14 packages the 2027 brochure still lacks ([`BROCHURE_2027_GAP_ANALYSIS.md`](../data/BROCHURE_2027_GAP_ANALYSIS.md)) meant 14 blank forms. | Listing had Edit and Delete only | High |
| F6 | **Live packages could be deleted in one click** from a native `confirm()`. | `index.blade.php` | Medium |
| F7 | **Nothing stopped an incomplete package being published.** Only `name` and `slug` were required. | `HajjPackageRequest` | Medium |
| F8 | **The admin had to invent the URL slug** (required field). | `HajjPackageRequest` | Medium |
| F9 | **Saving sent the admin back to the list,** away from what they had just saved. A failed save on a hidden section gave a flat error list with no location. | Controller redirects | Medium |
| F10 | **Inclusions and exclusions were two plain textareas**, one per line. No reordering, no reuse, duplicates allowed. | `inclusions_text` | Medium |
| F11 | **Muzdalifah could not be entered at all** (DB enum allowed Mina and Arafat only), although the public page already knew how to show it. | Migration `…201429` | Low |
| F12 | **No record of who published, archived or deleted what.** | No audit table | Medium |

Repeated content found in F4 (12 live Hajj packages):

| Content | Rows stored | Distinct values |
|---|---|---|
| Transport rows | 72 | 6 |
| Upgrades | 72 | 8 |
| Mina/Arafat rows | 24 | 2 |
| Notes | 74 | 10 |
| Included / not-included lines | 208 | 27 |
| Aziziya services | 56 | 14 |

## 3. Findings outside the form

### 3.1 Internal notes shown to customers and to the AI (privacy defect)

**What was published**
- Four packages carried brochure-audit commentary written for the people maintaining the data:
  - UB004, UB008 and UB011: "Brochure inconsistency preserved as printed: headed "14 Days Package" but the itinerary table lists 13 numbered days…"
  - UB013: "Brochure names this "Makkah First" … recorded as found."
- `HajjPackageSeeder` stored that text twice: as `packages.description` and as an important `package_notes` row.
- So it printed twice on each live package page, in the intro paragraph and under "Important Notes".
- It was also handed to the AI assistant as a package fact.

**Evidence:** `curl` of the live production pages counted 2 occurrences each on UB008, UB011 and UB013 (UB004 shown directly).

**Root cause:** there was nowhere to put an admin-only note, so internal commentary went into public fields.

**Fixed**
- Added `packages.internal_notes`, which nothing public reads. It is also in `$hidden`, so it never appears in JSON.
- The seeder writes the remark there.
- A migration moves the existing text on production.
- Tests prove it:
  - absent from the public page, the listing, `PackageContext`, the AI knowledge index and `toJson()`;
  - still visible in the admin.

### 3.2 Admin shell

| # | Finding | Fix |
|---|---|---|
| S1 | Navigation had no place for reusable content, templates or help. The desktop and mobile menus were two hand-copied lists. | One `admin/partials/nav.blade.php`, used by both, grouped into Hajj Packages, Reusable Content, Umrah & Tourism, Enquiries, Website Content, Company, AI Assistant, System and Help. |
| S2 | Every delete used the browser's `confirm()`: unstyled, one line, reads like an error. | One confirmation dialog for the whole admin (`data-confirm`). All 13 existing delete forms converted. |
| S3 | Double submits possible on slow saves. | Global loading state: buttons disabled, spinner on the pressed one. |
| S4 | Flash messages had no icon and no live-region role. | `admin-alert` with icon and `role="status"` / `role="alert"`. |
| S5 | Dashboard showed no Hajj-specific status, no FAQ, award or affiliation counts, nothing needing attention, no recent activity. | Rebuilt from live counts (§4). |
| S6 | Outline-secondary buttons were brand gold on white (~2.4:1 contrast), because gold is Bootstrap's "secondary". Found in screenshots. | Neutral grey in the admin only. The public site is unchanged. |
| S7 | No "view website" or help link in the header. | Added to the topbar and the user menu. |
| S8 | Admin pages were not marked `noindex`. | `<meta name="robots" content="noindex, nofollow">` on the admin layout. Draft previews also send `X-Robots-Tag: noindex`. |

## 4. What was built (summary)

**Package builder**
- 12 steps with progress marks.
- A step-aware error summary whose links open the step that fixes each error.
- Save intents: draft, save, continue, preview, publish.
- A sticky save bar with an unsaved-changes flag.
- A leave-page warning.
- A browser-kept copy of unsaved work that can be restored.

**Hotel options (A / B / C)**
- A choice card instead of typed codes.
- Every price and hotel sits in a coloured box for its option.
- Renaming a letter re-labels its rows.
- Removing an option asks first and names how many rows go with it.

**Plain-language inputs**
- Room types are chosen by name ("Triple Sharing"). The code, occupancy and label are filled for the admin.
- Every select shows words, not keys.
- The web address is made from the title.

**Reusable library (9 sections)** — hotels and accommodation, meal plans, transport, included services, not included, additional options, Mina/Arafat/Muzdalifah, notes and policies, journey templates.
- Each has search, filters, active and archived tabs, duplicate, a usage count and "where it is used".
- Deleting is refused while in use.
- An explicit "update N packages" action pushes changes to linked packages.

**Package actions**
- Duplicate, templates (save, create from, edit, apply to draft only), and copy any section from another package.
- Quick publish, unpublish, feature, archive and restore.
- Signed, expiring draft preview.
- Published packages cannot be deleted.
- Publishing requires completeness.

**Listing**
- Status tabs with counts.
- Search by title or code.
- Filters: series, arrival, Aziziya, days, featured.
- Sort, a featured star toggle, and an actions menu.

**Dashboard and records**
- A dashboard built from real counts, with "needs attention", reusable-content counts and recent activity.
- An audit trail (`admin_activities`): who published, unpublished, featured, archived, restored, deleted, duplicated, applied a template or updated packages from the library.

**Help page** — a plain-language guide at `/admin/help`.

## 5. Defects found and fixed during the build

| Defect | How found | Fix |
|---|---|---|
| Step progress marks only updated when a field lost focus, not while typing | Playwright journey test | Marks update on typing (debounced) |
| "Add day" repeated the previous day's date instead of the next day. `toISOString()` converts to UTC, which in Pakistan (UTC+5) moves local midnight to the previous day | Playwright journey test | Local-calendar date formatting |
| Hotel rows overflowed the page sideways at 1440 px (the builder sits beside two sidebars) | Screenshot | Every grid column may shrink. Rows adapt to the width of their own box through CSS container queries, not the screen width |
| The stacked "card" row style applied at every width. Sass cannot `@extend` a placeholder from inside `@container`, and silently hoisted the rule out of the query | Screenshot | Mixin instead of placeholder |
| Phone save bar took three lines, about 150 px of an 844 px screen | Screenshot | One line of short labels. Accessible names keep the full wording |
| Sidebar width varied by page (flex shrink) | Screenshot | `flex-shrink: 0` |
| `URL` facade not aliased in Blade (500 on the listing) | PHPUnit | `PackagePreview::url()` helper |
| Migration rollback would have deleted the moved internal remarks (it drops the column) | Manual rollback on a copy of the local database | `down()` restores them to `description` first. Verified by a migrate, rollback, migrate cycle |

## 6. Remaining limitations and follow-ups

1. **A journey plan day holds stays for two options.**
   - The day-by-day table has only `accommodation_a` and `accommodation_b` columns, and the public itinerary reads exactly those.
   - A package with a third option shows Option C's hotels and prices correctly, but its per-day stay text has no column.
   - Adding one means changing the public itinerary component; not done without a real three-option brochure package to design against.
2. **One photo per hotel and Mashaer record.** Galleries for library records are not built. Package galleries are unchanged and still work.
3. **Itinerary rows have no separate "transport" field.** The brochure's day table has no such column. The per-day description box covers it.
4. **Aziziya facilities are not in the library.** They are copied between packages with "Copy from another package → Hotels and Aziziya".
5. **The browser-kept copy of unsaved work lives in the same browser only**, and never includes chosen photo files. This is by design: nothing unsaved is written to the server, so a half-typed change can never reach a live package.
6. **The generic Umrah & Tourism form was not redesigned.** It inherits the new shell, dialog and button styles only. The task scope was the Hajj workflow.
7. **Roles.** Every admin role can use every package and library action, as before. Only Users & Roles is super-admin-only (existing `UserPolicy`). A finer split would need a business decision on who may publish.
