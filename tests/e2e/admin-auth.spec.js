import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@universalbrothers.test';
const ADMIN_PASSWORD = 'password';

// Deliberately runs unauthenticated (no storageState) — this file tests the
// login flow itself, so it must start from a logged-out browser context.
test.describe('Admin login flow', () => {
    test('15. admin can log in with seeded credentials', async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        // exact: true — see auth.setup.js for why (the login page's new
        // show/hide toggle button's accessible name otherwise substring-
        // matches this locator too).
        await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/admin$/);
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    });

    test('15b. admin login rejects wrong password', async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password', { exact: true }).fill('wrong-password');
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/admin\/login$/);
        await expect(page.locator('.alert-danger')).toBeVisible();
    });

    test('guest visiting /admin is redirected to login', async ({ page }) => {
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });

    // Regression: no E2E test exercised logout at all before the admin UI
    // redesign, which moved it from a standalone sidebar button into the
    // user-avatar dropdown menu. This deliberately logs in fresh in this
    // same file (not the shared `admin-chromium` storageState used by
    // admin.spec.js/journeys-admin.spec.js) — logging out for real
    // invalidates the session server-side, which would otherwise silently
    // break every other admin-chromium test that runs afterward and reuses
    // that same shared, now-invalidated session cookie.
    test('admin can log out via the user menu', async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/admin$/);

        await page.getByRole('button', { name: /Admin/, exact: false }).click();
        await page.getByRole('button', { name: 'Logout' }).click();
        await expect(page).toHaveURL(/\/admin\/login$/);

        // The session is genuinely gone, not just a client-side redirect.
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
