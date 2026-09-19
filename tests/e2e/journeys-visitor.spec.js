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

// The homepage is on the designer's template: its header is a flat list of ten
// links inside `nav.ub-primary-nav`, with no mega-menu and no dropdowns. Every
// OTHER page is still on the Bootstrap layout and still has `nav.navbar`, so a
// journey that has left the homepage keeps using the old selector — which is
// why both appear in this file.
const HOME_NAV = 'nav.ub-primary-nav';

test.describe('Full business journeys — visitor', () => {
    test('Journey A: Homepage -> Hajj -> Package Listing -> Package Detail -> Inquiry CTA -> Submit -> Confirmation', async ({ page }) => {
        try {
            await page.goto('/');
            // The packages section carries the route to the listing now. The
            // mega-menu that used to hold it does not exist on this layout.
            await page.locator('#featured-packages').getByRole('link', { name: /View all Hajj Packages/i }).click();
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
            // Umrah has no published packages, so it has no tab in the
            // packages section; the footer's service list is how a visitor
            // reaches that listing from the homepage.
            await page.locator('footer').getByRole('link', { name: 'Umrah Packages', exact: true }).click();
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
            // "Tourism" is a real link on this header, not a dropdown trigger.
            await page.locator(HOME_NAV).getByRole('link', { name: 'Tourism', exact: true }).click();
            await expect(page.url()).toMatch(/\/tourism/);

            // The domestic/international split is still reached the old way —
            // the listing page is on the Bootstrap layout and keeps its
            // dropdown — so that filter stays covered.
            await page.locator('nav.navbar').getByRole('button', { name: 'Tourism', exact: true }).click();
            await page.locator('nav.navbar').getByRole('link', { name: 'Domestic Tourism', exact: true }).click();
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
        await page.locator(HOME_NAV).getByRole('link', { name: 'Awards & Recognition', exact: true }).click();
        await expect(page).toHaveURL(/\/awards$/);

        await expect(page.getByRole('heading', { name: 'Excellence Recognized. Trust Earned.' })).toBeVisible();
        // Real, seeded award names — not placeholder/lorem content.
        await expect(page.getByText('FPCCI Achievement Award')).toBeVisible();
    });


    test('Journey E: Homepage -> Affiliations -> real affiliation content displays', async ({ page }) => {
        await page.goto('/');
        await page.locator(HOME_NAV).getByRole('link', { name: 'Affiliations', exact: true }).click();
        await expect(page).toHaveURL(/\/affiliations$/);

        await expect(page.getByRole('heading', { name: 'Strong Relationships. Trusted Connections.' })).toBeVisible();
        await expect(page.getByText('IATA', { exact: true })).toBeVisible();
    });

    test('Journey F: Homepage -> Media -> honest empty state (no real news/gallery/video content yet)', async ({ page }) => {
        await page.goto('/');
        await page.locator(HOME_NAV).getByRole('link', { name: 'Media', exact: true }).click();
        await expect(page).toHaveURL(/\/media$/);

        // Real content gap: no NewsArticle/MediaItem rows exist yet in this
        // environment — the News tab (shown by default) must say so honestly
        // rather than render a broken/empty-looking grid with no explanation.
        await expect(page.getByText(/No news articles have been published yet/i)).toBeVisible();
    });

    test('Journey G: Homepage -> Testimonials -> real testimonial content displays', async ({ page }) => {
        await page.goto('/');
        await page.locator(HOME_NAV).getByRole('link', { name: 'Testimonials', exact: true }).click();
        await expect(page).toHaveURL(/\/testimonials$/);

        await expect(page.getByRole('heading', { name: 'Their Journeys. Their Words.' })).toBeVisible();
        // Real, seeded testimonial — recovered from the live site audit, not invented.
        await expect(page.getByText('Haseeb Jawed')).toBeVisible();
    });

    test('Journey H: Homepage -> FAQs -> Contact', async ({ page }) => {
        await page.goto('/');
        await page.locator(HOME_NAV).getByRole('link', { name: 'FAQs', exact: true }).click();
        await expect(page).toHaveURL(/\/faqs$/);

        await expect(page.getByText('What is the Hajj 2027 payment plan?')).toBeVisible();

        await page.locator('nav.navbar').getByRole('link', { name: 'Contact', exact: true }).click();
        await expect(page).toHaveURL(/\/contact$/);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });
});
