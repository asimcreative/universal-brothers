# Admin CMS Enhancement — Test Report

**Issue:** [#13](https://github.com/asimcreative/universal-brothers/issues/13) · **Date:** 2026-09-15 · **Requirements:** [ADMIN_CMS_ENHANCEMENT_REQUIREMENTS.md](../requirements/ADMIN_CMS_ENHANCEMENT_REQUIREMENTS.md)

**Environments:**
- PHPUnit ran on in-memory SQLite.
- Playwright ran against `php artisan serve --env=testing` on port 8129 (`database/testing.sqlite`).
- The three new migrations were run, rolled back and run again on the local and testing databases.
- **Nothing ran against production.**

## 1. Summary

| Check | Result |
|---|---|
| PHPUnit, full suite | **456 tests, 4,122 assertions — all pass** |
| Playwright, full suite (6 projects, 281 tests) | **277 passed, 4 skipped (existing phone-only skips), 0 failed, 0 flaky**, run with `--retries=0` |
| Re-run after the last server change (saved-section permission, guide FAQ) | `page-builder-cms.spec.js` + `admin-guide.spec.js`: **21 passed** |
| New `tests/e2e/page-builder-cms.spec.js` | 11 tests pass (10 journeys + cleanup) |
| Updated `admin.spec.js`, `journeys-admin.spec.js` | 17 passed |
| Pint | Clean on every new or changed PHP file (the one reported file, `app/Support/HajjPackagePresenter.php`, was not touched by this work) |
| `npm run build` | Builds. `rich-text` chunk 463 KB / 145 KB gzip, `page-builder` 56 KB / 19 KB gzip, loaded only on admin screens that use them |
| Migrations | Up, down, up again: clean on SQLite local and testing |
| Secret scan | 149 changed / new files: no real secrets (see the audit, §4) |

## 2. New and changed automated tests

| File | Tests | What it proves |
|---|---|---|
| `tests/Feature/RichTextTest.php` (new) | 12 | Allowed formatting kept. Scripts, event handlers, `javascript:` links, head elements, foreign iframes and off-palette styles removed. Profiles differ. Empty editor = empty. Plain text renders as before. Plain-text output for search and the assistant. FAQ, news and package fields cleaned on save and on render. Progressive textareas. Source view for super admins only. |
| `tests/Feature/Admin/PageManagementTest.php` (rewritten) | 35 | Guests / inactive admins refused. Signed preview. Starters. Legacy pages converted without changing the live page. Draft never changes the live page. Publish, schedule, unpublish, archive / restore, revisions, duplicate, delete. Section order, hide, duplicate, delete. Validation by section with preserved input. Reserved and unique addresses. SEO fields reach the page. Saved sections: copy by default, linked for super admins only, latest content shown. Live-data sections. The published document matches the builder. |
| `tests/Feature/Admin/MediaLibraryTest.php` (new) | 6 | Upload with description. Unsafe and unsuitable files refused in plain words. Library search and description updates. Uploads for pages never appear in the public gallery. Guests refused. Gallery admin tabs and descriptions. |
| `tests/Feature/Admin/ContentBlockTest.php` (new) | 8 | Choose and create. Strict validation. Editing a linked section warns and updates the page. Only super admins can change or archive a linked section. In-use sections cannot be deleted. Duplicate / delete. Preview in the site design. |
| `tests/Feature/Admin/AdminContentUsabilityTest.php` (new) | 7 | AI pages on shared components. AI settings for super admins only. Key check never shows the key. Icon validation. Office map embed accepted / refused. Plain setting names. |
| `tests/Feature/Admin/AdminGuideTest.php` (updated) | 16 | The six new guide sections are required and render, their links work, no technical words, 32 sections counted. |
| `tests/e2e/page-builder-cms.spec.js` (new) | 11 | See §3 |
| `tests/e2e/admin.spec.js` (updated) | FAQ answer typed in the editor. About Us edited in the builder with a draft that is discarded afterwards. | |
| `tests/e2e/journeys-admin.spec.js` (updated) | Journey E: About Us story edited in the editor, published, verified on the site, then the original restored and published. | |

## 3. Browser journeys (`page-builder-cms.spec.js`, serial, super admin)

1. Create a page from the Information layout. The address fills in, the page opens with 4 sections, and the public address returns 404.
2. Write a line of text and a bold bulleted item with the toolbar and keyboard shortcuts. The hidden field holds the HTML. `www.example.com` in the link panel shows "Add https:// at the start". The word counter works. The draft is saved and reloads with the formatting.
3. Upload `fixtures/arafat-photo.webp` through the media picker with a description. The preview, description and "Replace image" appear, and the draft is saved.
4. Add "Questions and answers" from the searchable library. Move it up by button, then by keyboard on the handle. Undo. Drag it to the top with the mouse. Duplicate the call-to-action, with **no menu left open**. Hide the copy. Delete a section and undo from the toast. Save. Order and hidden state persist.
5. Empty the banner heading and press Publish. The draft is saved, the summary links "Section 2 (Page banner): heading is empty", and the link opens the section with the field marked invalid. Fix it and publish. The live page has one `<h1>` (the page title, because a section now comes before the banner), the banner heading as `<h2>`, the bold list item, the image with its alt text, and the call-to-action once (the hidden copy is not shown).
6. Save & preview. The preview banner and the draft content show in the frame, and the Phone width is 390 px.
7. Save the call-to-action for reuse, see it in Saved Sections, and insert a copy into the page.
8. At 390 px, the builder, pages list, AI settings, AI test panel and saved sections have no sideways scroll. The drag handle is at least 44 px tall.
9. The AI settings have one page title and the grouped panels. The API key input is a password field, and the page source does not contain the test key. The test panel shows 4 stat cards.
10. Alt+F10 reaches the editor toolbar, ArrowRight moves to Redo, and Escape returns to the text. No visible builder button lacks an accessible name.
11. `afterAll` deletes the saved section and every page the run created.

## 4. Defects found by the tests and fixed at the cause

| # | Found by | Defect | Fix |
|---|---|---|---|
| 1 | E2E journey 2 | Pressing "Add link" saved the whole page: the editor panels were `<form>`s nested inside the builder form | Panels are `div role="group"` with apply buttons; Enter applies, Escape closes |
| 2 | E2E journey 4 | Builder tabs stacked vertically inside a `.card` (flex column) | `.pb-tabs { flex-direction: row }` |
| 3 | E2E journey 4 | Mouse drag picked the wrong section when the list scrolled mid-drag | Sortable pointer fallback (`forceFallback`, 4 px tolerance); the test closes all sections first |
| 4 | E2E journey 4 | A duplicated section carried an open, uncloseable actions menu over other controls | Menus reset on the clone and closed on the original; assertion added |
| 5 | E2E journey 10 | The toolbar's Tab stop was the disabled Undo button, so keyboard users could not reach any formatting button | Unavailable buttons use `aria-disabled` and stay focusable; clicks on them are ignored |
| 6 | Review while writing the audit | Any admin could edit or archive a saved section linked into live pages, bypassing publishing | Same super-admin gate as linking; Save disabled with an explanation; 403 on the server; feature test added |
| 7 | PHPUnit | `hasOldInput('title')` was false for an emptied title, so a failed save lost the sections | Rebuild when any old input exists |
| 8 | PHPUnit | Sanitiser kept the text of `<style>` / `<title>` and dropped the text of disallowed wrappers | Head elements pre-stripped; default action "block" |

Test-only corrections (the application was right): exact accessible names for "Heading" and "API key", the preview banner locator, the saved-section link (a Preview link has a similar name), and the expected heading level after the drag in journey 4.

## 5. Not covered automatically

- The `beforeunload` "unsaved changes" prompt. Browsers suppress it in automation; it was checked by hand.
- Real Google or social previews of a published page. The markup (`canonical`, `robots`, `og:*`) is asserted instead.
- A live call to the AI provider for "Check the key". Tests use `Http::fake` with success and each failure type.
- Firefox and WebKit run only the public and responsive projects, as before; the admin runs in Chromium.
