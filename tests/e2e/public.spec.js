import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import { chooseCurrency } from './helpers/currency.js';

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

    test('2. header navigation reaches every top-level destination', async ({ page, isMobile }) => {
        // On mobile the header collapses to the drawer — its links live there
        // instead, covered by test 13. Nothing to check here for that viewport.
        test.skip(isMobile, 'The header links are inside the drawer at this width — see test 13.');

        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/');

        // One header serves the whole site. Its ten top-level items are
        // matched inside the top-level list, because several of the same
        // labels also appear as deep links inside the mega panel.
        const nav = page.locator('nav.ub-primary-nav');
        for (const label of ['Home', 'About Us', 'Hajj & Umrah', 'Tourism', 'Awards & Recognition',
            'Affiliations', 'Media', 'Testimonials', 'FAQs', 'Contact']) {
            await expect(nav.locator('> ul > li > a').filter({ hasText: new RegExp(`^\\s*${label}\\s*$`) })).toBeVisible();
        }

        // And the mega panel the inner pages have always had is here too: it
        // stays shut until the item is hovered, then offers the deep links.
        const hajjItem = nav.locator('.ub-nav-item').filter({ hasText: 'Hajj & Umrah' });
        const panel = hajjItem.locator('.ub-nav-panel');
        await expect(panel).toBeHidden();
        await hajjItem.hover();
        await expect(panel).toBeVisible();
        await expect(panel.getByRole('link', { name: 'How to Apply', exact: true }).first()).toBeVisible();

        // The Hajj and Umrah listings are still reachable from the homepage —
        // from the packages section and the footer rather than from a menu.
        await expect(page.locator('#featured-packages').getByRole('link', { name: /View all Hajj Packages/i })).toBeVisible();
        await expect(page.locator('footer').getByRole('link', { name: 'Umrah Packages', exact: true })).toBeVisible();

        // And the nav actually navigates.
        await nav.getByRole('link', { name: 'Contact', exact: true }).click();
        await expect(page).toHaveURL(/\/contact$/);
    });

    test('3. hero renders (fallback hero when no slider is configured)', async ({ page }) => {
        await page.goto('/');
        // `.hero-slide` belonged to the Bootstrap layout; the template's hero
        // carries `data-ub-hero`, which is also what its carousel script binds.
        await expect(page.locator('[data-ub-hero]')).toBeVisible();
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });

    test('3b. homepage packages section: one tab per category, switching shows that category only', async ({ page }) => {
        // Hajj and Umrah used to be two separate bands of identical shape. They
        // are one tabbed section now, so the thing worth guarding is that the
        // tabs actually switch — a pane that never shows is a category of
        // packages no visitor can reach from the homepage.
        await page.goto('/');

        const tabs = page.locator('.pt-tab');
        const tabCount = await tabs.count();
        test.skip(tabCount < 2, 'only one category has published packages in this database');

        // The first pane is open on load, the others are not.
        const firstId = await tabs.first().getAttribute('data-bs-target');
        const secondId = await tabs.nth(1).getAttribute('data-bs-target');
        await expect(page.locator(firstId)).toBeVisible();
        await expect(page.locator(secondId)).toBeHidden();

        await tabs.nth(1).click();

        await expect(page.locator(secondId)).toBeVisible();
        await expect(page.locator(firstId)).toBeHidden();
        await expect(page.locator(`${secondId} .package-card`).first()).toBeVisible();
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

    test('6b. the currency chosen in the header decides every price, on every page', async ({ page }) => {
        await page.goto('/hajj');
        await Promise.all([
            page.waitForURL(/\/hajj\/ub001-/),
            page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click(),
        ]);

        // UB001 Package B Quad, which the brochures price in all three
        // currencies. The page opens in rupees because that is what the
        // currency gate was answered with (see auth.setup.js) — the choice
        // belongs to the site, not to this page, which is the whole change:
        // it used to be a per-page switcher that rewrote the numbers in
        // JavaScript, and it is now a server-side choice that also decides
        // which packages are listed at all.
        const priceCell = page.locator('.currency-price[data-usd="16300.00"]').first();
        await expect(priceCell).toHaveText('PKR 4,640,000');

        await chooseCurrency(page, 'SAR');
        await expect(priceCell).toHaveText('SAR 59,500');

        await chooseCurrency(page, 'USD');
        await expect(priceCell).toHaveText('US$16,300');

        // Nothing else on the page moved.
        await expect(page.getByRole('heading', { name: 'Day-by-Day Itinerary' })).toBeVisible();
        await expect(page.getByText('Dar Al Tawhid Intercontinental').first()).toBeVisible();

        // Every room that is rendered carries a real price in the chosen
        // currency. A room the brochure prints "NA" against (UB001 Package A
        // Quad) is not rendered at all, rather than rendered blank.
        await expect(page.locator('.currency-price[data-usd=""]')).toHaveCount(0);

        // And it is still dollars on the next page: the answer is remembered
        // for the site rather than asked again.
        await page.goto('/hajj');
        await expect(page.locator('.package-card').first()).toContainText('US$');
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

    test('13. mobile navigation opens via the drawer toggle', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');

        const toggle = page.locator('[data-ub-menu-open]');
        const drawer = page.locator('#ub-mobile-nav');

        await expect(drawer).toBeHidden();
        await expect(toggle).toHaveAttribute('aria-expanded', 'false');

        await toggle.click();
        await expect(drawer).toBeVisible();
        await expect(toggle).toHaveAttribute('aria-expanded', 'true');

        // Items that own a panel become a group the reader expands; the rest
        // stay plain links. Both kinds must be reachable.
        const group = drawer.locator('.ub-drawer-group').filter({ hasText: 'Hajj & Umrah' });
        await expect(group).toBeVisible();
        await expect(group.getByRole('link', { name: 'How to Apply' }).first()).toBeHidden();
        await group.locator('summary').click();
        await expect(group.getByRole('link', { name: 'How to Apply' }).first()).toBeVisible();

        await expect(drawer.getByRole('link', { name: 'Contact', exact: true })).toBeVisible();
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
        test.skip(isMobile, 'The header buttons are inside the drawer at this width.');
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

        // A focus ring is either a real outline, or a box-shadow drawn as a ring.
        //
        // The shadow half used to be a regex over the whole `box-shadow` string,
        // which no longer works: the template draws its ring with Tailwind, and
        // Tailwind always emits several zero-size fully transparent layers
        // alongside the real one, so "does the string contain a transparent
        // layer" was answering yes for a button whose ring was plainly visible.
        //
        // This looks at each layer instead, and is stricter than what it
        // replaces: a layer only counts as a ring if it has a visible colour AND
        // no offset AND no blur AND at least 2px of spread. A drop shadow —
        // which has an offset — can no longer be mistaken for a focus ring.
        const isRingLayer = (layer) => {
            const transparent = /rgba?\([^)]*,\s*0\s*\)/.test(layer) || /\/\s*0\s*\)/.test(layer);
            if (transparent) return false;
            const lengths = (layer.match(/-?\d*\.?\d+px/g) || []).map(parseFloat);
            if (lengths.length < 4) return false;
            const [offsetX, offsetY, blur, spread] = lengths;
            return offsetX === 0 && offsetY === 0 && blur === 0 && Math.abs(spread) >= 2;
        };

        const visible = (ring) => {
            const width = parseFloat(ring.after.outlineWidth) || 0;
            const solid = ring.after.outlineStyle !== 'none' && !ring.after.outlineColor.includes('rgba(0, 0, 0, 0)');
            if (width >= 2 && solid) return true;
            // Split on commas that are not inside a colour function.
            return ring.after.boxShadow.split(/,(?![^(]*\))/).some(isRingLayer);
        };

        // `[data-ub-register]` rather than a class: the template styles this
        // button entirely with utilities, and a test should not break the day
        // one of them changes.
        const register = await focusRing('/', '[data-ub-register]');
        expect(register.reached, 'never reached Register Now by tabbing').toBe(true);
        expect(visible(register), `Register Now shows no focus ring: ${JSON.stringify(register.after)}`).toBe(true);

        const faq = await focusRing('/faqs', '.faq-accordion .accordion-button');
        expect(faq.reached, 'never reached an FAQ question by tabbing').toBe(true);
        expect(visible(faq), `FAQ question shows no focus ring: ${JSON.stringify(faq.after)}`).toBe(true);

        // The WhatsApp button only renders inside the mobile menu, so its rule
        // is checked in the stylesheet rather than by tabbing to it. It is read
        // here, on /faqs, because that rule lives in `app.scss` and the
        // homepage's template layout deliberately does not load it — the
        // template's own drawer gets its ring from a focus-visible utility.
        const whatsappRule = await page.evaluate(() => [...document.styleSheets]
            .flatMap((sheet) => { try { return [...sheet.cssRules]; } catch { return []; } })
            .filter((r) => r.selectorText === '.btn-whatsapp:focus-visible')
            .map((r) => r.style.outline || r.style.boxShadow));
        expect(whatsappRule.join(' '), 'the WhatsApp button has no focus-visible rule').toMatch(/3px|0 0 0/);
    });
});

