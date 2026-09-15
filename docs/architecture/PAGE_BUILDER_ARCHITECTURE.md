# Page Builder, Rich Text and Media Library — Architecture

**Issue:** [#13](https://github.com/asimcreative/universal-brothers/issues/13). Read [ARCHITECTURE.md](ARCHITECTURE.md) and [ADMIN_UI_DESIGN.md](ADMIN_UI_DESIGN.md) first. This document covers what issue #13 added: section-based CMS pages, the rich text editor and its sanitiser, the media library and image picker, saved (reusable) sections, draft / preview / publish / revisions, the page SEO panel, and the AI admin screens moved onto the shared admin components.

---

## 1. Principles

1. **The page's own columns are the published version.** Visitors only ever see `pages.*` columns. Everything an admin changes goes to `pages.draft` first. Publishing copies the draft into the columns in one transaction and records a revision. A preview renders the draft without writing anything.
2. **Nothing typed is thrown away.** Saving a draft never fails because content is incomplete: section problems come back as warnings and the draft is stored. The same checks become blocking errors only when publishing. After a failed publish the builder is rebuilt from the submitted input.
3. **HTML is sanitised on the way in and on the way out.** `RichText::clean()` runs when a value is saved and `RichText::render()` runs when it is shown. A row written by a seeder, an import, or before this feature existed can therefore never inject markup.
4. **Older plain-text content is never rewritten.** `RichText` recognises plain text, and shows it escaped with its line breaks, exactly as before. Nothing in the database is migrated. A value becomes HTML only when someone edits and saves it.
5. **The server owns every rule.** Block definitions, field limits, required fields, link and video checks, and image checks live in PHP (`BlockRegistry`, `SectionValidator`). The browser gives early hints using the same wording (`resources/js/admin/links.js`), but the server decides.
6. **Structured data stays structured.** Prices, dates, hotel names, package codes and similar fields remain plain inputs. The editor is used only where people write prose (see §9).

## 2. Data model

Three reversible migrations. Each `down()` drops exactly what its `up()` adds. Rollback and re-run were verified on the local and testing databases.

### `2026_09_17_100000_add_library_fields_to_media_items_table`

| Column | Type | Purpose |
|---|---|---|
| `collection` | string(20), default `gallery`, indexed | `gallery` = public Media page · `library` = uploaded for pages and text |
| `alt_text` | string, null | Image description for screen readers and search |
| `caption` | string(500), null | Optional caption |
| `original_name` | string, null | The uploader's file name (display and search only, never used as a path) |
| `mime_type`, `file_size`, `width`, `height` | null | Recorded from the real file after upload |
| `uploaded_by` | FK users, null on delete | Who uploaded it |

Existing rows default to `gallery`, so the public Media page is unchanged.

### `2026_09_17_100100_add_builder_fields_to_pages_table`

| Column | Type | Purpose |
|---|---|---|
| `status` | string(20), indexed | `draft` · `published` · `scheduled` · `archived`. Back-filled from `is_active`. |
| `sections` | longText JSON, null | Published sections. `null` = a page not yet published from the builder (rendered by the legacy template). |
| `draft` | longText JSON, null | The working copy — a `PageDocument` (§4). `null` = no unpublished changes. |
| `focus_keyword` | string(100), null | SEO panel hint only |
| `og_title`, `og_description`, `og_image` | null | Social sharing overrides |
| `noindex` | boolean, default false | Hide the page from search engines and the sitemap |
| `updated_by`, `published_by` | FK users, null on delete | Attribution |

`is_active` keeps its meaning: "may be shown publicly". The `Page::saving` hook keeps `status` and `is_active` consistent. `Page::live()` is `is_active = true AND (published_at IS NULL OR published_at <= now)`. The public page route, the sitemap and the AI knowledge index use it.

### `2026_09_17_100200_create_page_revisions_and_content_blocks_tables`

| Table | Columns | Purpose |
|---|---|---|
| `page_revisions` | page_id (FK cascade), action(30), data (JSON `PageDocument`), created_by, timestamps; index (page_id, created_at) | A copy of the page at each publish / schedule / unpublish / restore. `PageRevision::KEEP = 30` per page; older rows are pruned on write. |
| `content_blocks` | name(120), description(500), category(40), type(40), data (JSON), is_archived, created_by, updated_by | Saved sections |

## 3. Section JSON schema

A section, as stored in `pages.sections`, `pages.draft.sections`, `page_revisions.data.sections`:

```json
{
  "id": "s_k3j9x0q2ab",
  "type": "text_image",
  "visible": true,
  "data": { "...": "fields defined by BlockRegistry for this type" }
}
```

| Key | Rule |
|---|---|
| `id` | `/^s_[a-z0-9]{6,24}$/`, unique within the page. A missing, malformed or duplicate id is replaced. It is the anchor for error links (`#section-{id}`) and the public `id="section-{id}"`. |
| `type` | A key of `BlockRegistry::all()`. An unknown type is dropped with a warning. |
| `visible` | Hidden sections are saved and published but never rendered. Their problems are always warnings. |
| `data` | Only the fields the definition declares. Everything else submitted is discarded. |

Maximum 60 sections per page.

### Field types and their stored values

| Type | Stored as | Validation |
|---|---|---|
| `text` / `textarea` | string (control characters removed, trimmed) | `max` characters (default 255 / 1000) |
| `richtext` | sanitised HTML string (`profile`: full / standard / basic) | length `max` after cleaning; required = has visible text |
| `link` | string | `/page`, `#anchor`, `https://…` with a dotted host, `mailto:`, `tel:`. The error message suggests a fix ("Add https:// at the start…"). |
| `video` | original URL | must convert with `VideoEmbed::embedUrl()` (youtube.com/watch, youtu.be, youtube.com/embed, /shorts, /live, vimeo.com). Rendered only through `youtube-nocookie.com` or `player.vimeo.com`. |
| `image` | `{"path": "media/library/…webp", "alt": "…"}` | the path must match `^(media|pages)/…\.(jpe?g|png|webp|gif)$`, have no `..`, and exist in `media_items` or on the public disk. `alt` is required when the field has `alt: true` (decorative banners use `alt: false`). |
| `select` / `icon` | option key | an unknown value falls back to the default |
| `toggle` | bool | — |
| `number` / `hidden` | int or null | clamped to `min` / `max` |
| `items` | list of objects with the nested `fields` | `min_items` / `max_items`; nested errors are reported per item ("Card 2") |

Definition flags: `required`, `show_when` (a field counts only when another field has a value), `pair` (a button text and its link must be filled together), `default`, `col`, `help`, `placeholder`.

### Block types (26 in the library, plus 1 internal)

| Group | Types |
|---|---|
| Banners | `hero` Page banner · `hero_slider` Banner slider · `cta` Call-to-action banner |
| Text and images | `text` · `text_image` · `image_text` · `two_columns` |
| Cards and figures | `cards` · `stats` Figures · `services` |
| Packages | `packages_hajj` · `packages_umrah` · `packages_tourism` (live from the packages tables) |
| Company and trust | `company_story` · `testimonials` · `awards` · `affiliations` |
| Video and photos | `video` · `gallery` |
| Contact and news | `faqs` · `contact_form` Enquiry form · `offices` · `news` Latest news |
| Buttons and spacing | `buttons` · `spacer` Space or divider |
| internal | `saved_block` — `{"block_id": 12}`, a linked saved section (§6) |

Blocks that show live data (packages, testimonials, awards, affiliations, FAQs, offices, news, company figures, gallery from the Media page) read it at render time in `PageRenderer::extra()`. The Blade views run no queries. A section that would render empty (for example "Latest news" with no published news) is skipped.

## 4. `PageDocument` — the unit of drafting

`app/Support/PageBuilder/PageDocument.php` is everything an admin edits about a page, as one value:

```
title, slug, featured_image, sections,
meta_title, meta_description, focus_keyword, canonical_url,
og_title, og_description, og_image, noindex
```

| Method | Use |
|---|---|
| `forEditing($page)` | the draft if there is one, else the published page |
| `fromPublished($page, seedLegacy)` | the columns as a document. With `seedLegacy`, a page never built with sections is converted by `sectionsFromLegacy()`: About → story + figures + awards + affiliations; anything else → one Text section. The live page is untouched until publish. |
| `applyTo($page)` | writes the document into the columns (publishing, and the in-memory preview copy) |
| `bodyHtml()` | the visible sections' written content as one cleaned HTML string, stored in `pages.body` so existing readers (AI knowledge, meta-description fallback) see published words |

## 5. Request flow

```
Builder form (PUT admin/pages/{page}, intent=…)
  ├─ Laravel validation: title, slug (regex, reserved words, unique), SEO fields, images, publish_at
  ├─ SectionValidator(strict: false) → sections + warnings
  ├─ saveDraft(): pages.draft = PageDocument
  │     (a page that is not live also takes the new title/slug at once)
  └─ intent
       save            → back to builder, "Draft saved", warnings listed with jump links
       preview         → same, and the builder opens the preview modal
       save_block:{id} → also create a content_block from that section
       publish/schedule→ publishDraft()
                           SectionValidator(strict: true)
                           + at least one visible section, slug still free
                           errors → redirect with errors keyed sections.{id}.data.{field}
                                    (the summary links to #section-{id}; the draft is already saved)
                           ok     → DB::transaction: applyTo, status, is_active, published_at,
                                    draft = null, page_revisions row (prune to 30)
```

Other actions: `publish` from the list (same checks on the stored draft), `unpublish` (revision, then draft status), `archive` / `restore`, `discardDraft` (only when a published version exists), `duplicate` (new section ids, unique slug, draft), `revisions/{revision}/restore` (the revision becomes the draft and keeps the current slug), `destroy`.

**Preview:** `GET admin/pages/{page}/preview` requires an admin session **and** a temporary signed URL (120 minutes). It replicates the page in memory, applies the draft and renders the public `PageController::render()` with a "Preview" banner. The response carries `X-Robots-Tag: noindex, nofollow`. Nothing is saved.

**Public render:** `PageController::show()` loads `Page::live()->where('slug', …)`. When `sections` is an array it uses `page-sections.blade.php`: the standard title banner unless the first visible section is a banner, then one view per section from `resources/views/pages/blocks/`. Only the first banner gets `<h1>`; every other section heading is `<h2>`. Otherwise it uses the legacy `page.blade.php`.

## 6. Saved sections (`content_blocks`)

- **Save for reuse** from a section's menu in the builder, or create one under **Saved Sections**. A saved section is validated strictly, as if publishing.
- **Insert a copy** (default, any admin): `GET admin/pages/section-form?block={id}&mode=copy` returns an ordinary section with the block's data. The page owns it from then on.
- **Insert linked** (`link-saved-sections` gate, super admin): the section is `{"type": "saved_block", "data": {"block_id": id}}`. `PageRenderer` substitutes the block's current data at render time. A link never nests another link. An archived or deleted target is skipped on the public page and blocks publishing with an explanation.
- `ContentBlock::linkedPages()` finds pages whose published sections or draft contain `"block_id":{id}`. The edit screen lists them, with "(live)" beside published ones, and asks for confirmation before saving.
- **Editing, archiving or restoring a saved section that pages link to requires the same gate.** The change reaches live pages without anyone pressing Publish. Other admins see the Save button disabled with an explanation, the server returns 403, and they can duplicate it instead.
- A linked saved section cannot be deleted. Archive it, or remove the links first.

## 7. Rich text

### Server: `app/Support/Content/RichText.php`

symfony/html-sanitizer, one cached sanitizer per profile, default action **block** (a disallowed wrapper is removed, its text kept):

| Profile | Allowed | Used by |
|---|---|---|
| `full` | p, br, h2–h4, strong/em/u/s, ul/ol/li, a, blockquote, hr, table/thead/tbody/tr/th/td, figure/img, figcaption, the video-embed wrapper + iframe | page Text / Story sections, news body |
| `standard` | headings, lists, links, quotes, alignment | package descriptions, FAQ answers, two-column text |
| `basic` | bold, italic, underline, lists, links | Hajj package notes, Aziziya notes, Mashaer services and notes |
| `inline` | bold, italic, links, line breaks | text inside a sentence or list item |

Custom attribute sanitizers:

- `LinkTargetSanitizer`: `target` only `_blank`, which forces `rel="noopener noreferrer"`.
- `StyleSanitizer`: `style` only `text-align:left|center|right|justify` and `color:` from `RichText::COLORS`, the site palette.
- `EmbedSourceSanitizer`: iframe `src` only matching `EMBED_PATTERN` (youtube-nocookie/youtube embed, player.vimeo.com).

Before sanitising, `script|style|template|noscript|title|head` elements are removed with their contents, and `meta|link|base` tags are removed, so their text never survives as visible words. After it, iframes that lost their `src`, images without `src` and empty embed wrappers are removed. `javascript:`/`data:` URLs are dropped by the sanitizer's scheme lists. Maximum input is 500,000 characters.

Other helpers: `toPlainText()` (search descriptions, JSON-LD, the AI assistant's knowledge and package context), `isEmpty()`, `wordCount()`, `forEditor()`.

### Browser: `resources/js/admin/rich-text.js`

- Tiptap 3: StarterKit, TextAlign, TextStyle + Color, TableKit, Image, CharacterCount, Placeholder, and a custom `videoEmbed` node.
- **Progressive enhancement.** A `[data-rich-text]` wrapper contains an ordinary `<textarea>` that the form submits. The editor is built beside it and keeps it in sync. The textarea's `value` setter is wrapped so code that sets it also updates the editor. Without JavaScript, or if the editor fails, the textarea remains.
- The bundle loads lazily (dynamic `import()` from `admin.js`) only on pages that have an editor: ~145 KB gzip, admin only. A `MutationObserver` starts editors in sections added later, and rebuilds a cloned editor (duplicate / undo) from its textarea.
- **Toolbar:** a `role="toolbar"` with a roving tabindex (one Tab stop, arrow keys, Home/End). Alt+F10 moves from the text to the toolbar and Escape returns. Unavailable buttons (Undo with nothing to undo) use `aria-disabled`, so the toolbar never loses its Tab stop.
- **Link, colour, image description and video panels** are `div role="group"`, not `<form>`: the editor sits inside the page's own form, and nested forms submit the outer one. Enter applies a panel and Escape closes it.
- **HTML source view** only for the `edit-source` gate (super admin). The source is still sanitised on save.
- Ctrl+K for links, full-screen mode, a live word/character counter, and paste cleaned by the schema plus the server sanitiser.

## 8. Media library and image picker

- `GET admin/media-library` (JSON, search, 24 per page), `POST admin/media-library` (upload), `PATCH admin/media-library/{item}` (alt text, title, caption).
- Uploads accept JPG / PNG / WebP / GIF only. **SVG is refused** because it can carry script. The limits are ≤ 5 MB and 40–8000 px per side. Each file is re-read with `getimagesize()`, stored with `store('media/library')` under a random name whose extension comes from the detected content type, and recorded with its real mime type, size and dimensions. Errors are written for office staff ("This image is too large. Choose one smaller than 5 MB…").
- `resources/js/admin/media-picker.js` builds a single modal outside every form, with Library / Upload tabs and a details pane. `openMediaPicker({ title, requireAlt, suggestedAlt })` resolves to the chosen item. `[data-media-choose]` fields store `path` and `alt` in hidden inputs and show a preview.
- The Media admin screen has tabs for **Website gallery** and **Uploaded for pages and text**. Only `gallery` items appear on the public Media page.

## 9. Where the editor is used, and where it is not

| Editor (profile) | Stays a structured / plain field |
|---|---|
| Page sections: Text, Story (`full`); image+text, two columns (`standard`) | Headings, buttons, links, stats, card titles (`text`) |
| News article body (`full`) | News title, excerpt, dates |
| FAQ answer (`standard`) | FAQ question, category |
| Package description (`standard`), Hajj package basics description (`standard`) | Prices, currencies, codes, days, dates, seats |
| Hajj: Aziziya description and notes, Mashaer other services and notes, package notes (`basic`) | Hotel names, distances, room types, meal plans, transport rows |
| Content library "notes" and Mashaer entries (`basic`) | Itinerary day notes, transport notes, inclusion items, upgrade rows, hotel and award descriptions (plain, see limitations) |

Every public view that shows one of these uses `RichText::render()`. Views that output plain text (JSON-LD, meta tags, AI context) use `toPlainText()`.

## 10. SEO panel

`<x-admin.seo-fields>` provides the search title, description, focus keyword, canonical link, sharing title / description / image, and "hide from search engines".

- A live Google result preview, and a social card preview.
- Character guidance (about 60 / 160). The focus keyword is checked for presence in the search title, the search description and the page text.
- Everything is stored in the draft and published with the page. `partials/page-seo.blade.php` fills the layout's `canonical`, `robots`, `og:title`, `og:description` and `og:image`.
- `noindex` pages are left out of `sitemap.xml`.

## 11. Permissions

| Gate | Who | Protects |
|---|---|---|
| `manage-ai-settings` | super admin | `admin/ai` settings, key replace / clear / check and reindex (route middleware), and the AI settings nav link. The AI test panel and the conversation log stay open to both admin roles, as before. |
| `link-saved-sections` | super admin | inserting a linked saved section; editing, archiving or restoring a saved section that pages link to |
| `edit-source` | super admin | the editor's HTML source view |

Everything else under `admin/` keeps the existing `auth` + active-admin middleware. Office map embeds (`Office::normalizeMapEmbed()`) accept only an `https://www.google.com/maps/embed…` or `output=embed` URL. Only the extracted `src` is stored and rendered, never pasted iframe HTML.

## 12. AI admin screens

`admin/ai/index`, `test`, `conversations` and `conversation` were rebuilt on `x-admin.panel`, `x-admin.stat-card`, `x-admin.status-badge`, `x-admin.help-tip` and `x-admin.empty-state`. Each has one page title and flash messages from the layout.

API key handling:

- The input is `type=password` and is never filled from the stored value.
- The screen shows only whether a key is set, where it comes from (the server's environment or saved in the admin), and a masked form with just its first and last characters. The full key is never sent to the browser.
- **Replace** needs a new value, and **Clear** asks for confirmation.
- **Check the key** (`POST admin/ai/key/check`) sends one tiny request through `AiClient`. It maps failures to plain messages (wrong key, no credit, network, provider down) and never echoes the key or the provider's raw response.

## 13. Front-end modules

```
resources/js/admin.js            imports; initLazyModules() loads page-builder / rich-text only where used;
                                 initHelpTips, initSlugFields, initMediaFields
resources/js/admin/ui.js          csrfToken, escapeHtml, askConfirm (shared confirm modal), announce (live region), toast (with action)
resources/js/admin/links.js       isValidLink / linkSuggestion / videoEmbedUrl — same wording as SectionValidator
resources/js/admin/page-builder.js
    SortableJS (forceFallback pointer events) on the drag handle; up/down buttons; ArrowUp/ArrowDown on the focused handle
    duplicate (new id retagged through names/ids/labels, menus closed), delete with Undo toast, show/hide
    undo stack (last 30 structural changes), "Unsaved changes" state + beforeunload
    library modal (search, groups, saved sections copy/linked) → section-form JSON
    tabs (Sections / Title & address / Search & sharing / History), open-all / close-all, jump-to-error
    save-block and schedule modals (datetime-local → ISO in the browser's zone), preview modal with desktop/tablet/phone widths
resources/js/admin/media-picker.js
resources/js/admin/rich-text.js
resources/scss/_admin-cms.scss    builder, editor, picker, panels, toasts, previews, AI screens, 44 px touch targets
resources/scss/_page-blocks.scss  public .rich-text typography and pb-* section styles
```

## 14. Known limitations

- The **homepage** is still a coded page. Its sliders, packages and figures are managed in their own admin screens, not with the page builder.
- **Scheduling a page that is already live** takes it off the website until the scheduled time. The schedule dialog says so. There is no cron by project rule, so a scheduled page becomes visible through the `published_at` check on each request, not through a job.
- Itinerary day notes, transport notes, inclusion items, upgrade rows, and hotel and award descriptions remain plain text. They are short structured values used in lists and in the AI context.
- A linked saved section edits every linking page at once. There is no per-page override (use a copy for that).
- Revisions are whole-document snapshots with no visual diff. Restoring always creates a draft.
- The Tiptap bundle adds ~145 KB gzip to admin pages that have an editor. Public pages load none of it.
