import { test, expect } from '@playwright/test';
import { clickAndConfirm } from './helpers/confirm.js';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// The admin CMS enhancement (issue #13): building a page from sections with
// the rich text editor, the media picker, drag-and-drop and its keyboard and
// button alternatives, undo, saved sections, draft / preview / publish, and
// the AI pages on the shared admin design. Runs against the testing database
// (see playwright.config.js) as the seeded super admin.

test.describe.configure({ mode: 'serial' });

const stamp = Date.now();
const title = `E2E Builder Page ${stamp}`;
const slug = `e2e-builder-page-${stamp}`;
const blockName = `E2E saved call to action ${stamp}`;
let editUrl = null;

const sections = (page) => page.locator('#builder-sections > [data-section]');
const sectionNamed = (page, name) => sections(page).filter({ has: page.locator('.pb-section-name', { hasText: new RegExp(`^${name}$`) }) });

async function openSection(section) {
    const toggle = section.locator('[data-section-toggle]');
    if ((await toggle.getAttribute('aria-expanded')) !== 'true') await toggle.click();
    await expect(section.locator('.pb-section-body')).toBeVisible();
}

async function editorIn(section) {
    const editor = section.locator('.rt-editable').first();
    await expect(editor).toBeVisible();
    return editor;
}

async function sectionNames(page) {
    return sections(page).locator('.pb-section-name').allTextContents();
}

/**
 * The edit URL, found rather than inherited.
 *
 * `editUrl` is assigned by the first test and read by the eight after it.
 * When that first test is retried, Playwright runs the retry in a fresh
 * worker, and the tests that follow can find the variable still `null` —
 * which reaches `page.goto` as "expected string, got object", because
 * `typeof null` is `"object"`. That is what took this spec down in a full
 * run while it passed on its own.
 *
 * Looking the page up by its slug costs one navigation and makes each test
 * able to stand by itself.
 */
async function ensureEditUrl(page) {
    if (editUrl) return editUrl;

    await page.goto(`/admin/pages?q=${encodeURIComponent(title)}`);
    await page.getByRole('row', { name: new RegExp(`Builder Page ${stamp}`) })
        .first().getByRole('link', { name: 'Edit' }).click();
    await expect(page).toHaveURL(/\/admin\/pages\/\d+\/edit$/);
    editUrl = page.url();

    return editUrl;
}

test.afterAll(async ({ browser }) => {
    const context = await browser.newContext({ storageState: 'playwright/.auth/admin.json' });
    const page = await context.newPage();
    try {
        await page.goto('/admin/content-blocks');
        const blockRow = page.getByRole('row', { name: new RegExp(blockName) });
        if (await blockRow.count()) {
            await blockRow.getByRole('button', { name: `More actions for ${blockName}` }).click();
            await clickAndConfirm(page, blockRow.getByRole('button', { name: 'Delete' }));
        }
        await page.goto(`/admin/pages?q=${encodeURIComponent(title)}`);
        const rows = page.getByRole('row', { name: new RegExp(`Builder Page ${stamp}`) });
        while (await rows.count()) {
            const row = rows.first();
            await row.getByRole('button', { name: /More actions for/ }).click();
            await clickAndConfirm(page, row.getByRole('button', { name: 'Delete' }));
        }
    } finally {
        await context.close();
    }
});

test('an admin creates a page from a starting layout', async ({ page }) => {
    await page.goto('/admin/pages/create');
    await page.locator('#page-title').fill(title);
    await expect(page.locator('#page-slug')).toHaveValue(slug);
    await page.getByRole('button', { name: 'Create draft and start building' }).click();

    await expect(page).toHaveURL(/\/admin\/pages\/\d+\/edit$/);
    editUrl = page.url();
    await expect(page.getByText('Page created as a draft')).toBeVisible();
    expect(await sectionNames(page)).toEqual(['Page banner', 'Text', 'Text with image on the right', 'Call-to-action banner']);

    // Not on the website yet.
    const response = await page.request.get(`/${slug}`);
    expect(response.status()).toBe(404);
});

