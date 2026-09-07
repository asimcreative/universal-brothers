# Admin/CMS UI Design System

A complete redesign of the admin panel (2026-08-31) — the public site had already gone through its own visual redesign (see `UI_DESIGN_SYSTEM.md`); this document covers the equivalent pass for the CMS. No backend logic, route, model, migration, or policy was changed to make this happen — one CRITICAL data-integrity gap was found and fixed along the way (see §"Critical fix" below), which is the one place backend code changed.

## Scope

Every admin screen: login, the layout shell (sidebar/topbar), the dashboard, all 14 listing pages, all 14 create/edit forms, and the Hajj package form specifically (the most complex single form in the project).

## Brand direction — distinct from, not copied from, the public site

The public site's navy (`$ub-navy #101b45`) + gold (`$ub-gold #c9a227`) identity carries over as the admin's own palette, but the admin is deliberately calmer and more utilitarian: a light neutral-gray canvas (`#f4f5f7`) instead of the public site's cream/white sections, a dark navy sidebar instead of a navy hero, and no animation system at all (a CMS used daily for data entry has no use for scroll reveals or hero motion). All admin-specific rules live in a new `resources/scss/_admin.scss`, imported into the same compiled `app.scss` the public site uses, but scoped entirely under a `.admin-body` class (set on `<body>` in `layouts/admin.blade.php` and the login page) so none of it can leak into public pages sharing the same stylesheet — verified directly in the compiled CSS output (`.admin-body .card-header`, never a bare `.card-header`).

## Layout shell (`layouts/admin.blade.php`)

**Desktop**: a fixed 264px navy sidebar (brand mark, grouped navigation, a footer label) and a sticky white topbar (breadcrumb, page title, optional subtitle, primary action button, a user-avatar dropdown with name/role/logout) above a light-gray content area.

**Mobile**: the sidebar becomes a Bootstrap offcanvas, opened by a button in the topbar (`aria-label="Open menu"`) rather than a separate dark mobile-only bar the previous version used — the toggle button's accessible name is what the "admin panel is navigable on a mobile viewport" Playwright regression test now targets (its old CSS-class-based locator no longer matched anything real after this restructure).

**Navigation groups** (real routes only — no invented labels): Content (Pages, News, FAQs, Testimonials, Media Gallery, Homepage Sliders), Packages (Hajj Packages, Umrah & Tourism Packages, Categories & Series), Company (Awards, Affiliations, Offices, Site Settings), Leads (Inquiries — with a live unread-count badge), and System (Users & Roles, super-admin only). "Umrah & Tourism Packages" is the honest label for the generic `admin.packages.*` resource, which was never actually a third category on its own — it's whatever isn't Hajj.

## Dashboard

Expanded from 4 stat cards to 8, all backed by real queries (`DashboardController`): published/draft packages, new/total inquiries, active testimonials, and — new — Hajj/Umrah/Tourism package counts and active media items, plus "Recently Updated Packages," "Recent News," and a "Quick Actions" panel (direct links to the 4 most common create actions). No invented or decorative metric was added — every number traces to a real Eloquent query.

## Forms

Every create/edit form got: a `.admin-form-actions` sticky bottom bar (Save/Cancel always reachable without scrolling back up on a long form), `required-mark` asterisks on genuinely required fields, and consistent card-per-section structure with an icon in each `card-header`. Native `confirm()` dialogs remain the delete-confirmation mechanism (already accessible, already reliable — not replaced with custom modal JS for its own sake).

### The Hajj package form — the most important form in the project

Preserves 100% of the existing 18-section structure, every `name=` attribute, every `@php` data-hydration block, and the entire repeater JS engine (`data-repeater-add`/`data-repeater-template`) completely unchanged — verified by running the full `HajjPackageManagementTest` suite (12 tests covering create, update, nested-data replacement, duplicate/unresolvable variant-code rejection, media persistence across edits, and the zero-price-upgrade currency edge case) against the rewritten markup with zero changes needed to any test.

What changed is purely presentational: a sticky left-hand section navigation (14 sections — Basic Information, Package Variants, Accommodation, Aziziya, Mina, Arafat, Room Type Pricing, Day-by-Day Itinerary, Transportation, Inclusions & Exclusions, Optional Upgrades, Notes, Media, SEO) with an `IntersectionObserver` that highlights the section currently in view while scrolling, a mobile "Jump to section" `<select>` in place of the sidebar nav below `lg`, and an icon + business-friendly heading on every section (e.g. "Day-by-Day Itinerary" rather than the underlying `itinerary_days` table name). A staff member can now see at a glance which of the 14 sections they're in and how much of the form remains, on a form that previously had no orientation at all beyond scroll position.

### Critical fix found via this redesign, not before it

Reviewing a screenshot of the redesigned "Umrah & Tourism Packages" listing surfaced real Hajj packages (UB001, UB003, ...) mixed into it. Tracing this found a genuine, previously-undiscovered CRITICAL gap: the generic `Admin\PackageController` had no category scoping at all. `Route::resource('packages', ...)`'s implicit `{package}` model binding resolves *any* package by ID — Hajj included — and that controller's `edit()`/`update()` build a form and run a sync (`priceTiers`, not the `roomOptions` relation Hajj packages actually use) that unconditionally deletes and replaces `itineraryDays`/`inclusions`/`exclusions`. A real Hajj package reached through this route would have its real itinerary and inclusions destroyed and replaced with whatever the mismatched generic form happened to submit, while its variants/accommodations/Aziziya/Mashaer data was left orphaned. This existed since the Hajj-specific admin surface was first added and was never caught by any prior audit, because each admin surface (`PackageController` vs. `HajjPackageController`) was only ever tested reachable through its own intended route — never checked for cross-reachability.

**Fixed** with defense in depth: `PackageController::index()`/`create()` now exclude the Hajj category entirely (`nonHajjCategories()`), and `edit()`/`update()` redirect any Hajj package to the real `admin.hajj-packages.edit` route instead of rendering the wrong form — a self-healing redirect, not a dead end. A new regression test (`test_generic_controller_never_reaches_a_hajj_package`) proves the listing excludes Hajj, the edit route redirects, and a crafted update POST against the generic route cannot touch the package's real itinerary/inclusions at all.

## Accessibility

Every purely decorative icon across the redesigned admin panel (`<i class="bi ...">` paired with adjacent visible text — stat cards, empty states, buttons, nav links, section headers) now carries `aria-hidden="true"`, closing a gap the redesign itself introduced (icons are new; screen readers have no reason to announce a font-glyph a sighted user reads as pure decoration next to its own label). The login page's show/hide-password toggle has a real `aria-label`/`aria-pressed` pair that updates on click. All form fields keep proper `<label for=...>` association; the one new potential ambiguity this introduced — the toggle button's "Show password" accessible name substring-matching `getByLabel('Password')` — was fixed with `{ exact: true }` in the two tests that needed it, not by weakening the button's own accessible name.

## What was deliberately not changed

No route, controller signature (beyond the one critical-fix guard above), model, migration, or policy. No public-site file. The generic `admin.packages.*` `store()` action is not guarded against a Hajj category id — it's unreachable through the UI (the create-form dropdown excludes Hajj) and creates a new record rather than corrupting existing data, so it's a lower-severity gap than `edit`/`update` and was left as a disclosed, lower-priority item rather than expanded scope beyond the actual data-loss risk.
