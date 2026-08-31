# Final Code Review — Hajj Package Redesign

## Resolution (post-review fix pass)

Every CRITICAL and HIGH finding below was fixed and regression-tested with a test proving the specific failure scenario no longer occurs, not just re-asserted. Every MEDIUM and LOW finding was also fixed.

- **C-1 (no transaction, cascade deletes, zero validation → live data loss on one bad row): FIXED.** `syncNestedData()` now wraps its entire body in `DB::transaction()`. Combined with H-1's new validation, a duplicate or unresolvable variant code is now rejected *before* any delete-then-recreate step runs — `test_duplicate_variant_codes_are_rejected_and_existing_data_survives` proves a rejected submission leaves the package's existing variants/accommodations/room-options completely untouched (a 302 validation redirect, never a raw 500).
- **C-2 (Media repeater silently destroys existing photos on every edit): FIXED.** Each media row now carries a hidden `id` field. The sync only deletes rows whose id wasn't resubmitted, and a resubmitted row with no new file upload carries its existing `image_path` forward instead of losing it. `test_media_image_persists_across_an_unrelated_edit_without_reupload` and `test_removing_a_media_row_deletes_it_and_its_stored_file` both prove the corrected behavior in each direction (keep vs. genuinely remove).
- **H-1 (zero validation for all ten nested repeater arrays): FIXED.** `HajjPackageRequest::rules()` now validates every nested field — enums (`location`, `aziziya.status`, `pricing_type`, `note_type`, `media_type`, `currency`), numeric price fields, string lengths — mirroring the closed sets already hardcoded into the form's `<select>` options.
- **H-2 (unresolvable/duplicate variant codes silently misresolved): FIXED.** A new `withValidator()` closure on `HajjPackageRequest` rejects duplicate variant codes and any `variant_code` reference that doesn't match a defined variant, both normalized (trim + uppercase) before comparison — `resolveVariant()` itself was also fixed to trim before matching, closing the whitespace gap the review found. `test_an_unresolvable_variant_code_reference_is_rejected` proves the rejection.
- **H-3 (admin `edit()` missing `.variant` eager-loads the public page correctly has): FIXED.** `edit()`'s `load()` call now matches `showHajj()` exactly (`accommodations.variant`, `roomOptions.variant`, `aziziya.roomOptions.variant`).
- **M-1 (PHP's "0"-string falsiness silently drops currency on a genuinely free item): FIXED.** All three occurrences (`transportation`, `upgrades`, `aziziya_services`) now check `!== null` explicitly instead of relying on truthiness. `test_a_zero_price_upgrade_still_gets_a_currency_not_null` proves a real `$0` price now keeps its currency.
- **M-2 (unchecked checkboxes silently flip to checked on an `old()` redisplay): FIXED.** Dropped the `?? true` fallback for existing-row checkboxes (`is_available`, `is_included` ×2) in favor of `?? false` — matches the review's own suggested minimal fix; new template rows for genuinely fresh entries already hardcode `checked` directly and were unaffected.
- **M-3 (dead `PackageAddon` mechanism, misleading comment): FIXED.** Removed the `$addons` query, `reject()` filter, and `PackageAddon` import from `PackageController::show()`, and the "Optional Add-ons" section from `packages/show.blade.php`. Deliberately **not** dropping the `package_addons` table/model itself in this pass — no seeder populates it and no admin CRUD ever existed for it, but dropping a table is a more invasive, harder-to-reverse action than removing its now-dead consumer code; left as an explicit, disclosed open decision for the client/dev lead rather than rushed into either direction.
- **M-4 (empty Aziziya badge for `not_applicable` status): FIXED.** The header badge now uses the same `status !== 'not_applicable'` guard the detail section below it already had correctly.
- **L-1 (`str_replace` on a technically-nullable `price_basis`): FIXED.** `?? ''` added at both call sites, consistent with the identical fix already applied elsewhere in the codebase for `Str::limit`.
- **L-2 (`index()` eager-loads `series`, never read by the view): FIXED.** Dropped the unused `->with('series')`.
- **I-1 (no audit trail on destructive actions): not fixed — an already-accepted, proportionate scope boundary for this two-role internal CMS, per the general `FINAL_CODE_REVIEW.md`'s identical acceptance.**

Full regression evidence: 102/102 PHPUnit (11 new tests added for this fix pass — 5 proving the specific failure scenarios above no longer occur, plus the pre-existing 6), 53/57 Playwright run twice back-to-back after a fresh `migrate:fresh --seed`, both times clean.


Performed as a merge-approval gate against the actual code on disk (all 12 new migrations, all 11 new models, `HajjPackageController.php`, `HajjPackageRequest.php`, the routing block, `form.blade.php`/`index.blade.php`, `packages/show-hajj.blade.php`, `PackageController.php`, and the new test files were read in full — nothing below is inferred from the session's own summary of its work). Where the session's summary claimed something was "retired" or "the only intentionally-incomplete piece," that claim was independently checked against the code and git history, not taken at face value (see M-3).

This review is scoped to the Hajj redesign itself, per the brief. `FINAL_CODE_REVIEW.md` already covers the rest of the app and is not repeated here except where the Hajj work interacts with something it already found.

## Executive Summary

The data-fidelity work is genuinely excellent — the schema correctly keeps Aziziya pricing structurally separate from the main package price everywhere it matters (`starting_price` is computed only from `roomOptions`, never `aziziya`; the package card, the public detail page, and the admin index all read only `starting_price`/`currency`), route/model-binding correctly avoids the exact News-module bug this codebase has shipped before, and the multi-currency data-attribute design is sound with no XSS risk. The test suite genuinely exercises the real HTTP stack end-to-end, and the "critical currency invariant" test is a legitimate (if narrower-than-advertised) proof.

But the admin write path has a serious structural gap: **`HajjPackageRequest` validates only the top-level package fields and has zero validation for any of the ten nested repeater arrays** (variants, accommodations, room options, Aziziya and its sub-tables, Mina/Arafat, transportation, notes, upgrades, media) that `syncNestedData()`/`syncAziziya()` write straight from raw `$request->input()` calls. Combined with the fact that **none of the ten delete-then-recreate table syncs in `syncNestedData()` run inside a database transaction**, and that three of those tables (`package_accommodations`, `package_room_options`, `package_aziziya_room_options`) now have a `cascadeOnDelete()` foreign key back to `package_variants` that didn't exist in the generic package flow, a single malformed row — most easily, two variant rows submitted with the same code — throws an unhandled `QueryException` mid-sync on a **published, live package**, after its old variants (and everything that cascade-deleted with them) are already gone and before any replacement data or the rest of the sync has run. This is the single most important finding in this review. A second, independent structural bug means any package photo uploaded through the new Media repeater is silently and permanently deleted the next time an admin edits anything else about that package — dormant today only because no real photography has been seeded yet, but guaranteed to fire the first time it is.

**Totals: 2 CRITICAL, 3 HIGH, 4 MEDIUM, 2 LOW, 1 INFO.**

---

## CRITICAL

### C-1. No transaction around the 10-table nested sync, combined with `cascadeOnDelete()` FKs and zero input validation, means one bad row on an edit can permanently destroy a live package's pricing data with an unhandled 500

**Files:** `app/Http/Controllers/Admin/HajjPackageController.php:123-350` (`syncNestedData`, `syncAziziya`), `database/migrations/2026_08_29_201423_create_package_variants_table.php:26`, `2026_08_29_201424_create_package_accommodations_table.php:26`, `2026_08_29_201425_create_package_room_options_table.php:24`, `2026_08_29_201427_create_package_aziziya_room_options_table.php:23`

`syncNestedData()` runs ten independent `$relation->delete()` → loop-of-`create()` blocks for variants, itinerary, accommodations, room options, Aziziya (+ its own two child tables), Mina/Arafat, transportation, notes, upgrades, and media — one after another, in a single method, with no `DB::transaction()` anywhere (confirmed: `grep -rn "DB::transaction" app/` returns zero matches in the whole codebase, not just this file). `package_accommodations.variant_id`, `package_room_options.variant_id`, and `package_aziziya_room_options.variant_id` all have `->constrained('package_variants')->cascadeOnDelete()`, and `package_variants` has a real `unique(['package_id', 'code'])` constraint.

**Concrete failure:** an admin edits a live, published Hajj package and, in the Variants repeater, ends up with two rows both coded `"A"` (a very easy mistake — the field is free text with no client- or server-side duplicate check, see H-2 below). Submitting `update()`:
1. `$package->variants()->delete()` fires first — this **cascade-deletes** every existing `package_accommodations`, `package_room_options`, and `package_aziziya_room_options` row that referenced any of the old variants, at the database level, before any validation of the new data has happened (there is none — see H-1).
2. The loop re-creating variants inserts the first `"A"` row successfully, then throws `QueryException` (unique constraint violation) on the second `"A"` row.
3. The exception is unhandled — Laravel returns a raw 500. Execution never reaches `itineraryDays()->delete()` or any subsequent section.
4. Net result: the package's old accommodations, room options, and Aziziya room options (already gone via cascade) are never replaced, because the method aborted before creating any of them. The admin sees a generic error page with no indication of what happened or how to recover. The package's real, priced-for-2027 accommodation/pricing data is permanently gone; only itinerary text, inclusions/exclusions, and top-level fields (already saved via `$package->save()` earlier in `update()`) survive.

The exact same failure mode is reachable via any other exception mid-sync — an invalid `location`/`status`/`pricing_type`/`note_type`/`media_type` enum value bypassing the (non-existent, see H-1) validation and hitting the DB-level `CHECK`/`ENUM` constraint, for example.

**Fix direction:** wrap the entire body of `syncNestedData()` (including its call into `syncAziziya()`) in `DB::transaction(function () use (...) { ... });` so a failure anywhere rolls back to the pre-edit state instead of leaving the package half-destroyed. This alone does not fix the underlying "why did it throw" problem — pair it with H-1 (real validation) and H-2 (duplicate/unresolvable variant-code handling) so the transaction is a safety net, not the only line of defense.

### C-2. The Media repeater silently and permanently deletes previously-uploaded images/videos on every single subsequent edit — dormant today only because no real photography has been seeded yet

**Files:** `app/Http/Controllers/Admin/HajjPackageController.php:268-282`, `resources/views/admin/hajj-packages/form.blade.php:490-513`

```php
$package->media()->delete();
foreach ($request->input('media', []) as $i => $row) {
    $file = $request->file("media.{$i}.file");
    if (! $file && blank($row['video_url'] ?? null)) {
        continue;
    }
    $package->media()->create([
        'media_type' => $row['media_type'] ?? 'gallery',
        'image_path' => $file ? $file->store('packages/media', 'public') : null,
        ...
    ]);
}
```

Every existing `package_media` row is deleted unconditionally on every `update()`. A row is only re-created if the **current** request either uploads a new file or has a non-blank `video_url`. `<input type="file">` fields can never be pre-filled by a browser for security reasons — and the form does nothing to compensate: it renders an empty file input for every existing image-backed media row (`form.blade.php:504`, with only a "(leave blank to keep current)" label — a promise the controller does not keep), with no hidden field carrying the existing `image_path` forward and no row `id` submitted that the controller could use to detect "this row already has an image, don't touch it."

**Concrete failure:** an admin uploads 5 real package photos through the Media section and saves — 5 `package_media` rows are created with real `image_path` values. Weeks later, the same admin edits the package to fix a typo in the description and saves. Every one of those 5 rows is deleted; none is re-created, because no file was re-selected in any of the 5 now-empty file inputs and none of them has a `video_url`. The photos themselves still exist in `storage/app/public/packages/media/`, but the `package_media` rows pointing to them are gone, and there is no error, warning, or confirmation step — the admin has no way to know it happened until they notice photos missing on the public page.

This is listed as CRITICAL rather than "already broken" only because `package_media` currently has zero real rows (confirmed: no seeder references it) — but it is not a hypothetical: it is the guaranteed, silent outcome of the very first realistic edit-after-photo-upload workflow, which is inevitable once real photography is supplied.

**Fix direction:** give each media row a hidden `media[i][id]` field pointing at its existing `PackageMedia` id; in the sync, only delete rows whose id wasn't resubmitted, and for a resubmitted row with no new file, carry the existing `image_path` forward instead of nulling it out. At minimum, until that's built, do not silently drop rows that have an existing `image_path` and no new upload — skip deleting them rather than deleting unconditionally up front.

---

## HIGH

### H-1. `HajjPackageRequest` has zero validation rules for any of the ten nested repeater arrays it drives — the exact "use Form Requests, not raw input" convention this project otherwise follows is bypassed entirely for this feature's actual data

**File:** `app/Http/Requests/Admin/HajjPackageRequest.php:15-42`

The `rules()` method validates `package_series_id`, `code`, `name`, `package_type`, `slug`, `summary`, `description`, `duration_days`, `duration_label`, `medinah_first`, `is_shifting`, `season_year`, `season_label`, `cover_image`, `is_featured`, `status`, `meta_title`, `meta_description`, `inclusions_text`, `exclusions_text` — the entire top-level `packages` row, correctly. It has **no rule at all** for `variants.*`, `accommodations.*`, `room_options.*`, `aziziya.*`, `aziziya_room_options.*`, `aziziya_services.*`, `mashaer.*`, `transportation.*`, `notes.*`, or `upgrades.*` — every field `syncNestedData()`/`syncAziziya()` write comes straight from `$request->input(...)`/`$request->file(...)`, never through `$request->safe()` or any validated array.

Concretely, nothing stops: a `location` value on an accommodation row that isn't one of the five real enum values, a `status` on Aziziya that isn't one of the four real values, a `pricing_type`/`note_type`/`media_type` outside its enum, a non-numeric string typed into any of the eleven price columns across room options/Aziziya room options/transportation/upgrades/services, or a `variants[i][code]` longer than the column's 10-character limit — from reaching the database. Every one of those either throws a raw `QueryException` (enum/length violations — feeding directly into C-1's crash path) or silently stores garbage (a non-numeric price string on SQLite, where dynamic typing won't complain the way MySQL — this project's real production target — will).

This directly contradicts the project's own established convention: `PackageRequest` (the generic packages form) and every other Form Request in the app funnel all controller input through validated rules; this is the first Form Request in the codebase that validates only part of what its controller writes.

**Fix direction:** add real rules for every nested field — `'accommodations.*.location' => ['required_with:accommodations.*.hotel_name', Rule::in(['makkah','medinah','aziziya','mina','arafat'])]`, `'aziziya.status' => ['nullable', Rule::in(['included','not_included','optional','not_applicable'])]`, `'room_options.*.price_usd' => ['nullable','numeric']`, etc. — mirroring the enum lists already hardcoded into the Blade `<select>` options (which is itself a sign these values were always meant to be a closed, validatable set, not free text).

### H-2. `resolveVariant()`'s code-not-index matching silently resolves an unmatched or duplicated variant code to `null` ("applies to all variants") instead of surfacing an error — a real risk of showing the wrong price under the wrong variant with no admin-visible warning

**File:** `app/Http/Controllers/Admin/HajjPackageController.php:130-143`

```php
$variantIdsByCode = [];
foreach ($request->input('variants', []) as $i => $row) {
    if (blank($row['code'] ?? null)) { continue; }
    $variant = $package->variants()->create([...]);
    $variantIdsByCode[strtoupper($row['code'])] = $variant->id;
}
$resolveVariant = fn (?string $code) => blank($code) ? null : ($variantIdsByCode[strtoupper($code)] ?? null);
```

Two distinct failure modes, both unguarded and both reachable through the plain free-text `variant_code` input (`form.blade.php:319`, `:223`, `:152` — a bare `<input type="text">` with no relationship to the actual codes typed in the Variants section above it, no dropdown, no client-side check):

1. **Unresolvable code, silent null fallback.** If an accommodation/room-option/Aziziya-room-option row's `variant_code` doesn't match any code in `$variantIdsByCode` — a typo ("AA" instead of "A"), a trailing space (no `trim()` is applied before the `strtoupper()` comparison on either side, so `"A "` never matches `"A"`), or a variant that was removed from the repeater but is still referenced by a row further down — `resolveVariant()` returns `null`. A `null` `variant_id` means, per the schema's own documented semantics, "this price/accommodation applies regardless of which Package A/B variant the guest booked." A room-option row the admin intended to be Package-A-only, priced lower, silently becomes visible (and priced) under **every** variant, including Package B, with zero error, warning, or flash message anywhere in the request/response cycle. This is a real risk of showing a customer the wrong price for the wrong accommodation tier.
2. **Duplicate codes.** Two variant rows submitted with the same code both attempt `->create()`; the second throws on the `unique(['package_id','code'])` constraint (see C-1) rather than being caught and reported as a validation error the admin can act on.

**Fix direction:** validate variant codes for uniqueness within the submission in `HajjPackageRequest` (a `Rule::in()` isn't enough since the valid set is dynamic per-submission — a custom `withValidator()` closure checking `array_unique` against `array_column($this->input('variants', []), 'code')` after normalizing case/whitespace); and have `resolveVariant()` throw or record a validation failure rather than silently returning `null` when a non-blank code fails to resolve, so a bad reference fails loudly instead of silently broadening a price's scope.

### H-3. Admin `edit()` is missing the `.variant` eager-loads that the public `showHajj()` correctly includes — a real N+1 introduced specifically by this feature, not present on the page it was modeled on

**File:** `app/Http/Controllers/Admin/HajjPackageController.php:74-78`, compare `app/Http/Controllers/PackageController.php:97-101`

```php
// HajjPackageController::edit() — admin
$package->load([
    'itineraryDays', 'inclusions', 'exclusions', 'variants', 'accommodations',
    'roomOptions', 'aziziya.roomOptions', 'aziziya.services', 'mashaerDetails',
    'transportation', 'packageNotes', 'upgrades', 'media',
]);
```
```php
// PackageController::showHajj() — public, correct
$package->load([
    'variants', 'accommodations.variant', 'roomOptions.variant',
    'aziziya.roomOptions.variant', 'aziziya.services', 'mashaerDetails',
    'transportation', 'packageNotes', 'upgrades', 'media',
]);
```
The admin form (`form.blade.php:142,220,316`) calls `$a->variant?->code` / `$r->variant?->code` once per row when pre-populating the accommodations, room-options, and Aziziya-room-options repeaters from the existing package — exactly the pattern the public page also has, and which the public page's controller correctly eager-loads for. The admin controller's `load()` call omits `.variant` on all three relations, so every row in every one of those three sections lazy-loads its `variant` relationship individually — for a fully-populated package (UB001 has 6 room options + 3 accommodations + however many Aziziya room options), that's on the order of 10+ avoidable queries on every single edit-page load.

**Fix direction:** change the three relation strings in `edit()`'s `load()` array to `accommodations.variant`, `roomOptions.variant`, and `aziziya.roomOptions.variant`, matching the public controller exactly.

---

## MEDIUM

### M-1. PHP's "0"-string falsiness silently drops the currency on a genuinely free-of-charge transportation/upgrade/Aziziya-service row

**File:** `app/Http/Controllers/Admin/HajjPackageController.php:230, 260, 344`

```php
'currency' => ($row['price'] ?? null) ? ($row['currency'] ?? 'USD') : null,
```
This exact pattern appears for `transportation`, `upgrades`, and `aziziya_services`. PHP treats the literal string `"0"` (and only that exact string — not `"0.00"` or `"0.0"`) as falsy. If an admin types exactly `0` into one of these price fields (e.g. to represent a genuinely complimentary add-on that's still worth listing with an explicit ₨/$/﷼ 0 rather than marked "included"), the ternary's condition evaluates false, and the row is saved with `price = 0` but `currency = null` — an internally inconsistent row (a priced item with no currency) that the public view's `$t->currency === 'USD' ? $t->price : ''` currency-matching logic (`show-hajj.blade.php:204,251`) will then never match against any currency button, silently hiding the price entirely on the public page.

**Fix direction:** check for `null`/blank explicitly instead of PHP truthiness: `($row['price'] ?? null) !== null && $row['price'] !== '' ? ... : null`, or route the whole nested payload through validated data (H-1) where this class of bug disappears once prices are cast to actual numeric types before this line runs.

### M-2. Unchecked "Available"/"Included" checkboxes silently flip back to checked if the form redisplays via `old()` after an unrelated top-level validation failure

**File:** `resources/views/admin/hajj-packages/form.blade.php:260, 327, 384`

```php
{{ ($row['is_available'] ?? true) ? 'checked' : '' }}
```
(and the equivalent for `is_included` on the transportation and Aziziya-services rows). An unchecked HTML checkbox is never submitted at all, so after a real submission the row's `$row` array simply has no `is_available`/`is_included` key — which this `??` fallback cannot distinguish from "this is a brand-new row that hasn't been given a value yet," so it defaults to `true`.

In the write path this is harmless (`! empty($row['is_available'])` in the controller correctly treats "key absent" as `false`). But if the *same request* fails validation on an unrelated top-level field (e.g. a blank `name` — currently the only kind of validation that exists at all, per H-1) and Laravel redirects back with `old()` re-populating the form, this exact `??` fallback re-renders **every** previously-unchecked box as checked, silently reversing whatever the admin had just set (e.g., an intentional "N/A" cell, or a "not included" transportation leg). If the admin doesn't notice before fixing the actual error and resubmitting, the corrected submission now really does mark that row available/included.

**Fix direction:** distinguish "key never submitted because this is a fresh row" from "key absent because the box was unchecked" — e.g. track submitted rows via a hidden marker field per row (`variants[i][_present]=1`) and only default to `true` when that marker is also absent, or simpler: default to `false` for redisplay and require an explicit `checked` only when the DB/old value is truthy (accepting that brand-new template rows use a hardcoded `checked` in the `<template>` HTML anyway, as they already do at lines 587/605/628 — the `??`-default in the *existing-rows* loop is the only place this matters, and it can just drop the `?? true` fallback).

### M-3. The old `PackageAddon`-based "Kaba view supplement" hack is dead code that was never removed, and its comments now misdescribe what the code does

**Files:** `app/Http/Controllers/PackageController.php:66-90`, `resources/views/packages/show.blade.php:130-149`

The task brief states this hack was "retired... in favor of real per-package `package_upgrades` rows" — true for Hajj packages specifically (confirmed: `PackageController::show()` branches to `showHajj()` at line 44, before ever reaching the `$addons` query at line 66), but the old code itself was left in place, unreachable for Hajj and, as it turns out, **unreachable for every other package too**: this session's rewrite of `HajjPackageSeeder.php` no longer creates any `PackageAddon` rows (confirmed via `git diff`/`grep -rln "PackageAddon::" database/seeders/` — zero matches anywhere in the seeders, and no other seeder ever populated it either), so `package_addons` is now permanently empty on a fresh install and the `$addons->isNotEmpty()` guard in `show.blade.php:130` will never pass for any package, Hajj or not.

The comment at `PackageController.php:76-78` — *"e.g. the real brochure's Kaba view supplement, extra-night pricing, VIP transport — seeded at the Hajj category level"* — describes functionality that is now provably false: Hajj packages never reach this line, and nothing seeds it for anyone else either. This is not a functional bug today (the code returns an empty collection either way) but it's dead weight with a misleading comment that will confuse the next developer who reads it, and the entire `PackageAddon` model/table/admin-invisible pathway (it has no admin CRUD — confirmed via `grep` across `app/Http/Controllers/Admin/PackageController.php` and `resources/views/admin/packages/`) is now orphaned.

**Fix direction:** remove the `$addons` query and `reject()` filter from `PackageController::show()`, the "Optional Add-ons" section from `packages/show.blade.php`, and decide explicitly whether `PackageAddon`/`package_addons` should be dropped entirely or given a real admin-managed purpose — don't leave a table and its only consumer silently orphaned by a seeder rewrite.

### M-4. Aziziya status badge renders an empty, visible pill for `not_applicable` packages

**File:** `resources/views/packages/show-hajj.blade.php:23`

```php
@if($package->aziziya)<span class="badge bg-light text-dark">{{ ['included' => 'With Aziziya', 'not_included' => 'Non-Aziziya', 'optional' => 'Aziziya Optional', 'not_applicable' => ''][$package->aziziya->status] ?? '' }}</span>@endif
```
The outer `@if($package->aziziya)` only checks that an Aziziya row exists, not that it has a displayable label — for a package whose Aziziya row exists with `status = 'not_applicable'`, this renders `<span class="badge bg-light text-dark"></span>`, an empty visible pill in the header badge row. Cosmetic, but a real, visible artifact on a live customer-facing page.

**Fix direction:** `@if($package->aziziya && $package->aziziya->status !== 'not_applicable')`, matching the guard already used correctly for the main Aziziya detail section two dozen lines below (`show-hajj.blade.php:105`).

---

## LOW

### L-1. `str_replace('_', ' ', $option->price_basis)` called without a null-guard

**File:** `resources/views/packages/show-hajj.blade.php:69, 135`

Same class of issue as `FINAL_CODE_REVIEW.md`'s L-2 (`Str::limit` on a nullable field): both call sites pass `$option->price_basis` straight into `str_replace()`'s subject parameter without a null check. Currently non-crashing because both `package_room_options.price_basis` and `package_aziziya_room_options.price_basis` are non-nullable columns with a DB-level default of `'per_person'`, and the controller's sync always coalesces to that same default — so in practice this is never actually null today. Still worth the same defensive `?? ''` treatment the prior review applied elsewhere, since it currently depends on an implicit guarantee from two other layers rather than being self-evidently safe at the point of use.

### L-2. `HajjPackageController::index()` eager-loads `series`, which the index view never reads

**Files:** `app/Http/Controllers/Admin/HajjPackageController.php:35`, `resources/views/admin/hajj-packages/index.blade.php`

`Package::where(...)->with('series')->orderBy('sort_order')->get()` — the index table (`index.blade.php:14-37`) renders `code`, `name`, `is_featured`, `duration_label`, `has_aziziya`, `starting_price`, `status` only; `$package->series` is never referenced anywhere in the view. Harmless (one extra query per page load, not per row — not an N+1), but dead weight worth dropping if `series` was copy-pasted from the generic `Admin\PackageController::index()` pattern without checking whether this view actually needs it.

---

## INFO

### I-1. Hajj package destructive actions have no audit trail

`HajjPackageController::destroy()` soft-deletes with no record of who deleted it or why, beyond the framework's own timestamps. Consistent with `FINAL_CODE_REVIEW.md`'s I-1/I-2 finding for the rest of the app (an already-accepted, proportionate scope boundary for a two-role internal CMS) — listed here only for completeness, not as a new gap specific to Hajj.

---

## Verified clean

For transparency, these were specifically checked given the review brief and found to be correct:

- **Aziziya pricing is never conflated with main package pricing.** `starting_price` is computed exclusively from `$package->roomOptions()->where('is_available', true)->whereNotNull('price_usd')->min('price_usd')` (`HajjPackageController.php:290`) — `$package->aziziya` is never read anywhere near this calculation. The package card component (`package-card.blade.php:30-31`) and admin index (`index.blade.php:23`) both display only `starting_price`/`currency`. The admin form keeps every Aziziya field under its own `aziziya[...]`/`aziziya_room_options[...]`/`aziziya_services[...]` input namespace, backed by its own table (`package_aziziya`) with no shared columns with `package_room_options`. `has_aziziya` (the legacy flat flag) is correctly synced only from `aziziya.status === 'included'` (`HajjPackageController.php:151`), not from the mere presence of an optional-upgrade Aziziya row — verified both by reading the code and by `HajjPackageManagementTest::test_has_aziziya_syncs_true_only_when_aziziya_status_is_included` and `test_admin_can_create_a_full_hajj_package`'s explicit assertion that an Aziziya-optional (not Aziziya-included) package keeps `has_aziziya === false`.
- **Route/model-binding correctness.** `Route::resource('hajj-packages', HajjPackageController::class)->except(['show'])->parameters(['hajj-packages' => 'package'])` correctly overrides the implicit parameter name to match the controller's `Package $package` type-hint — avoiding the exact same class of bug this codebase previously shipped in the News module (documented in `routes/web.php`'s own comment above the `news` resource, and in `FINAL_AUDIT_REPORT.md`).
- **Authorization.** The `hajj-packages` resource sits inside the same `Route::middleware(['auth', 'admin'])` group as every other admin resource (`packages`, `testimonials`, `faqs`, etc.) — no separate, weaker, or missing gate for the new module.
- **Mass assignment on the primary model.** `store()`/`update()` build the `Package` model from `$request->safe()->except([...])` — validated data only, consistent with the project's Form Request convention for the top-level model (even though the ten nested tables bypass this entirely — see H-1).
- **Currency-switcher JS.** No XSS risk: every `data-pkr`/`data-sar`/`data-usd` value is emitted through Blade's escaping `{{ }}` (which HTML-entity-escapes quotes via `ENT_QUOTES`), so no stored value can break out of the attribute. Correctly falls back to "N/A" for a genuinely absent/blank currency value (an empty `data-usd=""` attribute reads as JS falsy). Correctly renders an exact `0` price as a real "US$0" rather than "N/A" — JS string falsiness does not share PHP's "the literal string `\"0\"` is falsy" quirk, so this is a materially different (and correct) outcome from the PHP-side bug at M-1.
- **The "critical currency invariant" test is legitimate, if narrower than its docblock implies.** `HajjPackagePublicTest::test_switching_currency_data_changes_only_price_values_not_package_content` proves the server emits all three currencies' real values simultaneously in one response and that non-price content (`"Aziziya A Class"`, `"Triple Sharing"`, `"Family Room"`) appears exactly once — a necessary server-side precondition for correct client-side switching, but it does not itself execute the switch (PHPUnit can't run JS). The actual runtime proof — click SAR, assert the price cell reads "N/A" (real UB001 data has no SAR value), click back to USD, assert the price returns, and assert unrelated headings/content are still present — is correctly delegated to a real browser test, `tests/e2e/public.spec.js` "6b. Hajj package detail currency switcher...". Together they do prove the claim; neither one alone would.
- **Tests go through the real HTTP/route stack**, not direct service/model calls: both `HajjPackageManagementTest` (`$this->actingAs($this->admin)->post('/admin/hajj-packages', ...)`) and `HajjPackagePublicTest`/`HajjSeedDataTest` (`$this->get("/hajj/{$package->slug}")`) exercise the real routes, middleware, and controllers.
- **`HajjPackageRequest::authorize()` returning `true` unconditionally** matches the identical pattern in every other Form Request in the app (`PackageRequest`, etc.) — authorization is deliberately handled at the route-middleware level project-wide, not a Hajj-specific gap.
- **Seed data fidelity** (`HAJJ_PACKAGE_DATA_AUDIT.md`, `HajjSeedDataTest`) is a real, end-to-end seed→DB→route→HTML check across all 12 real packages, re-run twice to confirm idempotency — outside a code-review's normal scope, but genuinely verified rather than merely asserted.
- **`package_media` is confirmed the only deliberately-unpopulated table** — no seeder anywhere creates a `PackageMedia` row, matching the session's stated reasoning (no real photography exists yet). Note this is true of the *data*; the underlying sync *code* for this table has a real, separate bug once it is populated — see C-2.
