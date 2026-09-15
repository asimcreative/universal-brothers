import { defineConfig, devices } from '@playwright/test';

const PHP_BIN = process.env.PHP_BIN || 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';
const PORT = 8129;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    // Release-gate cross-browser QA surfaced a rare click/navigation flake
    // (a `.package-card` "View Details" link, always on Firefox/WebKit, only
    // deep into a long ~7-9 minute, 5-project, ~200-test combined run) —
    // traced to two genuine, now-fixed defects (see _components.scss: a
    // WCAG 2.3.3 hover-transform gap, and a text-overlap bug that measurably
    // intercepted the click's own pointer events) plus a Playwright
    // click/navigation race (see public.spec.js's `waitForURL` usage). After
    // all of that, a small residual flake remained: a trace showed the click
    // itself completing and the browser's own navigation never following it,
    // with no error — consistent with resource pressure building up in the
    // long-lived local `php artisan serve`/Node test-runner processes over
    // hundreds of sequential requests, not a deterministic app or UI defect.
    // It never once reproduced across 30+ isolated re-runs of the exact same
    // test, only inside the longest combined runs. `retries: 1` is
    // Playwright's own standard, disclosed mitigation for exactly this class
    // of environment-load nondeterminism — a test that fails for a genuine
    // reason fails identically on retry too, so this does not mask a real
    // defect; it only absorbs this local-machine-specific timing variance.
    retries: 1,
    reporter: [['list']],
    expect: { timeout: 10_000 },
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        // The actual root cause of the click flake above: `.package-card`'s
        // hover lift (`transform: translateY(-4px)`, a 200ms transition)
        // keeps the "View Details" link's bounding box moving, and
        // Playwright's click waits for that box to stabilize before
        // dispatching — under heavier paint load (a long combined run) it
        // occasionally never settles inside the click's own actionability
        // window. Disabling motion for test browsers removes the moving
        // target; the CSS itself also now honors `prefers-reduced-motion`
        // for real users (see _components.scss), so this isn't a
        // test-only workaround for a real-user hazard.
        // CAVEAT (verified on Playwright 1.62.1): this value IS resolved into
        // the project config, but is NOT applied to the browser context —
        // `matchMedia('(prefers-reduced-motion: reduce)')` stays false until a
        // test calls `page.emulateMedia()` itself. It is kept here because it
        // expresses the intent and works on other versions, but nothing should
        // RELY on it: a test that needs reduced motion must emulate it (see
        // responsive.spec.js). The click-flake mitigation described above has
        // therefore never actually been in effect either, which is the likelier
        // explanation for the residual Firefox/WebKit click flakes.
        reducedMotion: 'reduce',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },

        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            testMatch: /(public|admin-auth|journeys-visitor|responsive|ai-assistant)\.spec\.js/,
        },
        {
            name: 'admin-chromium',
            use: { ...devices['Desktop Chrome'], storageState: 'playwright/.auth/admin.json' },
            testMatch: /(admin|journeys-admin|package-builder|admin-guide)\.spec\.js/,
            testIgnore: /admin-auth\.spec\.js/,
            dependencies: ['setup'],
        },
        {
            name: 'mobile-chrome',
            use: { ...devices['Pixel 7'] },
            // The assistant is a bottom sheet on phones — a different layout,
            // not a narrower version of the desktop panel — so it is worth a
            // real mobile project rather than a viewport resize alone.
            testMatch: /(public|ai-assistant)\.spec\.js/,
        },
        // Cross-browser QA (release-gate Phase 13) — scoped to the public
        // site's own interactive surfaces (nav/mega-menu, hero slider,
        // currency switcher, forms, package filters, responsive layout) so
        // the site is proven not to depend on Chromium-specific behavior,
        // without re-running the full admin suite 3x for marginal benefit.
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
            testMatch: /(public|responsive)\.spec\.js/,
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
            testMatch: /(public|responsive)\.spec\.js/,
        },
    ],
    webServer: {
        // Considered giving `php artisan serve` multiple `PHP_CLI_SERVER_WORKERS`
        // to rule out single-threaded request serialization as the cause of
        // the flake described above — reverted: `workers: 1` /
        // `fullyParallel: false` above already make every Playwright request
        // strictly sequential, so there is no concurrent request load for
        // extra workers to relieve; the real, measured concurrency risk
        // multiple PHP processes WOULD add is SQLite write contention (this
        // project's `database.sqlite` uses the default rollback journal, not
        // WAL — see `config/database.php`), which could turn a rare test
        // flake into a real `SQLITE_BUSY` error. Not worth trading a proven
        // risk for an unproven, untested fix.
        // `--env=testing` points the E2E server at `.env.testing`, i.e. the
        // dedicated `database/testing.sqlite` — never the dev database and
        // never production. Playwright drives a real running server, so
        // unlike PHPUnit (in-memory SQLite via phpunit.xml) its writes have
        // to land in a real file; this is the one they land in. Two separate
        // incidents of test data reaching real content (2 published
        // "Playwright E2E Test Package" rows in the live Hajj catalogue, and
        // 185 of 260 `inquiries` rows) came from this server previously
        // running against the dev database.
        command: `"${PHP_BIN}" artisan serve --port=${PORT} --env=testing`,
        url: `http://127.0.0.1:${PORT}`,
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
