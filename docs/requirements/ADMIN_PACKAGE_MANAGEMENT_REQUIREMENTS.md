# Admin Package Management — Requirements and Traceability

**Issue:** [#10](https://github.com/asimcreative/universal-brothers/issues/10) · **Goal:** a professional admin panel in which a non-technical administrator can create and maintain Hajj packages without knowing anything about databases, JSON or Laravel.

Each requirement below links to where it is built and the test that proves it:

- **PB** — `tests/Feature/Admin/PackageBuilderTest.php`
- **PL** — `tests/Feature/Admin/PackageLibraryTest.php`
- **BF** — `tests/Feature/PackageLibraryBackfillTest.php`
- **DB** — `tests/Feature/Admin/DashboardTest.php`
- **HM** — `tests/Feature/Admin/HajjPackageManagementTest.php`
- **E2E** — `tests/e2e/package-builder.spec.js`
- **ADM** — `tests/e2e/admin.spec.js`

Status key: ✅ built and tested · ◐ built with a documented limit.

---

## 1. Admin shell and design

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R1.1 | Grouped navigation with active states, same on desktop and phone | ✅ | `admin/partials/nav.blade.php` | PL `every_library_section_appears_in_the_menu`; ADM mobile nav test |
| R1.2 | Topbar: breadcrumb, title, subtitle, actions, view-site, help, user menu | ✅ | `layouts/admin.blade.php` | All admin page tests |
| R1.3 | One styled confirmation dialog for destructive actions | ✅ | `admin.js` `initConfirmDialog`; 13 forms converted | ADM (all delete tests use `clickAndConfirm`) |
| R1.4 | Success/error notifications with icons and live-region roles | ✅ | `admin-alert` | E2E asserts `role=status` messages |
| R1.5 | Loading state; no double submits | ✅ | `admin.js` `initSubmitLoading` | Manual; E2E saves |
| R1.6 | Empty states that say what to do next | ✅ | Listing, library, templates, rows | PB listing filter test |
| R1.7 | Responsive, no sideways scrolling on phones | ✅ | Container-query row layouts | E2E `fits a phone screen` |
| R1.8 | Readable contrast | ✅ | Neutral outline buttons in the admin | Audit §3.2 S6 |

## 2. Dashboard

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R2.1 | Real counts: Hajj total, published, draft, featured; enquiries total and unread; FAQs; awards; affiliations | ✅ | `DashboardController` | DB `the_numbers_are_real_database_counts` |
| R2.2 | Recent package updates and recent enquiries | ✅ | `dashboard.blade.php` | DB |
| R2.3 | Quick actions (create or manage packages, FAQs, awards, affiliations, enquiries, settings) | ✅ | `dashboard.blade.php` | ADM test 16 |
| R2.4 | Cards clickable to filtered lists | ✅ | Stat cards link to filtered listings | — |
| R2.5 | What needs attention | ✅ | Live packages failing publishing rules; unanswered enquiries | DB `points_at_live_packages_that_are_no_longer_complete` |
| R2.6 | No invented statistics | ✅ | Every figure is a query | DB |

## 3. Package builder

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R3.1 | Logical steps: basics, setup, options, prices, hotels, journey, Mashaer, transport and meals, services, additional options, notes, media and SEO | ✅ | `steps/*.blade.php` | PB round-trip; E2E journey |
| R3.2 | Progress indicator and completion status per step | ✅ | Step list + progress bar | E2E asserts `is-done` |
| R3.3 | Required marks, help text, plain labels (no IDs, slugs or JSON) | ✅ | All steps | Audit §2 |
| R3.4 | Save draft, save and continue, save and preview, publish; sticky actions | ✅ | `_intent` | PB `save_keeps…`, `preview_intent…`, `publish_intent…`, `draft_intent…` |
| R3.5 | Draft may be incomplete; publishing is refused until complete, naming the step to fix | ✅ | `PackageCompleteness` | PB `incomplete_draft…`, `publishing_an_incomplete…`, `every_hotel_option_needs_a_price…`; E2E |
| R3.6 | Unsaved-changes warning; safe auto-save | ◐ | Leave-page warning + browser copy with restore | E2E `unsaved changes are kept…` — the copy never includes chosen photo files |
| R3.7 | Conditional fields (Aziziya details only when Aziziya is included or optional; option boxes only with options; extra price only when not included) | ✅ | `package-builder.js` | E2E |
| R3.8 | Validation errors kept with what was typed | ✅ | `PackageFormState::withOldInput` | PB `publishing_an_incomplete…` asserts typed title survives; HM media test |
| R3.9 | Web address generated from the title | ✅ | `HajjPackageRequest::prepareForValidation` | PB `…gets_a_web_address_from_its_title` |

## 4. Options, prices and hotels

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R4.1 | Package A/B/C (and more), each with its own hotels and prices, never merged | ✅ | Option boxes; `variant_code` per row | PB `options_a_b_and_c…`; E2E |
| R4.2 | Room types by name: quad, triple, double, sharing room, twin, single, other | ✅ | `RoomTypes`, `rows/room.blade.php` | PB `a_room_named_only_by_its_label…`; ADM test 28 |
| R4.3 | Available / not available per room type | ✅ | Switch | PB completeness tests |
| R4.4 | PKR, SAR and USD prices, clearly labelled; empty stays empty | ✅ | Three inputs | PB `options_a_b_and_c` asserts SAR empty |
| R4.5 | Supplements such as Kaaba view | ✅ | Additional options step; Aziziya supplements in Hotels | PB library links test |
| R4.6 | Pick a saved hotel; add a new hotel without leaving | ✅ | Hotel picker; new hotel dialog | PB `picking_a_saved_hotel…`; PL JSON test; E2E |
| R4.7 | Package-specific overrides without changing the saved record | ✅ | Copy-and-link | PB `…leaves_the_saved_hotel_alone` |
| R4.8 | Makkah, Madinah, Aziziya, Mina and Arafat accommodation; nights; distance; notes | ✅ | `rows/hotel.blade.php` | PB round-trip |

## 5. Journey, Mashaer, transport, meals, services, upgrades, notes, media

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R5.1 | Itinerary builder: day number, English date, Islamic date, city, stay, description | ◐ | `rows/day.blade.php` | E2E journey test — stay text exists for two options only; no separate per-day transport field (audit §6) |
| R5.2 | Add, remove, duplicate and reorder days; renumber; fill dates | ✅ | `package-builder.js` | E2E |
| R5.3 | Copy itinerary from another package; apply or save journey templates | ✅ | Copy dialog; journey templates | E2E copy test; PL `a_journey_template_keeps_its_days_in_order` |
| R5.4 | Mina, Arafat, Muzdalifah from saved arrangements with per-package changes | ✅ | `steps/mashaer.blade.php` | PB `muzdalifah_is_saved_and_shown…`, library links test |
| R5.5 | Transport and meal plans from saved content | ✅ | Picker; "apply to all hotels" | PB library links; E2E |
| R5.6 | Saved inclusions and exclusions; custom items; reorder; no duplicates | ✅ | Service rows; picker; writer dedupe | PB `the_same_included_service_is_saved_once`; E2E |
| R5.7 | Saved and package-specific upgrades with price, currency, conditions | ✅ | `rows/upgrade.blade.php` | PB |
| R5.8 | Reusable notes, package notes, and internal notes clearly separated | ✅ | Notes step; dashed "administrators only" card | PB `internal_notes_never_reach…` |
| R5.9 | Internal notes never public or in AI answers | ✅ | `internal_notes` column, `$hidden` | PB `internal_notes_never_reach…`, `…brochure_audit_remarks_are_internal…` |
| R5.10 | Main photo, social image, gallery with alt text and captions; previews; upload limits | ◐ | `steps/media.blade.php` | HM media tests — hotel and itinerary photos are one photo per library record; itinerary rows have no photos |
| R5.11 | SEO title and description with counters and a search preview | ✅ | `steps/media.blade.php` | ADM test 25 |

## 6. Reusable library

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R6.1 | Hotels & accommodation, transport, meal plans, inclusions, exclusions, upgrades, Mashaer, notes, journey templates, package templates | ✅ | `LibraryRegistry`, `PackageTemplateController` | PL data provider (9 sections); PB template tests |
| R6.2 | Search, filters, status, create, edit, duplicate, archive, restore | ✅ | `library/index.blade.php` | PL data provider |
| R6.3 | Usage count and "where it is used" | ✅ | `usage_count`; usage page | PL `usage_counts_protect…` |
| R6.4 | Deleting a record in use is refused; archive instead | ✅ | `LibraryController::destroy` | PL `usage_counts_protect…` |
| R6.5 | Updating shared information safely | ✅ | Explicit "Update N packages" | PL `editing_a_library_record_does_not_change_packages_until…` |
| R6.6 | Existing repeated content turned into library records | ✅ | `LibraryBackfill` | BF |

## 7. Duplication, templates, copying

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R7.1 | Duplicate: new code, draft, all content, links, options remapped, safe media, original untouched | ✅ | `PackageDuplicator` | PB `duplicating_copies_everything…`, `duplicating_twice…`; E2E |
| R7.2 | Save as template; create from template; edit templates; archive templates | ✅ | `PackageTemplates`, `PackageTemplateController` | PB template tests |
| R7.3 | Apply template to a draft only | ✅ | `applyToDraft` | PB `applying_a_template…`, `a_template_is_never_applied_to_a_published_package` |
| R7.4 | Copy hotels, prices, itinerary, inclusions, exclusions, transport, meals, notes, upgrades from another package, with confirmation | ✅ | Copy dialog + content JSON | PB content endpoint test; E2E copy test |

## 8. Listing, preview, safety

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R8.1 | Search by code or title; filter by status, series, featured, arrival, Aziziya, days; sort | ✅ | `HajjPackageController::index` | PB `the_listing_filters…` |
| R8.2 | Quick publish, unpublish, feature, archive, restore; duplicate; preview; edit | ✅ | `quick` | PB `quick_actions…`, `quick_publish_refuses…` |
| R8.3 | Signed, temporary, admin-only draft preview in the public design | ✅ | `preview` + `signed` | PB `a_draft_preview_needs…`; E2E |
| R8.4 | Published packages cannot be deleted | ✅ | `destroy` | HM `a_published_hajj_package_cannot_be_deleted` |
| R8.5 | CSRF on all actions; auth on all pages | ✅ | Admin route group | PB `new_admin_pages_require_login`, content endpoint test |
| R8.6 | Audit log | ✅ | `admin_activities` | PB `quick_actions…`; PL push test; DB activity test |
| R8.7 | Existing public URLs, route names and data preserved | ✅ | Additive migrations; `slug` unchanged | PB round-trip; BF public pages test; full regression suite |

---

## 9. How an administrator creates a package

The full guide is shown in the admin at **Help** (`/admin/help`). In short:

1. **Hajj Packages → Add Hajj Package**, or **From a template**, or open a similar package and choose **More → Duplicate**.
2. **Basic information** — title, code (e.g. UB025), number of days. "Length as shown" fills itself.
3. **Package setup** — Madinah first or Makkah first; shifting or not; Aziziya included, optional or not.
4. **Hotel options** — "Yes" if customers choose between hotels. Option A and B appear; name each after its hotel.
5. **Room prices** — in each option's box press **Add Quad, Triple & Double**, then type the prices you have. Leave a currency empty rather than guessing. Untick **Available** for a sold-out room.
6. **Hotels** — **Add hotel** in the right box, pick from the list. Missing? **New hotel**.
7. **Journey plan** — apply a journey template or **Copy from another package**, then **Fill dates** from the first day.
8. **Mina, Arafat & Muzdalifah; Transport; Included & not included; Additional options; Notes** — use **Add saved…** for anything standard; **Add your own** for anything unique to this package.
9. **Save draft** at any point. **Save & preview** to see it as visitors will.
10. **Publish** when the steps are ticked. If something is missing, the message links to the step.

## 10. How to update shared information safely

- **One package only.** Change it inside that package. The saved hotel, note or transport leg is not touched.
- **Every package that uses it.**
  1. Edit it under **Reusable Content**. Saving does not change any package yet; the message says how many still show the old details.
  2. Open **Where it is used**. Check the list (live packages are marked).
  3. Press **Update N packages**. Any change made to it inside those packages is replaced.
- **No longer offered.** **Archive** it. It disappears from the builder; packages that use it keep their details. It cannot be deleted while any package uses it.

## 11. How package-specific overrides work

Picking a saved item copies its values into the package and remembers the link. Editing those values in the package is the override:

- It shows only on that package.
- It keeps the link, so the item still counts as "used".
- It lasts until someone chooses **Update N packages** on that item.

To break the link entirely, remove the row and add your own.
