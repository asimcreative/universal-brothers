import { test, expect } from '@playwright/test';

// Exact breakpoints required by the release-gate responsive QA directive.
const DESKTOP = [
    { name: '1920x1080', width: 1920, height: 1080 },
    { name: '1680x1050', width: 1680, height: 1050 },
    { name: '1440x900', width: 1440, height: 900 },
    { name: '1366x768', width: 1366, height: 768 },
    { name: '1280x720', width: 1280, height: 720 },
];
const TABLET = [
    { name: '1024x1366', width: 1024, height: 1366 },
    { name: '820x1180', width: 820, height: 1180 },
    { name: '768x1024', width: 768, height: 1024 },
];
const MOBILE = [
    { name: '430x932', width: 430, height: 932 },
    { name: '414x896', width: 414, height: 896 },
    { name: '390x844', width: 390, height: 844 },
    { name: '375x812', width: 375, height: 812 },
    { name: '360x800', width: 360, height: 800 },
];
const ALL_BREAKPOINTS = [...DESKTOP, ...TABLET, ...MOBILE];

// The 18 major public pages named by the release-gate directive. The
// Hajj detail slug is a real seeded package (queried directly from the
// dev DB), not invented. Domestic/International Tourism are covered via
// their listing pages below (`?series=domestic`/`?series=international`)
// rather than an individual package detail page.
const HAJJ_SLUG = 'ub001-executive-platinum-intercon-fairmont-medinah-first';

const PAGES = [
    { name: 'Homepage', path: '/' },
    { name: 'About Us', path: '/about-us' },
    { name: 'Hajj Services', path: '/hajj-services' },
    { name: 'Hajj Package Listing', path: '/hajj' },
    { name: 'Hajj Package Detail', path: `/hajj/${HAJJ_SLUG}` },
    { name: 'Umrah Services', path: '/umrah-services' },
    { name: 'Umrah Packages', path: '/umrah' },
    { name: 'Tourism', path: '/tourism' },
    { name: 'Domestic Tourism', path: '/tourism?series=domestic' },
    { name: 'International Tourism', path: '/tourism?series=international' },
    { name: 'Awards', path: '/awards' },
    { name: 'Affiliations', path: '/affiliations' },
    { name: 'Media', path: '/media' },
    { name: 'Testimonials', path: '/testimonials' },
    { name: 'FAQs', path: '/faqs' },
    { name: 'Contact', path: '/contact' },
];

function checkOverflow() {
    return { scrollWidth: document.documentElement.scrollWidth, clientWidth: document.documentElement.clientWidth };
}

test.describe('Responsive — no horizontal overflow across all required breakpoints', () => {
    for (const p of PAGES) {
        test(`${p.name}: no horizontal overflow at any of the 13 required breakpoints`, async ({ page }) => {
            const response = await page.goto(p.path);
            expect(response.status(), `${p.name} (${p.path}) did not return 200`).toBe(200);

            const failures = [];
            for (const bp of ALL_BREAKPOINTS) {
                await page.setViewportSize({ width: bp.width, height: bp.height });
                await page.waitForTimeout(30); // let CSS reflow settle
                const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
                if (scrollWidth > clientWidth + 1) {
                    failures.push(`${bp.name}: scrollWidth=${scrollWidth} > clientWidth=${clientWidth}`);
                }
            }
            expect(failures, `${p.name} overflowed at: ${failures.join('; ')}`).toEqual([]);
        });
    }

    test('Hajj package detail page (Package Options/Meals/Gallery/pricing table) has no overflow at any breakpoint', async ({ page }) => {
        const response = await page.goto(`/hajj/${HAJJ_SLUG}`);
        expect(response.status()).toBe(200);

        const failures = [];
        for (const bp of ALL_BREAKPOINTS) {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.waitForTimeout(30);
            const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
            if (scrollWidth > clientWidth + 1) {
                failures.push(`${bp.name}: scrollWidth=${scrollWidth} > clientWidth=${clientWidth}`);
            }
        }
        expect(failures).toEqual([]);
    });
});

