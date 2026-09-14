import { expect } from '@playwright/test';

/**
 * Clicks a control that asks for confirmation, then confirms in the admin's
 * own dialog (layouts/admin.blade.php, #adminConfirmModal).
 *
 * The admin used to rely on the browser's native confirm(), which tests
 * answered with `page.once('dialog', …)`. The styled dialog explains the
 * consequence before anything is deleted, so tests confirm it the way an
 * administrator does: by pressing its button.
 */
export async function clickAndConfirm(page, control) {
    // Before admin.js loads, the layout falls back to the browser's confirm;
    // wait for the styled dialog so the test drives what administrators see.
    await page.waitForFunction(() => window.ubAdminConfirmReady === true);
    await control.click();
    const dialog = page.locator('#adminConfirmModal');
    await expect(dialog).toBeVisible();
    await dialog.locator('[data-confirm-accept]').click();
    await expect(dialog).toBeHidden();
}
