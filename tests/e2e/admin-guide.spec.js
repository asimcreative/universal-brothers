import { test, expect } from '@playwright/test';
import { clickAndConfirm } from './helpers/confirm.js';

// Runs under 'admin-chromium' (pre-authenticated) against database/testing.sqlite.
//
// The admin guide, first-visit welcome and guided tour, and the package
// wizard's Review / Publish / resume / reusable-content behaviour (issue #11),
// driven the way a non-technical administrator uses them.

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const stepButton = (page, name) => page.locator('#builder-step-list').getByRole('button', { name });

/** Puts this admin's tour back to "never taken", as the account menu does. */
async function restartTour(page) {
    await page.goto('/admin/guide');
    await page.getByRole('button', { name: /Take the (guided )?tour( again)?/ }).first().click();
    await expect(page).toHaveURL(/\/admin(\/)?(\?|$)/);
}

async function noSidewaysScroll(page, label) {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow, `sideways scroll: ${label}`).toBeLessThanOrEqual(1);
}

async function deletePackageByName(page, name) {
    const rowFor = () => page.getByRole('row', { name: new RegExp(escapeRegExp(name)) }).first();
    await page.goto('/admin/hajj-packages?q=' + encodeURIComponent(name));
    if (!(await rowFor().count())) return;
    await rowFor().getByRole('button', { name: /More actions/ }).click();
    await clickAndConfirm(page, rowFor().getByRole('button', { name: 'Delete' }));
    await expect(page.getByRole('status').filter({ hasText: 'Deleted' })).toBeVisible();
}

test.describe('Admin guide', () => {
    test('the guide can be searched, read and ticked off', async ({ page }) => {
        await page.goto('/admin/guide');
        await expect(page.getByRole('heading', { name: 'Admin Guide' })).toBeVisible();
        await noSidewaysScroll(page, 'guide index');

        await page.getByLabel('Search the guide').fill('Kaaba view');
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await expect(page.getByRole('link', { name: /Additional options \(upgrades\)/ })).toBeVisible();

        await page.getByRole('link', { name: /Additional options \(upgrades\)/ }).click();
        await expect(page.getByRole('heading', { name: 'Step by step' })).toBeVisible();
        await expect(page.getByText('Kaaba View Supplement — US$2,200 per person.', { exact: false })).toBeVisible();

        // Tick it, see it counted, then untick to leave the test data as it was.
        if (await page.getByRole('button', { name: 'Mark as not read' }).count()) {
            await page.getByRole('button', { name: 'Mark as not read' }).click();
        }
        await page.getByRole('button', { name: 'Mark as read' }).click();
        await expect(page.getByText('You have read this section.')).toBeVisible();
        await page.getByRole('link', { name: 'All guide sections' }).click();
        await expect(page.locator('.guide-card.is-done', { hasText: 'Additional options' })).toBeVisible();

        await page.goto('/admin/guide/upgrades');
        await page.getByRole('button', { name: 'Mark as not read' }).click();
        await expect(page.getByRole('button', { name: 'Mark as read' })).toBeVisible();
    });

    test('every admin page offers a closed "Need help?" panel that opens with the keyboard', async ({ page }) => {
        await page.goto('/admin/faqs');
        const help = page.locator('details.page-help');
        await expect(help).toBeVisible();
        await expect(help).not.toHaveAttribute('open', '');
        await help.locator('summary').focus();
        await page.keyboard.press('Enter');
        await expect(help).toHaveAttribute('open', '');
        await expect(help.getByRole('link', { name: /Read the full guide: FAQs/ })).toBeVisible();
    });
});

