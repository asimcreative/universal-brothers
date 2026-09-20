import { expect } from '@playwright/test';

/**
 * Change which of the three price lists the site is showing, the way a
 * visitor at this viewport width would.
 *
 * There are two copies of the same control and only ever one of them is on
 * screen: the top bar carries it above 1024px, and below that the bar's copy
 * is hidden and the drawer holds the only one. So this asks the page which it
 * is offering rather than assuming a desktop — a test that always reached for
 * the bar's copy would pass on Chrome and fail on the mobile project for a
 * reason that has nothing to do with currency.
 *
 * It is a form POST that reloads the page, deliberately: the currency decides
 * which packages are listed, not just how the numbers are written, so the
 * server has to answer again. Waiting for the button to come back marked
 * active is therefore also waiting for that new page.
 */
export async function chooseCurrency(page, code) {
    const bar = page.locator('.ub-currency-switch:not(.ub-currency-switch--drawer)');

    if (await bar.isVisible()) {
        await bar.locator(`button[value="${code}"]`).click();
    } else {
        const drawer = page.locator('#ub-mobile-nav');
        await page.locator('[data-ub-menu-open]').click();
        await expect(drawer).toBeVisible();
        await drawer.locator(`.ub-currency-switch--drawer button[value="${code}"]`).click();
    }

    await expect(
        page.locator(`.ub-currency-switch button[value="${code}"]`).first()
    ).toHaveClass(/is-active/);
}