test('formatted text is written with the editor toolbar, and bad links are explained', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));

    const text = sectionNamed(page, 'Text');
    await openSection(text);
    const editor = await editorIn(text);
    await editor.click();
    await page.keyboard.type('Documents to bring');
    await page.keyboard.press('Enter');
    await text.getByRole('button', { name: 'Bulleted list' }).click();
    await page.keyboard.press('Control+B');
    await page.keyboard.type('Original passport');
    await page.keyboard.press('Control+B');

    await expect(editor.locator('ul li strong')).toHaveText('Original passport');
    await expect(text.getByRole('button', { name: 'Bold' })).toHaveAttribute('aria-pressed', 'false');
    await expect(text.locator('textarea[name$="[data][content]"]')).toHaveValue(/<li><p><strong>Original passport<\/strong><\/p><\/li>|<li><strong>Original passport<\/strong><\/li>/);

    // A link that is not a real address gets an explanation, not a silent failure.
    await editor.click();
    await page.keyboard.press('Control+K');
    const linkInput = text.getByLabel('Link address');
    await linkInput.fill('www.example.com');
    await text.getByRole('button', { name: 'Add link' }).click();
    await expect(text.getByRole('alert').filter({ hasText: 'Add https:// at the start' })).toBeVisible();
    await text.getByRole('button', { name: 'Cancel' }).click();

    // The words counter.
    await expect(text.locator('[data-rt-count]')).toContainText('words');

    const withImage = sectionNamed(page, 'Text with image on the right');
    await openSection(withImage);
    const imageEditor = await editorIn(withImage);
    await imageEditor.click();
    await page.keyboard.type('Pilgrims gather at Arafat on the ninth of Dhul Hijjah.');

    await page.getByRole('button', { name: 'Save draft' }).click();
    await expect(page.getByText('Draft saved.')).toBeVisible();
    await openSection(sectionNamed(page, 'Text'));
    await expect((await editorIn(sectionNamed(page, 'Text'))).locator('ul li strong')).toHaveText('Original passport');
});

test('an image is uploaded with its description through the media picker', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));
    const section = sectionNamed(page, 'Text with image on the right');
    await openSection(section);

    await section.getByRole('button', { name: 'Choose image' }).click();
    const picker = page.locator('#mediaPickerModal');
    await expect(picker).toBeVisible();
    await picker.getByRole('tab', { name: /Upload a new image/ }).click();
    await picker.locator('#mp-file').setInputFiles(path.join(__dirname, 'fixtures', 'arafat-photo.webp'));
    await picker.getByLabel(/Image description/).fill('Pilgrims on the plain of Arafat');
    await picker.getByRole('button', { name: 'Upload image' }).click();
    await expect(picker).toBeHidden();

    await expect(section.locator('[data-media-preview]')).toBeVisible();
    await expect(section.getByLabel(/Describe the image/)).toHaveValue('Pilgrims on the plain of Arafat');
    await expect(section.getByRole('button', { name: 'Replace image' })).toBeVisible();

    await page.getByRole('button', { name: 'Save draft' }).click();
    await expect(page.getByText('Draft saved.')).toBeVisible();
});

