# Admin CMS Enhancement — Requirements and Traceability

**Issue:** [#13](https://github.com/asimcreative/universal-brothers/issues/13) · **Builds on:** [#10](https://github.com/asimcreative/universal-brothers/issues/10), [#11](https://github.com/asimcreative/universal-brothers/issues/11) · **Architecture:** [PAGE_BUILDER_ARCHITECTURE.md](../architecture/PAGE_BUILDER_ARCHITECTURE.md)

**Goal:** office staff build and change website pages visually, without code. They get formatted text where people write prose, reusable sections, images with descriptions, a safe draft → preview → publish flow with history, and error messages that say what is wrong, where, and how to fix it. The AI assistant screens now match the rest of the admin, and the API key is never exposed.

Each requirement below links to where it is built and to the test that proves it:

- **PM**: `tests/Feature/Admin/PageManagementTest.php` (35 tests)
- **RT**: `tests/Feature/RichTextTest.php` (12 tests)
- **ML**: `tests/Feature/Admin/MediaLibraryTest.php` (6 tests)
- **CB**: `tests/Feature/Admin/ContentBlockTest.php` (8 tests)
- **UX**: `tests/Feature/Admin/AdminContentUsabilityTest.php` (7 tests)
- **GD**: `tests/Feature/Admin/AdminGuideTest.php`
- **E2E**: `tests/e2e/page-builder-cms.spec.js` (11 tests)
- **E2E-A**: `tests/e2e/admin.spec.js`, `tests/e2e/journeys-admin.spec.js` (updated for the builder)

Status key: ✅ built and tested · ◐ built, with a limit described in the row.

---

## 1. Rich text editing

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R1.1 | Editor with headings, bold / italic / underline / strike, lists, alignment, links, quotes, horizontal line, tables, images with descriptions, safe video embeds, site-palette text colour, clear formatting, undo / redo, full screen, word and character count | ✅ | `resources/js/admin/rich-text.js`, `x-admin.rich-text` | E2E "formatted text is written with the editor toolbar…"; RT `test_supported_formatting_is_kept` |
| R1.2 | HTML source view for advanced users only | ✅ | `edit-source` gate | RT `test_only_super_admins_get_the_source_view` |
| R1.3 | Pasted and submitted HTML is made safe; scripts, event handlers, unsafe links, foreign iframes and arbitrary styles are removed | ✅ | `App\Support\Content\RichText` + 3 attribute sanitizers | RT `test_scripts_event_handlers_and_dangerous_links_are_removed`, `test_only_allowed_video_players_and_site_colours_survive`; PM `test_unsafe_formatted_text_is_cleaned_before_it_is_stored_and_shown` |
| R1.4 | Used wherever people write prose: page text, news body, FAQ answers, package descriptions, Hajj notes / Aziziya / Mashaer text, content-library notes | ✅ | see the audit's field inventory | RT `test_faq_answers_…`, `test_the_faq_form_cleans_…`, `test_news_article_text_is_cleaned_…`, `test_editor_fields_render_as_progressive_textareas_in_the_admin` |
| R1.5 | Structured fields (prices, dates, codes, hotels, rooms, transport) stay structured | ✅ | unchanged inputs | existing package builder tests still pass |
| R1.6 | Existing plain-text content keeps rendering exactly as before; no data migration | ✅ | `RichText::isHtml` / `render` | RT `test_older_plain_text_renders_the_way_it_always_did` |
| R1.7 | Plain text where HTML does not belong (meta description, JSON-LD, AI knowledge) | ✅ | `RichText::toPlainText` | RT `test_plain_text_for_search_engines_and_the_assistant` |
| R1.8 | Works without JavaScript, and is keyboard accessible (toolbar Tab stop, Alt+F10, Escape) | ✅ | textarea fallback, roving tabindex, `aria-disabled` | E2E "keyboard users reach the editor toolbar…" |

## 2. Page builder

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R2.1 | Pages built from a library of ready-made sections (26 kinds in 8 groups, searchable) | ✅ | `BlockRegistry`, `pages/blocks/*.blade.php`, library modal | PM `test_the_section_library_returns_a_ready_to_edit_section_card`, `test_live_data_sections_render_from_the_admin_managed_records`; E2E |
| R2.2 | Create from a starting layout (simple, information, service, company) | ✅ | `PageStarters` | PM `test_creating_a_page_makes_a_draft_from_the_chosen_layout…`; E2E |
| R2.3 | Reorder by drag and drop, **and** by up / down buttons, **and** by keyboard arrows on the handle | ✅ | `page-builder.js` (SortableJS pointer fallback) | PM `test_the_submitted_order_becomes_the_page_order`; E2E "sections are added, reordered by button, keyboard and dragging…" |
| R2.4 | Duplicate, hide / show, delete with confirmation, undo | ✅ | `page-builder.js` | PM `test_hidden_sections_are_kept_but_not_shown`, `test_a_duplicated_section_is_saved_as_its_own_section`, `test_a_deleted_section_is_removed_from_the_page`; E2E |
| R2.5 | Plain labels, help and examples on every field; no technical keys shown | ✅ | `partials/fields.blade.php` | PM `test_the_builder_opens_with_the_sections_and_their_plain_labels` |
| R2.6 | Existing pages (About Us, policies) open as sections; the live page is unchanged until publish | ✅ | `PageDocument::sectionsFromLegacy` | PM `test_a_page_built_before_sections_opens_with_its_content_as_sections…`; E2E-A tests 24 and Journey E |
| R2.7 | Sections that show live data (packages, testimonials, awards, affiliations, FAQs, offices, news, figures) stay current automatically | ✅ | `PageRenderer::extra` | PM `test_live_data_sections_render_from_the_admin_managed_records` |
| R2.8 | The homepage | ◐ | — | Still a coded page; its content is managed in its own screens. |

## 3. Media and images

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R3.1 | Image picker with library, search and upload, usable from any image field and the editor | ✅ | `media-picker.js`, `MediaLibraryController` | ML `test_the_library_lists_and_searches_images…`; E2E "an image is uploaded with its description through the media picker" |
| R3.2 | Image description (alt text) asked for at upload and required before publishing informative images; decorative banners exempt | ✅ | `SectionValidator::image` | PM `test_missing_image_descriptions_block_publishing_but_decorative_banners_do_not` |
| R3.3 | Safe upload validation with plain messages (type, size, dimensions, real image, no SVG, random names) | ✅ | `MediaLibraryController@store` | ML `test_unsafe_or_unsuitable_files_are_refused_in_plain_words`, `test_an_admin_can_upload_an_image_with_its_description` |
| R3.4 | Images uploaded for pages do not appear in the public gallery | ✅ | `media_items.collection` | ML `test_images_uploaded_for_pages_never_appear_in_the_public_gallery`, `test_the_gallery_admin_shows_descriptions_and_the_uploaded_tab` |

## 4. Reusable sections

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R4.1 | Save a section for reuse from the builder, or create one on its own screen | ✅ | `PageController::saveSectionAsBlock`, `ContentBlockController` | PM `test_a_section_can_be_saved_for_reuse_from_the_builder`; CB `test_choosing_a_kind_then_creating_a_saved_section`; E2E |
| R4.2 | Inserting makes an independent copy by default | ✅ | `sectionForm` `mode=copy` | PM `test_a_saved_section_is_inserted_as_an_independent_copy_by_default`; E2E |
| R4.3 | Optional linked mode that always shows the latest content, restricted to super admins | ✅ | `saved_block` type, `link-saved-sections` gate | PM `test_only_a_super_admin_can_insert_a_linked_saved_section`, `test_a_linked_saved_section_shows_its_latest_content_on_the_page` |
| R4.4 | Editing a linked saved section says which pages change; only super admins can change, archive or restore one that pages link to; one in use cannot be deleted | ✅ | `ContentBlockController` | CB `test_editing_a_linked_saved_section_warns_…`, `test_only_a_super_admin_can_change_a_saved_section_that_pages_link_to`, `test_a_linked_saved_section_cannot_be_deleted_but_can_be_archived` |
| R4.5 | Preview, duplicate, archive, restore, search and groups | ✅ | Saved Sections screens | CB `test_the_preview_shows_the_section_in_the_site_design`, `test_an_unused_saved_section_can_be_deleted_and_duplicated` |

## 5. Draft, preview, publish, revisions

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R5.1 | Saving a draft never changes the live page | ✅ | `pages.draft` | PM `test_saving_a_draft_keeps_the_live_page_unchanged` |
| R5.2 | Private preview of the draft at desktop, tablet and phone widths; never indexed | ✅ | signed `preview` route, preview modal | PM `test_the_preview_needs_a_signature_…`, `test_the_preview_shows_the_unpublished_draft_and_is_never_indexed`, `test_save_and_preview_saves_then_returns_to_open_the_preview`; E2E |
| R5.3 | Publish, unpublish, archive / restore, discard unpublished changes, duplicate page, delete | ✅ | `PageController` | PM `test_publishing_puts_the_sections_on_the_website_and_keeps_a_version`, `test_unpublishing_…`, `test_archiving_and_restoring_a_page`, `test_duplicating_a_page_creates_a_private_draft_copy`, `test_admin_can_delete_a_page` |
| R5.4 | Schedule for later | ◐ | `status=scheduled`, `published_at` | PM `test_scheduling_publishes_later…`, `test_a_scheduled_time_in_the_past_is_refused`. Scheduling a page that is already live takes it offline until then (stated in the dialog). |
| R5.5 | Version history with restore as draft (30 kept per page) | ✅ | `page_revisions` | PM `test_an_earlier_version_can_be_restored_as_the_draft` |
| R5.6 | Unsaved-changes warning when leaving the builder | ✅ | `beforeunload` in `page-builder.js` | manual check |

## 6. Validation messages

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R6.1 | Each message says **what** is wrong, **where** (section number and name, item number) and **how** to fix it | ✅ | `SectionValidator` messages | PM `test_publishing_with_problems_saves_the_draft_and_explains_each_problem_by_section` |
| R6.2 | A summary at the top links to each problem and opens the right section; invalid fields are highlighted | ✅ | `x-admin.validation-summary`, `errorLinks` | E2E "publishing stops on a problem, links to it, then publishes the page" |
| R6.3 | Nothing typed is lost after a failed save or publish | ✅ | old-input rebuild | PM `test_a_failed_save_keeps_everything_that_was_typed` |
| R6.4 | Friendly page-address, link, video and image messages with suggestions | ✅ | `messages()`, `linkSuggestion` | PM `test_the_page_address_must_be_valid_unique…`; E2E link explanation |
| R6.5 | Cannot publish a page with no visible sections | ✅ | `publishDraft` | PM `test_a_page_with_only_hidden_sections_cannot_be_published` |

## 7. SEO

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R7.1 | Search title and description with length guidance and a Google result preview | ✅ | `x-admin.seo-fields` | PM `test_search_and_sharing_fields_reach_the_public_page` |
| R7.2 | Focus phrase tips, canonical link, hide from search engines (also removed from the sitemap) | ✅ | same, `SitemapController` | same |
| R7.3 | Social sharing title, description and image with a card preview | ✅ | `partials/page-seo.blade.php` | same |

## 8. AI admin screens

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R8.1 | Settings, test panel and conversation screens use the shared admin design (panels, stat cards, status badges, empty states, one page title) | ✅ | `admin/ai/*.blade.php` | UX `test_ai_pages_use_the_shared_admin_layout_and_components`; E2E "the AI pages share the admin design…" |
| R8.2 | Settings grouped with plain labels and help; icon choice validated | ✅ | `admin/ai/index.blade.php` | UX `test_the_assistant_icon_must_be_an_icon_name` |
| R8.3 | API key masked, never sent to the browser, replaceable, clearable with confirmation, and checkable without being shown | ✅ | `AiAssistantController@checkKey` | UX `test_checking_the_key_reports_the_result_without_ever_showing_the_key`; E2E |
| R8.4 | Only super admins open or change AI settings | ✅ | `manage-ai-settings` gate | UX `test_only_super_admins_can_open_or_change_the_ai_settings` |

## 9. Shared components, other screens

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R9.1 | Reusable admin components: panel, stat card, status badge, help tip, validation summary, empty state, image picker, rich text, SEO fields | ✅ | `resources/views/components/admin/` | used across pages, saved sections, media and AI screens |
| R9.2 | Office map accepts the Google Maps embed code and keeps only its address, with instructions | ✅ | `Office::normalizeMapEmbed` | UX `test_an_office_map_accepts_…`, `test_an_office_map_that_is_not_google_maps_is_refused…` |
| R9.3 | Site settings show plain names instead of stored keys | ✅ | `SettingLabels` | UX `test_site_settings_show_plain_names_instead_of_stored_keys` |

## 10. Responsive and accessible

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R10.1 | Builder, saved sections, pages list and AI screens fit a 390 px phone with no sideways scrolling; 44 px touch targets | ✅ | `_admin-cms.scss` | E2E "the builder and the AI settings fit a phone screen" |
| R10.2 | Every builder control has an accessible name; status changes announced in a live region; focus managed after move / delete / undo | ✅ | `ui.js` `announce`, `page-builder.js` | E2E "…every builder control has a name" |
| R10.3 | Only one `<h1>` on a built page; section headings are `<h2>` | ✅ | `page-sections.blade.php` | E2E publish test |

## 11. Admin guide and documentation

| # | Requirement | Status | Where | Proof |
|---|---|---|---|---|
| R11.1 | New guide sections: building a page, writing formatted text, images and descriptions, saved sections, search results and sharing, when a page says something needs fixing; the AI assistant, settings and website content sections updated | ✅ | `resources/data/admin-guide.php` (32 sections) | GD (every section renders, links work, no technical words) |
| R11.2 | Staff guide, architecture, requirements, security audit, test report | ✅ | [HOW_TO_BUILD_A_PAGE.md](../guides/HOW_TO_BUILD_A_PAGE.md), [PAGE_BUILDER_ARCHITECTURE.md](../architecture/PAGE_BUILDER_ARCHITECTURE.md), this file, [ADMIN_CMS_CONTENT_SECURITY_AUDIT.md](../audits/ADMIN_CMS_CONTENT_SECURITY_AUDIT.md), [ADMIN_CMS_ENHANCEMENT_TEST_REPORT.md](../testing/ADMIN_CMS_ENHANCEMENT_TEST_REPORT.md) | — |

## 12. Out of scope / limitations

- The homepage remains a coded page (R2.8).
- Itinerary day notes, transport notes, inclusion items, upgrade rows, and hotel and award descriptions stay plain text.
- Scheduling a live page takes it offline until the scheduled time (R5.4).
- Revisions have no visual diff.
- A linked saved section cannot be overridden per page; use a copy instead.
