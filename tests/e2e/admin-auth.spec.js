import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@universalbrothers.test';
const ADMIN_PASSWORD = 'password';

// Deliberately runs unauthenticated (no storageState) — this file tests the
// login flow itself, so it must start from a logged-out browser context.
test.describe('Admin login flow', () => {
    test('15. admin can log in with seeded credentials', async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill(ADMIN_PASSWORD);
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/admin$/);
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    });

    test('15b. admin login rejects wrong password', async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel('Email').fill(ADMIN_EMAIL);
        await page.getByLabel('Password').fill('wrong-password');
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/admin\/login$/);
        await expect(page.locator('.alert-danger')).toBeVisible();
    });

    test('guest visiting /admin is redirected to login', async ({ page }) => {
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
