import { test, expect } from '@playwright/test';

// This whole file runs under the 'admin-chromium' project, which supplies a
// pre-authenticated storageState (see auth.setup.js) — no per-test login is
// needed, which also keeps us well clear of the real throttle:5,1 rate limit
// on the login route (login-flow itself is tested separately in
// admin-auth.spec.js, which intentionally runs unauthenticated).

test.describe('Admin CMS', () => {
    test('16. dashboard shows real stat tiles', async ({ page }) => {
        await page.goto('/admin');
        await expect(page.getByText('Published Packages')).toBeVisible();
        await expect(page.getByText('New Inquiries')).toBeVisible();
    });

    test('17-19. admin can create, edit, and publish/unpublish a package, verified on the frontend', async ({ page }) => {
        const uniqueCode = 'E2E' + Date.now();
        const uniqueName = 'Playwright E2E Test Package ' + Date.now();

        // 17. Create as draft
        await page.goto('/admin/packages/create');
        await page.locator('select[name="package_category_id"]').selectOption({ label: 'Hajj' });
        await page.locator('input[name="code"]').fill(uniqueCode);
        await page.locator('input[name="name"]').fill(uniqueName);
        await page.locator('select[name="status"]').selectOption('draft');
        await page.getByRole('button', { name: 'Create Package' }).click();
        await expect(page).toHaveURL(/\/admin\/packages$/);
        await expect(page.getByText(uniqueName)).toBeVisible();

        // 20 (part 1). Draft package must NOT appear on the public frontend yet
        const slug = uniqueName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        const draftResponse = await page.goto('/hajj/' + slug);
        expect(draftResponse.status()).toBe(404);

        // 18/19. Edit and publish
        await page.goto('/admin/packages');
        await page.getByRole('row', { name: new RegExp(uniqueName) }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('input[name="duration_label"]').fill('7 Days Package (E2E)');
        await page.locator('select[name="status"]').selectOption('published');
        await page.getByRole('button', { name: 'Update Package' }).click();
        await expect(page).toHaveURL(/\/admin\/packages$/);

        // 20 (part 2). Published package now appears on the public frontend
        const liveResponse = await page.goto('/hajj/' + slug);
        expect(liveResponse.status()).toBe(200);
        await expect(page.getByRole('heading', { name: uniqueName })).toBeVisible();
        await expect(page.getByText('7 Days Package (E2E)')).toBeVisible();

        // Cleanup: delete the test package so repeated runs don't accumulate data
        await page.goto('/admin/packages');
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('row', { name: new RegExp(uniqueName) }).getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(uniqueName)).not.toBeVisible();
    });

    test('21. admin can edit a testimonial', async ({ page }) => {
        await page.goto('/admin/testimonials');
        await page.getByRole('row', { name: /Haseeb Jawed/ }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('textarea[name="quote"]').fill('Updated via Playwright E2E test — real testimonial content preserved otherwise.');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/testimonials$/);
        await expect(page.getByText(/Updated via Playwright/)).toBeVisible();
    });

    test('admin can create, edit, and delete a News article (regression: route param mismatch previously broke edit/update/delete)', async ({ page }) => {
        const title = 'Playwright E2E News ' + Date.now();

        await page.goto('/admin/news/create');
        await page.locator('input[name="title"]').fill(title);
        await page.locator('textarea[name="excerpt"]').fill('Test excerpt.');
        await page.locator('input[name="is_active"]').check();
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/news$/);
        await expect(page.getByText(title)).toBeVisible();

        await page.getByRole('row', { name: new RegExp(title) }).getByRole('link', { name: 'Edit' }).click();
        await expect(page.locator('input[name="title"]')).toHaveValue(title);
        await page.locator('input[name="title"]').fill(title + ' Updated');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/news$/);
        await expect(page.getByText(title + ' Updated')).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('row', { name: new RegExp(title) }).getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText(title + ' Updated')).not.toBeVisible();
    });

    test('22. admin can manage a FAQ', async ({ page }) => {
        await page.goto('/admin/faqs/create');
        await page.locator('select[name="category"]').selectOption('hajj');
        await page.locator('input[name="question"]').fill('Playwright E2E test question?');
        await page.locator('textarea[name="answer"]').fill('Playwright E2E test answer.');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/faqs$/);
        await expect(page.getByText('Playwright E2E test question?')).toBeVisible();
    });

    test('23. admin can view and update an inquiry status', async ({ page }) => {
        // Create a real inquiry via the public contact form first
        await page.goto('/contact');
        await page.getByLabel('Full Name').fill('E2E Inquiry Source');
        await page.getByLabel('Email').fill('e2e-inquiry@example.com');
        await page.getByLabel('Phone').fill('+923001112222');
        await page.getByLabel('Message').fill('Testing admin inquiry management.');
        await page.getByRole('button', { name: 'Send Message' }).click();

        await page.goto('/admin/inquiries');
        await page.getByRole('row', { name: /E2E Inquiry Source/ }).getByRole('link', { name: 'View' }).click();
        await page.locator('select[name="status"]').selectOption('contacted');
        await page.getByRole('button', { name: 'Update Status' }).click();
        await expect(page.locator('select[name="status"]')).toHaveValue('contacted');
    });

    test('24. admin can manage the About Us page', async ({ page }) => {
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();
        await expect(page.locator('input[name="slug"]')).toHaveValue('about-us');
        await page.getByRole('button', { name: 'Update Page' }).click();
        await expect(page).toHaveURL(/\/admin\/pages$/);

        const response = await page.goto('/about-us');
        expect(response.status()).toBe(200);
    });

    test('25. package SEO fields are saved and rendered in the public page head', async ({ page }) => {
        await page.goto('/admin/packages');
        await page.getByRole('row', { name: /UB001/ }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('input[name="meta_title"]').fill('Playwright SEO Title Test | Universal Brothers');
        await page.locator('input[name="meta_description"]').fill('Playwright SEO description test.');
        await page.getByRole('button', { name: 'Update Package' }).click();

        await page.goto('/hajj');
        await page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click();
        await expect(page).toHaveTitle('Playwright SEO Title Test | Universal Brothers');
        await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', 'Playwright SEO description test.');
    });
});
