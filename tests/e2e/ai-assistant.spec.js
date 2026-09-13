import { expect, test } from '@playwright/test';

/**
 * AI assistant widget.
 *
 * Every test intercepts POST /ai/chat in the browser and fulfils it locally,
 * so no request ever leaves the machine and no provider credit is spent. The
 * backend path is covered separately by AiAssistantTest; what is proven here
 * is the part only a real browser can prove — that the panel opens, that
 * Enter sends and Shift+Enter does not, that the busy state actually disables
 * the controls, that an error offers a retry, and that the API key appears
 * nowhere in the page or in anything the browser sends.
 */

const REPLY = {
    ok: true,
    reply: 'UB010 quad sharing is PKR 3,485,000 per person.\n\n- Package A: Dar Al Taqwa\n- Package B: Dallah Taibah',
    sources: [
        {
            title: 'UB010 — Executive Platinum Makkah Tower',
            url: '/hajj/ub010-executive-platinum-makkah-tower-medinah-first',
            reason: 'Full package details, itinerary and prices',
        },
    ],
    offer_lead: false,
};

async function fulfilChat(page, body = REPLY, status = 200) {
    await page.route('**/ai/chat', (route) =>
        route.fulfill({
            status,
            contentType: 'application/json',
            body: JSON.stringify(body),
        })
    );
}

async function openPanel(page) {
    await page.goto('/');
    const launcher = page.locator('[data-ai-toggle]');
    await expect(launcher).toBeVisible();
    await launcher.click();
    await expect(page.locator('#ai-assistant-panel')).toBeVisible();
}

