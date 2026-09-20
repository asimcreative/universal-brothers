import { test, expect } from '@playwright/test';
import { clickAndConfirm } from './helpers/confirm.js';

// Runs under 'admin-chromium' (pre-authenticated) against database/testing.sqlite.
//
// The step-by-step Hajj package builder (issue #10), driven the way an
// administrator uses it: steps, hotel options, saved hotels and services,
// the journey plan tools, publishing rules, duplication, the browser copy of
// unsaved work, and the phone layout.

// The full step list (on phones it opens from the compact "Step 4 of 14" line).
const stepButton = (page, name) => page.locator('#builder-step-list').getByRole('button', { name });

async function openStep(page, name) {
    await stepButton(page, name).click();
    await expect(stepButton(page, name)).toHaveAttribute('aria-current', 'step');
}

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/** Removes a test package through the listing: unpublish first if live, then delete. */
async function deletePackageByName(page, name) {
    const rowFor = () => page.getByRole('row', { name: new RegExp(escapeRegExp(name)) }).first();

    await page.goto('/admin/hajj-packages?q=' + encodeURIComponent(name));
    if (!(await rowFor().count())) return;

    await rowFor().getByRole('button', { name: /More actions/ }).click();
    const unpublish = rowFor().getByRole('button', { name: 'Unpublish' });
    if (await unpublish.isVisible()) {
        await clickAndConfirm(page, unpublish);
        await page.goto('/admin/hajj-packages?q=' + encodeURIComponent(name));
        await rowFor().getByRole('button', { name: /More actions/ }).click();
    }
    await clickAndConfirm(page, rowFor().getByRole('button', { name: 'Delete' }));
    await expect(page.getByRole('status').filter({ hasText: 'Deleted' })).toBeVisible();
}