test.describe('Welcome and guided tour', () => {
    test('a new admin can start the tour, move with buttons and keys, skip, resume and finish it', async ({ page }) => {
        await restartTour(page);

        // Restart lands on the dashboard with the tour already open.
        const tour = page.locator('.tour-popover');
        await expect(tour).toBeVisible();
        await expect(tour).toContainText('Step 1 of 9');
        await expect(tour.getByRole('heading', { name: 'Your dashboard' })).toBeFocused();
        await expect(page.locator('[data-tour="dashboard"]')).toHaveClass(/tour-target/);
        await expect(tour.getByRole('button', { name: 'Back' })).toBeDisabled();

        await tour.getByRole('button', { name: 'Next' }).click();
        await expect(tour).toContainText('Step 2 of 9');
        await expect(tour.getByRole('heading', { name: 'The menu' })).toBeVisible();

        await page.keyboard.press('ArrowRight');
        await expect(tour).toContainText('Step 3 of 9');
        await page.keyboard.press('ArrowLeft');
        await expect(tour).toContainText('Step 2 of 9');
        await tour.getByRole('button', { name: 'Next' }).click();
        await tour.getByRole('button', { name: 'Next' }).click();
        await expect(tour).toContainText('Step 4 of 9');

        // Escape skips; the page is usable again and remembers the place.
        const paused = page.waitForResponse((r) => r.url().includes('/admin/onboarding/tour') && r.request().postData()?.includes('"paused"'));
        await page.keyboard.press('Escape');
        await paused;
        await expect(tour).toHaveCount(0);
        const resume = page.getByRole('button', { name: 'Resume tour (step 4 of 9)' });
        await expect(resume).toBeVisible();

        // Even after leaving the page.
        await page.reload();
        await expect(page.getByRole('button', { name: 'Resume tour (step 4 of 9)' })).toBeVisible();
        await page.getByRole('button', { name: 'Resume tour (step 4 of 9)' }).click();
        await expect(tour).toContainText('Step 4 of 9');

        for (let step = 4; step < 9; step++) {
            await tour.getByRole('button', { name: 'Next' }).click();
        }
        await expect(tour).toContainText('Step 9 of 9');
        await expect(page.locator('[data-tour="help"]')).toHaveClass(/tour-target/);
        await tour.getByRole('button', { name: 'Finish' }).click();

        await expect(tour).toHaveCount(0);
        await expect(page.getByText('You have finished the tour')).toBeVisible();

        // Finished means it never comes back by itself.
        await page.reload();
        await expect(page.getByText('Welcome to your website admin')).toHaveCount(0);
    });

    test('on a phone the tour opens the menu for menu stops and never blocks the screen', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await restartTour(page);

        const tour = page.locator('.tour-popover');
        await expect(tour).toHaveClass(/is-sheet/);
        await noSidewaysScroll(page, 'tour step 1 on a phone');

        await tour.getByRole('button', { name: 'Next' }).click();
        await expect(tour).toContainText('Step 2 of 9');
        await expect(page.locator('#adminMobileNav')).toHaveClass(/show/);
        await expect(page.locator('#adminMobileNav [data-tour="sidebar"]')).toHaveClass(/tour-target/);

        await tour.getByRole('button', { name: 'Next' }).click();
        await expect(page.locator('#adminMobileNav [data-tour="nav-hajj"]')).toBeInViewport();
        await noSidewaysScroll(page, 'tour menu stop on a phone');

        await tour.getByRole('button', { name: 'Skip the tour' }).click();
        await expect(tour).toHaveCount(0);
        await expect(page.locator('#adminMobileNav')).not.toHaveClass(/show/);

        // The page underneath works normally again.
        await page.getByRole('button', { name: 'Open menu' }).click();
        await page.locator('#adminMobileNav a[href$="/admin/faqs"]').click();
        await expect(page).toHaveURL(/\/admin\/faqs$/);

        // Leave the tour finished for the other tests.
        await page.evaluate(async () => {
            const data = JSON.parse(document.getElementById('admin-tour-data').textContent);
            await fetch(data.stateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ status: 'completed', step: 0 }),
            });
        });
    });
});