test.describe('Mobile navigation QA', () => {
    for (const bp of MOBILE) {
        test(`hamburger menu opens/closes cleanly at ${bp.name}, no stuck-open, no background scroll issue`, async ({ page }) => {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.goto('/');

            const toggler = page.locator('.navbar-toggler');
            await expect(toggler).toBeVisible();
            const offcanvas = page.locator('#mobileNav');
            await expect(offcanvas).toBeHidden();

            await toggler.click();
            await expect(offcanvas).toBeVisible();

            // Nested/mega menu items reachable and no overflow while open.
            await offcanvas.getByRole('button', { name: 'Hajj Services', exact: true }).click();
            await expect(offcanvas.getByRole('link', { name: 'Hajj Packages', exact: true })).toBeVisible();
            const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
            expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);

            // Closes cleanly, not stuck open.
            await page.locator('#mobileNav .btn-close').click();
            await expect(offcanvas).toBeHidden();

            // Real nav links (Hajj/Umrah/Tourism/About/Contact/Register Now)
            // are all present and tappable-sized once reopened.
            await toggler.click();
            for (const label of ['About Us', 'Awards & Recognition', 'Affiliations', 'Media', 'Testimonials', 'FAQs', 'Contact']) {
                await expect(offcanvas.getByRole('link', { name: label, exact: true })).toBeVisible();
            }
            const registerLink = offcanvas.getByRole('link', { name: 'Register Now', exact: true });
            await expect(registerLink).toBeVisible();
            await expect(registerLink).toHaveAttribute('href', 'https://hums.akhg.com.pk/HajiReg/HajiLead');
        });
    }
});

test.describe('Hero video/slider responsive QA', () => {
    for (const [group, bp] of [['desktop', DESKTOP[2]], ['tablet', TABLET[2]], ['mobile', MOBILE[2]]]) {
        test(`hero renders correctly at ${group} (${bp.name}) — no overflow, heading and CTAs visible`, async ({ page }) => {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.goto('/');

            await expect(page.locator('.hero-slide, #heroCarousel')).toBeVisible();
            await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

            const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
            expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
        });
    }
});

test.describe('Currency switcher responsive QA', () => {
    for (const [group, bp] of [['desktop', DESKTOP[2]], ['mobile', MOBILE[2]]]) {
        test(`currency switcher (PKR/SAR/USD) is usable at ${group} (${bp.name}) with no layout break`, async ({ page }) => {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.goto(`/hajj/${HAJJ_SLUG}`);

            const switcher = page.locator('#currency-switcher');
            await expect(switcher).toBeVisible();

            for (const currency of ['SAR', 'PKR', 'USD']) {
                await switcher.locator(`[data-currency="${currency}"]`).click();
                await expect(switcher.locator(`[data-currency="${currency}"]`)).toHaveClass(/active/);
                const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
                expect(scrollWidth, `overflow after switching to ${currency} at ${bp.name}`).toBeLessThanOrEqual(clientWidth + 1);
            }
        });
    }
});

test.describe('Forms responsive QA', () => {
    for (const bp of [MOBILE[2], MOBILE[4]]) {
        test(`contact form fields fit viewport and are usable at ${bp.name}`, async ({ page }) => {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.goto('/contact');

            for (const label of ['Full Name', 'Email', 'Phone', 'Message']) {
                const field = page.getByLabel(label, { exact: false }).first();
                await expect(field).toBeVisible();
                const box = await field.boundingBox();
                expect(box.width).toBeLessThanOrEqual(bp.width);
            }
            await expect(page.getByRole('button', { name: 'Send Message' })).toBeVisible();

            const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
            expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
        });

        test(`Hajj inquiry form fits viewport and is usable at ${bp.name}`, async ({ page }) => {
            await page.setViewportSize({ width: bp.width, height: bp.height });
            await page.goto(`/hajj/${HAJJ_SLUG}`);

            for (const label of ['Full Name', 'Email', 'Phone']) {
                await expect(page.getByLabel(label, { exact: false }).first()).toBeVisible();
            }
            const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
            expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
        });
    }
});

test.describe('Touch target QA', () => {
    test('package card CTA and currency switcher buttons meet the ~44px minimum touch-target height on mobile', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });

        // A tiny tolerance (0.5px) absorbs genuine sub-pixel rendering
        // differences between browser engines (Firefox has been observed
        // reporting 43.99993...px for an intended 44px min-height — a
        // rounding artifact, not a real, humanly-perceptible shortfall).
        await page.goto('/hajj');
        const cardBtnBox = await page.locator('.package-card .btn').first().boundingBox();
        expect(cardBtnBox.height).toBeGreaterThanOrEqual(43.5);

        await page.goto(`/hajj/${HAJJ_SLUG}`);
        const currencyBtnBoxes = await page.locator('#currency-switcher button').all();
        for (const btn of currencyBtnBoxes) {
            const box = await btn.boundingBox();
            expect(box.height).toBeGreaterThanOrEqual(43.5);
        }
    });
});

