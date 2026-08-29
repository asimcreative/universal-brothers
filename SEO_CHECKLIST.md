# SEO Checklist

Verified against actual rendered HTML/XML output (curl + grep against the real running app), not just source-code inspection.

| Item | Status | Evidence |
|---|---|---|
| Unique `<title>` per page | ✅ | Homepage, About Us, and a Hajj package detail page all confirmed to render distinct, real titles |
| Meta description per page | ✅ | Confirmed rendered with real content (not a placeholder) on homepage |
| Exactly one `<h1>` per page | ✅ | Confirmed via grep count on homepage and About Us (1 each) |
| Canonical URL | ✅ | Confirmed present and correct on a package detail page |
| Open Graph tags | ✅ | `og:title`, `og:description`, `og:type` present in the shared layout |
| Friendly slugs | ✅ | `/hajj/ub001-executive-platinum-intercon-fairmont-medinah-first`, `/about-us` — no numeric IDs in any public URL |
| `robots.txt` | ✅ | Present, disallows `/admin`, references sitemap |
| `sitemap.xml` | ✅ (fixed this pass) | **Found and fixed a real gap**: the sitemap controller only ever queried `Package`/`PackageCategory` — the new Pages module (About Us) was invisible to it. Fixed by adding `Page::where('is_active', true)` to the sitemap query and a `<url>` block per page; added `SitemapTest.php` (didn't exist before) asserting published pages appear and drafts don't. |
| Draft/unpublished content excluded from sitemap | ✅ | Verified via the new `SitemapTest` — a draft page does not appear |
| JSON-LD structured data | ✅ | `TravelAgency` schema in the shared layout head, real office phone/email when available |
| No state-mutating GET endpoints | ✅ | Every write path in the app is POST/PUT/DELETE; grepped routes/web.php to confirm no GET route does a create/update/delete |
| Breadcrumbs | ✅ | Present on category, detail, contact, and static page templates (visual only — not yet marked up as BreadcrumbList JSON-LD; noted as a light follow-up, not a defect) |

## Not yet done (real, not padding)

- No 301-redirect map from the two legacy sites' URLs yet — correctly deferred until this site's final domain/URL structure is live (see EXISTING_WEBSITE_AUDIT.md §4), not an oversight.
- BreadcrumbList JSON-LD schema markup: breadcrumbs are visually present (Bootstrap breadcrumb component) but not additionally marked up as structured data. Low-cost follow-up, not done in this pass since it wasn't part of the explicit gap list being closed.
