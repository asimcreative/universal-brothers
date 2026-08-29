# Project Discovery

Status date: 2026-08-29. This document records the actual state of the workspace and environment at project start, per Phase 0 of the execution directive. Nothing below is assumed — every claim was verified by directly inspecting the filesystem and toolchain.

## 1. Existing Workspace Contents

The working directory (`c:\laragon\www\asim-projects\universal-brothers`) contained, at project start, **only two files**:

- `Website Redesign Proposal.pdf` (59 KB, 12 pages, text-based)
- `HAJJ 2027 Packages overseas.pdf` (14 MB, 37 pages, scanned/image-based — no extractable text layer)

There is **no existing codebase** in this directory:
- No `composer.json`, no `artisan` file → no existing Laravel (or any PHP framework) project.
- No `.git` directory → not a git repository (confirmed by environment metadata).
- No database dumps, `.env` files, migrations, routes, controllers, models, views, or frontend assets.
- No documentation files.
- No test files.
- No deployment configuration (no `Dockerfile`, `.github/workflows`, `Procfile`, etc.).

**Conclusion: this is a greenfield build.** There is nothing to migrate away from technically — the only continuity requirement is content/SEO/business-data continuity from the *live, externally-hosted* client websites (see EXISTING_WEBSITE_AUDIT.md, in progress) and from the two source PDFs, not from any local legacy code.

## 2. Source Documents

Both mandatory source documents were read in full (not sampled):

- **Website Redesign Proposal.pdf** — 12 pages, plain text, read directly via the PDF text layer. Full content captured — see PROJECT_REQUIREMENTS.md for the extracted requirements.
- **HAJJ 2027 Packages overseas.pdf** — 37 pages, each page is a rasterized image with zero extractable text (confirmed via PyMuPDF: `page.get_text()` returned 0 characters on every page, `page.get_images()` returned exactly 1 embedded image per page). Poppler (`pdftoppm`) is not installed in this environment, so the standard PDF-page-render path was unavailable. Worked around by rendering each page to PNG directly with PyMuPDF (`fitz`) at 1.8x zoom and visually reading all 37 rendered pages in sequence. All 37 pages were read — none were skipped or inferred.

Key facts extracted from the packages brochure (ground truth for all Hajj package data — see PROJECT_REQUIREMENTS.md and the eventual seeders for full detail):
- Company: **Universal Brothers (Pvt) Ltd**, a company of Maxim's Group, brand name **"Crown Packages"**. IATA member. Hajj Registration No. 4143, Government License No. 2014.
- Address: A-9, 1st Floor, Hassan Homes FL-3/8, Opp. Nehr-e-Khyyam, KDA Scheme Block-5, Clifton, Karachi.
- Contacts: Landline 021-111-102-786 / 021-111-106-786, WhatsApp +92 322 2102786, phone 0322 3350151, web www.universalbrothers.com, email info@maximsgroup.org, social `@Universalbrotherstravel`.
- Leadership: Furqan Abdul Qadir (Chief Executive), Junaid Abdul Qadir (Director).
- Document covers **Hajj 2027 / 1448 AH** only — 9 distinct packages (codes UB001–UB024) across 3 series (Non-Aziziya Platinum, Aziziya Platinum, Aziziya Value), each with real hotel names, per-room-type USD pricing, day-by-day itineraries, and a shared terms/payment-plan/booking-form appendix.
- The document contains **no Umrah or Tourism package data** — those product lines must ship as empty, CMS-manageable categories per the directive's "never invent" rule, pending client-supplied content.

## 3. Development Environment (verified working)

This machine runs Laragon on Windows. Verified directly (not assumed from Laragon's presence):

| Component | Version found | Notes |
|---|---|---|
| PHP | 8.3.16 (Laragon, ZTS VC16 x64) | Matches proposal's "PHP 8.3+" requirement exactly. Not on PATH — must be invoked by full path or PATH must be set per-session. |
| Composer | 2.8.4 | Runs via `composer.phar` through the PHP 8.3 binary. |
| MySQL | 8.0.45 available (5.7.44 and 9.6.0 also installed side-by-side) | Matches proposal's "MySQL 8.x" requirement. |
| Node.js | v24.18.0 | Available for any build-step tooling (asset minification etc.), though the proposal explicitly avoids JS frameworks. |
| Git | Not yet initialized in this directory | `Is a git repository: false` per environment metadata. |
| Poppler (`pdftoppm`) | Not installed | Worked around with PyMuPDF for PDF page rendering (see §2). Not required for the Laravel app itself. |

Python 3.14 with `pymupdf`, `pypdf`, `pdfplumber`, `pdf2image` etc. is also available on this machine and was used only for source-document extraction, not part of the deliverable stack.

## 4. Risks and Notes Carried Forward

- **No git repo yet at project start** — initialized as part of the Laravel scaffolding step (local-only, non-destructive; nothing has been pushed anywhere).
- **PATH does not include PHP/Composer/MySQL binaries** in this shell — every dev-environment command in this project must reference the full Laragon path (or the shell's PATH must be extended for the session) to avoid "command not found" false negatives.
- **MySQL root credentials not available.** The Laragon MySQL 8.0.45 server is running (port 3306), but `root@localhost` requires a password that isn't stored anywhere accessible in this environment (checked phpMyAdmin's config — it uses cookie auth with no stored credential) and wasn't provided. Per the proposal, MySQL 8.x is the target production database, but this is a real credential gap, not a design choice. **Workaround in place:** local development proceeds on SQLite (Laravel 12's default, already migrating cleanly), which is schema-compatible with the MySQL migrations we write since we use Laravel's database-agnostic schema builder throughout. Switching to MySQL later is a one-line `.env` change (`DB_CONNECTION=mysql` + credentials) plus `php artisan migrate:fresh` — no code changes needed. **Blocked on:** the user supplying the local MySQL root password (or a dedicated dev DB user/password) if MySQL verification is wanted before final deployment.
- **The Hajj packages PDF is the only source of real package data.** Umrah and Tourism packages have zero source material — the CMS must make these fully manageable so the client can populate them later; nothing about their content will be invented.
- **Existing live websites** (`hajjumrah.universalbrothers.com/hajj/`, `tourism.universalbrothers.com`) are being audited in parallel (see EXISTING_WEBSITE_AUDIT.md) for content and SEO continuity — this doc will be updated once that lands.
- **Avenix template reference** (`html.awaikenthemes.com/avenix/index-slider.html`) is being audited in parallel for design-direction extraction, per Phase 3.
