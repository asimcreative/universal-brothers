# Admin CMS Enhancement — Content Fields and Security Audit

**Issue:** [#13](https://github.com/asimcreative/universal-brothers/issues/13) · **Date:** 2026-09-15 · **Scope:** everything issue #13 added or changed: section pages, rich text, media library uploads, saved sections, previews, SEO fields, AI settings screens and office map embeds. Architecture: [PAGE_BUILDER_ARCHITECTURE.md](../architecture/PAGE_BUILDER_ARCHITECTURE.md).

---

## 1. Content-field inventory

The rule applied: **the editor only where people write prose; structured values stay structured.** Every rich text field is cleaned when saved (`RichText::clean`) and when shown (`RichText::render`).

| Screen | Field | Before | After | Profile | Public output |
|---|---|---|---|---|---|
| Pages (builder) | Text / Story section text | whole-page HTML textarea | editor per section | full | `render('full')` |
| Pages (builder) | Text-with-image, two-column text | — | editor | standard | `render('standard')` |
| Pages (builder) | headings, buttons, card titles, figures, captions | — | plain inputs | — | escaped `{{ }}` |
| News | Body | textarea, rendered raw | editor | full | `render('full')` |
| News | Title, excerpt | plain | plain | — | escaped |
| FAQs | Answer | textarea (plain) | editor; empty answer refused | standard | `render`; JSON-LD uses `toPlainText` |
| Packages (Umrah / tourism) | Description | textarea | editor | standard | `render` |
| Hajj package builder | Basics description | textarea | editor | standard | `render` |
| Hajj package builder | Aziziya description and notes, Mashaer other services and notes, package notes | textarea | editor | basic | `render('basic')` |
| Content library | notes, Mashaer entries | textarea | editor | basic | `render('basic')` |
| Hajj / packages | prices, currencies, codes, days, dates, seats, hotel names, distances, room types, meals, transport rows | plain / numeric | **unchanged** | — | escaped |
| Hajj / packages | itinerary day notes, transport notes, inclusion items, upgrade rows | plain | **unchanged** (short list items, used in AI context) | — | escaped |
| Hotels, awards | description | plain | **unchanged** | — | escaped |
| Offices | map | raw iframe HTML pasted | Google Maps embed URL only (§3.6) | — | `src` attribute only |
| Media | alt text, caption | — | new plain fields | — | escaped attributes |
| Settings | labels | stored keys shown | plain names and help (`SettingLabels`) | — | escaped |

Existing plain-text rows were **not migrated**. `RichText::isHtml()` detects plain text, which renders escaped with `nl2br`, exactly as before (`test_older_plain_text_renders_the_way_it_always_did`).

## 2. Threat model

| # | Threat | Entry point | Control | Evidence |
|---|---|---|---|---|
| T1 | Stored XSS through formatted text | any editor field, the HTML source view, direct POST | server sanitiser on save **and** render. Allow-list tags per profile, default action block, `<script>/<style>/<template>/<noscript>/<title>/<head>` removed with their contents, event handler attributes dropped, link schemes limited to http/https/mailto/tel, media schemes to http/https | `RichTextTest::test_scripts_event_handlers_and_dangerous_links_are_removed`, `PageManagementTest::test_unsafe_formatted_text_is_cleaned_before_it_is_stored_and_shown`, `test_news_article_text_is_cleaned_when_saved_and_again_when_shown` |
| T2 | CSS injection / unreadable text | `style` attribute | `StyleSanitizer`: only `text-align` (4 values) and `color` from the 5-colour site palette | `test_only_allowed_video_players_and_site_colours_survive` |
| T3 | Malicious iframe (phishing frame, crypto miner) | video embed in text; video section | `EmbedSourceSanitizer` + `EMBED_PATTERN`: youtube(-nocookie).com/embed and player.vimeo.com only. The video section stores the URL and renders only `VideoEmbed::embedUrl()` output. | same test; `SectionValidator` video errors |
| T4 | Reverse tabnabbing | links with `target=_blank` | `LinkTargetSanitizer` forces `rel="noopener noreferrer"` | RichTextTest |
| T5 | Malicious upload (SVG script, polyglot, PHP disguised as image) | media library upload | mimes jpg/jpeg/png/webp/gif, the `image` rule, `getimagesize()` re-read, dimensions 40–8000 px, ≤ 5 MB, stored under a random `hashName()` with the extension from the detected type. `original_name` is never used as a path. SVG refused. | `MediaLibraryTest::test_unsafe_or_unsuitable_files_are_refused_in_plain_words` |
| T6 | Path traversal / arbitrary file reference | image fields (`path`), featured / OG images | `SectionValidator::isKnownImage()`: regex `^(media|pages)/…\.(jpe?g|png|webp|gif)$`, no `..`, must exist in `media_items` or on the public disk | PageManagementTest image tests |
| T7 | Open redirect / `javascript:` links in buttons | link fields | `isValidLink()`: `/path` (not `//`), `#anchor`, http(s) with a dotted host, `mailto:`, `tel:`. Everything else is refused with a suggestion. | `SectionValidator` link errors; E2E "bad links are explained" |
| T8 | Draft leakage | preview URL | admin session **and** temporary signed URL (120 min); `X-Robots-Tag: noindex, nofollow`; nothing written | `test_the_preview_needs_a_signature_as_well_as_an_admin_session`, `test_the_preview_shows_the_unpublished_draft_and_is_never_indexed` |
| T9 | Unpublished page reachable | public `/{slug}`, sitemap, AI knowledge | `Page::live()` everywhere (is_active + `published_at` ≤ now); `noindex` pages excluded from the sitemap | `test_creating_a_page_makes_a_draft…`, `test_scheduling_publishes_later…`, `test_unpublishing…` |
| T10 | Route hijack by page address | slug | regex, `RESERVED_SLUGS` (admin, ai, api, hajj, umrah, contact, storage, build, login, deploy, …), unique | `test_the_page_address_must_be_valid_unique_and_not_used_by_the_website` |
| T11 | Privilege escalation: live content changed without publishing | linked saved sections | inserting linked, **and editing / archiving / restoring a saved section that pages link to**, require the `link-saved-sections` gate (super admin). The UI disables Save with an explanation; the server returns 403. | `test_only_a_super_admin_can_insert_a_linked_saved_section`, `ContentBlockTest::test_only_a_super_admin_can_change_a_saved_section_that_pages_link_to` |
| T12 | Raw HTML editing by non-super admins | editor source view | `edit-source` gate; source is still sanitised on save | `test_only_super_admins_get_the_source_view` |
| T13 | API key exposure | AI settings page, key check | never rendered in HTML (`type=password`, never filled), masked display with only first and last characters, `checkKey` returns mapped messages and never the key or the provider body, `manage-ai-settings` gate on settings / key / reindex routes | `test_checking_the_key_reports_the_result_without_ever_showing_the_key`, `test_only_super_admins_can_open_or_change_the_ai_settings`, E2E "never show the key" |
| T14 | Icon class injection | AI assistant icon setting | `^bi-[a-z0-9-]+$` | `test_the_assistant_icon_must_be_an_icon_name` |
| T15 | Arbitrary iframe via office map | Offices form | `Office::normalizeMapEmbed()` extracts and accepts only `https://www.google.com/maps/embed…` (or `output=embed`) from a URL or pasted iframe; only that `src` is stored and rendered | `test_an_office_map_accepts_the_google_maps_embed_code…`, `…that_is_not_google_maps_is_refused…` |
| T16 | CSRF / method spoofing | all builder actions | Laravel CSRF on every form and fetch (`X-CSRF-TOKEN` from `ui.js`); state changes only via POST/PUT/PATCH/DELETE; the section-form and media list GETs are read-only | route list |
| T17 | Mass assignment | page and block saves | explicit `Validator` rules; `PageDocument::make()` keeps only its 12 keys; `SectionValidator` keeps only declared fields; `forceFill` only with server-built arrays | code review |
| T18 | Denial of service by huge input | editor content, sections | sanitiser max input 500,000 chars; per-field `max`; 60 sections per page; 24 gallery images; revisions pruned to 30 per page | SectionValidator |
| T19 | Guests / inactive admins | every new admin route | existing `auth` + active-admin middleware | `test_guests_cannot_reach_any_page_builder_screen`, `test_an_inactive_admin_is_sent_back_to_the_login`, `MediaLibraryTest::test_guests_cannot_list_or_upload`, `ContentBlockTest::test_guests_cannot_reach_saved_sections` |

## 3. Findings raised and fixed during this work

1. **Nested form submitted the page** (functional + data-integrity). The editor's link, colour, image-description and video panels were `<form>` elements inside the builder's form, so "Add link" saved the whole page. The panels are now `div role="group"` with explicit apply buttons, Enter applies and Escape closes. Covered by E2E.
2. **Toolbar unreachable by keyboard** (accessibility). The toolbar's single Tab stop was "Undo", which was `disabled` on a fresh editor, so Tab and Alt+F10 could not reach any formatting button. Unavailable buttons now use `aria-disabled` and stay focusable. Covered by E2E.
3. **Duplicated section kept an open, uncloseable menu** (UI). The clone copied Bootstrap's `show` state, so an orphan menu covered other controls. Menus are reset on clone and the original's menu is closed. Covered by E2E.
4. **Linked saved sections could be changed by any admin** (authorisation, T11). Linking was restricted to super admins, but editing the linked target was not, which let a content editor change live pages without publishing. Now gated. Feature test added.
5. **Head elements leaked as visible text** (sanitiser). `<style>p{…}</style>` left `p{…}` in the page because the sanitiser drops only the tags. They are now removed with their contents before sanitising.
6. **Office map stored arbitrary iframe HTML** (T15). Only a validated Google Maps embed address is kept.

## 4. Secret and privacy checks

- The working tree was scanned (149 changed and new files, excluding `public/build`) for API keys (`sk-…`, `AKIA…`, `ghp_…`, Slack tokens), private keys, password assignments, database passwords and WHM tokens. **Result: no real secrets.** The only match is the obviously fake constant `sk-usability-test-key-1234567890abcd` in `tests/Feature/Admin/AdminContentUsabilityTest.php`, used to prove the key is never rendered.
- `.env` is git-ignored. The OpenAI key exists only in `.env` and is never logged, printed or rendered.
- No production credentials appear in code, tests, docs or fixtures.
- The E2E fixture `tests/e2e/fixtures/arafat-photo.webp` is a 560 px copy of a photo already used on the public site.
- All automated tests ran against local databases only: PHPUnit on in-memory SQLite, Playwright on `database/testing.sqlite` (`--env=testing`, port 8129). The production database was never touched.
- No diagnostic scripts were added to the web root and no cron entries were created.

## 5. Residual risks and recommendations

| Risk | Rating | Note |
|---|---|---|
| A super admin can still paste any sanitiser-allowed HTML in the source view | Low | Output is sanitised; allowed markup is harmless by construction. |
| Scheduling a live page removes it until the scheduled time | Low (functional) | The dialog states it. No cron by project rule. |
| `linkedPages()` finds links with a JSON `LIKE` search | Low | Exact `"block_id":N,` / `N}` patterns; fine at this site's page count. Revisit if pages reach thousands. |
| Uploaded images are served as stored (no EXIF stripping / re-encoding) | Low | Admin-only uploads. Consider re-encoding to strip location metadata from phone photos. |
| Content editors can publish pages | By design | Two-role model documented in `routes/web.php`; only account management, AI settings, linking and source editing are super-admin-only. |
