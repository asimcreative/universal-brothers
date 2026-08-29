import { test, expect } from '@playwright/test';

test.describe('Public website', () => {
    test('1. homepage loads successfully', async ({ page }) => {
        const response = await page.goto('/');
        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Universal Brothers/);
    });

    test('2. header navigation links to Hajj/Umrah/Tourism/Contact', async ({ page, isMobile }) => {
        // On mobile, the desktop nav is intentionally collapsed behind the
        // hamburger — its links live in the offcanvas instead, already
        // covered by test 13. Nothing to check here for that viewport.
        test.skip(isMobile, 'Desktop nav links are hidden on mobile by design — see test 13 for the offcanvas equivalent.');

        await page.goto('/');
        const nav = page.locator('nav.navbar');
        await expect(nav.getByRole('link', { name: 'Hajj', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Umrah', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Tourism', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Contact', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'About Us', exact: true })).toBeVisible();
    });

    test('3. hero renders (fallback hero when no slider is configured)', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('.hero-slide')).toBeVisible();
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });

    test('4. Hajj category page loads with real seeded packages', async ({ page }) => {
        const response = await page.goto('/hajj');
        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'Hajj Packages' })).toBeVisible();
    });

    test('5. Hajj package listing shows real brochure package cards', async ({ page }) => {
        await page.goto('/hajj');
        const cards = page.locator('.package-card');
        await expect(cards.first()).toBeVisible();
        expect(await cards.count()).toBeGreaterThanOrEqual(9);
        await expect(page.getByText('UB001')).toBeVisible();
    });

    test('6. Hajj package detail shows real itinerary and pricing', async ({ page }) => {
        await page.goto('/hajj');
        await page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click();
        await expect(page).toHaveURL(/\/hajj\/ub001-/);
        await expect(page.getByText('Dar Al Tawhid Intercontinental').first()).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Day-by-Day Itinerary' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Room Type Pricing' })).toBeVisible();
    });

    test('7. Umrah page loads and shows the honest empty state (no invented packages)', async ({ page }) => {
        const response = await page.goto('/umrah');
        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'Umrah Packages' })).toBeVisible();
        await expect(page.getByText(/No umrah packages are published yet/i)).toBeVisible();
    });

    test('8. Tourism page loads and shows real recovered package names', async ({ page }) => {
        const response = await page.goto('/tourism');
        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'Tourism Packages' })).toBeVisible();
        await expect(page.getByText(/Splendid Skardu/i)).toBeVisible();
    });

    test('9. package card CTA navigates to the detail page', async ({ page }) => {
        await page.goto('/tourism');
        await page.getByRole('link', { name: 'View Details' }).first().click();
        await expect(page.url()).toMatch(/\/tourism\//);
    });

    test('10. inquiry form on a package detail page submits successfully', async ({ page, isMobile }) => {
        // Business logic (server-side submit) is already fully proven on
        // desktop; re-running it here would only contend with desktop's
        // tests for the same shared per-IP rate-limit bucket. Mobile's job
        // is rendering/layout, covered by tests 13 and 14.
        test.skip(isMobile, 'Submission logic covered on desktop — avoids shared rate-limit contention.');

        await page.goto('/hajj');
        await page.getByRole('link', { name: 'View Details' }).first().click();

        await page.getByLabel('Full Name').fill('Playwright Tester');
        await page.getByLabel('Email').fill('playwright@example.com');
        await page.getByLabel('Phone / WhatsApp').fill('+923001234567');
        await page.getByLabel('Message').fill('Automated E2E test inquiry.');
        await page.getByRole('button', { name: 'Submit Inquiry' }).click();

        await expect(page.getByText(/inquiry has been received/i)).toBeVisible();
    });

    test('11. contact form: empty submit is blocked client-side by HTML5 required fields', async ({ page }) => {
        await page.goto('/contact');
        await page.getByRole('button', { name: 'Send Message' }).click();
        // Native browser validation should prevent navigation entirely — no round-trip to the server.
        await expect(page).toHaveURL(/\/contact$/);
        const isNameValid = await page.locator('#contact-name').evaluate((el) => el.checkValidity());
        expect(isNameValid).toBe(false);
    });

    test('11c. contact form: server-side validation renders when an invalid email bypasses client-side checks', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Submission logic covered on desktop — avoids shared rate-limit contention.');

        await page.goto('/contact');
        await page.getByLabel('Full Name').fill('Playwright Tester');
        await page.getByLabel('Email').fill('not-a-valid-email');
        await page.getByLabel('Phone').fill('+923001234567');
        await page.getByLabel('Message').fill('Testing server-side email validation.');

        // Disable native HTML5 validation so the (deliberately invalid) email
        // actually reaches the server — proving Laravel's own validation catches
        // it independently of the browser's client-side check.
        await page.locator('form').evaluate((form) => { form.noValidate = true; });
        await page.getByRole('button', { name: 'Send Message' }).click();

        await expect(page).toHaveURL(/\/contact$/);
        await expect(page.locator('.alert-danger')).toBeVisible();
        await expect(page.getByText('The email field must be a valid email address.')).toBeVisible();
    });

    test('11b. contact form submits successfully with valid data', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Submission logic covered on desktop — avoids shared rate-limit contention.');

        await page.goto('/contact');
        await page.getByLabel('Full Name').fill('Playwright Contact Tester');
        await page.getByLabel('Email').fill('contact-e2e@example.com');
        await page.getByLabel('Phone').fill('+923009998888');
        await page.getByLabel('Message').fill('Automated E2E contact form test.');
        await page.getByRole('button', { name: 'Send Message' }).click();
        await expect(page.getByText(/thank you for contacting us/i)).toBeVisible();
    });

    test('12. footer shows real contact details and working links', async ({ page }) => {
        await page.goto('/');
        const footer = page.locator('footer');
        await expect(footer.getByText(/Karachi/i)).toBeVisible();
        await expect(footer.getByRole('link', { name: 'Hajj', exact: true })).toHaveAttribute('href', /\/hajj$/);
    });

    test('13. mobile navigation opens via offcanvas toggle', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');
        await page.locator('.navbar-toggler').click();
        const offcanvas = page.locator('#mobileNav');
        await expect(offcanvas).toBeVisible();
        await expect(offcanvas.getByRole('link', { name: 'Hajj', exact: true })).toBeVisible();
    });

    test('14. no obvious horizontal overflow at mobile width', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/');
        const { scrollWidth, clientWidth } = await page.evaluate(() => ({
            scrollWidth: document.documentElement.scrollWidth,
            clientWidth: document.documentElement.clientWidth,
        }));
        expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
    });

    test('static About Us page renders real seeded content', async ({ page }) => {
        const response = await page.goto('/about-us');
        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { name: 'About Us', exact: true })).toBeVisible();
        await expect(page.getByText(/50,000/)).toBeVisible();
    });
});
