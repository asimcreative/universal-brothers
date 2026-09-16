# Read-Only Live Website and Admin Panel Verification

**Audit date:** 2026-09-16, 10:00–11:30 PKT
**Live URL reviewed:** https://universal-brothers.iisol.co
**Local project reviewed:** `C:\laragon\www\asim-projects\universal-brothers` (branch `main`, at commit `2f19b0b`)
**Nature of this audit:** verification only. Nothing was changed, submitted, published or deployed. See §16 for the confirmation.

**How the live site was inspected**
- 66 pages downloaded with plain GET requests and analysed offline.
- A headless browser pass over 17 representative pages at 1440 px, 768 px and 390 px, recording console errors, failed requests, layout overflow, headings and screenshots.
- A second browser pass for tap targets, the mobile menu, keyboard focus order, colour contrast, the contact form's structure and the currency switcher.
- No form was submitted, no message was sent to the assistant, and no record was created in the production database.

**A note on the host's bot protection.** Rapid automated requests are answered by an Imunify360 "One moment, please…" interstitial instead of the application. Early curl-based path checks were affected; every result in this report was re-taken through a real browser, which passes the challenge. Informational, not a defect.

---

## 0. What has been done since this audit

This report records the site **as it was on 16 Sep at 11:30**. Acting on it came later and is tracked separately:

