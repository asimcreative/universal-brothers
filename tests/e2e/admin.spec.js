import { test, expect } from '@playwright/test';
import { clickAndConfirm } from './helpers/confirm.js';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

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

    test('admin panel is navigable on a mobile viewport (regression: sidebar had no mobile fallback)', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin');

        // The desktop sidebar is intentionally hidden below md — there must
        // be a working alternative, not a silent dead-end. The admin UI
        // redesign moved the mobile toggle into the shared topbar (no
        // longer a separate `nav.navbar.d-md-none` bar), so it's matched by
        // its accessible name instead of the old CSS-class selector.
        const mobileToggle = page.getByRole('button', { name: 'Open menu' });
        await expect(mobileToggle).toBeVisible();
        await mobileToggle.click();

        const mobileNav = page.locator('#adminMobileNav');
        await expect(mobileNav).toBeVisible();
        // Matched by href, not link text: "Packages" text-matching would
        // ambiguously match the new "Hajj Packages" nav entry too (both
        // literally contain the substring "Packages"), and the accessible
        // name computed from the icon+label markup carries incidental
        // whitespace that makes an exact text match unreliable.
        await mobileNav.locator('a[href$="/admin/packages"]').click();
        await expect(page).toHaveURL(/\/admin\/packages$/);
    });

    test('17-19. admin can create, edit, and publish/unpublish a package, verified on the frontend', async ({ page }) => {
        const uniqueCode = 'E2E' + Date.now();
        const uniqueName = 'Playwright E2E Test Package ' + Date.now();

        // Regression (2026-08-30): an earlier version of this test deleted
        // the package as its own final step, so any assertion failure
        // *before* that point (this exact test hit one — a strict-mode
        // selector ambiguity, since fixed) left a real, published package
        // permanently orphaned in the live database, visible on the actual
        // public Hajj listing. Two such orphans were found via a visual
        // screenshot review of that same listing page days later — not by
        // reading code or by any automated test, since nothing was asserting
        // "the catalog contains only real packages". Wrapping creation
        // through verification in try/finally guarantees the delete step
        // (in `finally`) always runs, even if an assertion above throws.
        try {
            // 17. Create as draft
            // Regression, admin panel redesign (2026-08-31): this used to
            // select "Hajj" here — through the *generic* package form, the
            // exact CRITICAL data-loss path closed by PackageController's
            // new Hajj-category guard (see ADMIN_UI_DESIGN.md). "Hajj" is no
            // longer an option in this dropdown at all, so this now uses
            // "Umrah" — a real category this generic controller legitimately
            // owns — with the public-URL checks below updated to match.
            await page.goto('/admin/packages/create');
            await page.locator('select[name="package_category_id"]').selectOption({ label: 'Umrah' });
            await page.locator('input[name="code"]').fill(uniqueCode);
            await page.locator('input[name="name"]').fill(uniqueName);
            await page.locator('select[name="status"]').selectOption('draft');
            await page.getByRole('button', { name: 'Create Package' }).click();
            await expect(page).toHaveURL(/\/admin\/packages$/);
            await expect(page.getByText(uniqueName)).toBeVisible();

            // 20 (part 1). Draft package must NOT appear on the public frontend yet
            const slug = uniqueName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            const draftResponse = await page.goto('/umrah/' + slug);
            expect(draftResponse.status()).toBe(404);

            // 18/19. Edit and publish
            await page.goto('/admin/packages');
            await page.getByRole('row', { name: new RegExp(uniqueName) }).getByRole('link', { name: 'Edit' }).click();
            await page.locator('input[name="duration_label"]').fill('7 Days Package (E2E)');
            await page.locator('select[name="status"]').selectOption('published');
            await page.getByRole('button', { name: 'Update Package' }).click();
            await expect(page).toHaveURL(/\/admin\/packages$/);

            // 20 (part 2). Published package now appears on the public frontend
            const liveResponse = await page.goto('/umrah/' + slug);
            expect(liveResponse.status()).toBe(200);
            await expect(page.getByRole('heading', { name: uniqueName })).toBeVisible();
            // Frontend visual redesign: duration now legitimately appears twice
            // (the hero badge row and the new quick-overview "at a glance"
            // strip) — both intentional, so assert presence via `.first()`
            // rather than requiring page-wide uniqueness.
            await expect(page.getByText('7 Days Package (E2E)').first()).toBeVisible();
        } finally {
            // Cleanup: delete the test package so repeated runs — and a
            // failed run — never accumulate real, live, published data.
            await page.goto('/admin/packages');
            const row = page.getByRole('row', { name: new RegExp(uniqueName) });
            if (await row.count()) {
                await clickAndConfirm(page, row.getByRole('button', { name: 'Delete' }));
                await expect(page.getByText(uniqueName)).not.toBeVisible();
            }
        }
    });

    test('21. admin can edit a testimonial', async ({ page }) => {
        // Haseeb Jawed is a real testimonial recovered from the live site
        // (see TestimonialSeeder), not test fixture data — capture and
        // restore its real quote rather than permanently overwriting it.
        // (A previous version of this test didn't restore it, and because
        // TestimonialSeeder's updateOrCreate matches on name+quote together,
        // re-seeding after the fact created a duplicate row instead of
        // healing the corrupted one — both the test and that seeder match
        // key are worth remembering as a pair.)
        await page.goto('/admin/testimonials');
        await page.getByRole('row', { name: /Haseeb Jawed/ }).getByRole('link', { name: 'Edit' }).click();
        const originalQuote = await page.locator('textarea[name="quote"]').inputValue();

        await page.locator('textarea[name="quote"]').fill('Updated via Playwright E2E test — real testimonial content preserved otherwise.');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/testimonials$/);
        await expect(page.getByText(/Updated via Playwright/)).toBeVisible();

        // Cleanup: restore the real quote.
        await page.goto('/admin/testimonials');
        await page.getByRole('row', { name: /Haseeb Jawed/ }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('textarea[name="quote"]').fill(originalQuote);
        await page.getByRole('button', { name: 'Save' }).click();
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

        await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(title) }).getByRole('button', { name: 'Delete' }));
        await expect(page.getByText(title + ' Updated')).not.toBeVisible();
    });

    test('22. admin can manage a FAQ', async ({ page }) => {
        const question = 'Playwright E2E test question ' + Date.now() + '?';

        await page.goto('/admin/faqs/create');
        await page.locator('select[name="category"]').selectOption('hajj');
        await page.locator('input[name="question"]').fill(question);
        // The answer is formatted text: typed into the visual editor, which
        // keeps the form's hidden textarea in step.
        const answer = page.locator('.rt-field', { has: page.locator('textarea[name="answer"]') }).locator('.rt-editable');
        await answer.click();
        await page.keyboard.type('Playwright E2E test answer.');
        await expect(page.locator('textarea[name="answer"]')).toHaveValue('<p>Playwright E2E test answer.</p>');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/faqs$/);
        await expect(page.getByText(question)).toBeVisible();

        // Cleanup: FAQ test data has no natural expiry (unlike an inquiry,
        // which real admins never delete through the UI), so remove it
        // rather than let repeated suite runs accumulate duplicate rows.
        await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(question.replace('?', '\\?')) }).getByRole('button', { name: 'Delete' }));
        await expect(page.getByText(question)).not.toBeVisible();
    });

    test('23. admin can view and update an inquiry status', async ({ page }) => {
        // Create a real inquiry via the public contact form first — uniquely
        // named so the test targets its own row even amid other inquiries.
        // Regression: this test previously had no way to remove the row it
        // created (the admin inquiry-detail page had no delete action at
        // all) — after this whole engagement's many Playwright runs, that
        // left 185 of 260 real `inquiries` rows as permanent test residue,
        // found via an admin dashboard screenshot showing "234 New
        // Inquiries" real staff would see. A delete action now exists
        // (admin/inquiries/show.blade.php), so this cleans up in `finally`.
        const name = 'E2E Inquiry Source ' + Date.now();

        try {
            await page.goto('/contact');
            await page.getByLabel('Full Name').fill(name);
            await page.getByLabel('Email').fill('e2e-inquiry@example.com');
            await page.getByLabel('Phone').fill('+923001112222');
            await page.getByLabel('Message').fill('Testing admin inquiry management.');
            await page.getByRole('button', { name: 'Send Message' }).click();

            await page.goto('/admin/inquiries');
            await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'View' }).click();
            await page.locator('select[name="status"]').selectOption('contacted');
            await page.getByRole('button', { name: 'Update Status' }).click();
            await expect(page.locator('select[name="status"]')).toHaveValue('contacted');
        } finally {
            await clickAndConfirm(page, page.getByRole('button', { name: 'Delete Inquiry' }));
        }
    });

    test('24. admin can manage the About Us page', async ({ page }) => {
        // Pages are built from sections (issue #13): editing opens the builder,
        // and saving a draft never changes the live page.
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();
        await expect(page.locator('#builder-sections > [data-section]').first()).toBeVisible();
        await page.getByRole('tab', { name: /Title & address/ }).click();
        await expect(page.locator('input[name="slug"]')).toHaveValue('about-us');
        await page.getByRole('button', { name: 'Save draft' }).click();
        await expect(page.getByText('Draft saved.')).toBeVisible();

        const response = await page.goto('/about-us');
        expect(response.status()).toBe(200);

        // Leave the page with no pending draft for the other tests.
        await page.goBack();
        await page.goto('/admin/pages');
        await page.getByRole('row', { name: /About Us/ }).getByRole('link', { name: 'Edit' }).click();
        const discard = page.getByRole('button', { name: 'Discard unpublished changes' });
        if (await discard.count()) {
            await page.getByRole('button', { name: 'More', exact: true }).click();
            await clickAndConfirm(page, discard);
        }
    });

    test('25. package SEO fields are saved and rendered in the public page head', async ({ page }) => {
        // UB001 is one of the 12 real, brochure-sourced Hajj packages, not
        // test fixture data — a previous version of this test overwrote its
        // real seeded meta_title/meta_description with throwaway test text
        // and never restored them. Capture the originals and restore them
        // afterward instead of permanently corrupting real seed data.
        //
        // Regression, admin panel redesign (2026-08-31): this used to reach
        // UB001 via the *generic* `/admin/packages` listing, which no longer
        // lists Hajj packages at all (see ADMIN_UI_DESIGN.md's critical
        // fix) — it now goes through the real Hajj admin route instead.
        //
        // Package builder redesign (issue #10): SEO lives in the "Photos &
        // search engines" step, and the builder returns to the package after
        // saving instead of the list.
        await page.goto('/admin/hajj-packages');
        await page.getByRole('row', { name: /UB001/ }).getByRole('link', { name: 'Edit' }).click();
        await page.getByRole('button', { name: /Photos & search engines/ }).click();
        const originalMetaTitle = await page.locator('input[name="meta_title"]').inputValue();
        const originalMetaDescription = await page.locator('textarea[name="meta_description"]').inputValue();

        try {
            await page.locator('input[name="meta_title"]').fill('Playwright SEO Title Test | Universal Brothers');
            await page.locator('textarea[name="meta_description"]').fill('Playwright SEO description test.');
            await page.getByRole('button', { name: 'Save changes' }).click();
            await expect(page).toHaveURL(/\/admin\/hajj-packages\/\d+\/edit/);
            await expect(page.getByRole('status').filter({ hasText: 'Changes saved' })).toBeVisible();

            await page.goto('/hajj');
            await page.getByText('UB001').first().locator('xpath=ancestor::div[contains(@class,"package-card")]').getByRole('link', { name: 'View Details' }).click();
            await expect(page).toHaveTitle('Playwright SEO Title Test | Universal Brothers');
            await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', 'Playwright SEO description test.');
        } finally {
            // Cleanup: restore the real seeded values.
            await page.goto('/admin/hajj-packages');
            await page.getByRole('row', { name: /UB001/ }).getByRole('link', { name: 'Edit' }).click();
            await page.getByRole('button', { name: /Photos & search engines/ }).click();
            await page.locator('input[name="meta_title"]').fill(originalMetaTitle);
            await page.locator('textarea[name="meta_description"]').fill(originalMetaDescription);
            await page.getByRole('button', { name: 'Save changes' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Changes saved' })).toBeVisible();
        }
    });

    test('26. admin can create, edit, and delete a Media Gallery item (image upload)', async ({ page }) => {
        const title = 'Playwright E2E Media ' + Date.now();

        await page.goto('/admin/media/create');
        await page.locator('#media-type').selectOption('image');
        await page.locator('#media-gallery-type').selectOption('gallery');
        await page.locator('#media-title').fill(title);
        await page.locator('#media-file').setInputFiles(path.join(__dirname, 'fixtures', 'test-image.jpg'));
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/media$/);
        await expect(page.getByText(title)).toBeVisible();

        await page.getByRole('row', { name: new RegExp(title) }).getByRole('link', { name: 'Edit' }).click();
        await expect(page.locator('#media-title')).toHaveValue(title);
        await page.locator('#media-title').fill(title + ' Updated');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/media$/);
        await expect(page.getByText(title + ' Updated')).toBeVisible();

        await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(title) }).getByRole('button', { name: 'Delete' }));
        await expect(page.getByText(title + ' Updated')).not.toBeVisible();
    });

    test('28. admin can edit a real Hajj package and add a new room type to one hotel option', async ({ page }) => {
        await page.goto('/admin/hajj-packages');
        await page.getByRole('row', { name: /UB001/ }).getByRole('link', { name: 'Edit' }).click();

        // Real seeded data populates the builder: the options step holds
        // Option A and B, and the Madinah hotel shared by both options sits in
        // the "for every option" box.
        await page.getByRole('button', { name: /Hotel options/ }).click();
        await expect(page.locator('input[name="variants[0][code]"]')).toHaveValue('A');
        await page.getByRole('button', { name: /Hotels & accommodation/ }).click();
        await expect(page.locator('[data-option-group="accommodations"][data-option-uid="shared"] [data-field="hotel_name"]').first()).toHaveValue('Dar Al Taqwa');

        await page.getByRole('button', { name: /Room prices/ }).click();
        const optionA = page.locator('[data-option-group="room_options"][data-option-code="A"]');
        await expect(optionA.locator('[data-option-title]')).toContainText('Option A');
        const before = await optionA.locator('[data-row="room_options"]').count();

        await optionA.getByRole('button', { name: 'Add room type' }).click();
        const newRow = optionA.locator('[data-row="room_options"]').last();
        await expect(optionA.locator('[data-row="room_options"]')).toHaveCount(before + 1);
        await newRow.locator('[data-room-type]').selectOption('custom');
        await newRow.locator('[data-field="display_label"]').fill('Quint Sharing');
        await newRow.locator('[data-field="price_usd"]').fill('9999');

        try {
            await page.getByRole('button', { name: 'Save changes' }).click();
            await expect(page).toHaveURL(/\/admin\/hajj-packages\/\d+\/edit/);
            await expect(page.getByRole('status').filter({ hasText: 'Changes saved' })).toBeVisible();

            const response = await page.goto('/hajj/ub001-executive-platinum-intercon-fairmont-medinah-first');
            expect(response.status()).toBe(200);
            await expect(page.getByText('Quint Sharing').first()).toBeVisible();
            await expect(page.locator('.currency-price[data-usd="9999.00"]').first()).toBeVisible();
        } finally {
            // Cleanup: restore the real seeded state so repeated suite runs
            // don't accumulate a fake "Quint Sharing" row on real brochure data.
            await page.goto('/admin/hajj-packages');
            await page.getByRole('row', { name: /UB001/ }).getByRole('link', { name: 'Edit' }).click();
            await page.getByRole('button', { name: /Room prices/ }).click();
            const quint = page.locator('[data-row="room_options"]').filter({ has: page.locator('[data-field="display_label"][value="Quint Sharing"]') });
            if (await quint.count()) {
                await quint.first().locator('[data-row-action="remove"]').click();
                await page.getByRole('button', { name: 'Save changes' }).click();
                await expect(page.getByRole('status').filter({ hasText: 'Changes saved' })).toBeVisible();
            }
        }
    });

    test('27. super admin can create, edit, and delete another admin user (Users & Roles module)', async ({ page }) => {
        const email = `e2e-editor-${Date.now()}@universalbrothers.test`;

        await page.goto('/admin/users/create');
        await page.locator('#user-name').fill('Playwright E2E Editor');
        await page.locator('#user-email').fill(email);
        await page.locator('#user-role').selectOption('content_editor');
        await page.locator('#user-password').fill('password123');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/users$/);
        await expect(page.getByText(email)).toBeVisible();

        await page.getByRole('row', { name: new RegExp(email) }).getByRole('link', { name: 'Edit' }).click();
        await page.locator('#user-role').selectOption('super_admin');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(/\/admin\/users$/);
        await expect(page.getByRole('row', { name: new RegExp(email) })).toContainText('super admin');

        await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(email) }).getByRole('button', { name: 'Delete' }));
        await expect(page.getByText(email)).not.toBeVisible();
    });

    test('admin can create, edit, and delete an Award (Awards module)', async ({ page }) => {
        const name = 'Playwright E2E Test Award ' + Date.now();

        try {
            await page.goto('/admin/awards/create');
            await page.locator('#award-name').fill(name);
            await page.locator('#award-organization').fill('E2E Testing Organization');
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page).toHaveURL(/\/admin\/awards$/);
            await expect(page.getByText(name)).toBeVisible();

            await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Edit' }).click();
            await page.locator('#award-year').fill('2026');
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page.getByRole('row', { name: new RegExp(name) })).toContainText('2026');
        } finally {
            await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(name) }).getByRole('button', { name: 'Delete' }));
            await expect(page.getByText(name)).not.toBeVisible();
        }
    });

    test('admin can create, edit, and delete an Affiliation (Affiliations module)', async ({ page }) => {
        const name = 'Playwright E2E Test Affiliation ' + Date.now();

        try {
            await page.goto('/admin/affiliations/create');
            await page.locator('#affiliation-organization-name').fill(name);
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page).toHaveURL(/\/admin\/affiliations$/);
            await expect(page.getByText(name)).toBeVisible();

            await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Edit' }).click();
            await page.locator('#affiliation-year').fill('2026');
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page.getByRole('row', { name: new RegExp(name) })).toContainText('2026');
        } finally {
            await clickAndConfirm(page, page.getByRole('row', { name: new RegExp(name) }).getByRole('button', { name: 'Delete' }));
            await expect(page.getByText(name)).not.toBeVisible();
        }
    });
});
