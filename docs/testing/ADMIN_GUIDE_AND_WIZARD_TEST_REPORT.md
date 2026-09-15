# Admin Guide and Package Wizard — Test Report

**Issue:** [#11](https://github.com/asimcreative/universal-brothers/issues/11) · **Date:** 2026-09-14 · **Environment:** local only — PHPUnit on in-memory SQLite (`phpunit.xml`), Playwright against `php artisan serve --env=testing` (`database/testing.sqlite`). **No test touched the production database.**

## 1. Summary

| Suite | Result |
|---|---|
| PHPUnit, full suite | **372 tests, 2,760 assertions — all pass** (was 335 before this work; 37 new) |
| New: `tests/Feature/Admin/AdminGuideTest.php` | 16 tests pass |
| New: `tests/Feature/Admin/PackageWizardTest.php` | 21 tests pass |
| Playwright, full suite (6 projects, 264 tests) | **259 passed, 4 skipped (existing phone skips on the public site), 1 failed → fixed and re-run green.** The one failure was a test locator: the phone test now ends on the Publish step, which has its own "Save changes" button, so the locator matched two buttons. It now targets the save bar. The whole `package-builder.spec.js` then passed 9/9. No test needed a retry. |
| New: `tests/e2e/admin-guide.spec.js` (admin-chromium) | 11 tests pass |
| Updated: `tests/e2e/package-builder.spec.js` | passes with 14 steps and the phone step menu |
| Migration `2026_09_15_100000` | applied to the local and testing databases; rolled back and re-applied cleanly on the local database |
| Pint | clean on every changed PHP file |
| `npm run build` | builds without warnings |

## 2. What the new tests prove

### Guide and onboarding — `AdminGuideTest`

| Area | Tests |
|---|---|
| Guide content | All 26 required sections exist, in a known group, each with a summary, why, steps, an example and common questions. No technical words appear in guide, builder-help or tour text. All 14 builder steps have complete "Need help?" answers pointing to real guide sections. |
| Guide pages | The index lists every group and section with "0 of 26 sections read". Every section page renders its steps, example and first question, and **every link on every section opens with 200**. Search finds "Kaaba View" under upgrades and shows an empty state for nonsense. Unknown sections return 404. `/admin/help` redirects. |
| Mark as read | Per admin, idempotent (two posts → one row), un-mark works, other admins are unaffected, unknown keys return 404. |
| Help panels | FAQs, enquiries, hotels, templates and Hajj packages pages show "Need help with this page?" linking to the right section. |
| Welcome and tour | A new admin sees the welcome panel with start, skip and open-guide, and every one of the 9 tour anchors exists on the dashboard. Progress is saved and "Resume tour (step 4 of 9)" is offered. A completed tour never returns until restarted. Skipping hides the panel for that admin only. Invalid status or step values are refused (422). Guests are redirected to login. |

### Wizard — `PackageWizardTest`

| Area | Tests |
|---|---|
| Shape | 14 steps, each with its help panel; the phone step menu markup; templates have no Photos, Review or Publish steps and no assess URL; plain labels (Arrive in Madinah / Jeddah, INCLUDED / NOT INCLUDED IN THIS PACKAGE, four note kinds, the internal-notes warning, Find a hotel, example day). |
| Live assessment | An empty form lists blocking problems, returns exactly the 13 checklist labels, and **creates no package**. A complete form can publish with only "Final review" left (92%). Assessing a saved package judges the unsaved input and **leaves the saved package byte-for-byte unchanged**. |
| Review warnings | Plan shorter than the package, dates out of order, a room missing SAR and PKR, an option without its own hotel, a hotel without a meal plan, no main photo and nothing included — each with an Edit link to its step — while publishing stays allowed. An available room with no price and an unnamed option are also pointed out. |
| **Reported case** | **UB004** (sold as 14 days, printed plan of 13) is publishable, and its Review shows "The package is 14 days but the journey plan has 13 days." |
| Privacy | Internal-note text never appears in the assessment JSON, the Review body or the public package page. |
| Final review | Saving from Review ticks it. Publishing keeps it. Changing a price un-ticks it. An incomplete package is never marked reviewed. |
| Publish step | The three finish cards are shown. Publish is disabled with "Fix these first" when incomplete, and the server still refuses a publish request. It is enabled when complete. |
| Resume | A draft reopens on its saved step with the "where you saved last time" banner. "Save & continue" from Photos remembers Review. `?step` always wins. Published packages open on step 1. The listing shows "N% complete" with Continue, and the dashboard shows Unfinished drafts, both linking to the saved step. |
| Journey transport | Saved, returned by the form state, and shown on the public package page. |
| Reusable content | Usage counts count each package once and ignore deleted packages (hotel used by Option A and B of one package = 1). They match `packagesUsing()`. Mashaer records carry their edit link. Editing a used hotel shows the shared warning with Update / Save as a new separate record / Cancel. Saving a copy creates a new hotel and **leaves the original and its packages unchanged**. An unchanged name becomes "(Copy)". |
| Templates | A package started from a template opens in the 14-step wizard with its content. |

### Browser — `admin-guide.spec.js`

| Test | Proves |
|---|---|
| Guide search, read, tick | Search → section → Mark as read → counted on the index → un-mark (test data left as found); no sideways scroll |
| Help panel keyboard | A closed `<details>` panel opens with Enter |
| Tour, desktop | Restart opens the tour on stop 1 with focus on its heading. Next and Back work, and Back is disabled on stop 1. Arrow keys move and Escape skips. Resume survives a reload. Stop 9 highlights the help icon. Finish shows the finished panel, which does not return after a reload. |
| Tour, phone (390 px) | Bottom sheet; menu stops open the phone menu and highlight inside it; no sideways scroll; skipping closes the menu and the page's own menu and links work |
| Review | UB004 Review shows the 13-day warning. Edit opens the Journey step. Clearing the title shows the red problem as you type, and restoring it clears the problem. |
| Publish and resume | Publish disabled with a blocker link that opens Basic information; saving a draft on Hotels; the listing's Continue reopens Hotels; the dashboard lists the draft |
| Find a hotel | Search, details with "Used in", add; badge "Saved content · used in N packages" becomes "Changed for this package only" after editing the name |
| Copy confirmation | The dialog lists "Hotel options: A, B (replaces 0 options here)", room price counts, and what is never copied; Cancel changes nothing |
| Shared hotel | Shared warning; Save as a new separate record; original unchanged; copy deleted again |
| Phone step menu | "Step 1 of 14", expands, choosing Room prices updates it to "Step 4 of 14 · Room prices" and collapses; help panel opens; no sideways scroll |

## 3. Existing tests updated

| File | Change | Why |
|---|---|---|
| `tests/Feature/Admin/PackageBuilderTest.php` | Login-required list uses the guide routes instead of `admin.help` | Help page replaced by the guide (`/admin/help` now redirects) |
| `tests/e2e/package-builder.spec.js` | Step buttons scoped to `#builder-step-list`; "Madinah first" → "Arrive in Madinah"; phone test opens the step menu, also visits Review and Publish, and checks the save bar's "Save changes" | New labels, the compact phone menu, and the Publish step's own save button |
| `playwright.config.js` | `admin-guide.spec.js` added to the admin-chromium project | New spec |

## 4. Manual visual check

The following were captured at 1440 px and 390 px against the testing database and inspected for layout, contrast and overflow:

- guide index and a guide section
- dashboard welcome and tour (desktop and phone)
- Review, Publish, Pricing, Options, Hotels (with a help panel open), Notes, Mashaer and the empty Journey step
- the Find a hotel dialog, the phone step menu and Review on a phone
- the shared hotel edit screen and the package list

A capture script reported no JavaScript errors, no 4xx/5xx responses and no horizontal overflow. Two layout faults found this way were fixed:

- The supplements hint and the internal-notes warning split into columns. The text is now wrapped as one block.
- The new "Complete" column pushed the package list's Actions column off-screen at 1440 px. Completion now sits under the status.

## 5. Not covered by automated tests

- Screen-reader announcements (live regions) are checked in markup only, not with a real screen reader.
- Tour card placement at every viewport between 768 px and 1440 px (checked at 1440 and 390).
- Firefox and WebKit run the public and responsive projects only, per the existing configuration; the admin suites run in Chromium.