test.describe('Package wizard', () => {
    test('the Review step lists what to check, and "Edit" goes straight to the step', async ({ page }) => {
        await page.goto('/admin/hajj-packages?q=UB004');
        await page.getByRole('row', { name: /UB004/ }).first().getByRole('link', { name: 'Edit' }).click();
        await stepButton(page, /Review everything/).click();

        const review = page.locator('#step-review');
        await expect(review).toContainText('Nothing blocks publishing.');
        const warning = review.locator('.review-list.is-warning li', { hasText: 'The package is 14 days but the journey plan has 13 days.' });
        await expect(warning).toBeVisible();
        await expect(review.locator('.review-checklist')).toContainText('Room prices added');

        await warning.getByRole('link', { name: /Edit/ }).click();
        await expect(page.locator('#step-journey')).toBeVisible();
        await expect(stepButton(page, /Journey plan/)).toHaveAttribute('aria-current', 'step');

        // The review follows unsaved typing.
        await stepButton(page, /Basic information/).click();
        const title = await page.getByLabel('Package title').inputValue();
        await page.getByLabel('Package title').fill('');
        await stepButton(page, /Review everything/).click();
        await expect(review).toContainText('Add a package title.');
        await expect(page.locator('[data-checklist-panel]')).toContainText('Basic information completed');

        await stepButton(page, /Basic information/).click();
        await page.getByLabel('Package title').fill(title);
        await stepButton(page, /Review everything/).click();
        await expect(review).toContainText('Nothing blocks publishing.');
        page.on('dialog', (dialog) => dialog.accept());
    });

    test('publish waits until nothing blocks it, and a draft reopens where it was saved', async ({ page }) => {
        const stamp = Date.now();
        const title = `E2E Wizard Draft ${stamp}`;

        try {
            await page.goto('/admin/hajj-packages/create?step=publish');
            const publish = page.locator('#step-publish').getByRole('button', { name: 'Publish package' });
            await expect(publish).toBeDisabled();
            await expect(page.locator('#step-publish [data-publish-blockers]')).toContainText('Add a package title.');

            await page.locator('#step-publish [data-publish-blockers]').getByRole('link', { name: 'Add a package title.' }).click();
            await expect(page.locator('#step-basics')).toBeVisible();
            await page.getByLabel('Package title').fill(title);
            await page.getByLabel('Package code').fill(`W${String(stamp).slice(-6)}`);

            await stepButton(page, /Hotels & accommodation/).click();
            await page.getByRole('button', { name: 'Save draft' }).click();
            await expect(page.getByRole('status').filter({ hasText: 'Draft saved' })).toBeVisible();

            // From the list: "Continue" opens the step it was saved on.
            await page.goto('/admin/hajj-packages?q=' + encodeURIComponent(title));
            const row = page.getByRole('row', { name: new RegExp(escapeRegExp(title)) }).first();
            await expect(row).toContainText('%');
            await row.getByRole('link', { name: `Continue ${title}` }).click();
            await expect(page.locator('#step-hotels')).toBeVisible();
            await expect(stepButton(page, /Hotels & accommodation/)).toHaveAttribute('aria-current', 'step');

            // The dashboard offers the same.
            await page.goto('/admin');
            await expect(page.locator('.draft-progress-item', { hasText: title })).toBeVisible();
        } finally {
            await deletePackageByName(page, title);
        }
    });

    test('a hotel can be found with its details and usage, and a package change is labelled as this package only', async ({ page }) => {
        await page.goto('/admin/hajj-packages/create?step=hotels');
        await page.getByRole('button', { name: 'Find a hotel' }).click();

        const modal = page.locator('#findHotelModal');
        await expect(modal).toBeVisible();
        await modal.getByPlaceholder('Type a hotel name…').fill('Dar Al Taqwa');
        const item = modal.locator('[data-find-hotel-id]', { hasText: 'Dar Al Taqwa' }).first();
        await expect(item).toContainText(/used in \d+ package/);
        await item.click();
        await expect(modal.locator('[data-find-hotel-details]')).toContainText('Used in');
        await modal.getByRole('button', { name: 'Add this hotel' }).click();
        await expect(modal).toBeHidden();

        const row = page.locator('[data-row="accommodations"]').last();
        await expect(row.locator('[data-field="hotel_name"]')).toHaveValue('Dar Al Taqwa');
        await expect(row.locator('[data-lib-badge]')).toContainText(/Saved content · used in \d+ package/);

        await row.locator('[data-field="hotel_name"]').fill('Dar Al Taqwa (Tower rooms)');
        await expect(row.locator('[data-lib-badge]')).toContainText('Changed for this package only');
        page.on('dialog', (dialog) => dialog.accept());
    });

    test('copying from another package says exactly what will be copied and replaced before doing it', async ({ page }) => {
        await page.goto('/admin/hajj-packages/create?step=pricing');
        await page.locator('#step-pricing').getByRole('button', { name: /Copy from another package/ }).click();

        const modal = page.locator('#copyFromModal');
        const ub001 = await modal.locator('[data-copy-package] option', { hasText: 'UB001 —' }).first().getAttribute('value');
        await modal.locator('[data-copy-package]').selectOption(ub001);
        await modal.getByRole('button', { name: 'Copy into this form' }).click();

        const confirm = page.locator('#adminConfirmModal');
        await expect(confirm).toBeVisible();
        await expect(confirm).toContainText('Hotel options: A, B (replaces 0 options here)');
        await expect(confirm).toContainText(/Room prices: \d+ rows \(replaces 0\)/);
        await expect(confirm).toContainText('The title, code, photos and internal notes are not copied.');

        await confirm.locator('.modal-footer').getByRole('button', { name: 'Cancel' }).click();
        await expect(page.locator('[data-row="room_options"]')).toHaveCount(0);
    });

    test('editing a shared hotel offers to update it or save a separate copy', async ({ page }) => {
        const copyName = `E2E Separate Hotel ${Date.now()}`;

        await page.goto('/admin/library/hotels?q=Dar%20Al%20Taqwa');
        await page.getByRole('row', { name: /Dar Al Taqwa/ }).first().getByRole('link', { name: 'Edit' }).click();

        await expect(page.getByText(/This is shared information\. Used in \d+ packages?\./)).toBeVisible();
        await expect(page.getByRole('button', { name: 'Update shared record' })).toBeVisible();

        await page.getByLabel('Hotel name').fill(copyName);
        await page.getByRole('button', { name: 'Save as a new separate record' }).click();
        await expect(page.getByRole('status').filter({ hasText: `Saved as a new hotel, "${copyName}"` })).toBeVisible();

        // The original is unchanged.
        await page.goto('/admin/library/hotels?q=Dar%20Al%20Taqwa');
        await expect(page.getByRole('row', { name: /Dar Al Taqwa/ }).first()).toBeVisible();

        // The copy is unused, so it can be deleted again.
        await page.goto('/admin/library/hotels?q=' + encodeURIComponent(copyName));
        const listed = page.getByRole('row', { name: new RegExp(escapeRegExp(copyName)) });
        await listed.getByRole('button', { name: /More actions/ }).click();
        await clickAndConfirm(page, listed.getByRole('button', { name: 'Delete' }));
        await expect(page.getByRole('row', { name: new RegExp(escapeRegExp(copyName)) })).toHaveCount(0);
    });

    test('on a phone the steps fold into one "Step N of 14" menu', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin/hajj-packages/create');

        const toggle = page.locator('[data-steps-toggle]');
        await expect(toggle).toBeVisible();
        await expect(toggle).toContainText('Step 1 of 14');
        await expect(toggle).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('#builder-step-list')).toBeHidden();

        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-expanded', 'true');
        await stepButton(page, /Room prices/).click();
        await expect(toggle).toContainText('Step 4 of 14');
        await expect(toggle).toContainText('Room prices');
        await expect(page.locator('#builder-step-list')).toBeHidden();
        await noSidewaysScroll(page, 'builder step menu on a phone');

        await page.locator('#step-pricing details.need-help summary').click();
        await expect(page.locator('#step-pricing .need-help-body')).toContainText('What should I enter?');
        await noSidewaysScroll(page, 'help panel on a phone');
    });
});
