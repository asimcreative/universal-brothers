import { test, expect } from '@playwright/test';
import { clickAndConfirm } from './helpers/confirm.js';

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
        await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(name) }).getByRole('button', { name: 'Delete' }));
    });

    test('Journey E: Admin -> Edit About Us Page -> Publish -> Frontend Verify', async ({ page }) => {
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();

        // About Us is a real, persistent page, so its story text is captured
        // and put back afterwards. The page is built from sections (issue
        // #13): the story is the first section, edited in the text editor.
        const story = page.locator('#builder-sections > [data-section]').first();
        await story.locator('[data-section-toggle]').click();
        const storyField = story.locator('textarea[name$="[data][content]"]');
        const originalStory = await storyField.inputValue();

        const marker = 'Journey E marker ' + Date.now();
        const editor = story.locator('.rt-editable');
        await editor.click();
        await page.keyboard.press('Control+End');
        await page.keyboard.press('Enter');
        await page.keyboard.type(marker);
        await clickAndConfirm(page, page.getByRole('button', { name: /^Publish/ }).first());
        await expect(page.getByText('is published and visible on the website')).toBeVisible();

        const response = await page.goto('/about-us');
        expect(response.status()).toBe(200);
        await expect(page.getByText(marker)).toBeVisible();

        // Cleanup: put the real story back and publish it.
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();
        const restoreStory = page.locator('#builder-sections > [data-section]').first();
        await restoreStory.locator('[data-section-toggle]').click();
        await restoreStory.locator('textarea[name$="[data][content]"]').evaluate((field, value) => { field.value = value; }, originalStory);
        await clickAndConfirm(page, page.getByRole('button', { name: /^Publish/ }).first());
        await expect(page.getByText('is published and visible on the website')).toBeVisible();
    });
});