test('sections are added, reordered by button, keyboard and dragging, duplicated, hidden, deleted and restored', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));

    // Add from the library.
    await page.getByRole('button', { name: 'Add a section' }).last().click();
    const library = page.locator('#pbLibraryModal');
    await expect(library).toBeVisible();
    await library.getByLabel('Search sections').fill('questions');
    await library.getByRole('button', { name: /Questions and answers/ }).click();
    await expect(library).toBeHidden();
    await expect(sections(page)).toHaveCount(5);
    expect((await sectionNames(page))[4]).toBe('Questions and answers');

    // Move up with the button.
    await page.getByRole('button', { name: 'Move Questions and answers up' }).click();
    expect((await sectionNames(page))[3]).toBe('Questions and answers');

    // Move up with the keyboard on the handle.
    const handle = sectionNamed(page, 'Questions and answers').locator('[data-drag-handle]');
    await handle.focus();
    await page.keyboard.press('ArrowUp');
    expect((await sectionNames(page))[2]).toBe('Questions and answers');
    await expect(sectionNamed(page, 'Questions and answers').locator('[data-section-position]')).toHaveText('Section 3');

    // Undo the keyboard move.
    await page.getByRole('button', { name: 'Undo' }).click();
    expect((await sectionNames(page))[3]).toBe('Questions and answers');

    // Drag the questions section to the top of the list. Close every section
    // first so the whole list is on screen and nothing scrolls mid-drag.
    await page.getByRole('button', { name: 'Close all' }).click();
    await page.locator('#builder-sections').scrollIntoViewIfNeeded();
    const dragHandle = sectionNamed(page, 'Questions and answers').locator('[data-drag-handle]');
    const target = sections(page).first().locator('[data-drag-handle]');
    await target.scrollIntoViewIfNeeded();
    const from = await dragHandle.boundingBox();
    const to = await target.boundingBox();
    await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
    await page.mouse.down();
    await page.mouse.move(from.x + from.width / 2, from.y - 10, { steps: 5 });
    await page.mouse.move(to.x + to.width / 2, to.y + 2, { steps: 20 });
    await page.mouse.up();
    await expect.poll(async () => (await sectionNames(page))[0]).toBe('Questions and answers');

    // Duplicate the call to action, hide the copy.
    const cta = sectionNamed(page, 'Call-to-action banner');
    await cta.getByRole('button', { name: 'More actions for Call-to-action banner' }).click();
    await cta.getByRole('button', { name: 'Duplicate' }).click();
    await expect(sections(page)).toHaveCount(6);
    // Neither the original nor the copy is left with its actions menu open.
    await expect(page.locator('#builder-sections .dropdown-menu.show')).toHaveCount(0);
    const copy = sectionNamed(page, 'Call-to-action banner').nth(1);
    await copy.getByRole('button', { name: 'Hide Call-to-action banner' }).click();
    await expect(copy.locator('[data-hidden-badge]')).toBeVisible();

    // Delete, undo from the message, delete again.
    const faq = sectionNamed(page, 'Questions and answers');
    await faq.getByRole('button', { name: 'More actions for Questions and answers' }).click();
    await faq.getByRole('button', { name: 'Delete section' }).click();
    const confirm = page.locator('#adminConfirmModal');
    await expect(confirm).toBeVisible();
    await confirm.locator('[data-confirm-accept]').click();
    await expect(sections(page)).toHaveCount(5);
    await page.locator('#admin-toasts').getByRole('button', { name: 'Undo' }).click();
    await expect(sections(page)).toHaveCount(6);
    expect((await sectionNames(page))[0]).toBe('Questions and answers');

    await page.getByRole('button', { name: 'Save draft' }).click();
    await expect(page.getByText('Draft saved.')).toBeVisible();
    expect(await sectionNames(page)).toEqual(['Questions and answers', 'Page banner', 'Text', 'Text with image on the right', 'Call-to-action banner', 'Call-to-action banner']);
    await expect(sectionNamed(page, 'Call-to-action banner').nth(1).locator('[data-hidden-badge]')).toBeVisible();
});

test('publishing stops on a problem, links to it, then publishes the page', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));

    const banner = sectionNamed(page, 'Page banner');
    await openSection(banner);
    await banner.getByRole('textbox', { name: 'Heading', exact: true }).fill('');

    await clickAndConfirm(page, page.getByRole('button', { name: 'Publish', exact: true }));
    const summary = page.locator('[data-validation-summary]');
    await expect(summary).toBeVisible();
    await expect(page.getByText('saved as a draft, but the page was not published')).toBeVisible();
    const problem = summary.getByRole('link', { name: /Section 2 \(Page banner\): heading is empty/ });
    await expect(problem).toBeVisible();

    await problem.click();
    const heading = sectionNamed(page, 'Page banner').getByRole('textbox', { name: 'Heading', exact: true });
    await expect(heading).toBeVisible();
    await expect(heading).toHaveClass(/is-invalid/);
    await heading.fill('Prepare for Hajj 2027');

    await clickAndConfirm(page, page.getByRole('button', { name: 'Publish', exact: true }));
    await expect(page.getByText(`"${title}" is published and visible on the website.`)).toBeVisible();

    await page.goto(`/${slug}`);
    // The questions section was dragged above the banner, so the page opens
    // with the standard title banner (the only h1) and the builder's banner
    // follows as a normal section heading.
    await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);
    await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible();
    await expect(page.getByRole('heading', { level: 2, name: 'Prepare for Hajj 2027' })).toBeVisible();
    await expect(page.locator('.rich-text li strong', { hasText: 'Original passport' })).toBeVisible();
    await expect(page.getByRole('img', { name: 'Pilgrims on the plain of Arafat' })).toBeVisible();
    await expect(page.getByText('Ready to begin your journey?')).toHaveCount(1);
    const order = await page.locator('main section[id^="section-"]').evaluateAll((els) => els.map((el) => el.className));
    expect(order[0]).toContain('pb-section');
});

