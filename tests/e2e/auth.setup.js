import { test as setup, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@universalbrothers.test';
const ADMIN_PASSWORD = 'password';
const authFile = 'playwright/.auth/admin.json';

setup('authenticate as admin', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email').fill(ADMIN_EMAIL);
    await page.getByLabel('Password').fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Login' }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    await page.context().storageState({ path: authFile });
});
