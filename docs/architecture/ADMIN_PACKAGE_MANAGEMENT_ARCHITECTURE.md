# Admin Package Management — Architecture

**Issue:** [#10](https://github.com/asimcreative/universal-brothers/issues/10) · **Related:**
- [`HAJJ_PACKAGE_DETAIL_SYSTEM.md`](HAJJ_PACKAGE_DETAIL_SYSTEM.md) — the public page, unchanged in shape
- [`ADMIN_UI_DESIGN.md`](ADMIN_UI_DESIGN.md) — the earlier admin shell
- [`ADMIN_UX_AUDIT.md`](../audits/ADMIN_UX_AUDIT.md)

The Hajj package admin has three parts:

1. **The package builder** — a 12-step form for one package.
2. **The reusable library** — content written once and picked into many packages.
3. **Whole-package operations** — duplicate, templates, preview, quick status actions, and an audit trail.

---

## 1. The central decision: copy-and-link, not live references

When an admin picks a library record (a hotel, a transport leg, a note…), its values are **copied into the package's own row**, and the row stores **which record it came from** in a nullable link column (`hotel_id`, `transport_option_id`, …).

| | Live reference (rejected) | Copy-and-link (chosen) |
|---|---|---|
| Public page, `HajjPackagePresenter`, AI `PackageContext` | All rewritten to read library tables with fallbacks | **Unchanged** — they read the package rows exactly as before |
| Editing a library record | Instantly rewrites every live package | Changes nothing until the admin presses **Update N packages** |
| Package-specific wording ("Swissotel — Kaaba-view floors") | Needs a parallel override column per field | The package row *is* the override |
| Usage counts, "where is this used", delete protection | Via the reference | Via the link column |
| Existing data | Must be migrated into references | Stays where it is; links are added beside it |

Live references would have meant rewriting every consumer of the package data (the presenter, 20 `x-hajj.*` components, `PackageContext`, `KnowledgeIndexer`, filters, sitemap). They also carried a real business risk: fixing a hotel's star rating in one place would silently change the price page of twelve published packages. Copy-and-link keeps one source of truth for what visitors see — the package rows — and makes shared updates explicit and previewable.

## 2. Data model

All changes are additive. They live in `2026_09_14_200000_create_package_library_tables`.

### 2.1 Library tables

| Table | Model | Linked from | Copied fields (on "Update N packages") |
|---|---|---|---|
| `hotels` (extended) | `Hotel` | `package_accommodations.hotel_id` | `hotel_name ← name`, `star_rating` |
| `meal_plans` | `MealPlan` | `package_accommodations.meal_plan_id` | `meal_plan ← name` |
| `transport_options` | `TransportOption` | `package_transportation.transport_option_id` | type, from, to, included, price, currency, basis, notes |
| `service_items` (`type` = inclusion \| exclusion) | `ServiceItem` | `package_features.service_item_id` | `description` |
| `upgrade_options` | `UpgradeOption` | `package_upgrades.upgrade_option_id` | name, description, price, currency, basis, included, `notes ← conditions` |
| `mashaer_locations` | `MashaerLocation` | `package_mashaer_details.mashaer_location_id` | the 11 `FACT_FIELDS` |
| `note_templates` | `NoteTemplate` | `package_notes.note_template_id` | note_type, `title ← heading`, content, is_important |
| `itinerary_templates` | `ItineraryTemplate` | — (copied, never linked) | — |
| `package_templates` | `PackageTemplate` | `source_package_id` (information only) | — |

Notes on the table choices:

- **`hotels` columns added:** `location` (makkah \| medinah \| aziziya \| mina \| arafat \| other), `address`, `website_url`, `map_url`, `notes`.
- **Note templates, `title` vs `heading`:** `title` is the admin's name for finding a note; `heading` is the optional customer-facing heading. This keeps "Ticket note" out of the public page, while "Makkah (similar)" still has its heading.
- **Every library table has `is_active`.** Archiving is `is_active = false`; there are no soft deletes.
- **All link columns are `nullOnDelete`.** Deleting a library record (only allowed while unused) can never cascade into package data.

### 2.2 Package columns

| Column | Purpose |
|---|---|
| `internal_notes` (text, `$hidden`) | Admin-only. Read by nothing public. Hidden from serialisation. |
| `archived_at` (timestamp) | Archive without widening the `status` enum. Archiving also sets `status = draft` and `is_featured = false`, so every existing `published()` query already hides it. |
| `social_image` | `og:image` on the package page (falls back to `cover_image`). |

### 2.3 Other schema changes

- `package_mashaer_details.location` enum widened to include `muzdalifah`. On SQLite this rebuilds the table through `change()`; the table has no children. On MySQL it is `MODIFY`.
- `admin_activities` — `user_id`, `action`, `subject_type`/`subject_id`, `description`.

## 3. Code map

```
app/Support/Packages/
  PackageFormState      one array shape for a package (builder input = template payload = snapshot)
  HajjPackageWriter     saves nested content from that shape, in one transaction
  PackageCompleteness   publishing rules + error-key → builder-step mapping
  PackageDuplicator     copy into a new draft (content, links, and image files)
  PackageTemplates      save-as-template, apply-to-draft (refuses published)
  PackagePreview        signed, expiring preview URL
  RoomTypes             named room types ↔ sharing_type/occupancy/display_label
app/Support/Library/
  LibraryType           one section's definition: fields, rules, columns, filters, usage, push
  LibraryRegistry       the nine sections
  LibraryBackfill       builds the library from live packages; idempotent
  PackageBuilderData    the builder's pick-lists (active records + records a package already links)
app/Support/HajjPackagePage   data for the Hajj detail view, shared by the public page and the preview
app/Http/Controllers/Admin/
  HajjPackageController       listing, builder, quick actions, duplicate, preview, content JSON, templates
  PackageTemplateController   template CRUD in the builder's "template mode"
  LibraryController           every library section
  DashboardController, HelpController
resources/views/admin/hajj-packages/
  form.blade.php  steps/*.blade.php  rows/*.blade.php  partials/{option-group,modals}.blade.php
resources/js/admin.js                confirm dialog, loading state, counters, filters, image previews
resources/js/admin/package-builder.js
resources/scss/_admin-ui.scss
```

### 3.1 `PackageFormState` — why one shape

Four different paths all move a whole package's content, and all use the builder's own input names:

- The builder renders from the shape.
- `HajjPackageRequest` validates the shape.
- `HajjPackageWriter` saves it.
- A template stores it; duplication, apply-template and the "copy from another package" JSON endpoint read it.

Because there is one shape, a template payload, a validated request and a snapshot of a live package are interchangeable. A round-trip test (§8) proves that `fromPackage → HTTP save → fromPackage` is lossless for all 12 brochure packages.

`forTemplate()` is the identity boundary. It keeps content and basic fields and drops:

- `code`, `slug`, `name`, `status`, `is_featured`, `sort_order`
- SEO, media and `internal_notes`

The content JSON endpoint uses the same boundary, so copying from another package can never carry its identity, photos or private notes.

### 3.2 `HajjPackageWriter`

This is the previous controller sync logic, moved and kept to the same guarantees:

- **One transaction.** The delete-then-recreate of ten tables cannot leave a live package with its prices half-deleted (FINAL_CODE_REVIEW_HAJJ_REDESIGN C-1).
- **Option codes** are resolved case-insensitively and stored in capitals. `HajjPackageRequest::validateOptionCodes` rejects unknown or duplicate codes before the writer runs (H-2).
- **A literal price `0` keeps its currency** (M-1).
- **Library fill-in.** A row that names a library record but leaves its fields empty is filled from that record. The builder copies values itself; this is for templates and any caller that sends only the link.
- **Inclusions and exclusions** come as rows (with links and order) or as the legacy one-per-line text. The same sentence twice is saved once.
- **Room rows** with only a label get a key made from it (`Quint Sharing` → `quint_sharing`).
- `starting_price` is recomputed from the cheapest available USD room.

Media stays in the controller, because it needs the uploaded files. The old C-2 rule is kept: an empty file input on an existing row means keep the photo.

## 4. Saving, status and publishing

The builder's buttons send `_intent`. `HajjPackageRequest::prepareForValidation` derives `status` from it:

| Intent | Status after save | Redirect |
|---|---|---|
| `draft` | draft | edit page, "Draft saved" |
| `publish` | published — **only if `PackageCompleteness::problems()` is empty** | edit page |
| `save` | unchanged (new package → draft) | edit page |
| `continue` | unchanged | edit page, next step |
| `preview` | unchanged | the signed preview |
| *(none — legacy posts)* | the posted `status` | edit page |

**Publishing rules** (`PackageCompleteness::problems`). The package must have:

- a title, a code and a number of days;
- at least one hotel;
- at least one available room type with a price;
- a price for **every** hotel option — unless some price applies to every option.

All 12 brochure packages pass (tested). A failed rule becomes a validation error keyed `publish.{step}`. `stepForField()` maps every error key (including `room_options.3.price_usd`) to its step, so the error summary links to the right step and the step list marks it.

A **draft may be incomplete**; only its title is required. The web address (`slug`) is generated from the title and made unique across all packages, including soft-deleted ones.

## 5. Whole-package operations

| Operation | Route | Guarantees |
|---|---|---|
| Quick publish / unpublish / feature / unfeature / archive / restore | `PATCH hajj-packages/{package}/quick/{action}` | Publish runs the completeness rules. Archive takes the package off the site and un-features it. Restore returns it as a draft. Every action is recorded. |
| Delete | `DELETE hajj-packages/{package}` | Refused for a published package. Soft delete. |
| Duplicate | `POST hajj-packages/{package}/duplicate` | New draft with `CODE-COPY`, `-COPY-2`…, a unique slug and "(Copy)" in the name. Content goes through the writer, so option rows point at the copy's own options. Library links are kept. Images are copied as new files, so deleting the copy's photo can never delete the original's. The original is untouched (tested field by field). |
| Save as template | `POST …/save-as-template` | Stores `forTemplate()`, which carries no identity and no internal notes. |
| Apply template | `POST …/apply-template` | Replaces content sections and fills empty basic fields. Keeps title, code, status, photos and internal notes. **Refused for published packages**, and nothing is changed. |
| Content JSON | `GET …/content` | Admin-only. Same boundary as templates. Powers "copy from another package". |
| Preview | `GET …/preview` + `signed` | Requires an admin session **and** a valid, unexpired signature (60 minutes). Renders the real public view through `HajjPackagePage::data()` with a preview banner, `noindex` meta and an `X-Robots-Tag` header. Guest → login; unsigned or expired → 403. |

Every builder action first checks `ensureHajj()`: route-model binding knows nothing about categories. This mirrors the existing guard in the generic Umrah/Tourism controller.

## 6. The reusable library

One `LibraryController` drives all nine sections through `LibraryRegistry`. Each `LibraryType` declares:

- **fields** (type, label, help, options, required) — used to render the form **and** build the validation rules;
- **list columns and filters, and searchable columns**;
- **`fixed` attributes** — e.g. `type = inclusion`. The query is always scoped to them, so `/library/inclusions/{id}` can never open or delete an exclusion;
- **`usageRelation`** — distinct live packages using a record, for the list count, the usage page and delete protection;
- **`syncMap`** — which columns "Update N packages" copies into the linked rows.

| Rule | Where |
|---|---|
| A record used by any live package cannot be deleted — archive instead | `LibraryController::destroy` |
| Archived records leave the builder's pick-lists; a record a package already links still shows for that package, labelled "(archived)" | `PackageBuilderData` |
| Saving a library record never changes packages; the success message says how many still show the previous details | `LibraryController::update` |
| "Update N packages" names how many are live before running, and is recorded | `usage.blade.php`, `LibraryType::pushToPackages` |
| Hotels get `city` and a unique `slug` automatically | `fillDerivedColumns` |
| JSON responses for adding a hotel or journey template from inside the builder | `store()` with `Accept: application/json` |

## 7. Migration and backfill

`2026_09_14_200100_backfill_package_library` calls `LibraryBackfill::run()`. `PackageLibrarySeeder` calls the same thing, last in `DatabaseSeeder` — on a fresh install the migration runs before any package exists.

What it does, only for live (not deleted) Hajj packages:

1. **Hotel locations.** Sets `hotels.location` from the free-text `city`, but only where the column still holds its default.
2. **Internal remarks.** Moves brochure-audit remarks (exact prefixes `Brochure inconsistency preserved as printed`, `Brochure names this`) out of `description` and `package_notes` into `internal_notes`, word for word, never twice. This is the only step that changes what visitors see, deliberately (audit §3.1).
3. **Hotels.** Links each accommodation row to a hotel matched by normalised name. The normalisation lower-cases, drops parentheses, stars, "similar" and a trailing city name. "Dar Al Tawhid Intercontinental" therefore matches the seeded "Dar Al Tawhid Intercontinental Makkah". A hotel is created only when no match exists.
4. **Everything else.** Links meal plans, transport, services, upgrades, Mashaer and notes to a library record with **identical values** (prices compared numerically), creating one only when none exists.
5. **Superseded hotels.** Archives the two seed hotels the current brochure replaced (Taibah Front Medinah, Abraaj Tower / Swiss Maqam), only if unused.

**Idempotent.** It touches only rows whose link is empty and reuses identical records. A second run returns an empty result, and a full re-seed leaves counts unchanged (tested).

**Result on the local copy of production-equivalent data** (12 packages):

| Hotels | Meal plans | Transport | Included services | Upgrades | Mashaer | Notes |
|---|---|---|---|---|---|---|
| 13 (2 archived) | 4 | 6 | 27 | 8 | 2 | 8 |

- 4 remarks moved to internal notes.
- 0 unlinked rows remain.

**Rollback.** `down()` puts the moved remarks back into `description` before the tables migration drops `internal_notes`, so a rollback loses nothing. Verified with a migrate → rollback → migrate cycle on a copy of the local database.

## 8. Front end

**`admin.js`** (all admin pages):

- The `data-confirm` dialog.
- Submit loading state. The button is disabled on the next tick so the `_intent` value still posts.
- Character counters, auto-submitting filter selects and image previews.

**`package-builder.js`**. Everything it adds is progressive: every existing row is rendered by Blade with real input names, and saving is a normal POST. It handles:

- **Steps.** `?step=` in the URL; a required box on a hidden step opens that step before the browser reports it.
- **Repeaters.** Rows come from `<template>` elements rendered by the same Blade partial as saved rows, and support add, remove, move and duplicate. Reordering is DOM order; PHP keeps submitted order.
- **Option boxes** for room prices and hotels. Each option row has a stable uid, so renaming a letter re-labels its box and rewrites its rows' hidden `variant_code`. The journey plan shows a second stay column once there are two options.
- **Pickers.** Hotel (filtered by city), meal plan and Mashaer fill the row. A generic "add saved" dialog greys out items already in the package.
- **"Copy from another package"** replaces the ticked sections from the content JSON. Options are always copied with prices or hotels.
- **Journey tools** — add day (next date), renumber, fill dates, apply template, save as template.
- **Progress marks** mirroring the publishing rules.
- **Unsaved changes.** A flag, a leave-page warning, and a copy in `localStorage` keyed by package and its last-saved timestamp. Restore is offered only when the saved version has not changed since, and never after a failed save (the form already shows what was typed).

**Layout.** Row layouts adapt to the width of the builder column through CSS container queries: one line, a compact two-line row, or a stacked card. A wide screen beside two sidebars can still give the form a narrow column; no row can scroll the page sideways.

## 9. Security and privacy

- **Access.** All new routes sit in the existing `auth` + `admin` group, so all mutations are CSRF-protected. No state changes on GET.
- **Validation.** Request objects validate every nested field, and the controller passes only `validated()` data to the writer. Library ids use `exists:` rules.
- **Internal notes** appear only on the admin edit page. The public view, presenter, AI context and knowledge indexer never read them, and they are hidden from serialisation.
- **Preview.** Signed, expiring, and requires an admin session.
- **Audit trail.** Every destructive or visibility-changing action is recorded with the user.

## 10. Known limits

See [`ADMIN_UX_AUDIT.md` §6](../audits/ADMIN_UX_AUDIT.md#6-remaining-limitations-and-follow-ups): per-day stays for a third option, single photos for library records, Aziziya facilities not in the library, and no finer role split.