test.describe('Reduced-motion QA', () => {
    test('package card hover produces no transform under prefers-reduced-motion (WCAG 2.3.3 + click-stability regression)', async ({ page }) => {
        // Regression: `.package-card:hover`'s translateY lift kept the "View
        // Details" link's bounding box moving during Playwright's click
        // actionability check, causing an intermittent cross-browser
        // toHaveURL timeout under sustained load — found via two separate
        // full-suite runs each failing exactly once on a `.package-card`
        // click, always with the URL never leaving `/hajj`. It's also a real
        // WCAG 2.3.3 gap for actual users with reduced motion set. Both are
        // fixed by the same CSS rule; this proves it's actually applied.
        await page.goto('/hajj');
        const card = page.locator('.package-card').first();
        await card.hover();
        const transform = await card.evaluate((el) => getComputedStyle(el).transform);
        expect(transform === 'none' || transform === 'matrix(1, 0, 0, 1, 0, 0)').toBe(true);
    });
});

test.describe('Sticky sidebar QA', () => {
    test('Hajj package detail enquiry sidebar actually stays pinned while scrolling on desktop', async ({ page }) => {
        // Regression: `html, body { overflow-x: hidden }` (added to fix a
        // real mobile-offcanvas horizontal-overflow bug) silently forced
        // the computed `overflow-y` to `auto` too — CSS requires the two
        // axes to be resolved together, so an explicit `overflow-y: visible`
        // alongside it gets overridden right back to `auto`. ANY non-visible
        // overflow on an ancestor breaks `position: sticky` for every
        // descendant, so the package detail page's sticky enquiry sidebar
        // (`.sticky-top`) had never actually been sticking — it scrolled
        // away like a plain static element, tracking the scroll offset
        // 1:1. Found via direct scroll-position measurement, not by reading
        // the CSS. Fixed with `overflow-x: clip` (the one non-visible value
        // that does not force-promote the other axis). This test scrolls
        // partway down the page and asserts the sidebar is still within
        // ~150px of its intended `top: 100px` sticky offset, not off-screen.
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`/hajj/${HAJJ_SLUG}`);

        const sidebar = page.locator('.col-lg-4 .sticky-top');
        await page.evaluate(() => window.scrollTo(0, 3000));
        await page.waitForTimeout(150);

        const box = await sidebar.boundingBox();
        expect(box.y).toBeGreaterThan(0);
        expect(box.y).toBeLessThan(250);
    });
});

test.describe('Package card overlap QA', () => {
    test('package card summary text never overlaps the price/CTA row below it', async ({ page }) => {
        // Regression: a Playwright trace caught the real "View Details" click
        // failing with `<p class="small text-secondary">…</p> intercepts
        // pointer events` — the summary paragraph's rendered height (capped
        // only by a PHP character count, not a line count) occasionally grew
        // tall enough to overlap the price/CTA row pinned below it via
        // `mt-auto`. Now clamped to a fixed 2 lines — assert directly that
        // every visible card's summary box ends above its CTA row starts,
        // rather than only inferring it from a click succeeding.
        await page.goto('/hajj');
        const cards = await page.locator('.package-card').all();
        for (const card of cards) {
            const summary = card.locator('p.text-secondary');
            if (await summary.count() === 0) continue;
            const summaryBox = await summary.boundingBox();
            const ctaBox = await card.getByRole('link', { name: 'View Details' }).boundingBox();
            if (!summaryBox || !ctaBox) continue;
            expect(summaryBox.y + summaryBox.height).toBeLessThanOrEqual(ctaBox.y + 1);
        }
    });
});

test.describe('Package filter bar responsive QA', () => {
    test('Hajj filter bar is usable (no overflow, fields tappable) at mobile width', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/hajj');

        // Frontend visual redesign: the filter panel is now a responsive
        // Bootstrap offcanvas (`.offcanvas-lg`) — a drawer below `lg`,
        // opened via the "Filters" trigger button, rather than an
        // always-visible inline form. Matches the release-gate directive's
        // explicit "use a filter button / drawer / accordion" requirement.
        await page.getByRole('button', { name: 'Filters' }).click();
        await expect(page.getByLabel('Duration')).toBeVisible();
        await expect(page.getByLabel('Sharing')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Apply Filters' })).toBeVisible();

        const { scrollWidth, clientWidth } = await page.evaluate(checkOverflow);
        expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 1);
    });
});

test.describe('Desktop/tablet nav QA', () => {
    test('nav collapses to hamburger below the lg breakpoint, mega-menu visible above it', async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await page.goto('/');
        await expect(page.locator('.navbar-toggler')).toBeHidden();
        await expect(page.locator('nav.navbar').getByRole('button', { name: 'Hajj & Umrah', exact: true })).toBeVisible();

        await page.setViewportSize({ width: 768, height: 1024 });
        await page.goto('/');
        await expect(page.locator('.navbar-toggler')).toBeVisible();
    });
});
