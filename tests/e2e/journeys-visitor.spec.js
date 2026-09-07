import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

const PHP_BIN = process.env.PHP_BIN || 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';

// See public.spec.js's identical helper for the full regression this closes
// — these journeys submit real inquiries with no admin session available to
// delete them through the UI, and were part of the 185-of-260 real
// `inquiries` rows left as permanent test residue across this engagement.
// `--env=testing` — see the identical helper in public.spec.js for why.
function deleteTestInquiriesByEmail(email) {
    execFileSync(PHP_BIN, ['artisan', 'tinker', '--env=testing', '--execute', `App\\Models\\Inquiry::where('email', '${email}')->delete();`], { stdio: 'ignore' });
}

test.describe('Full business journeys — visitor', () => {
    test('Journey A: Homepage -> Hajj -> Package Listing -> Package Detail -> Inquiry CTA -> Submit -> Confirmation', async ({ page }) => {
        try {
            await page.goto('/');
            // "Hajj & Umrah" is a mega-menu trigger (role="button" — it opens a
            // panel, it isn't real navigation); the real "Hajj Packages" link
            // lives inside that panel.
            await page.locator('nav.navbar').getByRole('button', { name: 'Hajj & Umrah', exact: true }).click();
            await page.locator('.mega-menu').getByRole('link', { name: 'Hajj Packages', exact: true }).click();
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
        } finally {
            deleteTestInquiriesByEmail('journey-a@example.com');
        }
    });

    test('Journey B: Homepage -> Umrah -> (no packages yet) -> Contact instead', async ({ page }) => {
        try {
            await page.goto('/');
            await page.locator('nav.navbar').getByRole('button', { name: 'Hajj & Umrah', exact: true }).click();
            await page.locator('.mega-menu').getByRole('link', { name: 'Umrah Packages', exact: true }).click();
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
        } finally {
            deleteTestInquiriesByEmail('journey-b@example.com');
        }
    });

    test('Journey C: Homepage -> Tourism -> Package -> Inquiry', async ({ page }) => {
        try {
            await page.goto('/');
            // "Tourism" is a dropdown trigger (role="button"); it reveals
            // Domestic/International sub-links rather than navigating itself.
            await page.locator('nav.navbar').getByRole('button', { name: 'Tourism', exact: true }).click();
            await page.getByRole('link', { name: 'Domestic Tourism', exact: true }).click();
            await expect(page.url()).toMatch(/\/tourism/);

            await page.getByRole('link', { name: 'View Details' }).first().click();
            await expect(page.url()).toMatch(/\/tourism\//);

            await page.getByLabel('Full Name').fill('Journey C Tester');
            await page.getByLabel('Email').fill('journey-c@example.com');
            await page.getByLabel('Phone / WhatsApp').fill('+923001110003');
            await page.getByLabel('Message').fill('Journey C automated test.');
            await page.getByRole('button', { name: 'Submit Inquiry' }).click();

            await expect(page.getByText(/inquiry has been received/i)).toBeVisible();
        } finally {
            deleteTestInquiriesByEmail('journey-c@example.com');
        }
    });

    test('Journey D: Homepage -> Awards -> real award content displays', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: 'Awards & Recognition', exact: true }).click();
        await expect(page).toHaveURL(/\/awards$/);

        await expect(page.getByRole('heading', { name: 'Excellence Recognized. Trust Earned.' })).toBeVisible();
        // Real, seeded award names — not placeholder/lorem content.
        await expect(page.getByText('FPCCI Achievement Award')).toBeVisible();
    });

    test('Journey E: Homepage -> Affiliations -> real affiliation content displays', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Affiliations', exact: true }).click();
        await expect(page).toHaveURL(/\/affiliations$/);

        await expect(page.getByRole('heading', { name: 'Strong Relationships. Trusted Connections.' })).toBeVisible();
        await expect(page.getByText('IATA', { exact: true })).toBeVisible();
    });

    test('Journey F: Homepage -> Media -> honest empty state (no real news/gallery/video content yet)', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Media', exact: true }).click();
        await expect(page).toHaveURL(/\/media$/);

        // Real content gap: no NewsArticle/MediaItem rows exist yet in this
        // environment — the News tab (shown by default) must say so honestly
        // rather than render a broken/empty-looking grid with no explanation.
        await expect(page.getByText(/No news articles have been published yet/i)).toBeVisible();
    });

    test('Journey G: Homepage -> Testimonials -> real testimonial content displays', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'Testimonials', exact: true }).click();
        await expect(page).toHaveURL(/\/testimonials$/);

        await expect(page.getByRole('heading', { name: 'Their Journeys. Their Words.' })).toBeVisible();
        // Real, seeded testimonial — recovered from the live site audit, not invented.
        await expect(page.getByText('Haseeb Jawed')).toBeVisible();
    });

    test('Journey H: Homepage -> FAQs -> Contact', async ({ page }) => {
        await page.goto('/');
        await page.locator('nav.navbar').getByRole('link', { name: 'FAQs', exact: true }).click();
        await expect(page).toHaveURL(/\/faqs$/);

        await expect(page.getByText('What is the Hajj 2027 payment plan?')).toBeVisible();

        await page.locator('nav.navbar').getByRole('link', { name: 'Contact', exact: true }).click();
        await expect(page).toHaveURL(/\/contact$/);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });
});
