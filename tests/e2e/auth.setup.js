import { test as setup, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@universalbrothers.test';
const ADMIN_PASSWORD = 'password';
const authFile = 'playwright/.auth/admin.json';

setup('authenticate as admin', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email').fill(ADMIN_EMAIL);
    // exact: true — the login page's new show/hide toggle button has an
    // accessible name ("Show password") that otherwise substring-matches
    // getByLabel('Password') too (Playwright's default match is substring,
    // case-insensitive), a real strict-mode collision from that legitimate
    // a11y addition, not a weakened assertion.
    await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Login' }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    await page.context().storageState({ path: authFile });
});
