import { test, expect } from '@playwright/test';

// Runs under 'admin-chromium' with pre-authenticated storageState.
test.describe('Full business journeys — admin', () => {
    test('Journey D: Admin Dashboard -> Create Package -> Publish -> Frontend Verify -> Edit -> Frontend Verify', async ({ page }) => {
        await page.goto('/admin');
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

        const name = 'Journey D Package ' + Date.now();
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

        await page.goto('/admin/packages/create');
        await page.locator('select[name="package_category_id"]').selectOption({ label: 'Tourism' });
        await page.locator('input[name="name"]').fill(name);
        await page.locator('select[name="status"]').selectOption('published');
        await page.getByRole('button', { name: 'Create Package' }).click();
        await expect(page).toHaveURL(/\/admin\/packages$/);

        let response = await page.goto('/tourism/' + slug);
        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name })).toBeVisible();

        await page.goto('/admin/packages');
        await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('textarea[name="summary"]').fill('Updated during Journey D E2E test.');
        await page.getByRole('button', { name: 'Update Package' }).click();

        response = await page.goto('/tourism/' + slug);
        expect(response.status()).toBe(200);
        await expect(page.getByText('Updated during Journey D E2E test.')).toBeVisible();

        // Cleanup
        await page.goto('/admin/packages');
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('row', { name: new RegExp(name) }).getByRole('button', { name: 'Delete' }).click();
    });

    test('Journey E: Admin -> Edit About Us Page -> Publish -> Frontend Verify', async ({ page }) => {
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();

        const marker = 'Journey E marker ' + Date.now();
        await page.locator('textarea[name="body"]').fill('<p>' + marker + '</p>');
        await page.locator('input[name="is_active"]').check();
        await page.getByRole('button', { name: 'Update Page' }).click();
        await expect(page).toHaveURL(/\/admin\/pages$/);

        const response = await page.goto('/about-us');
        expect(response.status()).toBe(200);
        await expect(page.getByText(marker)).toBeVisible();
    });
});
