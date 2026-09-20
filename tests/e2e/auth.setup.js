import { test as setup, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const ADMIN_EMAIL = 'admin@universalbrothers.test';
const ADMIN_PASSWORD = 'password';
const authFile = 'playwright/.auth/admin.json';
const publicFile = 'playwright/.auth/public.json';

/**
 * A visitor who has already answered the currency question.
 *
 * The site asks which of the three price lists to read on the first visit,
 * and the dialog is modal — its backdrop takes the pointer events, exactly as
 * it should for a real visitor. A fresh browser context has never answered
 * it, so without this every public test would be clicking at a page behind a
 * dialog and every one of them would time out.
 *
 * Only the currency cookie is saved, deliberately — NOT a logged-in session.
 * Handing every public test the same Laravel session would let one test's
 * validation errors and flash messages arrive in the next one.
 *
 * The gate itself is covered by its own test, which starts from an empty
 * storage state (see public.spec.js).
 */
setup('remember a currency, so the gate is not in every test\'s way', async ({ baseURL }) => {
    const url = new URL(baseURL);

    fs.mkdirSync(path.dirname(publicFile), { recursive: true });
    fs.writeFileSync(publicFile, JSON.stringify({
        cookies: [{
            name: 'ub_currency',
            value: 'PKR',
            domain: url.hostname,
            path: '/',
            expires: -1,
            httpOnly: false,
            secure: false,
            sameSite: 'Lax',
        }],
        origins: [],
    }, null, 4));
});

setup('authenticate as admin', async ({ page, baseURL }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email').fill(ADMIN_EMAIL);
    // exact: true — the login page's new show/hide toggle button has an
    // accessible name ("Show password") that otherwise substring-matches
    // getByLabel('Password') too (Playwright's default match is substring,
    // case-insensitive), a real strict-mode collision from that legitimate
    // a11y addition, not a weakened assertion.
    await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Login' }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    // The admin pages never show the currency gate, but some admin journeys
    // step out onto a public page to check what was published. Carrying the
    // answer means they meet the page rather than the dialog.
    await page.context().addCookies([{ name: 'ub_currency', value: 'PKR', url: baseURL }]);

    await page.context().storageState({ path: authFile });
});