test.describe('AI assistant', () => {
    test('the launcher opens and closes the panel', async ({ page }) => {
        await page.goto('/');

        const launcher = page.locator('[data-ai-toggle]');
        const panel = page.locator('#ai-assistant-panel');

        await expect(panel).toBeHidden();
        await expect(launcher).toHaveAttribute('aria-expanded', 'false');

        await launcher.click();
        await expect(panel).toBeVisible();
        await expect(launcher).toHaveAttribute('aria-expanded', 'true');

        await page.locator('[data-ai-close]').click();
        await expect(panel).toBeHidden();
        await expect(launcher).toHaveAttribute('aria-expanded', 'false');
    });

    test('Escape closes the panel and returns focus to the launcher', async ({ page }) => {
        await openPanel(page);

        await page.keyboard.press('Escape');

        await expect(page.locator('#ai-assistant-panel')).toBeHidden();
        await expect(page.locator('[data-ai-toggle]')).toBeFocused();
    });

    test('the welcome message and quick actions are shown', async ({ page }) => {
        await openPanel(page);

        await expect(page.locator('.ai-messages .ai-bubble').first()).toContainText('Universal Brothers');
        await expect(page.locator('[data-ai-quick]')).toHaveCount(7);
        await expect(page.locator('[data-ai-quick]').first()).toBeVisible();
    });

    test('a visitor can send a message and see the reply with source links', async ({ page }) => {
        await fulfilChat(page);
        await openPanel(page);

        await page.locator('[data-ai-input]').fill('What is UB010 in PKR?');
        await page.locator('[data-ai-send]').click();

        await expect(page.locator('.ai-message--user').last()).toContainText('What is UB010 in PKR?');
        await expect(page.locator('.ai-message--assistant').last()).toContainText('PKR 3,485,000');

        // The reply's "- " lines become a real list, which is the only
        // formatting the escaper re-introduces.
        await expect(page.locator('.ai-message--assistant').last().locator('.ai-list li')).toHaveCount(2);

        const source = page.locator('.ai-source-card').first();
        await expect(source).toBeVisible();
        await expect(source).toContainText('UB010');
        await expect(source).toHaveAttribute('href', /ub010-executive-platinum/);
    });

    test('Enter sends and Shift+Enter inserts a newline', async ({ page }) => {
        await fulfilChat(page);
        await openPanel(page);

        const input = page.locator('[data-ai-input]');

        await input.click();
        await input.type('First line');
        await page.keyboard.down('Shift');
        await page.keyboard.press('Enter');
        await page.keyboard.up('Shift');
        await input.type('Second line');

        // Still nothing sent.
        await expect(page.locator('.ai-message--user')).toHaveCount(0);
        await expect(input).toHaveValue(/First line\nSecond line/);

        await page.keyboard.press('Enter');

        await expect(page.locator('.ai-message--user')).toHaveCount(1);
        await expect(input).toHaveValue('');
    });

    test('a quick action sends its predefined question', async ({ page }) => {
        await fulfilChat(page);
        await openPanel(page);

        await page.locator('[data-ai-quick]').first().click();

        await expect(page.locator('.ai-message--user').last()).toContainText('Hajj 2027 packages');
        // The chips step out of the way once the conversation has started.
        await expect(page.locator('[data-ai-quick-actions]')).toBeHidden();
    });

    test('the composer is disabled while a reply is pending', async ({ page }) => {
        let release;
        const held = new Promise((resolve) => {
            release = resolve;
        });

        await page.route('**/ai/chat', async (route) => {
            await held;
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(REPLY),
            });
        });

        await openPanel(page);

        await page.locator('[data-ai-input]').fill('Tell me about UB010');
        await page.locator('[data-ai-send]').click();

        await expect(page.locator('.ai-typing')).toBeVisible();
        await expect(page.locator('[data-ai-send]')).toBeDisabled();
        await expect(page.locator('[data-ai-input]')).toBeDisabled();

        release();

        await expect(page.locator('.ai-typing')).toHaveCount(0);
        await expect(page.locator('[data-ai-send]')).toBeEnabled();
    });

    test('a failed reply shows an error with a working retry', async ({ page }) => {
        let attempt = 0;

        await page.route('**/ai/chat', (route) => {
            attempt += 1;

            if (attempt === 1) {
                return route.fulfill({
                    status: 502,
                    contentType: 'application/json',
                    body: JSON.stringify({ ok: false, reply: 'Sorry, I cannot reach that right now.', sources: [] }),
                });
            }

            return route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(REPLY),
            });
        });

        await openPanel(page);

        await page.locator('[data-ai-input]').fill('What is UB010?');
        await page.locator('[data-ai-send]').click();

        const error = page.locator('.ai-message--error');
        await expect(error).toBeVisible();
        await expect(error).toContainText('cannot reach');

        await error.locator('.ai-retry').click();

        await expect(page.locator('.ai-message--error')).toHaveCount(0);
        await expect(page.locator('.ai-message--assistant').last()).toContainText('PKR 3,485,000');
    });

    test('the conversation can be cleared back to the welcome message', async ({ page }) => {
        await fulfilChat(page);
        await page.route('**/ai/reset', (route) =>
            route.fulfill({ status: 200, contentType: 'application/json', body: '{"ok":true}' })
        );

        await openPanel(page);

        await page.locator('[data-ai-input]').fill('What is UB010?');
        await page.locator('[data-ai-send]').click();
        await expect(page.locator('.ai-message--user')).toHaveCount(1);

        await page.locator('[data-ai-clear]').click();

        await expect(page.locator('.ai-message--user')).toHaveCount(0);
        await expect(page.locator('.ai-messages .ai-bubble')).toHaveCount(1);
        await expect(page.locator('[data-ai-quick-actions]')).toBeVisible();
    });

    test('the lead form is absent from the page until the backend offers it', async ({ page }) => {
        await fulfilChat(page, { ...REPLY, offer_lead: true });
        await openPanel(page);

        // toHaveCount(0), not toBeHidden(): the form lives in a <template> and
        // is not in the document at all until it is offered. A hidden form
        // would still be real DOM, and its "Email" field would compete with
        // the page's own contact form for the same accessible name — which is
        // exactly what it did before this was fixed.
        await expect(page.locator('[data-ai-lead-form]')).toHaveCount(0);

        await page.locator('[data-ai-input]').fill('I want to book UB010');
        await page.locator('[data-ai-send]').click();

        const form = page.locator('[data-ai-lead-form]');
        await expect(form).toBeVisible();
        await expect(form.locator('input[name="name"]')).toBeVisible();
        await expect(form.locator('input[name="consent"]')).toBeVisible();

        await page.locator('[data-ai-lead-cancel]').click();
        await expect(form).toHaveCount(0);
    });

    test('the widget never competes with the page for an accessible name', async ({ page }) => {
        // The regression this guards, which bit twice:
        //
        //   1. the lead form's "Email" field matched alongside the contact
        //      form's, because a hidden form is still real DOM;
        //   2. the send button's "Send message" label matched both "Message"
        //      (the textarea) and "Send Message" (the submit button), because
        //      accessible-name matching is substring-based.
        //
        // The assistant renders on EVERY page, so any name it contributes is a
        // name it contributes everywhere. This is not only a test concern — a
        // screen-reader user meets the same ambiguity.
        //
        // The full label vocabulary of the public forms, so this catches the
        // whole class rather than the two instances already found.
        const PUBLIC_FORM_LABELS = [
            'Full Name',
            'Email',
            'Phone',
            'Phone / WhatsApp',
            'Message',
            'Service',
            'Duration',
            'Sharing',
            'Arrival',
            'Aziziya',
            'Package Type',
        ];

        // /contact has the contact form; the package detail page has the
        // enquiry form and the filter controls.
        for (const path of ['/contact', '/hajj/ub010-executive-platinum-makkah-tower-medinah-first']) {
            await page.goto(path);

            for (const openPanelFirst of [false, true]) {
                if (openPanelFirst) {
                    await page.locator('[data-ai-toggle]').click();
                    await expect(page.locator('#ai-assistant-panel')).toBeVisible();
                }

                for (const label of PUBLIC_FORM_LABELS) {
                    const count = await page.getByLabel(label).count();
                    expect(
                        count,
                        `"${label}" matched ${count} elements on ${path} (panel ${openPanelFirst ? 'open' : 'closed'}) — the assistant is competing with the page`
                    ).toBeLessThanOrEqual(1);
                }

                const sendButtons = await page.getByRole('button', { name: 'Send Message' }).count();
                expect(
                    sendButtons,
                    `"Send Message" matched ${sendButtons} buttons on ${path} — the assistant's send control is colliding`
                ).toBeLessThanOrEqual(1);
            }
        }
    });

    test('an assistant reply cannot inject markup', async ({ page }) => {
        await fulfilChat(page, {
            ok: true,
            reply: '<img src=x onerror="window.__xss=1"> <script>window.__xss=1</script> **bold** stays',
            sources: [
                // An off-site URL must never become a clickable card.
                { title: 'Evil', url: 'https://evil.example.com/steal', reason: 'nope' },
            ],
            offer_lead: false,
        });

        await openPanel(page);

        await page.locator('[data-ai-input]').fill('Try an injection');
        await page.locator('[data-ai-send]').click();

        const bubble = page.locator('.ai-message--assistant .ai-bubble').last();
        await expect(bubble).toContainText('<img src=x');
        await expect(bubble.locator('img')).toHaveCount(0);
        await expect(bubble.locator('script')).toHaveCount(0);
        // Bold is the one thing the formatter re-introduces, and it comes from
        // markdown syntax rather than from markup in the reply.
        await expect(bubble.locator('strong')).toHaveText('bold');

        expect(await page.evaluate(() => window.__xss)).toBeUndefined();
        await expect(page.locator('.ai-source-card')).toHaveCount(0);
    });

    test('the API key is absent from the page and from what the browser sends', async ({ page }) => {
        const payloads = [];

        await page.route('**/ai/chat', (route) => {
            payloads.push(route.request().postData() ?? '');
            return route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(REPLY),
            });
        });

        await openPanel(page);

        await page.locator('[data-ai-input]').fill('What is UB010?');
        await page.locator('[data-ai-send]').click();
        await expect(page.locator('.ai-message--assistant').last()).toContainText('PKR');

        const html = await page.content();

        for (const needle of ['sk-e2e-placeholder', 'OPENAI_API_KEY', 'api.openai']) {
            expect(html).not.toContain(needle);
        }

        expect(payloads.length).toBeGreaterThan(0);
        payloads.forEach((payload) => {
            expect(payload).not.toContain('sk-');
        });

        // Nothing in the bundle either.
        const scripts = await page.evaluate(() =>
            Array.from(document.querySelectorAll('script')).map((s) => s.textContent).join('\n')
        );
        expect(scripts).not.toContain('sk-e2e-placeholder');
    });

    test('the panel is usable at phone width', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 780 });
        await fulfilChat(page);
        await openPanel(page);

        const panel = page.locator('#ai-assistant-panel');
        const box = await panel.boundingBox();

        // A bottom sheet: full width, anchored to the bottom, never wider than
        // the viewport and never causing a horizontal scrollbar.
        expect(box.width).toBeLessThanOrEqual(390);
        expect(box.x).toBeGreaterThanOrEqual(0);

        const overflows = await page.evaluate(
            () => document.documentElement.scrollWidth > document.documentElement.clientWidth
        );
        expect(overflows).toBe(false);

        await page.locator('[data-ai-input]').fill('Hello');
        await page.locator('[data-ai-send]').click();
        await expect(page.locator('.ai-message--assistant').last()).toContainText('PKR');
    });

    test('the widget is keyboard reachable and labelled', async ({ page }) => {
        await page.goto('/');

        const launcher = page.locator('[data-ai-toggle]');

        await expect(launcher).toHaveAttribute('aria-controls', 'ai-assistant-panel');
        await expect(launcher).toHaveAttribute('aria-label', /Open the/);

        await launcher.focus();
        await page.keyboard.press('Enter');

        const panel = page.locator('#ai-assistant-panel');
        await expect(panel).toBeVisible();
        await expect(panel).toHaveAttribute('role', 'dialog');
        await expect(panel).toHaveAttribute('aria-labelledby', 'ai-panel-title');
        await expect(page.locator('.ai-messages')).toHaveAttribute('aria-live', 'polite');
    });
});