test('the preview shows the saved draft at desktop, tablet and phone widths', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));
    await page.getByRole('button', { name: 'Save & preview' }).click();

    const preview = page.locator('#pbPreviewModal');
    await expect(preview).toBeVisible();
    const frame = preview.frameLocator('[data-preview-frame]');
    await expect(frame.locator('.admin-preview-banner')).toContainText('this is how the page will look');
    await expect(frame.getByRole('heading', { level: 2, name: 'Prepare for Hajj 2027' })).toBeVisible();

    await preview.getByRole('button', { name: 'Phone' }).click();
    await expect(preview.locator('[data-preview-frame]')).toHaveCSS('width', '390px');
    await preview.getByRole('button', { name: 'Close preview' }).click();
});

test('a section is saved for reuse and inserted into a page as a copy', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));

    const cta = sectionNamed(page, 'Call-to-action banner').first();
    await cta.getByRole('button', { name: 'More actions for Call-to-action banner' }).click();
    await cta.getByRole('button', { name: 'Save for reuse on other pages' }).click();
    const modal = page.locator('#pbSaveBlockModal');
    await expect(modal).toBeVisible();
    await modal.getByLabel(/^Name/).fill(blockName);
    await modal.getByLabel('Group').selectOption('call-to-action');
    await modal.getByRole('button', { name: 'Save section' }).click();
    await expect(page.getByText(`the section was saved as "${blockName}"`)).toBeVisible();

    await page.goto('/admin/content-blocks');
    await expect(page.getByRole('link', { name: blockName, exact: true })).toBeVisible();

    await page.goto(await ensureEditUrl(page));
    const before = await sections(page).count();
    await page.getByRole('button', { name: 'Add a section' }).last().click();
    const library = page.locator('#pbLibraryModal');
    await library.getByRole('tab', { name: /Saved sections/ }).click();
    await library.getByRole('button', { name: `Insert a copy of ${blockName}` }).click();
    await expect(sections(page)).toHaveCount(before + 1);
});

test('the builder and the AI settings fit a phone screen', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });

    for (const url of [editUrl, '/admin/pages', '/admin/ai', '/admin/ai/test', '/admin/content-blocks']) {
        await page.goto(url);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        expect(overflow, `${url} scrolls sideways`).toBeLessThanOrEqual(1);
    }

    await page.goto(await ensureEditUrl(page));
    const first = sections(page).first();
    await expect(first.getByRole('button', { name: /^Move .* down$/ })).toBeVisible();
    const box = await first.locator('[data-drag-handle]').boundingBox();
    expect(box.height).toBeGreaterThanOrEqual(44);
});

test('the AI pages share the admin design and never show the key', async ({ page }) => {
    await page.goto('/admin/ai');
    await expect(page.locator('h1.admin-page-title')).toHaveCount(1);
    await expect(page.getByRole('heading', { name: 'Provider and API key' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Limits and logging' })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'API key', exact: true })).toHaveAttribute('type', 'password');
    expect(await page.content()).not.toContain('sk-e2e-placeholder-not-a-real-key');

    await page.goto('/admin/ai/test');
    await expect(page.locator('h1.admin-page-title')).toHaveText('AI Test Panel');
    await expect(page.locator('.admin-stat-card')).toHaveCount(4);
});

test('keyboard users reach the editor toolbar and every builder control has a name', async ({ page }) => {
    await page.goto(await ensureEditUrl(page));
    const text = sectionNamed(page, 'Text');
    await openSection(text);
    const editor = await editorIn(text);
    await editor.click();
    await page.keyboard.press('Alt+F10');
    await expect(text.locator('.rt-toolbar :focus')).toBeVisible();
    await page.keyboard.press('ArrowRight');
    await expect(text.locator('.rt-toolbar :focus')).toHaveAttribute('aria-label', 'Redo');
    await page.keyboard.press('Escape');
    await expect(editor).toBeFocused();

    const unnamed = await page.locator('#page-builder-form button').evaluateAll((buttons) => buttons
        .filter((b) => b.offsetParent !== null)
        .filter((b) => !(b.getAttribute('aria-label') || b.textContent.trim() || b.getAttribute('title')))
        .map((b) => b.outerHTML.slice(0, 80)));
    expect(unnamed).toEqual([]);
});