| Finding | Status |
|---|---|
| D-1 sharing tags | Fixed and deployed the same day — issue [#15](https://github.com/asimcreative/universal-brothers/issues/15). All 63 sitemap URLs re-checked afterwards. |
| D-3 robots.txt, D-4 keyboard focus | Fixed — issue [#16](https://github.com/asimcreative/universal-brothers/issues/16). |
| D-2 sharing image | Open. Needs an image from the client (see the row for why). |
| §7 content values | Open. Waiting on the owner's confirmation. |
| Everything else | Open as written below. |

## 1. Pages reviewed

| Group | Count | Result |
|---|---|---|
| Home | 1 | 200 |
| Hajj listing + filters and paging | 5 | 200 |
| Hajj 2027 package detail pages | 12 | 200 (all) |
| Umrah (`/umrah`, `/umrah-services`) | 2 | 200 |
| Hajj services (`/hajj-services`) | 1 | 200 |
| Tourism listing, filters, paging | 6 | 200 |
| Tourism package detail pages | 35 | 200 |
| About, Contact, FAQs, Media, Testimonials, Awards, Affiliations | 7 | 200 |
| Privacy, Terms, Refund policy | 3 | 200 |
| `sitemap.xml`, `robots.txt` | 2 | 200 |
| Unknown URL (`/this-page-does-not-exist-audit-check`) | 1 | 404, correct |
| News | 0 | No news URL exists publicly (§4, U-5) |

Sitemap contains 63 URLs. Every URL in the sitemap returned 200.

## 2. Features reviewed

Public: navigation and mobile menu, hero and CTA buttons, package listings with series filters and paging, package detail pages with the currency switcher, contact form structure, FAQ accordions, media gallery with lightbox, testimonials, awards, affiliations, office map embed, footer links, AI assistant presence, SEO metadata, sitemap and robots.

Admin: verified by code inspection only — the live admin could not be opened (§15).

## 3. Working (verified on the live site)

| # | Item | Evidence |
|---|---|---|
| W-1 | All 63 sitemap URLs and every page tested return 200; an unknown URL returns a correct 404 | crawl of 66 pages |
| W-2 | No JavaScript console errors and no failed requests on any of the 17 pages tested, at three widths | browser pass |
| W-3 | No horizontal overflow at 1440 px, 768 px or 390 px on any page tested | `scrollWidth - clientWidth = 0` everywhere |
| W-4 | Exactly one `<h1>` per page on every page tested | browser pass |
| W-5 | Mobile menu opens at 390 px and exposes 27 links | browser pass |
| W-6 | Currency switcher works on a package page: USD → PKR → SAR each re-render real amounts (for example PKR 3,700,000 / SAR 47,400 on UB010) | browser pass |
| W-7 | All 12 Hajj 2027 package pages show the full data set (§5) | per-package extraction |
| W-8 | Canonical URL present on every page | crawl |
| W-9 | Contact form carries a CSRF token, every field is labelled, and required fields are marked | DOM inspection, not submitted |
| W-10 | Admin routes redirect anonymous visitors to `/admin/login` (`/admin`, `/admin/pages`, `/admin/ai`, `/admin/media-library`, `/admin/content-blocks`, `/admin/guide`) | browser pass |
| W-11 | Sensitive paths are not served: `/.env`, `/.git/config`, `/composer.json`, `/.env.example`, `/vendor/composer/installed.json`, `/storage/logs/laravel.log`, `/telescope`, `/horizon`, `/phpinfo.php` all 404; `/deploy.php` returns 403 | browser pass |
| W-12 | Security headers present: CSP, `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`; no `X-Powered-By` | response headers |
| W-13 | Session cookie is `Secure` + `HttpOnly` + `SameSite=Lax`; XSRF cookie is `Secure` + `SameSite=Lax` | cookie inspection |
| W-14 | Every `<img>` has an `alt` attribute. Decorative photos use `alt=""`, and affiliation logos use `alt=""` beside a heading carrying the name — both correct practice | markup inspection of 64 pages |
| W-15 | Skip link present and receives a visible 3 px outline on the first Tab | focus order capture |
| W-16 | Colour contrast of the sampled text styles ranges 7.6:1 to 18.4:1 (approximate; alpha not composited) | computed styles |
| W-17 | The office map is a Google Maps embed URL only, not pasted iframe markup | contact page markup |
| W-18 | The AI assistant is present on the public site and carries the session-token address added on 16 Sep | DOM inspection (no message sent) |

## 4. Confirmed technical defects

| # | Severity | Finding | Where | Evidence |
|---|---|---|---|---|
| D-1 | **High** | **Social sharing tags output raw template code.** `og:title` and `og:description` render the literal text `@yield('title', 'Universal Brothers')` and `@yield('meta_description', …)` instead of the page's title and description. A link shared on WhatsApp, Facebook or LinkedIn therefore shows template code instead of the page name. **59 of 64 pages are affected** — every page except About Us and the three policy pages, which set their own sharing fields. | `resources/views/layouts/app.blade.php` lines 28–29. Cause: in Blade, a directive placed immediately after `@else` with no separating character is not compiled, so the fallback `@yield(...)` is printed as text. Introduced by commit `1dc3963` (15 Sep 2026); before that the same tags used a plain `@yield` and rendered correctly. | Live HTML of `/`, `/hajj`, all 12 package pages, `/contact` … : `<meta property="og:title" content="@yield('title', 'Universal Brothers')">` |
| D-2 | **Medium** | **No `og:image` on any page.** Shared links show no preview image anywhere on the site. The tag is only emitted when a CMS page sets a sharing image, and none does. Choosing one is an owner decision, and it cannot be taken from the current library: every photograph on the site is a temporary Wikimedia stand-in under a CC licence whose attribution is shown on the page (`docs/data/image-assets.md`), and a share preview cannot carry that attribution; the files in `public/images/company/` are other organisations' logos. It needs Universal Brothers' own photograph or a designed brand banner. | `resources/views/layouts/app.blade.php` line 30 | 0 of 64 pages carry `og:image` |
| D-3 | **Low** | **`robots.txt` points to a relative sitemap.** The line reads `Sitemap: /sitemap.xml`. The robots.txt standard requires an absolute URL, so search engines may ignore this directive (the sitemap is still reachable directly). | `public/robots.txt` | https://universal-brothers.iisol.co/robots.txt |
| D-4 | **Low** | **Keyboard focus is invisible on three controls.** Verified by tabbing to each one on the live site and reading the computed style: **FAQ accordion headers** show nothing, because `.accordion-button:focus { box-shadow: none; }` removes the ring; **`.btn-register-now`** (the header "Register Now") and **`.btn-whatsapp`** show nothing either, because those custom variants never define `--bs-btn-focus-shadow-rgb`, the variable Bootstrap's ring reads, so it resolves to nothing. Standard variants (`.btn-secondary`, `.btn-outline-light`), `.nav-link` and the skip link all do show a ring — the first audit pass reported the hero buttons as unclear, and that has now been resolved in their favour. | `resources/scss/_components.scss` (line 3227, and the `.btn-register-now` / `.btn-whatsapp` blocks) | keyboard-focus capture on `/` and `/faqs`, before-and-after computed styles |

No broken internal links, no 404s from linked pages, no server errors, no JavaScript errors and no failed network requests were found.

## 5. Hajj 2027 package verification (12 of 12)

Verified on the live detail pages. "Y" = the information is present on the page.

| Code | Days | Options shown | Room types | Mina | Arafat | Muzdalifah | Aziziya | Itinerary | Exclusions | Upgrades | Hijri dates |
|---|---|---|---|---|---|---|---|---|---|---|---|
| UB001 | 13 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB003 | 10 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB004 | 14 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB006 | 10 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB008 | 14 | A, B | 4 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB010 | 10 | A, B | 4 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB011 | 14 | single option | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB013 | 10 | single option | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB015 | 14 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB016 | 10 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB023 | 14 | A, B | 3 | Y | Y | Y | Y | Y | Y | Y | Y |
| UB024 | 10 | single option | 3 | Y | Y | Y | Y | Y | Y | Y | Y |

Also present on all 12: package code, title, duration, Madinah-first / Makkah-first, shifting or non-shifting, hotel names with star ratings and nights, transport, meals, inclusions, notes, images (14–15 per page), a page title and a meta description.

Facts worth recording, not defects:
- **No page shows a "Package C".** Nine pages show Package A and Package B; three (UB011, UB013, UB024) present a single option with no A/B labels. Whether a third option should exist is for the owner to confirm.
- Prices are shown in one currency at a time through a USD / SAR / PKR switcher, with USD selected first. Sample verified on UB010: Package A quad US$12,890 → PKR 3,700,000 → SAR 47,400.
- Where a room type is not sold on an option, the page states "N/A — not offered on this option" or "Not published" rather than hiding the row.

## 6. Usability observations

| # | Severity | Observation | Where |
|---|---|---|---|
| U-1 | Low | Hajj package page titles run 75–94 characters; search results usually cut off near 60. | all 12 `/hajj/...` pages |
| U-2 | Low | All 35 tourism detail pages share an identical 68-character meta description, so they compete with each other in search. | `/tourism/...` |
| U-3 | Informational | Both spellings appear on the site: "Hajj License No." and "Hajj Licence No.". | home, about, contact |
| U-4 | Informational | "Next Flight Date — To be announced" is published on two pages. Correct today if the schedule is genuinely unset. | `/hajj-services`, `/umrah-services` |
| U-5 | Low | The News module exists in the admin and `/news/{slug}` is routed, but no news article is linked from any public page and none appears in the sitemap. There is no `/news` index page, so a visitor has no way to reach news at all. | site-wide |
| U-6 | Low | At 390 px, 38 of 103 buttons and navigation links are under the 44 px recommended tap size — mostly the top-bar phone/email links (22 px) and footer/menu links (18 px). Inline text links are expected to be small; the top-bar contact links are the ones worth enlarging. | all pages, phone width |
| U-7 | Informational | Contact form inputs have no `autocomplete` attributes, so browsers cannot offer saved name, email or phone. | `/contact` |
| U-8 | Informational | The tourism section runs to 35 detail pages across 4 pages of listing; the Hajj section has 12. Consider whether the tourism listing needs stronger filtering for visitors. | `/tourism` |

## 7. Possible content discrepancies — requires owner verification

None of these is called an error. Each is the value currently published, recorded exactly, for the owner to confirm.

| # | Current value (exact) | Where | Why it may need confirmation |
|---|---|---|---|
| C-1 | `info@maximsgroup.org` | every page (127 occurrences) | The address is on a different domain (`maximsgroup.org`) from the website. It may be the group's approved mailbox — **requires owner verification**. |
| C-2 | `(92-21) 111-102-786` (site-wide), plus `(92-21) 111-106-786` on the contact page | header, footer, contact | Two landline numbers are published; only the first appears site-wide. **Requires owner verification** that both are current. |
| C-3 | WhatsApp `+92 322 2102786` (`https://wa.me/923222102786`) | header, footer | **Requires owner verification.** |
| C-4 | `A-9, 1st Floor, Hassan Homes, FL-3/8, Opposite Nehr-e-Khayyam, KDA Scheme Block-5, Clifton, Karachi, Pakistan` | contact, footer | Single "Head Office — Karachi" address. **Requires owner verification**, including whether other offices should be listed. |
| C-5 | "IATA Registered", "Hajj Licence No. 2014", "Registration No." on About | header strip, home, about | Licence and registration wording. **Requires owner verification.** |
| C-6 | "20+ Years of Experience", "10,000+ Pilgrims Served", "20+ Awards & Recognitions", "Zone 1, Category A Mina Camp" | home, about | Company statistics. **Requires owner verification** that they are still accurate for 2027. |
| C-7 | UB010 Package A: quad US$12,890 / PKR 3,700,000 / SAR 47,400; triple US$14,050; double US$16,990. Package B: sharing and quad US$12,200 / PKR 3,485,000; triple US$13,790; double US$16,450 | `/hajj/ub010-…` | Sample of live prices. **Requires owner verification** against the approved 2027 brochure, for all 12 packages. |
| C-8 | Hotel option labels "Package A" / "Package B" with hotels Dar Al Taqwa and Dallah Taibah (Premier Floor) | package pages | Wording shown to customers for the Madinah hotel choice. **Requires owner verification.** |

## 8. Admin CMS findings

**The live admin could not be opened: no credentials were used, and none are stored.** Everything below is verified from the code on `main` at `2f19b0b` and from the automated test suite recorded in `docs/testing/ADMIN_CMS_ENHANCEMENT_TEST_REPORT.md` (run on 15 Sep against local and testing databases). Live behaviour of these screens is therefore **not** independently verified here.

| Feature | Status | Evidence (code) |
|---|---|---|
| Edit page content with a rich text editor | Working (code-verified) | `resources/views/components/admin/rich-text.blade.php`, `resources/js/admin/rich-text.js` |
| Add page sections | Working (code-verified) | `PageController@sectionForm`, `admin/pages/partials/section.blade.php` |
| Reorder sections by drag and drop | Working (code-verified) | `resources/js/admin/page-builder.js` (SortableJS) |
| Move sections with up/down buttons and keyboard | Working (code-verified) | same file |
| Duplicate sections | Working (code-verified) | same file |
| Hide / show sections | Working (code-verified) | same file |
| Upload images | Working (code-verified) | `MediaLibraryController@store` |
| Add image alt text | Working (code-verified) | `media-picker.js`, `SectionValidator::image()` requires it before publishing |
| Save drafts | Working (code-verified) | `pages.draft`, `PageController@update` |
| Preview pages | Working (code-verified) | signed `admin.pages.preview` route |
| Publish pages | Working (code-verified) | `PageController@publishDraft` |
| Reuse saved content blocks | Working (code-verified) | `ContentBlockController`, `saved_block` section type |
| Edit package descriptions | Working (code-verified) | `admin/packages/form.blade.php`, `hajj-packages/steps/basics.blade.php` |
| Edit package notes | Working (code-verified) | `hajj-packages/rows/note.blade.php`, `steps/mashaer.blade.php` |
| Edit inclusions and exclusions | Working (code-verified) — structured list fields, no HTML needed | Hajj builder inclusion/exclusion steps |
| Edit terms and policies without HTML | Working (code-verified) — the three policy pages are CMS pages, editable in the builder | `LegalPageSeeder`, page builder |
| Same features on the live admin | **Could not be verified** | no credentials |

## 9. AI admin design consistency

Also **code-verified only**; the live AI screens could not be opened.

| Item | Finding |
|---|---|
| Sidebar, header, breadcrumbs, page title | The four AI views extend `layouts.admin` and use its breadcrumb and title sections, as the rest of the admin does. |
| Cards, tables, forms, buttons, badges, tabs, modals, alerts | Built from the shared components: `x-admin.panel` (24 uses across the four views), `x-admin.stat-card` (7), `x-admin.status-badge`, `x-admin.empty-state`, `x-admin.help-tip`. |
| Empty states | Present on the conversations list and the test panel. |
| Spacing, borders, shadows, typography, colours | Inherited from `_admin-ui.scss` / `_admin-cms.scss`; no page-local overrides found. |
| Legacy styling left over | None found. `admin-card-footer` on the conversations list is the shared pagination footer used by eight other admin screens, not a leftover. |
| Responsive behaviour | Phone-width checks for `/admin/ai` and `/admin/ai/test` exist in `tests/e2e/page-builder-cms.spec.js` and passed on 15 Sep. Not re-verified live. |
| API key masking | Code shows the key is never rendered: the field is `type=password`, never pre-filled, and only a masked first/last-character form is displayed. A key check reports the result without echoing the key. **No key was viewed or copied during this audit.** |
| Permissions | `admin/ai` settings, key replace/clear/check and reindex sit behind the `manage-ai-settings` gate (super admin). The AI test panel and conversation log remain open to both admin roles. |
| Error handling | Provider failures map to plain messages; the assistant falls back to its configured message. |
| Secret exposure risk | None found in code or in the live HTML. |

## 10. Security observations

| # | Severity | Observation |
|---|---|---|
| S-1 | Medium | **`Strict-Transport-Security` header is absent.** HTTPS works, but browsers are not told to refuse plain HTTP for this domain. |
| S-2 | Low | The CSP allows `'unsafe-inline'` for both scripts and styles, and `frame-src 'self' https:` allows framing content from any HTTPS origin. Both weaken the policy's value against injected content. |
| S-3 | Informational | `/deploy.php` exists in the document root and answers `403` to a GET, as expected for a signature-checked webhook. No further probing was done. |
| S-4 | Informational | Host-level bot protection (Imunify360) challenges rapid automated requests. Good for abuse, but it also means uptime checks and crawlers may receive an interstitial. |
| S-5 | Informational | Anonymous access to admin routes, and to `.env`, `.git`, `composer.json`, `telescope`, `horizon`, `phpinfo.php` and the private storage path, is correctly refused (W-10, W-11). |
| S-6 | Not verified | Unsafe HTML rendering / XSS: the code sanitises rich text on save and on render (`App\Support\Content\RichText`), and the published HTML showed no injected markup. A live authenticated test was not possible. |
| S-7 | Not verified | N+1 query risk, database relation integrity and cache invalidation under production load. Requires database or profiler access. |

## 11. Responsive and accessibility observations

| # | Severity | Observation |
|---|---|---|
| A-1 | — | No horizontal overflow, no text clipping and no off-screen buttons at 1440 / 768 / 390 px on 17 pages. |
| A-2 | — | Mobile menu opens and lists 27 links; one `<h1>` per page. |
| A-3 | Low | Focus visibility: FAQ accordion headers have their focus ring removed (D-4); homepage hero buttons showed no focus style change when tabbed. |
| A-4 | Low | Tap targets under 44 px at phone width (U-6). |
| A-5 | — | Contrast of sampled styles 7.6:1 and above; no contrast problem found in the sample. |
| A-6 | — | All images carry `alt`; decorative images correctly use empty `alt`. |
| A-7 | Informational | Screenshots at desktop and phone width for all 17 pages were captured for this audit (see §14). |

## 12. Items that could not be verified

1. The live admin panel and every authenticated screen — no credentials were used. Admin findings in §8 and §9 are code-verified only.
2. Whether unpublished news articles exist in the production database (U-5 records only what is publicly visible).
3. Production database contents, relations and query counts (§10 S-7).
4. Email delivery and enquiry handling — no form was submitted.
5. Live AI answer quality today — no message was sent to the assistant during this audit, to avoid creating a record in the production database. (A check on 15–16 Sep is recorded in issue #14.)
6. SAR and PKR prices for the other 11 packages — the currency switcher was exercised on UB010 only.
7. Whether "Package C" should exist for any package (§5).
8. Print, email-client and screen-reader rendering.

## 13. Recommended next steps, for owner approval

Nothing below has been done. Each needs the owner's go-ahead.

| Priority | Recommendation |
|---|---|
| 1 | Fix D-1 (raw `@yield` in the sharing tags). One-line change in `layouts/app.blade.php`; every shared link is affected until then. Add a test that asserts the rendered `og:title` on a page that does **not** set its own sharing fields — the existing test only covers pages that do, which is why this passed unnoticed. |
| 2 | Supply a sharing image for D-2 — Universal Brothers' own photograph or a designed 1200×630 banner, in JPG or PNG (the site's photos are licensed stand-ins and cannot be used for this, see D-2). |
| 3 | Make the `Sitemap:` line in `robots.txt` absolute (D-3). |
| 4 | Restore a visible focus ring on FAQ accordion headers and confirm the hero buttons' focus state (D-4). |
| 5 | Confirm the content values in §7 (C-1 … C-8), especially the contact address, the two phone numbers and the 2027 prices. |
| 6 | Decide whether news should be visible: publish and link articles, add a `/news` index, and include them in the sitemap — or remove the module from the menu plan (U-5). |
| 7 | Shorten the Hajj package page titles and give the tourism pages distinct descriptions (U-1, U-2). |
| 8 | Add `Strict-Transport-Security` and consider tightening the CSP (S-1, S-2). |
| 9 | Settle the "License" / "Licence" spelling (U-3) and confirm the "To be announced" flight dates (U-4). |
| 10 | For a complete admin verification, arrange a supervised session on the live admin, or a staging copy with its own credentials, so §8 and §9 can be confirmed in the browser rather than in code. |

## 14. Evidence

Collected under the session scratchpad (temporary, outside the repository):
`C:\Users\asimc\AppData\Local\Temp\claude\c--laragon-www-asim-projects-universal-brothers\e318dcd3-4c37-4906-8c39-a5ba1738308a\scratchpad\audit\`

| File | Contents |
|---|---|
| `pages/` | 66 downloaded live pages (HTML) |
| `pages.json` | URL, status, size, content type for each |
| `analysis.json` | SEO fields, heading counts, image alt data, link map |
| `packages.json` | per-package field extraction |
| `content-values.json` | emails, phone numbers, WhatsApp numbers with page counts |
| `browser.json` | per-page console errors, failed requests, overflow at three widths, currency switcher results |
| `a11y.json` | tap targets, mobile menu, focus order, contrast samples, contact form structure |
| `shots/` | desktop and phone screenshots of the 17 pages tested |

## 15. Facts and assumptions

- **Facts:** everything in §3, §4, §5, §6, §7, §10 (S-1 to S-5), §11 — each was observed directly on the live site or read from the source files named.
- **Assumption-free but code-only:** §8 and §9 describe what the code does. They are not live verification.
- **Assumptions avoided:** no published email, phone number, address, licence detail, price or hotel name is called incorrect anywhere in this report. Items that may warrant confirmation are listed in §7 and marked "requires owner verification".
- One correction made during the audit: an early automated check flagged "images missing alt text". On inspection every image has an `alt` attribute and the empty ones are correctly decorative, so the finding was withdrawn rather than reported.

## 16. Read-only confirmation

During this audit:

- No code was changed.
- No database record was changed, and no record was created in the production database.
- No content was changed.
- No email address or phone number was changed.
- No design was changed.
- No configuration was changed.
- No package was installed or updated.
- No migration was created or run.
- No content was published or unpublished.
- No form was submitted and no message was sent to the AI assistant.
- No Git commit, push or deployment was performed.
- No API key was viewed, copied or recorded.

The only file written by this audit is this report.