test.describe('Hajj package builder', () => {
    test('an admin builds a package with two hotel options, saves a draft, publishes it and sees it on the website', async ({ page }) => {
        // This journey walks the whole fourteen-step wizard, publishes, and
        // then checks the package on the public site — thirty-odd navigations.
        // It also waits up to 30s on its own for the publish confirmation at
        // the end, which is the entire default test budget, so on any machine
        // where publishing is not instant the test could never finish. The
        // assertions are unchanged; only the wall-clock allowance is.
        test.setTimeout(150_000);

        const stamp = Date.now();
        const title = `E2E Builder Package ${stamp}`;
        const code = `E2E${String(stamp).slice(-6)}`;

        try {
            await page.goto('/admin/hajj-packages/create');
            await expect(page.getByRole('heading', { name: 'Add Hajj Package' })).toBeVisible();

            // Step 1 — basics
            await page.getByLabel('Package title').fill(title);
            await page.getByLabel('Package code').fill(code);
            await page.getByLabel('Number of days').fill('14');
            await expect(page.getByLabel('Length as shown')).toHaveValue('14 Days Package');
            await expect(stepButton(page, /Basic information/)).toHaveClass(/is-done/);

            // Step 2 — setup
            await page.getByRole('button', { name: /Next: Package setup/ }).click();
            await page.getByText('Arrive in Madinah', { exact: true }).click();
            await page.getByText('Optional upgrade', { exact: true }).click();

            // Step 3 — options: choosing "Yes" adds Option A and B
            await openStep(page, /Hotel options/);
            await page.getByText('Yes — customers choose an option').click();
            await expect(page.locator('[data-row="variants"]')).toHaveCount(2);
            await page.locator('input[name$="[label]"]').nth(0).fill('Swissotel');
            await page.locator('input[name$="[label]"]').nth(1).fill('Fairmont');

            // Step 4 — prices: each option has its own box
            await openStep(page, /Room prices/);
            const optionA = page.locator('[data-option-group="room_options"][data-option-code="A"]');
            const optionB = page.locator('[data-option-group="room_options"][data-option-code="B"]');
            await expect(optionA).toContainText('Option A — Swissotel');
            await expect(optionB).toContainText('Option B — Fairmont');
            await optionA.getByRole('button', { name: /Add Quad, Triple/ }).click();
            await expect(optionA.locator('[data-row="room_options"]')).toHaveCount(3);
            await optionA.locator('[data-row="room_options"]').first().locator('[data-field="price_usd"]').fill('12345');
            await optionB.getByRole('button', { name: 'Add room type' }).click();
            await optionB.locator('[data-room-type]').selectOption('quad');
            await optionB.locator('[data-field="price_usd"]').fill('15432');
            await expect(stepButton(page, /Room prices/)).toHaveClass(/is-done/);

            // Step 5 — hotels, picked from the saved list
            await openStep(page, /Hotels & accommodation/);
            const shared = page.locator('[data-option-group="accommodations"][data-option-uid="shared"]');
            await shared.getByRole('button', { name: 'Add hotel' }).click();
            const madinah = shared.locator('[data-row="accommodations"]').last();
            await madinah.locator('[data-field="location"]').selectOption('medinah');
            await madinah.locator('[data-hotel-picker]').selectOption({ label: 'Dar Al Taqwa' });
            await expect(madinah.locator('[data-field="hotel_name"]')).toHaveValue('Dar Al Taqwa');

            const hotelsA = page.locator('[data-option-group="accommodations"][data-option-code="A"]');
            await hotelsA.getByRole('button', { name: 'Add hotel' }).click();
            await hotelsA.locator('[data-hotel-picker]').selectOption({ label: 'Swissotel Makkah' });
            await expect(hotelsA.locator('[data-field="star_rating"]')).toHaveValue('5');

            // Step 6 — journey: a new day continues the dates
            await openStep(page, /Journey plan/);
            await page.getByRole('button', { name: 'Add day' }).click();
            await page.locator('[data-row="itinerary"]').first().locator('[data-field="date_gregorian"]').fill('2027-05-07');
            await page.locator('[data-row="itinerary"]').first().locator('[data-field="city"]').fill('Madinah');
            await page.getByRole('button', { name: 'Add day' }).click();
            await expect(page.locator('[data-row="itinerary"]').nth(1).locator('[data-field="day_number"]')).toHaveValue('2');
            await expect(page.locator('[data-row="itinerary"]').nth(1).locator('[data-field="date_gregorian"]')).toHaveValue('2027-05-08');
            await expect(page.locator('[data-row="itinerary"]').first().locator('[data-option-b-only]')).toBeVisible();

            // Step 8 — transport from saved content
            await openStep(page, /Transport & meals/);
            await page.getByRole('button', { name: 'Add saved transport' }).click();
            const picker = page.locator('#libraryPickerModal');
            await expect(picker).toBeVisible();
            await picker.locator('[data-picker-list] label').filter({ hasText: 'Makkah → Medinah' }).locator('input').check();
            await picker.getByRole('button', { name: 'Add selected' }).click();
            await expect(page.locator('[data-row="transportation"] [data-field="from_location"]')).toHaveValue('Makkah');

            // Step 9 — a saved service can only be added once
            await openStep(page, /Included & not included/);
            await page.locator('[data-open-picker="inclusions"]').click();
            await picker.locator('[data-picker-search]').fill('Religious guide book');
            await picker.locator('[data-picker-list] input').first().check();
            await picker.getByRole('button', { name: 'Add selected' }).click();
            await expect(page.locator('[data-row="inclusions"]')).toHaveCount(1);
            await page.locator('[data-open-picker="inclusions"]').click();
            await picker.locator('[data-picker-search]').fill('Religious guide book');
            await expect(picker.locator('[data-picker-list] input').first()).toBeDisabled();
            await picker.getByRole('button', { name: 'Cancel' }).click();

            // Step 11 — internal note
            await openStep(page, /Notes & policies/);
            await page.getByLabel('Internal notes').fill(`E2E-PRIVATE-${stamp}`);

            await expect(page.locator('[data-dirty-flag]')).toBeVisible();

            // Save the draft: back on the same package, nothing public yet
            await page.getByRole('button', { name: 'Save draft' }).click();
            await expect(page).toHaveURL(/\/admin\/hajj-packages\/\d+\/edit/);
            await expect(page.getByRole('status').filter({ hasText: 'Draft saved' })).toBeVisible();
            const slug = await page.evaluate(() => document.querySelector('[data-seo-slug]').value);
            expect((await page.request.get(`/hajj/${slug}`)).status()).toBe(404);

            await openStep(page, /Hotels & accommodation/);
            await expect(page.locator('[data-option-group="accommodations"][data-option-code="A"] [data-field="hotel_name"]')).toHaveValue('Swissotel Makkah');

            // Publish, confirming in the dialog
            await clickAndConfirm(page, page.getByRole('button', { name: 'Publish', exact: true }));
            // Publishing saves every section and re-indexes the package, which can take a while on a busy test server.
            await expect(page.getByRole('status').filter({ hasText: 'is published and live' })).toBeVisible({ timeout: 30_000 });

            await page.goto(`/hajj/${slug}`);
            await expect(page.getByRole('heading', { name: title })).toBeVisible();
            await expect(page.locator('.currency-price[data-usd="15432.00"]').first()).toBeVisible();
            await expect(page.getByText(`E2E-PRIVATE-${stamp}`)).toHaveCount(0);
        } finally {
            await deletePackageByName(page, title);
        }
    });

    test('publishing an incomplete package is refused and the message opens the step to fix', async ({ page }) => {
        await page.goto('/admin/hajj-packages/create');
        await page.getByLabel('Package title').fill('E2E incomplete ' + Date.now());

        await clickAndConfirm(page, page.getByRole('button', { name: 'Publish', exact: true }));

        const summary = page.locator('[data-error-summary]');
        await expect(summary).toContainText('The package cannot be published yet.');
        await expect(stepButton(page, /Room prices/)).toHaveClass(/has-error/);

        await summary.getByRole('link', { name: 'Room prices' }).click();
        await expect(page.locator('#step-pricing')).toBeVisible();
        await expect(page.locator('#step-pricing')).toContainText('Add at least one available room type with a price.');
    });

    test('a hotel added from inside the builder is saved to the hotel list and selected', async ({ page }) => {
        const hotel = 'E2E Hotel ' + Date.now();

        await page.goto('/admin/hajj-packages/create?step=hotels');
        await page.getByRole('button', { name: 'New hotel' }).click();
        const modal = page.locator('#newHotelModal');
        await modal.getByLabel('Hotel name').fill(hotel);
        await modal.getByLabel('City / place').selectOption('medinah');
        await modal.getByRole('button', { name: 'Save hotel' }).click();
        await expect(modal).toBeHidden();

        const row = page.locator('[data-row="accommodations"]').last();
        await expect(row.locator('[data-field="hotel_name"]')).toHaveValue(hotel);
        await expect(row.locator('[data-field="location"]')).toHaveValue('medinah');
        await expect(row.locator('[data-hotel-picker] option:checked')).toHaveText(hotel);

        // It is in the library, unused, so it can be deleted again.
        page.on('dialog', (dialog) => dialog.accept());
        await page.goto('/admin/library/hotels?q=' + encodeURIComponent(hotel));
        const listed = page.getByRole('row', { name: new RegExp(hotel) });
        await expect(listed).toContainText('0 packages');
        await listed.getByRole('button', { name: /More actions/ }).click();
        await clickAndConfirm(page, listed.getByRole('button', { name: 'Delete' }));
        await expect(page.getByRole('row', { name: new RegExp(hotel) })).toHaveCount(0);
    });

    test('the journey plan can be copied from another package and dates filled in one go', async ({ page }) => {
        await page.goto('/admin/hajj-packages/create?step=journey');

        await page.locator('#step-journey').getByRole('button', { name: /Copy from another package/ }).click();
        const modal = page.locator('#copyFromModal');
        const ub001 = await modal.locator('[data-copy-package] option', { hasText: 'UB001 —' }).first().getAttribute('value');
        await modal.locator('[data-copy-package]').selectOption(ub001);
        await expect(modal.locator('#copy-section-journey')).toBeChecked();
        await modal.getByRole('button', { name: 'Copy into this form' }).click();
        await page.locator('#adminConfirmModal [data-confirm-accept]').click();

        await expect(page.locator('[data-row="itinerary"]')).toHaveCount(13);
        await expect(page.locator('[data-row="itinerary"]').first().locator('[data-field="city"]')).toHaveValue('To Medinah');

        await page.getByRole('button', { name: 'Fill dates' }).click();
        await page.getByLabel('English date of the first day').fill('2028-04-25');
        await page.getByRole('button', { name: "Fill every day's date" }).click();
        await expect(page.locator('[data-row="itinerary"]').nth(12).locator('[data-field="date_gregorian"]')).toHaveValue('2028-05-07');

        // Leave without saving: nothing was created.
        page.on('dialog', (dialog) => dialog.accept());
    });

    test('duplicating a live package creates a separate draft copy', async ({ page }) => {
        await page.goto('/admin/hajj-packages?q=UB003');
        const row = page.getByRole('row', { name: /UB003/ }).first();
        await row.getByRole('button', { name: /More actions/ }).click();
        await clickAndConfirm(page, row.getByRole('button', { name: 'Duplicate' }));

        await expect(page).toHaveURL(/\/admin\/hajj-packages\/\d+\/edit/);
        await expect(page.getByRole('status').filter({ hasText: 'A copy was created as a draft' })).toBeVisible();
        await expect(page.getByLabel('Package code')).toHaveValue(/^UB003-COPY/);
        const copyCode = await page.getByLabel('Package code').inputValue();
        const copyName = await page.getByLabel('Package title').inputValue();
        expect(copyName).toMatch(/\(Copy\)$/);
        await expect(page.locator('.builder-actionbar')).toContainText('Draft');

        await openStep(page, /Room prices/);
        await expect(page.locator('[data-row="room_options"]')).not.toHaveCount(0);

        // The original is still live and unchanged in the list.
        await page.goto('/admin/hajj-packages?q=UB003');
        await expect(page.getByRole('row', { name: /UB003/ }).filter({ hasText: 'Published' }).filter({ hasNotText: '(Copy)' })).toHaveCount(1);

        await deletePackageByName(page, copyName);
        await page.goto('/admin/hajj-packages?q=' + copyCode);
        await expect(page.getByRole('row').filter({ hasText: copyCode })).toHaveCount(0);
    });

    test('unsaved changes are kept in the browser and can be restored', async ({ page }) => {
        await page.goto('/admin/hajj-packages?q=UB004');
        await page.getByRole('row', { name: /UB004/ }).first().getByRole('link', { name: 'Edit' }).click();
        const original = await page.getByLabel('Short description').inputValue();

        await page.getByLabel('Short description').fill('E2E typed but not saved');
        await expect(page.locator('[data-dirty-flag]')).toBeVisible();
        await page.waitForTimeout(1800); // the builder keeps its copy 1.5s after typing stops

        page.once('dialog', (dialog) => dialog.accept()); // "leave this page?"
        await page.reload();

        const banner = page.locator('[data-restore-banner]');
        await expect(banner).toBeVisible();
        await expect(page.getByLabel('Short description')).toHaveValue(original);
        await banner.getByRole('button', { name: 'Restore them' }).click();
        await expect(page.getByLabel('Short description')).toHaveValue('E2E typed but not saved');

        // Discard it again without saving.
        page.once('dialog', (dialog) => dialog.accept());
        await page.reload();
        await page.locator('[data-restore-banner]').getByRole('button', { name: 'Discard' }).click();
        await page.reload();
        await expect(page.locator('[data-restore-banner]')).toBeHidden();
        await expect(page.getByLabel('Short description')).toHaveValue(original);
    });

    test('a preview link without its signature shows nothing', async ({ page }) => {
        await page.goto('/admin/hajj-packages?q=UB006');
        const href = await page.getByRole('row', { name: /UB006/ }).first().locator('a', { hasText: 'Preview' }).getAttribute('href');
        const unsigned = href.split('?')[0];

        expect((await page.request.get(unsigned)).status()).toBe(403);

        await page.goto(href);
        await expect(page.locator('.admin-preview-banner')).toContainText('Preview');
    });

    test('the builder fits a phone screen with no sideways scrolling', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin/hajj-packages?q=UB001');
        await page.getByRole('row', { name: /UB001/ }).first().getByRole('link', { name: 'Edit' }).click();

        for (const step of [/Room prices/, /Hotels & accommodation/, /Journey plan/, /Transport & meals/, /Review everything/, /Save, preview & publish/]) {
            await page.locator('[data-steps-toggle]').click();
            await stepButton(page, step).click();
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            expect(overflow, `sideways scroll on ${step}`).toBeLessThanOrEqual(1);
        }

        // The save bar stays on screen on every step (the Publish step has its own copy too).
        await expect(page.locator('.builder-actionbar').getByRole('button', { name: 'Save changes' })).toBeVisible();
    });
});
