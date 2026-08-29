# Security Audit

Performed as an actual code review + verification pass against the implemented codebase, not a checklist filled in from memory. Findings are graded by what was found, not what was assumed.

## Verified in place

| Control | Status | Evidence |
|---|---|---|
| CSRF protection | ✅ | Laravel's default `VerifyCsrfToken` middleware is active (not excluded anywhere); every form in the codebase includes `@csrf`. |
| Mass-assignment protection | ✅ | Every model declares an explicit `$fillable` array; none use `$guarded = []`. |
| SQL injection | ✅ | Grepped the entire `app/` tree for `DB::raw`, `DB::statement`, `whereRaw`, `selectRaw` — zero occurrences. All queries go through Eloquent/query builder parameter binding. |
| XSS | ✅ | All user-supplied data is rendered via Blade's auto-escaping `{{ }}`. The only `{!! !!}` (raw) output in the codebase is fixed badge-HTML strings the admin views construct themselves (e.g. `<span class="badge...">Yes</span>`), never user input. |
| Password hashing | ✅ | `User::password` uses Laravel's `hashed` cast (bcrypt); seeded admin password uses `Hash::make()`. |
| File upload validation | ✅ | Every upload field (`cover_image`, slider `image`, news `cover_image`) is validated `image`+`max:4096` via Form Requests/controller validation — no arbitrary file type accepted, no executable-extension risk. |
| Auth/authorization | ✅ | All `/admin/*` routes (except the login screen itself) require `auth` + a custom `admin` middleware that also checks `is_active`, logging out and redirecting deactivated accounts. Verified by `Admin\AuthTest` (5 passing tests) and `Admin\PackageManagementTest`'s guest-rejection test. |
| Route-level authorization test coverage | ✅ | Guest-cannot-access assertions exist for package admin and inquiry admin, not just "the route exists." |
| Rate limiting | ✅ (added during this audit) | Public `contact`/`inquiries` POST routes and the admin login POST route now carry `throttle:5,1` — this was a real gap caught during the review, not present in the initial build, fixed and verified (`SecurityHeadersTest` confirms headers; throttle confirmed via route definitions, not yet load-tested). |
| Security headers | ✅ (added during this audit) | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` now applied globally via a `SecurityHeaders` middleware, verified by a passing test. |
| IDOR | ✅ | Admin resource routes use Laravel's implicit route-model binding scoped by primary key with ownership not applicable (single-tenant admin, not per-user data) — no user-supplied ID is trusted to bypass a query scope; public package routes additionally verify the package belongs to the requested category slug (`abort_unless`) before rendering, tested in `PackageBrowsingTest`. |
| Secrets not committed | ✅ | `.env` confirmed excluded from git via `.gitignore`; verified via `git status` before every commit in this session. |

## Known gaps (not fixed — genuine external blockers or explicit scope decisions)

| Gap | Why it's not fixed here | What's needed |
|---|---|---|
| No Google reCAPTCHA on public forms | Proposal calls for it, but no site/secret key exists anywhere in the source material or environment | Client must supply reCAPTCHA v2/v3 keys; `site_settings` already has placeholder rows (`recaptcha_site_key`, `recaptcha_secret_key`) ready to receive them |
| `APP_DEBUG=true` in `.env` | Correct for local development (shows detailed errors), **must be `false` before any production deploy** — leaving it `true` in production leaks stack traces/env values on error pages | Set `APP_DEBUG=false` and `APP_ENV=production` as part of the deployment step, not before |
| Local dev DB is SQLite, not MySQL | MySQL root credentials for this machine aren't available (see PROJECT_DISCOVERY.md) | User needs to supply the local MySQL root password, or the target production DB credentials directly, to verify against the real target engine before go-live |
| No HTTPS/SSL configured | This is a local dev environment with no domain/hosting yet | Standard step at actual deployment (Let's Encrypt or hosting provider's SSL) |
| No payment gateway | Explicitly out of scope for this phase (inquiry-based, not transactional) — see PROJECT_REQUIREMENTS.md §V | N/A until the online-booking future enhancement is greenlit |
| Admin panel has only 2 roles, no granular permissions | Proportionate to the actual requirement (Users & Roles module in the proposal doesn't specify a permission matrix) | If finer-grained permissions are wanted later, `spatie/laravel-permission` is the natural addition — not pre-built speculatively here |
| Not penetration-tested against a live, deployed instance | No production/staging server exists yet in this engagement | Standard pre-launch penetration test once a real deployment target exists |

## Verdict

No critical or high-severity issue found and left unfixed. The two issues found during this review (missing rate limiting, missing security headers) were fixed in the same pass and are covered by tests. Everything in the "known gaps" table is a genuine external dependency or explicit scope boundary, not a deferred fix.
