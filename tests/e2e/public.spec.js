import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

const PHP_BIN = process.env.PHP_BIN || 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';

// Regression: these two tests submit real inquiry/contact forms to prove the
// real business flow works end-to-end — that's the point, so the resulting
// row must actually be written, not mocked. But with no admin session in
// this file (deliberately anonymous/visitor-only), neither test had any way
// to remove what it created afterward, and after this whole engagement's
// many Playwright runs that left 185 of 260 real `inquiries` rows as
// permanent "Playwright Tester"/"E2E Inquiry Source ..." test residue —
// found via an admin dashboard screenshot showing "234 New Inquiries" real
// staff would see. This deletes only the exact rows this run's own tests
// just created, by email, every time — safe as a no-op if the row already
// doesn't exist.
// `--env=testing` is required: the E2E server itself runs with it (see
// playwright.config.js), so the rows this deletes have to be looked up in
// the same `database/testing.sqlite` the server wrote them to. Without it
// the cleanup silently no-ops against the dev database instead.
function deleteTestInquiriesByEmail(email) {
    execFileSync(PHP_BIN, ['artisan', 'tinker', '--env=testing', '--execute', `App\\Models\\Inquiry::where('email', '${email}')->delete();`], { stdio: 'ignore' });
}

test.describe('Public website', () => {
    test('1. homepage loads successfully', async ({ page }) => {
        const response = await page.goto('/');
        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Universal Brothers/);
    });

    test('2. header navigation links to Hajj & Umrah mega-menu/Tourism/Contact', async ({ page, isMobile }) => {
        // On mobile, the desktop nav is intentionally collapsed behind the
        // hamburger — its links live in the offcanvas instead, already
        // covered by test 13. Nothing to check here for that viewport.
        test.skip(isMobile, 'Desktop nav links are hidden on mobile by design — see test 13 for the offcanvas equivalent.');

        await page.goto('/');
        const nav = page.locator('nav.navbar');
        // The Hajj & Umrah / Tourism triggers are dropdown-openers, not real
        // navigation, so they're marked role="button" (WAI-ARIA authoring
        // practice for a menu trigger) rather than the implicit link role.
        await expect(nav.getByRole('button', { name: 'Hajj & Umrah', exact: true })).toBeVisible();
        await expect(nav.getByRole('button', { name: 'Tourism', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Contact', exact: true })).toBeVisible();

        // The company pages (About Us, Awards, Affiliations, Testimonials,
        // Media, FAQs) moved into one About group in the 2026-09-16 design
        // pass, so the bar carries five items instead of ten. Each page is
        // still one click away, which is what this asserts.
        await expect(nav.getByRole('button', { name: 'About', exact: true })).toBeVisible();
        await nav.getByRole('button', { name: 'About', exact: true }).click();
        for (const name of ['About Us', 'Awards & Recognition', 'Affiliations', 'Testimonials', 'Media', 'FAQs']) {
            await expect(nav.getByRole('link', { name, exact: true })).toBeVisible();
        }
        await page.keyboard.press('Escape');

        // The Hajj & Umrah mega-menu itself carries the real Hajj/Umrah
        // package links — Bootstrap's dropdown reveals it on click.
        await nav.getByRole('button', { name: 'Hajj & Umrah', exact: true }).click();
        const megaMenu = page.locator('.mega-menu');
        await expect(megaMenu.getByRole('link', { name: 'Hajj Packages', exact: true })).toBeVisible();
        await expect(megaMenu.getByRole('link', { name: 'Umrah Packages', exact: true })).toBeVisible();
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
        // Release-gate cross-browser QA: this exact click reproduced an
        // intermittent WebKit-only failure across 3 separate full-suite runs
        // — the trace showed the click itself completing and Playwright's
        // own post-click "wait for scheduled navigation" reporting done, yet
        // the URL never changed. That's the classic Playwright navigation
        // race: a `.click()` that starts a fast local-dev-server navigation
        // can complete before a *subsequently* awaited navigation-wait
        // attaches its listener. Arming `waitForURL` in the same `Promise.all`
        // as the click (Playwright's own recommended pattern for this race)
        // closes the window entirely, regardless of how fast the navigation
        // completes.
        await Promise.all([
            page.waitForURL(/\/hajj\/ub001-/),
            page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click(),
        ]);
        await expect(page.getByText('Dar Al Tawhid Intercontinental').first()).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Day-by-Day Itinerary' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Room Type Pricing' })).toBeVisible();
    });

    test('6b. Hajj package detail currency switcher changes displayed price without a page reload', async ({ page }) => {
        await page.goto('/hajj');
        await Promise.all([
            page.waitForURL(/\/hajj\/ub001-/),
            page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click(),
        ]);

        // UB001 Package B Quad. This assertion used to read "USD, then N/A on
        // SAR", because the brochure supplied to this project was USD-only.
        // The client has since supplied the PKR and Riyal brochures and the
        // backfill landed (issue #5), so all three currencies are now real
        // seeded values — and asserting all three is a stronger test of the
        // switcher than USD-and-a-blank ever was.
        const priceCell = page.locator('.currency-price[data-usd="16300.00"]').first();
        await expect(priceCell).toHaveText('US$16,300');

        await page.locator('#currency-switcher [data-currency="SAR"]').click();
        await expect(priceCell).toHaveText('SAR 59,500');

        await page.locator('#currency-switcher [data-currency="PKR"]').click();
        await expect(priceCell).toHaveText('PKR 4,640,000');

        await page.locator('#currency-switcher [data-currency="USD"]').click();
        await expect(priceCell).toHaveText('US$16,300');

        // No N/A assertion here any more, and deliberately so. A room with no
        // price in any currency (UB001 Package A Quad — the brochure prints
        // "NA") is not rendered as an empty row at all, and every room that IS
        // rendered now has all three currencies. The N/A branch in the
        // switcher still exists for a partially-priced room, but no such room
        // is currently published, so asserting it here would mean asserting
        // against markup the page does not produce.
        await expect(page.locator('.currency-price[data-usd=""]')).toHaveCount(0);

        // Switching currency must not touch anything else on the page.
        await expect(page.getByRole('heading', { name: 'Day-by-Day Itinerary' })).toBeVisible();
        await expect(page.getByText('Dar Al Tawhid Intercontinental').first()).toBeVisible();
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
        // Same click/navigation race as test 6 — arm the wait before clicking.
        await Promise.all([
            page.waitForURL(/\/tourism\//),
            page.getByRole('link', { name: 'View Details' }).first().click(),
        ]);
        await expect(page).toHaveURL(/\/tourism\//);
    });

    test('10. inquiry form on a package detail page submits successfully', async ({ page, isMobile }) => {
        // Business logic (server-side submit) is already fully proven on
        // desktop; re-running it here would only contend with desktop's
        // tests for the same shared per-IP rate-limit bucket. Mobile's job
        // is rendering/layout, covered by tests 13 and 14.
        test.skip(isMobile, 'Submission logic covered on desktop — avoids shared rate-limit contention.');

        try {
            await page.goto('/hajj');
            // Same click/navigation race as test 6 — arm the wait before clicking.
            await Promise.all([
                page.waitForURL(/\/hajj\/.+/),
                page.getByRole('link', { name: 'View Details' }).first().click(),
            ]);

            await page.getByLabel('Full Name').fill('Playwright Tester');
            await page.getByLabel('Email').fill('playwright@example.com');
            await page.getByLabel('Phone / WhatsApp').fill('+923001234567');
            await page.getByLabel('Message').fill('Automated E2E test inquiry.');
            await page.getByRole('button', { name: 'Submit Inquiry' }).click();

            await expect(page.getByText(/inquiry has been received/i)).toBeVisible();
        } finally {
            deleteTestInquiriesByEmail('playwright@example.com');
        }
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
        // Scoped to the contact form specifically. A bare locator('form') was
        // fine when /contact had exactly one form; the AI assistant's composer
        // is a second, legitimate form on every page (a <form> is the right
        // element for it — it is what gives Enter-to-submit its semantics), so
        // this now has to say which form it means.
        await page.locator('form[action$="/contact"]').evaluate((form) => { form.noValidate = true; });
        await page.getByRole('button', { name: 'Send Message' }).click();

        await expect(page).toHaveURL(/\/contact$/);
        await expect(page.locator('.alert-danger')).toBeVisible();
        await expect(page.getByText('The email field must be a valid email address.')).toBeVisible();
    });

    test('11b. contact form submits successfully with valid data', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Submission logic covered on desktop — avoids shared rate-limit contention.');

        try {
            await page.goto('/contact');
            await page.getByLabel('Full Name').fill('Playwright Contact Tester');
            await page.getByLabel('Email').fill('contact-e2e@example.com');
            await page.getByLabel('Phone').fill('+923009998888');
            await page.getByLabel('Message').fill('Automated E2E contact form test.');
            await page.getByRole('button', { name: 'Send Message' }).click();
            await expect(page.getByText(/thank you for contacting us/i)).toBeVisible();
        } finally {
            deleteTestInquiriesByEmail('contact-e2e@example.com');
        }
    });

    test('12. footer shows real contact details and working links', async ({ page }) => {
        await page.goto('/');
        const footer = page.locator('footer');
        await expect(footer.getByText(/Karachi/i)).toBeVisible();
        // Real Hajj column link, driven by the actual active "hajj" category
        // (see FooterTest for the CMS-driven-services regression this covers).
        await expect(footer.getByRole('link', { name: 'Hajj Packages', exact: true })).toHaveAttribute('href', /\/hajj$/);
    });

    test('13. mobile navigation opens via offcanvas toggle', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');
        await page.locator('.navbar-toggler').click();
        const offcanvas = page.locator('#mobileNav');
        await expect(offcanvas).toBeVisible();
        // "Hajj Services" is the collapsible group trigger (role="button" —
        // it toggles a collapse, it isn't real in-page navigation); expanding
        // it reveals the real "Hajj Packages" link underneath.
        await offcanvas.getByRole('button', { name: 'Hajj Services', exact: true }).click();
        await expect(offcanvas.getByRole('link', { name: 'Hajj Packages', exact: true })).toBeVisible();
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

        // Two separate assertions rather than one loose `getByText(/10,000/)`.
        //
        // That single locator used to match exactly one element only because
        // the stat counter server-rendered the literal string "0" and was
        // rewritten to the real figure by JavaScript after load — so the
        // pilgrims-served number was invisible to this assertion, and to
        // crawlers, and to anyone whose JS had not run. Now that the counter
        // renders "10,000+" server-side (see components/stat-number.blade.php)
        // the page legitimately contains two matches: the prose sentence and
        // the statistic. Asserting each one explicitly is both unambiguous and
        // a stronger check than the regex ever was.
        await expect(page.getByText('10,000 Hajis')).toBeVisible();
        await expect(page.locator('.stat-number', { hasText: '10,000+' })).toBeVisible();
    });

    // A keyboard user must be able to see where they are. Bootstrap gives its
    // own button variants a focus ring; the site's custom ones never set the
    // variable it reads, and the FAQ headers had the ring switched off to stop
    // it showing on mouse clicks. Both were invisible to keyboard users.
    test('keyboard focus is visible on the header buttons and the FAQ questions', async ({ page, isMobile, browserName }) => {
        // Desktop Chromium only, for two honest reasons: at phone width these
        // buttons live inside the closed offcanvas menu, and WebKit does not
        // move keyboard focus to links at all unless Safari's "Tab to links"
        // setting is on, so tabbing can never reach them there.
        test.skip(isMobile, 'The header buttons are inside the mobile menu at this width.');
        test.skip(browserName === 'webkit', 'WebKit does not tab to links by default.');

        // Tabbing, not el.focus(): only real keyboard focus triggers
        // :focus-visible, which is what these rules are written against.
        const focusRing = async (url, selector) => {
            await page.goto(url);
            // The mobile menu holds hidden copies of these buttons; test the one on screen.
            const target = page.locator(`${selector}:visible`).first();
            await expect(target).toBeVisible();
            const before = await target.evaluate((el) => getComputedStyle(el).outlineWidth);

            await target.evaluate((el) => el.setAttribute('data-focus-target', '1'));
            let reached = false;
            for (let i = 0; i < 60 && !reached; i += 1) {
                await page.keyboard.press('Tab');
                reached = await page.evaluate(() => document.activeElement?.hasAttribute('data-focus-target') ?? false);
            }

            const after = await target.evaluate((el) => {
                const s = getComputedStyle(el);
                return { outlineWidth: s.outlineWidth, outlineStyle: s.outlineStyle, outlineColor: s.outlineColor, boxShadow: s.boxShadow };
            });
            return { reached, before, after };
        };

        const visible = (ring) => {
            const width = parseFloat(ring.after.outlineWidth) || 0;
            const solid = ring.after.outlineStyle !== 'none' && !ring.after.outlineColor.includes('rgba(0, 0, 0, 0)');
            const shadowRing = /\d+px \d+px|0px 0px 0px [1-9]/.test(ring.after.boxShadow) && !ring.after.boxShadow.includes('rgba(0, 0, 0, 0) 0px 0px 0px 0px');
            return (width >= 2 && solid) || shadowRing;
        };

        const register = await focusRing('/', '.btn-register-now');
        expect(register.reached, 'never reached Register Now by tabbing').toBe(true);
        expect(visible(register), `Register Now shows no focus ring: ${JSON.stringify(register.after)}`).toBe(true);

        // The WhatsApp button only renders inside the mobile menu, so its rule
        // is checked in the stylesheet rather than by tabbing to it.
        const whatsappRule = await page.evaluate(() => [...document.styleSheets]
            .flatMap((sheet) => { try { return [...sheet.cssRules]; } catch { return []; } })
            .filter((r) => r.selectorText === '.btn-whatsapp:focus-visible')
            .map((r) => r.style.outline || r.style.boxShadow));
        expect(whatsappRule.join(' '), 'the WhatsApp button has no focus-visible rule').toMatch(/3px|0 0 0/);

        const faq = await focusRing('/faqs', '.faq-accordion .accordion-button');
        expect(faq.reached, 'never reached an FAQ question by tabbing').toBe(true);
        expect(visible(faq), `FAQ question shows no focus ring: ${JSON.stringify(faq.after)}`).toBe(true);
    });
});
