# Admin Guide and Package Wizard — Requirements and Traceability

**Issue:** [#11](https://github.com/asimcreative/universal-brothers/issues/11) · **Builds on:** [#10](https://github.com/asimcreative/universal-brothers/issues/10) ([ADMIN_PACKAGE_MANAGEMENT_REQUIREMENTS.md](ADMIN_PACKAGE_MANAGEMENT_REQUIREMENTS.md))

**Goal:** a non-technical administrator can learn the admin on their own and create a complete Hajj package without outside help. They get a help centre, a first-visit tour, help inside every builder step, a checklist, a review screen and a clear publish step.

Each requirement below links to where it is built and to the test that proves it:

- **GD** — `tests/Feature/Admin/AdminGuideTest.php` (16 tests)
- **WZ** — `tests/Feature/Admin/PackageWizardTest.php` (21 tests)
- **PB** — `tests/Feature/Admin/PackageBuilderTest.php` (existing, still passing)
- **E2E-G** — `tests/e2e/admin-guide.spec.js` (11 tests)
- **E2E-B** — `tests/e2e/package-builder.spec.js` (updated for 14 steps and the phone menu)

Status key: ✅ built and tested · ◐ built, with a limit described in the row.

---

## 1. Guide at `/admin/guide`

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R1.1 | Guide index at `/admin/guide`, grouped, searchable | ✅ | `GuideController@index`, `admin/guide/index.blade.php` | GD `the_guide_index_lists_every_section_by_group_with_progress`, `searching_the_guide_finds_sections_by_their_words`; E2E-G guide test |
| R1.2 | The 26 sections: dashboard, website content, Hajj packages, package options, room pricing, hotels, itinerary, Mina/Arafat/Muzdalifah, transport, meals, inclusions, exclusions, upgrades, notes, templates, media, FAQs, awards, affiliations, testimonials, news, enquiries, settings, AI assistant, preview/publishing, safe-editing rules | ✅ | `resources/data/admin-guide.php` | GD `the_guide_has_every_required_section_with_real_help_in_it` |
| R1.3 | Each section has why it matters, steps, a real example, safe-editing tips, common questions and links to the page | ✅ | `admin/guide/show.blade.php` | GD `every_section_page_renders_its_steps_example_questions_and_working_links` (also opens every link) |
| R1.4 | Plain language with no technical words | ✅ | Writing rules at the top of `admin-guide.php` | GD `the_guide_is_written_without_technical_words` (guide, builder help and tour text) |
| R1.5 | Real Universal Brothers examples (UB001, Dar Al Taqwa, Kaaba View Supplement…) | ✅ | `admin-guide.php` | Content review |
| R1.6 | Mark a section read, and un-mark it; progress per admin | ✅ | `GuideController@complete/uncomplete`, `admin_guide_completions` | GD `sections_are_marked_read_per_admin_and_only_once`; E2E-G |
| R1.7 | The old `/admin/help` address still works | ✅ | Redirect route | GD `an_unknown_section_is_not_found_and_the_old_help_address_redirects` |

## 2. First-time onboarding

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R2.1 | Welcome panel on the dashboard with "Start guided tour" | ✅ | `dashboard.blade.php`, `User::shouldSeeOnboarding()` | GD `a_new_admin_is_welcomed_with_the_tour…` |
| R2.2 | Start, skip, resume later, open the guide | ✅ | `OnboardingController@tour/dismiss`; "Resume tour (step N of 9)" | GD `the_tour_saves_progress_and_offers_to_resume…`, `skipping_the_welcome_hides_it_for_that_admin_only`; E2E-G tour test |
| R2.3 | Not repeated once completed unless restarted | ✅ | `tour_status = completed`; `OnboardingController@restart` from the account menu and the guide | GD `a_finished_tour_does_not_come_back_until_restarted`; E2E-G |
| R2.4 | State kept per admin on the server (works across devices) | ✅ | `users.onboarding_dismissed_at`, `tour_status`, `tour_step` | GD; the endpoint takes no user id (identity comes from the session) |

## 3. Interactive tour

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R3.1 | 9 stops: Dashboard, Sidebar, Hajj Packages, Reusable data, Templates, Enquiries, Settings, AI, Help | ✅ | `admin-guide.php` `tour`; `data-tour` anchors in the nav, dashboard and topbar | GD checks every anchor exists on the dashboard |
| R3.2 | Each stop shows step number, title, text, Next, Back, Skip and a progress bar | ✅ | `resources/js/admin/tour.js` | E2E-G tour test |
| R3.3 | Works on desktop and mobile, not hover-only | ✅ | Popover beside the target on desktop; bottom sheet on phones; menu stops open the phone menu | E2E-G `on a phone the tour opens the menu…` |
| R3.4 | Never blocks navigation permanently | ✅ | Escape and the close button always skip; the page underneath stays usable | E2E-G (menu link followed after skipping) |
| R3.5 | Keyboard and screen readers | ✅ | Arrow keys move, Escape skips, Tab stays inside the card, focus moves to each title, live region announces "Step N of 9" | E2E-G (arrow keys, Escape, focus) |

## 4. Package creation wizard (14 steps)

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R4.1 | 14 steps: the 12 builder steps plus Review and Save/Preview/Publish | ✅ | `PackageCompleteness::STEPS` | WZ `the_builder_has_fourteen_steps…` |
| R4.2 | Step explanations and friendly labels ("Arrive in Madinah", "Included in this package"…) | ✅ | `steps/*.blade.php` | WZ `the_wizard_uses_plain_labels…` |
| R4.3 | Option cards | ✅ | Choice cards plus a live "What each option has so far" summary (hotels, room types, From price, what is missing) | E2E-G; screenshots |
| R4.4 | Room types with a supplements explanation and a live price summary | ✅ | Pricing hint (supplements belong in Additional options) and a live table with the "From" price | E2E-B; screenshots |
| R4.5 | Hotel search, add and details; shared vs package-specific explanation | ✅ | "Find a hotel" dialog (search, city, details, usage, add to option), "New hotel", explainer panel | E2E-G `a hotel can be found with its details and usage…` |
| R4.6 | Itinerary with transport, description and an example row | ✅ | `package_itinerary_days.transport`; example day shown when empty; shown on the public page | WZ `transport_on_a_journey_day_is_saved_and_shown_to_visitors` |
| R4.7 | Mina/Arafat/Muzdalifah shared-data warnings | ✅ | Warning with usage count and a link to the saved arrangement | WZ (usage and edit link data); screenshots |
| R4.8 | Separate INCLUDED / NOT INCLUDED IN THIS PACKAGE panels | ✅ | `steps/services.blade.php` (green and red panels) | WZ labels test |
| R4.9 | Upgrades explanation | ✅ | `steps/extras.blade.php` | Content |
| R4.10 | Notes in 4 kinds with "Internal admin notes are never shown to website visitors." | ◐ | Customer note, important policy, terms & conditions and internal admin note are explained with badges. The first three are the note's kind and "Important" switch; the internal note is its own private box. They are not four separate lists. | WZ labels and privacy tests |
| R4.11 | Images and SEO explanation | ✅ | `steps/media.blade.php` | Content |
| R4.12 | Step 13 Review: every section, warnings for missing required info, prices, hotels, images, incomplete itinerary, invalid dates, missing currencies and missing option data; "Edit" back to each step | ✅ | `PackageReview`, `partials/review-body.blade.php` | WZ `the_review_warns_about_the_things_worth_checking`, `an_available_room_without_any_price…`, UB004 acceptance; E2E-G Review test |
| R4.13 | Step 14 Save / Preview / Publish explained, with publish validation | ✅ | `steps/publish.blade.php`; Publish is disabled with a "Fix these first" list, and the server still refuses | WZ `the_publish_step_explains_the_three_ways…`; E2E-G |

**Reported brochure case (acceptance):** UB004 is sold as 14 days, but its printed plan lists 13. The package can still be published. The Review step says "The package is 14 days but the journey plan has 13 days." — WZ `ub004_is_publishable_but_the_review_points_out_its_thirteen_day_plan`.

## 5. "Need help?" panels

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R5.1 | Every builder step answers: what it is for, what to enter, is it required, an example, what happens after saving | ✅ | `resources/data/package-builder-help.php`, `partials/need-help.blade.php` | GD `every_builder_step_has_a_complete_need_help_panel`; WZ checks all 14 panels render |
| R5.2 | Help panels on the other admin pages | ✅ | `@section('guide', key)` → `partials/page-help.blade.php` on 23 admin pages (lists and forms, including every Reusable Content list and form) | GD `admin_pages_offer_a_need_help_panel_from_the_guide`; E2E-G keyboard test |

## 6. Reusable data

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R6.1 | Usage counts ("Used in 4 packages"), each package counted once, deleted packages not counted | ✅ | `LibraryType::usageCounts()`; pickers, Find a hotel, row badges, Mashaer warning | WZ `saved_content_shows_how_many_packages_use_it…` |
| R6.2 | Warning before editing shared information | ✅ | Library edit: "This is shared information. Used in N packages." | WZ `editing_shared_content_offers_update_or_a_separate_copy`; E2E-G |
| R6.3 | Choices: Update shared record / Create a separate copy / Cancel | ✅ | "Update shared record" (asks to confirm), "Save as a new separate record" (`_save_as=copy`), Cancel | WZ (original and packages unchanged); E2E-G |
| R6.4 | A change inside a package is package-specific | ✅ | Row badge switches from "Saved content · used in N packages" to "Changed for this package only" | E2E-G |

## 7. Dynamic checklist (13 items)

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R7.1 | Basic information, configuration, options, room prices, accommodation, itinerary, Mashaer, transport and meals, inclusions/exclusions, notes, images, SEO, final review | ✅ | `PackageReview::CHECKLIST`; sidebar checklist and Review step | WZ `assessing_an_empty_new_package…` (exact 13 labels) |
| R7.2 | Updates as the admin types, from the server's own rules | ✅ | `POST admin/hajj-packages/assess` (debounced, nothing is saved) | WZ `assessing_an_existing_package_judges_the_unsaved_form…`; E2E-G Review test |
| R7.3 | "Final review completed" is tied to the content that was reviewed | ✅ | `packages.reviewed_hash` fingerprint | WZ `saving_from_the_review_step_ticks_final_review…`, `an_incomplete_package_is_never_marked_reviewed` |

## 8. Save and resume

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R8.1 | Save draft at any step | ✅ | Existing intents | PB; E2E-B |
| R8.2 | Save the current step; resume from it | ✅ | `packages.builder_step`; drafts reopen there with a "Start from step 1" link; `?step=` always wins | WZ `a_draft_reopens_on_the_step_where_it_was_saved`; E2E-G |
| R8.3 | Completion %, last updated, incomplete sections | ✅ | Sidebar %, listing "N% complete", dashboard "Unfinished drafts" with Continue, unticked checklist items | WZ `the_package_list_and_dashboard_show_how_complete…`; E2E-G |
| R8.4 | Unsaved-changes warning | ✅ | Existing leave-page warning, "Unsaved changes" flag and restorable browser copy | E2E-B `unsaved changes are kept…` |

## 9. Copy and template confirmations

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R9.1 | Copying from another package says exactly what is copied and what it replaces, with counts | ✅ | `describeCopy()` in `package-builder.js` | E2E-G `copying from another package says exactly…` |
| R9.2 | Templates and duplicates explain what happens; nothing is overwritten silently | ◐ | Apply-template, save-as-template and duplicate dialogs name the sections affected and what is kept. They do not count rows (the template is applied on the server). | PB template tests |

## 10. Help content management

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R10.1 | Help kept in version-controlled files | ✅ | `resources/data/admin-guide.php`, `resources/data/package-builder-help.php`, read by `App\Support\Guide\GuideContent` | GD content tests fail if a section, answer or link goes missing |

## 11. Responsive and accessible

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R11.1 | No sideways scrolling | ✅ | Guide, tour, builder, review, publish | E2E-G `noSidewaysScroll` checks; E2E-B phone test covers all new steps |
| R11.2 | Keyboard navigation and visible focus | ✅ | Focus-visible outlines; `<details>` panels; tour keys | E2E-G keyboard tests |
| R11.3 | Progress available to screen readers | ✅ | `role=progressbar` with values; review announcements through a live region | Markup |
| R11.4 | Errors connected to fields | ✅ | `x-admin.error` ids + `initFieldErrors` (`aria-invalid`, `aria-describedby`) | Markup; PB error tests |
| R11.5 | Compact progress menu on phones | ✅ | "Step 4 of 14 · Room prices" button opens the list | E2E-G phone menu test |

## 12–13. Tests and documentation

- Tests: see [ADMIN_GUIDE_AND_WIZARD_TEST_REPORT.md](../testing/ADMIN_GUIDE_AND_WIZARD_TEST_REPORT.md).
- Architecture: [ADMIN_GUIDE_AND_WIZARD_ARCHITECTURE.md](../architecture/ADMIN_GUIDE_AND_WIZARD_ARCHITECTURE.md).
- Admin guide for office staff: [How to Create a Hajj Package — Step by Step](../guides/HOW_TO_CREATE_A_HAJJ_PACKAGE.md).

## Known limits

1. Help text is changed by editing the two content files, not from an admin screen. This is allowed by the requirement.
2. Review is advice, not a gate. Quick "Publish" from the package list still applies only the publishing rules. It does not require the Review step to have been seen.
3. All 12 live brochure packages show 85% complete. None has a main photo, and none has been through the new Review step yet. This is accurate, not a fault.
4. Existing packages have no per-day transport. The field is new and optional.
5. The tour covers the dashboard, menu and help. Inside the builder, help comes from the "Need help?" panels and the Review step instead of tour stops.
