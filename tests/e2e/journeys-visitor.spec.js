import { test, expect } from '@playwright/test';

test.describe('Full business journeys — visitor', () => {
    test('Journey A: Homepage -> Hajj -> Package Listing -> Package Detail -> Inquiry CTA -> Submit -> Confirmation', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Hajj', exact: true }).click();
        await expect(page).toHaveURL(/\/hajj$/);

        await expect(page.locator('.package-card').first()).toBeVisible();
        await page.getByRole('link', { name: 'View Details' }).first().click();
        await expect(page).toHaveURL(/\/hajj\//);

        await page.getByLabel('Full Name').fill('Journey A Tester');
        await page.getByLabel('Email').fill('journey-a@example.com');
        await page.getByLabel('Phone / WhatsApp').fill('+923001110001');
        await page.getByLabel('Message').fill('Journey A automated test.');
        await page.getByRole('button', { name: 'Submit Inquiry' }).click();

        await expect(page.getByText(/inquiry has been received/i)).toBeVisible();
    });

    test('Journey B: Homepage -> Umrah -> (no packages yet) -> Contact instead', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Umrah', exact: true }).click();
        await expect(page).toHaveURL(/\/umrah$/);

        // Real content gap: Umrah has no packages yet (source data doesn't exist).
        // The honest empty state must point the visitor somewhere useful, not dead-end.
        await expect(page.getByText(/No umrah packages are published yet/i)).toBeVisible();
        await page.getByRole('link', { name: 'contact us', exact: true }).click();
        await expect(page).toHaveURL(/\/contact$/);

        await page.getByLabel('Full Name').fill('Journey B Tester');
        await page.getByLabel('Email').fill('journey-b@example.com');
        await page.getByLabel('Phone').fill('+923001110002');
        await page.getByLabel('Message').fill('Journey B automated test — asking about Umrah availability.');
        await page.getByRole('button', { name: 'Send Message' }).click();

        await expect(page.getByText(/thank you for contacting us/i)).toBeVisible();
    });

    test('Journey C: Homepage -> Tourism -> Package -> Inquiry', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Tourism', exact: true }).click();
        await expect(page).toHaveURL(/\/tourism$/);

        await page.getByRole('link', { name: 'View Details' }).first().click();
        await expect(page.url()).toMatch(/\/tourism\//);

        await page.getByLabel('Full Name').fill('Journey C Tester');
        await page.getByLabel('Email').fill('journey-c@example.com');
        await page.getByLabel('Phone / WhatsApp').fill('+923001110003');
        await page.getByLabel('Message').fill('Journey C automated test.');
        await page.getByRole('button', { name: 'Submit Inquiry' }).click();

        await expect(page.getByText(/inquiry has been received/i)).toBeVisible();
    });
});