/**
 * The currency gate, from the one state every other test deliberately avoids:
 * a visitor who has never answered it.
 *
 * `storageState` is emptied here on purpose. Every other public test starts
 * with the answer already in a cookie, because the dialog is modal and its
 * backdrop takes the pointer events — correctly, for a real visitor, but it
 * would otherwise be in front of every click in the suite.
 */
test.describe('Currency gate', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('asks once, then never again, and decides what prices the site quotes', async ({ page }) => {
        await page.goto('/');

        const gate = page.locator('[data-ub-currency-gate]');
        await expect(gate).toBeVisible();

        // Three real choices and no way past without making one. A dismiss
        // would leave the question hanging and re-ask on the next page.
        await expect(gate.locator('button[name="currency"]')).toHaveCount(3);

        await gate.locator('button[value="SAR"]').click();
        await expect(page.locator('[data-ub-currency-gate]')).toHaveCount(0);

        // The answer decides the prices...
        await page.goto('/hajj');
        await expect(page.locator('.package-card').first()).toContainText('SAR');

        // ...and it is not asked again, on this page or any other.
        await page.goto('/');
        await expect(page.locator('[data-ub-currency-gate]')).toHaveCount(0);
    });

    test('is remembered past the end of the session, not just within it', async ({ page, context }) => {
        await page.goto('/');
        await page.locator('[data-ub-currency-gate] button[value="USD"]').click();
        await expect(page.locator('[data-ub-currency-gate]')).toHaveCount(0);

        // Everything the session was carried in, thrown away. What is left is
        // the year-long preference cookie, which is the point: a visitor who
        // comes back next week is not interrogated again.
        const kept = (await context.cookies()).filter((c) => c.name === 'ub_currency');
        await context.clearCookies();
        await context.addCookies(kept);

        await page.goto('/hajj');
        await expect(page.locator('[data-ub-currency-gate]')).toHaveCount(0);
        await expect(page.locator('.package-card').first()).toContainText('US$');
    });
});
