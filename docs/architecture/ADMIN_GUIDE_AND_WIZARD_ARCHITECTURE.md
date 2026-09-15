# Admin Guide and Package Wizard — Architecture

**Issue:** [#11](https://github.com/asimcreative/universal-brothers/issues/11). Read [ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md](ADMIN_PACKAGE_MANAGEMENT_ARCHITECTURE.md) first. This document covers only what issue #11 added on top of the package builder: the guide, onboarding and tour, the 14-step wizard with its live assessment, save and resume, and reusable-content usage.

---

## 1. Principles

1. **The server owns every verdict.** The checklist, step ticks, percentage, review warnings and whether Publish is allowed all come from one PHP class, `PackageReview`. The builder asks the server as the admin types; the browser never re-implements the rules. What the checklist says therefore cannot disagree with what publishing allows.
2. **Help is content, not code.** Every sentence of the guide, the tour and the "Need help?" panels lives in two version-controlled PHP arrays under `resources/data/`. Changing wording needs no code change, and tests fail if a section, answer or link goes missing.
3. **State belongs to the admin, not the browser.** Tour progress, "welcome dismissed" and "sections read" are stored on the user's account, so they follow the admin across devices. Only the unsaved-form copy (from #10) stays in `localStorage`.
4. **Nothing new can block work.** The tour never traps the page, review warnings never block publishing (only the existing publishing rules do), and assessing never saves.

## 2. Data model

Migration `2026_09_15_100000_add_admin_guide_and_builder_progress` (reversible; `down()` drops exactly what `up()` adds):

| Table.column | Type | Purpose |
|---|---|---|
| `users.onboarding_dismissed_at` | timestamp, null | "Skip for now" on the welcome panel |
| `users.tour_status` | string(20), default `not_started` | `not_started` · `in_progress` · `paused` · `completed` |
| `users.tour_step` | tinyint, default 0 | 0-based stop to resume at |
| `admin_guide_completions` | id, user_id (FK, cascade), section_key(60), completed_at; unique (user_id, section_key) | Guide sections an admin has read |
| `packages.builder_step` | string(20), null | The step a draft reopens on |
| `packages.reviewed_hash` | string(64), null | SHA-256 fingerprint of the content at the last completed review |
| `package_itinerary_days.transport` | string, null | Transport on a journey day, shown on the public itinerary |

All new columns are nullable or defaulted, so existing rows need no data repair. The production deploy runs `migrate --force`.

`User::shouldSeeOnboarding()` = not dismissed **and** tour not completed.

## 3. Guide content

```
resources/data/admin-guide.php          groups, 26 sections, 9 tour stops
resources/data/package-builder-help.php  one "Need help?" entry per builder step (14)
app/Support/Guide/GuideContent.php       loads both; sections(), grouped($search), section(),
                                         neighbours(), links() (drops links whose route is gone),
                                         tour(), builderHelp($step)
```

A section has `title, group, icon, summary, why, steps[], example, tips[], faqs[[q, a]], links[[label, route, params]]`. A builder help entry has `purpose, enter, required, example, after, guide` (the guide section key).

**To add a guide section:** add a keyed entry to `sections` with an existing `group`, and add its key to the list in `AdminGuideTest::test_the_guide_has_every_required_section…`. The index, search, previous/next and progress pick it up automatically.

**To show help on an admin page:** add `@section('guide', 'section-key')` to the view. The layout renders `admin/partials/page-help.blade.php`, a closed `<details>` panel.

## 4. Routes

| Method & path | Name | Controller |
|---|---|---|
| GET `admin/guide` | `admin.guide.index` | `GuideController@index` (`?q=` search) |
| GET `admin/guide/{section}` | `admin.guide.show` | `GuideController@show` (404 for unknown keys) |
| POST / DELETE `admin/guide/{section}/complete` | `admin.guide.complete` / `.uncomplete` | idempotent (`firstOrCreate`) |
| GET `admin/help` | `admin.help` | redirect to the guide (old links keep working) |
| POST `admin/onboarding/tour` | `admin.onboarding.tour` | `{status, step}` validated; step bounded by the tour length |
| POST `admin/onboarding/dismiss` | `admin.onboarding.dismiss` | JSON or redirect |
| POST `admin/onboarding/restart` | `admin.onboarding.restart` | resets and redirects to `/admin?tour=start` |
| POST `admin/hajj-packages/assess` | `admin.hajj-packages.assess` | assess a new, unsaved package |
| POST `admin/hajj-packages/{package}/assess` | `admin.hajj-packages.assess-existing` | assess edits to a saved package |

All are inside the `auth` + `admin` group. None takes a user id: the acting admin is always `$request->user()`. The unparameterised `assess` route is registered before `Route::resource('hajj-packages')` so the word "assess" is never read as a package.

## 5. The wizard

### 5.1 Steps

`PackageCompleteness::STEPS` now has 14 keys: the 12 from #10, plus `review` and `publish`. `PACKAGE_ONLY_STEPS = [media, review, publish]` are removed for templates by `PackageTemplateController`. `_step` validation, `stepForField()` and "Save & continue" all read the same constant.

Each step view includes `partials/need-help.blade.php` under its heading. The new steps are `steps/review.blade.php`, which renders `partials/review-body.blade.php`, and `steps/publish.blade.php`, with three finish cards.

### 5.2 `App\Support\Packages\PackageReview`

Input: a `PackageFormState` array, the saved `Package` if there is one, and a context (`new_cover`, `new_media`, `remove_cover`, `reviewed`).

| Method | Returns |
|---|---|
| `problems()` / `canPublish()` | `PackageCompleteness::problems()`, the only thing that blocks publishing |
| `warnings()` | Advice: no summary; Aziziya unset; unnamed option; option without its own hotel; an available room with no price; rooms missing a currency; a Makkah/Madinah hotel without a meal plan; no journey plan; plan shorter than the package; undated days; dates not increasing; day numbers out of sequence; no Mashaer; no transport; empty included/not included; no notes; no main photo; empty Google title or description |
| `checklist()` | The 13 items in `CHECKLIST`, each `{key, label, step, done}` |
| `percent()` | Done items ÷ 13 |
| `stepStatus()` | Tick per step, derived from the checklist |
| `nextStep()` | First unfinished checklist step, used by "Continue" links |
| `summary()` | Normalised data for the Review screen. Internal notes are reduced to a written/none flag, never their text |
| `forPackage()`, `fingerprint()`, `isReviewed()` | Saved-package helpers |

### 5.3 Live assessment flow

```
input/change in the form ──debounce 600 ms──▶ assessNow()
   FormData without files (+ _new_cover, _new_media, _reviewed)
   └─ POST assess[-existing] ──▶ HajjPackageController@assess
          state = PackageFormState::withOldInput(saved-or-blank, input)
          review = new PackageReview(state, package, context)
          ◀── {percent, checklist, steps, problems, warnings, can_publish, review_html}
   applyAssessment(): step ticks, progress bar, sidebar checklist,
   Review body (server-rendered HTML), Publish button + "Fix these first", live-region message
```

- An in-flight request is cancelled by the next one (`AbortController`). If a request fails, the last result stays on screen.
- The page arrives with the server's result already drawn, so no request is made on load.
- The request is never saved and never validated as a save. Tests assert the package is unchanged afterwards.
- Templates have no assess URL. They keep the simple local step count from #10.

### 5.4 "Final review completed"

- Opening the Review step sets the hidden `_reviewed=1` and assesses immediately.
- Any later change on another step resets it to 0.
- On save, `recordProgress()` stores `reviewed_hash = PackageReview::fingerprint(fresh package)`, but only if `_reviewed=1` **and** the saved package has no problems.
- `fingerprint()` hashes the `PackageFormState` minus `status, is_featured, sort_order, media, internal_notes`. Publishing or featuring does not "un-review" a package; changing a price does.
- `isReviewed()` compares the stored hash with a fresh fingerprint, so there is nothing to invalidate by hand.

### 5.5 Save and resume

- `recordProgress()` runs after every builder save. It stores `builder_step`: the next step for "Save & continue", otherwise the current one.
- It saves with `timestamps = false` and `saveQuietly()`. That keeps `updated_at`, and the browser's unsaved-copy check, tied to real content saves.
- Opening a **draft** without `?step` goes to `builder_step` and shows "Opened on X, where you saved last time — Start from step 1". Published packages always open on Basic information. An explicit `?step` always wins.
- The listing shows "N% complete" under each status. Unfinished drafts get a **Continue** link, which goes to `builder_step` or else `nextStep()`.
- The dashboard's "Unfinished drafts" card shows the five most recent drafts with progress and Continue.
- The listing and dashboard eager-load `PackageFormState::RELATIONS`, so percentages cost no query per package.

## 6. Reusable content

- `LibraryType::usageCounts()` returns `record id => distinct packages`, for a whole type in one grouped query. It joins `packages` with `deleted_at IS NULL`, matching `packagesUsing()` and the library list's own count.
- `PackageBuilderData` adds `used` to hotels, meal plans, transport, services, upgrades, Mashaer and notes. Hotels also carry `location_label, city, address, description, website_url, map_url`, for the Find a hotel details. Mashaer records carry an `edit_url`.
- In the builder:
  - A row linked to a saved record shows **"Saved content · used in N packages"**. When a compared field differs from the saved record, the badge switches to **"Changed for this package only"**.
  - Compared fields: hotel name and stars; service description; note text; transport from, to and price; upgrade name and price. This is `LINKED_ROWS` in `package-builder.js`.
  - The Mashaer card shows a shared-information warning with the usage count and a link to edit the saved arrangement.
- In the library edit form, a record used by packages gets a warning and three choices:
  - **Update shared record** asks to confirm. Packages keep their copy until "Update packages", unchanged from #10.
  - **Save as a new separate record** submits `_save_as=copy`. `LibraryController@update` replicates the record, applies the edited values, adds " (Copy)" if the name is unchanged, and logs an `AdminActivity`. The original and every package are untouched.
  - **Cancel.**
- The shared confirm dialog (`initConfirmDialog`) now also honours `data-confirm*` on the pressed **submit button**, not only on the form.

## 7. Onboarding and tour (`resources/js/admin/tour.js`)

- **Data.** `layouts/admin.blade.php` embeds `#admin-tour-data`: the steps, the admin's status and step, and the state, dashboard and guide URLs.
- **Anchors.** `data-tour` sits on the dashboard's Hajj stats, the nav (`sidebar`), nav groups (`nav-hajj`, `nav-reusable`, `nav-enquiries`, `nav-ai`), nav links (`nav-templates`, `nav-settings`) and the topbar help icon (`help`, now visible at every width). The nav partial is rendered twice, once for the desktop sidebar and once for the phone menu. `targetFor()` picks the visible copy on desktop and the copy inside `#adminMobileNav` on phones.
- **Starting.** `[data-tour-start="n"]` buttons, `?tour=start` or `?tour=resume`. The parameter is removed from the address after reading.
- **Layers.**
  - A fixed highlight ring, which dims the page with a large box-shadow and has `pointer-events: none`.
  - A `role="dialog"` card placed beside the target, below it or above it.
  - On phones (<768 px), a bottom sheet.
- **Phone menu stops.** The menu is opened by adding `.show` to the offcanvas, not through Bootstrap's `Offcanvas`. Bootstrap's focus trap would pull keyboard focus away from the tour card. The tour removes the class again when it moves on or ends.
- **Keyboard.** Arrow keys move, Escape skips, and Tab cycles within the card. Focus moves to each stop's heading. A visually hidden live region announces "Step N of 9: Title". Focus returns to the starting control at the end.
- **Saving state.**
  - Every move saves `in_progress` + step (`fetch` with `keepalive`).
  - Skip saves `paused`, and the dashboard button becomes "Resume tour (step N of 9)".
  - Finish saves `completed` and replaces the welcome panel with a "finished" message.
- `window.ubAdminTour` exposes `start/skip/finish` for debugging.

## 8. Accessibility additions

- `x-admin.error` messages get stable ids. `initFieldErrors()` sets `aria-invalid` and adds the message id to the field's `aria-describedby`.
- Global `:focus-visible` outlines in the admin.
- Progress bars use `role="progressbar"` with `aria-valuenow`. The Review step announces updates through a polite live region.
- On phones and tablets (<992 px), the builder's step list folds into one button, "Step N of 14 · Title", with `aria-expanded` and `aria-controls`. Choosing a step closes it.
- Confirmation messages may contain line breaks (`white-space: pre-line`). The copy confirmation uses them to list what is copied.

## 9. Files

| Area | Files |
|---|---|
| Content | `resources/data/admin-guide.php`, `resources/data/package-builder-help.php` |
| PHP | `app/Support/Guide/GuideContent.php`, `app/Support/Packages/PackageReview.php`, `app/Http/Controllers/Admin/{GuideController,OnboardingController}.php`, `app/Models/AdminGuideCompletion.php`; changes to `HajjPackageController`, `DashboardController`, `LibraryController`, `PackageTemplateController`, `PackageCompleteness`, `PackageFormState`, `HajjPackageWriter`, `HajjPackageRequest`, `LibraryType`, `PackageBuilderData`, `User`, `PackageItineraryDay`. `HelpController` and `admin/help.blade.php` were removed; their content lives in the guide. |
| Views | `admin/guide/{index,show}`, `admin/partials/page-help`, `admin/hajj-packages/steps/{review,publish}`, `admin/hajj-packages/partials/{need-help,review-body}`; changes to the layout, nav, dashboard, listing, all step views, the day row, modals, library form and list, `x-admin.error`, the public itinerary day |
| JS / CSS | `resources/js/admin/tour.js` (new), `resources/js/admin/package-builder.js`, `resources/js/admin.js`, `resources/scss/_admin-guide.scss` (new), `resources/scss/_admin-ui.scss` |
