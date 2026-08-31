<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Universal Brothers (Pvt) Ltd — Project Documentation

This repository is the Universal Brothers (Hajj/Umrah/Tourism operator) website build. All project-specific documentation — as opposed to the standard Laravel framework docs below — lives under [`docs/`](docs/), organized as:

- [`docs/source-documents/`](docs/source-documents/) — the original client-supplied brochure/proposal/website-flow documents (PDF/DOCX) and their extracted Markdown, treated as ground truth for all business content.
- [`docs/architecture/`](docs/architecture/) — [`ARCHITECTURE.md`](docs/architecture/ARCHITECTURE.md), [`DATABASE_DESIGN.md`](docs/architecture/DATABASE_DESIGN.md), [`UI_DESIGN_SYSTEM.md`](docs/architecture/UI_DESIGN_SYSTEM.md).
- [`docs/requirements/`](docs/requirements/) — [`PROJECT_REQUIREMENTS.md`](docs/requirements/PROJECT_REQUIREMENTS.md), [`REQUIREMENTS_TRACEABILITY.md`](docs/requirements/REQUIREMENTS_TRACEABILITY.md), [`FRONTEND_IMPLEMENTATION_PLAN.md`](docs/requirements/FRONTEND_IMPLEMENTATION_PLAN.md).
- [`docs/audits/`](docs/audits/) — every review/audit pass: [`FINAL_AUDIT_REPORT.md`](docs/audits/FINAL_AUDIT_REPORT.md) (start here for overall project status and the release-gate decision), [`FINAL_GAP_ANALYSIS.md`](docs/audits/FINAL_GAP_ANALYSIS.md), [`FRONTEND_QA.md`](docs/audits/FRONTEND_QA.md), [`SECURITY_AUDIT.md`](docs/audits/SECURITY_AUDIT.md), [`PERFORMANCE_AUDIT.md`](docs/audits/PERFORMANCE_AUDIT.md), [`FRONTEND_ACCESSIBILITY_AUDIT.md`](docs/audits/FRONTEND_ACCESSIBILITY_AUDIT.md), [`RESPONSIVE_QA.md`](docs/audits/RESPONSIVE_QA.md), [`SEO_CHECKLIST.md`](docs/audits/SEO_CHECKLIST.md), the three `FINAL_CODE_REVIEW*.md` independent code-review passes, and the historical [`EXISTING_WEBSITE_AUDIT.md`](docs/audits/EXISTING_WEBSITE_AUDIT.md)/[`PROJECT_DISCOVERY.md`](docs/audits/PROJECT_DISCOVERY.md).
- [`docs/testing/`](docs/testing/) — [`REGRESSION_TEST_RESULTS.md`](docs/testing/REGRESSION_TEST_RESULTS.md), the full chronological PHPUnit/Playwright/E2E regression log.
- [`docs/data/`](docs/data/) — [`HAJJ_PACKAGE_DATA_AUDIT.md`](docs/data/HAJJ_PACKAGE_DATA_AUDIT.md), the per-package Hajj brochure-vs-database verification record.

**Current status, business decisions, and remaining blockers**: see `FINAL_AUDIT_REPORT.md`'s Release Gate section (§21 onward) for the authoritative, up-to-date summary.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
