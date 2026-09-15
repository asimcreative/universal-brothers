// Records the admin video training (issue #12).
//
//   node tools/training-video/record.mjs            record every chapter
//   node tools/training-video/record.mjs --until=hotels   stop early (dry run)
//
// What it does, in order:
//   1. Prepares the SEPARATE training-recording database
//      (php artisan training:recording prepare --env=recording) — the command
//      refuses to run against any other database.
//   2. Starts `php artisan serve --env=recording` on port 8130.
//   3. Drives the real admin panel in Chromium at 1280×720, capturing every
//      screen frame, with a visible cursor, chapter title cards and captions.
//   4. Writes work/timeline.json (chapter and caption times) and work/frames/.
//   5. Removes the training packages again (training:recording cleanup).
//
// Then run encode.mjs to cut the chapters into videos. See
// docs/training/ADMIN_VIDEO_GUIDE_STRUCTURE.md.

import { chromium } from 'playwright';
import { spawn, spawnSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { overlayScript } from './overlay.js';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '..', '..');
const WORK = path.join(HERE, 'work');
const PHP = process.env.PHP_BIN || 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';
const PORT = 8130;
const BASE = `http://127.0.0.1:${PORT}`;
const VIEWPORT = { width: 1280, height: 720 };
const EMAIL = 'training-admin@example.test';

const until = (process.argv.find((a) => a.startsWith('--until=')) || '').split('=')[1] || null;
const chaptersMeta = JSON.parse(fs.readFileSync(path.join(HERE, 'chapters.json'), 'utf8'));
const demo = JSON.parse(fs.readFileSync(path.join(ROOT, 'resources/data/training-demo-package.json'), 'utf8'));
const chapterKeys = Object.keys(chaptersMeta);

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// ---------------------------------------------------------------------------
// Setup: database, server, browser, frame capture
// ---------------------------------------------------------------------------
function artisan(args, env = {}) {
    const result = spawnSync(PHP, ['artisan', ...args, '--env=recording'], { cwd: ROOT, env: { ...process.env, ...env }, encoding: 'utf8' });
    process.stdout.write(result.stdout || '');
    if (result.status !== 0) {
        process.stderr.write(result.stderr || '');
        throw new Error(`artisan ${args.join(' ')} failed`);
    }
}

async function waitForServer() {
    for (let i = 0; i < 60; i++) {
        try {
            const res = await fetch(`${BASE}/admin/login`);
            if (res.ok) return;
        } catch (e) { /* not up yet */ }
        await sleep(500);
    }
    throw new Error('The recording server did not start');
}

const password = crypto.randomBytes(24).toString('base64url');
fs.rmSync(WORK, { recursive: true, force: true });
fs.mkdirSync(path.join(WORK, 'frames'), { recursive: true });
fs.mkdirSync(path.join(WORK, 'posters'), { recursive: true });

artisan(['training:recording', 'prepare'], { TRAINING_RECORDER_PASSWORD: password });
const server = spawn(PHP, ['artisan', 'serve', `--port=${PORT}`, '--env=recording'], { cwd: ROOT, stdio: 'ignore' });
await waitForServer();

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1, locale: 'en-GB', timezoneId: 'Asia/Karachi' });
await context.addInitScript(overlayScript);
const page = await context.newPage();
page.setDefaultTimeout(20000);
page.on('dialog', (dialog) => dialog.accept());
page.on('pageerror', (error) => console.warn('[page error]', error.message));

const t0 = Date.now();
const now = () => Date.now() - t0;
const frames = [];
const timeline = { viewport: VIEWPORT, chapters: [] };
let current = null;

const cdp = await context.newCDPSession(page);
cdp.on('Page.screencastFrame', async ({ data, sessionId }) => {
    const index = frames.length;
    fs.writeFileSync(path.join(WORK, 'frames', `${String(index).padStart(6, '0')}.jpg`), Buffer.from(data, 'base64'));
    frames.push(now());
    try { await cdp.send('Page.screencastFrameAck', { sessionId }); } catch (e) { /* page closing */ }
});
await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 92, maxWidth: VIEWPORT.width, maxHeight: VIEWPORT.height, everyNthFrame: 1 });

// ---------------------------------------------------------------------------
// Helpers — human-paced actions with captions
// ---------------------------------------------------------------------------
const overlay = (fn, ...args) => page.evaluate(([name, a]) => { window.__training?.mount(); return window.__training?.[name](...a); }, [fn, args]).catch(() => {});

// Records a caption's start (and closes the previous one) for the WebVTT file.
function logCaption(text) {
    if (!current) return;
    const last = current.captions[current.captions.length - 1];
    if (last && !last.end) last.end = now();
    current.captions.push({ start: now(), text });
}

async function say(text, ms = null) {
    const wait = ms ?? Math.min(8000, Math.max(2600, 1400 + text.length * 48));
    await overlay('caption', text, current ? `Chapter ${current.number} · ${current.title}` : '');
    logCaption(text);
    await sleep(wait);
}

async function clearCaption() {
    await overlay('caption', '');
    const last = current?.captions[current.captions.length - 1];
    if (last && !last.end) last.end = now();
}

async function inView(locator) {
    await locator.waitFor({ state: 'visible' });
    const box = await locator.boundingBox();
    // Keep clear of the sticky top bar and the builder's save bar at the bottom.
    if (!box || box.y < 95 || box.y + Math.min(box.height, 300) > VIEWPORT.height - 110) {
        await locator.evaluate((el) => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        await sleep(750);
    }
    return locator.boundingBox();
}

// The point to aim at: the middle of the element's first line (a link that
// wraps onto two lines has an empty gap in the middle of its box).
async function aim(locator) {
    const box = await inView(locator);
    const first = await locator.evaluate((el) => {
        const r = el.getClientRects()[0];
        return r ? { x: r.x, y: r.y, width: r.width, height: r.height } : null;
    });
    const rect = first && first.width > 0 ? first : box;
    const x = rect.x + Math.min(rect.width / 2, 60);
    const y = rect.y + rect.height / 2;
    return { box, x, y, position: { x: x - box.x, y: y - box.y } };
}

let mouse = { x: VIEWPORT.width / 2, y: VIEWPORT.height / 2 };
async function moveTo(locator) {
    const { x, y } = await aim(locator);
    const steps = Math.min(40, Math.max(8, Math.round(Math.hypot(x - mouse.x, y - mouse.y) / 22)));
    await page.mouse.move(x, y, { steps });
    mouse = { x, y };
    await sleep(180);
}

async function click(locator, { after = 500 } = {}) {
    await moveTo(locator);
    const { position } = await aim(locator);
    await locator.click({ position });
    await sleep(after);
}

// Waits for a real navigation of the page (a form that returns to the same
// address included), then checks where it landed.
async function clickAndWait(locator, urlPattern) {
    await moveTo(locator);
    const { position } = await aim(locator);
    const navigated = page.waitForEvent('framenavigated', { predicate: (frame) => frame === page.mainFrame(), timeout: 30000 });
    await locator.click({ position });
    await navigated;
    await page.waitForLoadState('load');
    await page.waitForLoadState('networkidle').catch(() => {});
    if (urlPattern && !urlPattern.test(page.url())) throw new Error(`Expected ${urlPattern} but landed on ${page.url()}`);
    await sleep(700);
}

async function type(locator, text, { delay = 55 } = {}) {
    await click(locator, { after: 150 });
    if ((await locator.inputValue()) !== '') {
        await locator.press('Control+A');
        await locator.press('Delete');
    }
    await locator.pressSequentially(String(text), { delay });
    await sleep(350);
}

async function choose(locator, label) {
    await moveTo(locator);
    await locator.selectOption({ label });
    await sleep(600);
}

async function spot(locator, text = null, ms = null) {
    const box = await inView(locator);
    await overlay('spot', { x: box.x, y: box.y, width: box.width, height: Math.min(box.height, VIEWPORT.height - box.y - 10) });
    if (text) await say(text, ms);
    else await sleep(ms ?? 1200);
}

const unspot = () => overlay('spot', null);

async function scrollBy(pixels, pause = 900) {
    await page.evaluate((y) => window.scrollBy({ top: y, behavior: 'smooth' }), pixels);
    await sleep(pause);
}

async function confirmDialog(pause = 3200) {
    const modal = page.locator('#adminConfirmModal');
    await modal.waitFor({ state: 'visible' });
    await sleep(pause);
    await click(modal.locator('[data-confirm-accept]'), { after: 300 });
}

const step = (key) => page.locator(`#builder-step-list [data-step-button="${key}"]`);
async function openStep(key) {
    await click(step(key), { after: 700 });
    await page.locator(`#step-${key}`).waitFor({ state: 'visible' });
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
    await sleep(600);
}

async function chapter(key, body) {
    const meta = chaptersMeta[key];
    current = { key, number: meta.number, title: meta.title, start: now(), captions: [] };
    await unspot();
    await overlay('caption', '');
    await overlay('card', { ...meta, total: chapterKeys.length });
    await sleep(900);
    await page.screenshot({ path: path.join(WORK, 'posters', `${String(meta.number).padStart(2, '0')}-${key}.jpg`), type: 'jpeg', quality: 80 });
    await sleep(3600);
    await overlay('hideCard');
    await sleep(500);

    await body();

    await clearCaption();
    await unspot();
    await sleep(900);
    current.end = now();
    for (const c of current.captions) c.end ??= current.end;
    timeline.chapters.push(current);
    console.log(`recorded ${meta.number}. ${meta.title} (${Math.round((current.end - current.start) / 1000)} s)`);
    current = null;
    return key === until;
}

// ---------------------------------------------------------------------------
// The chapters
// ---------------------------------------------------------------------------
const b = demo.basics;
const photo = (p) => path.join(ROOT, p);
const pickerLabel = (text) => page.locator('#libraryPickerModal [data-picker-list] label').filter({ has: page.locator('strong', { hasText: text }) });

const script = {
    async welcome() {
        await page.goto(`${BASE}/admin/login`);
        return chapter('welcome', async () => {
            await say('This training builds one complete Hajj package in the real admin panel — from signing in to the published page.');
            await say('Open the website address followed by /admin. The admin sign-in page opens.');
            await say('Type your email address in "Email".', 1800);
            await type(page.locator('#login-email'), EMAIL, { delay: 35 });
            await say('Type your password. It is shown as dots.', 1800);
            await type(page.locator('#login-password'), password, { delay: 20 });
            await say('Press "Login".', 1400);
            await clickAndWait(page.locator('#login-submit'), /\/admin\/?$/);
            await say('The dashboard opens. You are signed in.');
        });
    },

    async dashboard() {
        return chapter('dashboard', async () => {
            await spot(page.locator('[data-onboarding-panel]'), 'On a first visit, a welcome panel offers a short guided tour. You can take it at any time from the account menu.');
            await say('Press "Skip for now" to hide it.', 1800);
            await clickAndWait(page.locator('[data-onboarding-panel] button', { hasText: 'Skip for now' }), /\/admin\/?$/);
            await spot(page.locator('[data-tour="dashboard"]'), 'These four cards count your Hajj packages: total, published, drafts and featured. Each card opens its list.');
            await unspot();
            await spot(page.locator('.admin-section-heading', { hasText: 'Enquiries and content' }).locator('xpath=following-sibling::div[1]'), 'The "Enquiries and content" cards count new enquiries, FAQs, awards and more.');
            await unspot();
            await say('"Needs attention" and "Unfinished drafts" appear here only when something is waiting for you. Today nothing is.');
            await spot(page.locator('.card', { hasText: 'Recent package updates' }).first(), '"Recent package updates" lists the packages changed most recently.');
            await unspot();
            await spot(page.locator('.card', { hasText: 'Quick actions' }).first(), '"Quick actions" open the most common jobs.');
            await unspot();
        });
    },

    async navigation() {
        const nav = page.locator('aside.admin-sidebar');
        return chapter('navigation', async () => {
            await page.evaluate(() => window.scrollTo({ top: 0 }));
            await spot(nav.locator('[data-tour="sidebar"]'), 'The menu on the left is grouped: Hajj Packages, Reusable Content, Umrah & Tourism, Enquiries, Website Content, Company and Help.');
            await unspot();
            await moveTo(nav.locator('[data-tour="nav-settings"]'));
            await spot(nav.locator('[data-tour="nav-settings"]'), 'Company → Site Settings holds the company details shown across the website.', 3200);
            await unspot();
            await moveTo(nav.locator('a', { hasText: 'Video Training' }));
            await spot(nav.locator('.admin-nav-group').last(), 'Under Help you will find the Admin Guide and this Video Training.', 3200);
            await unspot();
            await nav.locator('.admin-nav-scroll').evaluate((el) => el.scrollTo({ top: 0, behavior: 'smooth' }));
            await sleep(700);
            await say('Open Hajj Packages → Hajj Packages.', 1800);
            await clickAndWait(nav.locator('a.admin-nav-link', { hasText: /^\s*Hajj Packages\s*$/ }), /\/admin\/hajj-packages$/);
            await spot(page.locator('.admin-tabs'), 'The tabs show All, Published, Drafts and Archived, each with a count.');
            await unspot();
            await say('Type a package code in "Search by title or code" and press Search.', 2400);
            await type(page.locator('#filter-q'), 'UB015', { delay: 90 });
            await clickAndWait(page.locator('.admin-toolbar button', { hasText: 'Search' }), /q=UB015/);
            await spot(page.getByRole('row', { name: /UB015/ }).first(), 'Each row shows the code, days, arrival, the "From" price, the status, how complete it is, and Edit.');
            await unspot();
            await clickAndWait(page.getByRole('link', { name: 'Clear filters' }), /\/admin\/hajj-packages$/);
        });
    },

    async 'new-package'() {
        return chapter('new-package', async () => {
            await say('To start a new package, press "Add Hajj Package".', 2200);
            await clickAndWait(page.locator('.admin-topbar a', { hasText: 'Add Hajj Package' }), /hajj-packages\/create/);
            await spot(page.locator('#builder-step-list'), 'The builder has 14 steps. A green tick means a step has what it needs.');
            await unspot();
            await spot(page.locator('.builder-progress'), 'The percentage and the checklist show how complete the package is.', 3000);
            await unspot();
            await spot(page.locator('#step-basics .watch-guide-link'), 'Every step links to its own video…', 2600);
            await click(page.locator('#step-basics details.need-help summary'), { after: 400 });
            await spot(page.locator('#step-basics details.need-help'), '…and "Need help with this step?" explains what to enter, with an example.');
            await click(page.locator('#step-basics details.need-help summary'), { after: 300 });
            await unspot();
            await spot(page.locator('.builder-actionbar'), 'The bar at the bottom saves your work. Nothing appears on the website until you publish.');
            await unspot();
        });
    },

    async 'basic-information'() {
        return chapter('basic-information', async () => {
            await say('Step 1 — Basic information. Type the package title.', 2000);
            await type(page.locator('#pkg-name'), b.name, { delay: 45 });
            await say('Type the office code. Every package needs a different code. This is the training copy of UB015.', 3000);
            await type(page.locator('#pkg-code'), b.code, { delay: 110 });
            await say('Type the tier and choose the package series.', 2000);
            await type(page.locator('#pkg-type'), b.package_type, { delay: 55 });
            await choose(page.locator('#pkg-series'), b.series);
            await say('Write one or two sentences for the short description.', 2000);
            await type(page.locator('#pkg-summary'), b.summary, { delay: 25 });
            await say('Type the number of days. "Length as shown" fills in by itself…', 2400);
            await type(page.locator('#pkg-days'), b.duration_days, { delay: 150 });
            await spot(page.locator('#pkg-duration-label'), '…change it to the wording the brochure uses.', 2200);
            await unspot();
            await type(page.locator('#pkg-duration-label'), b.duration_label, { delay: 60 });
            await say('Type the Hajj year and the season as the brochure shows it.', 2200);
            await type(page.locator('#pkg-year'), b.season_year, { delay: 120 });
            await type(page.locator('#pkg-season-label'), b.season_label, { delay: 50 });
            await page.locator('[data-step-button="basics"].is-done').waitFor();
            await spot(step('basics'), 'Step 1 now has a green tick.', 2200);
            await unspot();
        });
    },

    async 'package-settings'() {
        return chapter('package-settings', async () => {
            await say('Step 2 — Package setup.', 1600);
            await openStep('setup');
            await say('This package arrives in Madinah first.', 1800);
            await click(page.locator('#step-setup label.choice-card').filter({ has: page.locator('strong', { hasText: demo.setup.arrival }) }));
            await say('Pilgrims move between hotels and Aziziya during their stay, so it is "Shifting".', 2600);
            await click(page.locator('#step-setup label.choice-card').filter({ has: page.locator('strong', { hasText: /^Shifting$/ }) }));
            await say('Aziziya accommodation is included in the price.', 2000);
            await click(page.locator('#step-setup label.choice-card').filter({ has: page.locator('strong', { hasText: /^Included$/ }) }));
            await spot(page.locator('#step-setup .form-help').last(), 'Choosing "Included" adds the Aziziya card to the Hotels step.', 2600);
            await unspot();
        });
    },

    async 'package-options'() {
        return chapter('package-options', async () => {
            await say('Step 3 — Hotel options. In this package customers choose between two Madinah hotels.', 2800);
            await openStep('options');
            await click(page.locator('#step-options label.choice-card', { hasText: 'Yes — customers choose an option' }), { after: 900 });
            await say('Option A and Option B are added. Give each a name customers recognise — usually its hotel.', 2800);
            const labels = page.locator('#step-options input[name$="[label]"]');
            await type(labels.nth(0), demo.options[0].label);
            await type(labels.nth(1), demo.options[1].label);
            await spot(page.locator('[data-options-card-summary]'), 'These cards show what each option still needs: prices and a hotel of its own.');
            await unspot();
        });
    },

    async hotels() {
        return chapter('hotels', async () => {
            await say('Step 5 — Hotels & accommodation.', 1600);
            await openStep('hotels');
            await spot(page.locator('#step-hotels .shared-explainer'), 'A saved hotel is shared by many packages. Anything you change in a row changes this package only.');
            await unspot();
            const box = (name) => (name === 'shared'
                ? page.locator('[data-option-group="accommodations"][data-option-uid="shared"]')
                : page.locator(`[data-option-group="accommodations"][data-option-code="${name}"]`));
            for (const hotel of demo.hotels) {
                const where = hotel.box === 'shared' ? 'the box for every option' : `the Option ${hotel.box} box`;
                await say(`In ${where}, press "Add hotel", choose the city and pick ${hotel.hotel}.`, 2600);
                await click(box(hotel.box).getByRole('button', { name: 'Add hotel' }), { after: 600 });
                const row = box(hotel.box).locator('[data-row="accommodations"]').last();
                await moveTo(row.locator('[data-field="location"]'));
                await row.locator('[data-field="location"]').selectOption(hotel.city);
                await sleep(500);
                await choose(row.locator('[data-hotel-picker]'), hotel.hotel);
                await type(row.locator('[data-field="nights"]'), hotel.nights, { delay: 120 });
                if (hotel.meal_plan) {
                    await say('The Aziziya building has its own meal plan.', 1800);
                    await choose(row.locator('[data-meal-picker]'), hotel.meal_plan);
                }
            }
            await spot(page.locator('[data-row="accommodations"] [data-lib-badge]').first(), 'The badge shows this is saved content, and how many packages use it.', 3000);
            await unspot();
            const az = demo.aziziya;
            await say('The Aziziya card appears because Aziziya is included. Fill in its details.', 2600);
            await type(page.locator('#az-name'), az.accommodation_name, { delay: 30 });
            await type(page.locator('#az-location'), az.location_note, { delay: 25 });
            await type(page.locator('#az-walk'), az.walk_distance, { delay: 25 });
            await type(page.locator('#az-days'), az.duration_days, { delay: 120 });
            await type(page.locator('#az-occupancy'), az.average_occupancy, { delay: 120 });
        });
    },

    async 'room-types'() {
        return chapter('room-types', async () => {
            await say('Step 4 — Room prices. Each option has its own coloured box.', 2200);
            await openStep('pricing');
            for (const code of ['A', 'B']) {
                const group = page.locator(`[data-option-group="room_options"][data-option-code="${code}"]`);
                await say(`In the Option ${code} box, press "Add Quad, Triple & Double".`, 2200);
                await click(group.getByRole('button', { name: /Add Quad, Triple/ }), { after: 900 });
            }
            await spot(page.locator('#step-pricing .price-hint'), 'A Kaaba-view room or an extra night is not a room type. Those go in Additional options.', 3400);
            await unspot();
        });
    },

    async prices() {
        return chapter('prices', async () => {
            await say('Type every price exactly as the brochure prints it — numbers only, no commas.', 2600);
            for (const code of ['A', 'B']) {
                const rows = page.locator(`[data-option-group="room_options"][data-option-code="${code}"] [data-row="room_options"]`);
                for (let i = 0; i < demo.rooms[code].length; i++) {
                    const room = demo.rooms[code][i];
                    const money = (n) => Number(n).toLocaleString('en-US');
                    await overlay('caption', `Option ${code} — ${room.type}: USD ${money(room.usd)} · SAR ${money(room.sar)} · PKR ${money(room.pkr)}`, `Chapter ${current.number} · ${current.title}`);
                    logCaption(`Option ${code} — ${room.type}: USD ${money(room.usd)}, SAR ${money(room.sar)}, PKR ${money(room.pkr)}`);
                    const row = rows.nth(i);
                    await type(row.locator('[data-field="price_usd"]'), room.usd, { delay: 85 });
                    await type(row.locator('[data-field="price_sar"]'), room.sar, { delay: 85 });
                    await type(row.locator('[data-field="price_pkr"]'), room.pkr, { delay: 70 });
                }
            }
            await page.locator('[data-step-button="pricing"].is-done').waitFor();
            await spot(page.locator('[data-price-summary]'), 'Check the price summary against the brochure. The cheapest available USD price, USD 11,100, becomes the "From" price.', 5200);
            await unspot();
        });
    },

    async itinerary() {
        return chapter('itinerary', async () => {
            await say('Step 6 — Journey plan. The approved 14-day plan already exists in UB015, so copy it.', 2800);
            await openStep('journey');
            await click(page.locator('#step-journey').getByRole('button', { name: /Copy from another package/ }), { after: 800 });
            const modal = page.locator('#copyFromModal');
            await modal.waitFor({ state: 'visible' });
            const ub015 = await modal.locator('[data-copy-package] option', { hasText: 'UB015 —' }).first().getAttribute('value');
            await say('Choose UB015. "Journey plan" is already ticked.', 2200);
            await moveTo(modal.locator('[data-copy-package]'));
            await modal.locator('[data-copy-package]').selectOption(ub015);
            await sleep(900);
            await click(modal.getByRole('button', { name: 'Copy into this form' }), { after: 400 });
            await say('Before anything changes, the confirmation says exactly what will be copied.', 1000);
            await confirmDialog(3600);
            await page.locator('[data-row="itinerary"]').nth(13).waitFor();
            await spot(page.locator('[data-row="itinerary"]').first(), 'Check each day: the English date, the Islamic date, the place and where pilgrims stay.', 3800);
            await unspot();
            await scrollBy(420, 1400);
            await scrollBy(420, 1400);
            const day4 = page.locator('[data-row="itinerary"]').nth(demo.journey.transport_day - 1);
            await spot(day4, 'Day 4 is a travel day — to Makkah. Add the transport.', 2600);
            await unspot();
            await type(day4.locator('[data-field="transport"]'), demo.journey.transport, { delay: 40 });
            await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
            await sleep(700);
            await spot(page.locator('#step-journey .builder-toolbar').last(), '"Fill dates" sets every date from the first day. "Renumber" numbers the days again.', 3200);
            await unspot();
        });
    },

    async mashaer() {
        return chapter('mashaer', async () => {
            await say('Step 7 — Mina, Arafat & Muzdalifah.', 1800);
            await openStep('mashaer');
            await say('In the Mina card, choose the saved arrangement. The boxes fill in.', 2400);
            await choose(page.locator('#mashaer-mina-pick'), demo.mashaer.mina);
            await spot(page.locator('[data-mashaer-shared="mina"]'), 'This is shared information used by other packages. Changes here apply to this package only.', 3800);
            await unspot();
            await say('Do the same for Arafat.', 1600);
            await choose(page.locator('#mashaer-arafat-pick'), demo.mashaer.arafat);
            await spot(page.locator('[data-mashaer-card="muzdalifah"] header'), 'Leave Muzdalifah empty — this brochure describes it inside the Arafat card.', 3200);
            await unspot();
        });
    },

    async 'transport-meals'() {
        return chapter('transport-meals', async () => {
            await say('Step 8 — Transport & meals.', 1600);
            await openStep('transport');
            await say('Press "Add saved transport" and tick the lines this package offers.', 2200);
            await click(page.getByRole('button', { name: 'Add saved transport' }), { after: 800 });
            for (const name of demo.transport_saved) {
                await click(pickerLabel(name).first(), { after: 350 });
            }
            await sleep(700);
            await click(page.locator('#libraryPickerModal').getByRole('button', { name: 'Add selected' }), { after: 900 });
            await spot(page.locator('[data-row="transportation"]').nth(3), 'Paid extras arrive with "In price" switched off and their price — here USD 165 per person.', 3800);
            await unspot();
            await say('Meals: choose one meal plan for every Makkah and Madinah hotel, and press "Apply to all".', 2800);
            await choose(page.locator('[data-meal-all]'), demo.meal_plan_all);
            await click(page.locator('[data-apply-meal-all]'), { after: 800 });
            await spot(page.locator('[data-meal-summary]'), 'Every hotel now shows its meal plan. The Aziziya building keeps its full-board plan.', 3600);
            await unspot();
        });
    },

    async inclusions() {
        return chapter('inclusions', async () => {
            await say('Step 9 — Included & not included. Two separate panels: green and red.', 2400);
            await openStep('services');
            await say('Copy the approved "Included" list from UB015.', 2000);
            await click(page.locator('[data-open-copy="inclusions"]'), { after: 700 });
            const modal = page.locator('#copyFromModal');
            await modal.waitFor({ state: 'visible' });
            const ub015 = await modal.locator('[data-copy-package] option', { hasText: 'UB015 —' }).first().getAttribute('value');
            await moveTo(modal.locator('[data-copy-package]'));
            await modal.locator('[data-copy-package]').selectOption(ub015);
            await sleep(700);
            await click(modal.getByRole('button', { name: 'Copy into this form' }), { after: 400 });
            await confirmDialog(3000);
            await page.locator('[data-row="inclusions"]').nth(demo.inclusions.count - 1).waitFor();
            await spot(page.locator('#step-services .service-panel.is-included'), `${demo.inclusions.count} included lines are now in the package.`, 2600);
            await unspot();
            await say('"Add saved" marks lines already in the package, so nothing can be added twice.', 2000);
            await click(page.locator('[data-open-picker="inclusions"]'), { after: 900 });
            await spot(page.locator('#libraryPickerModal [data-picker-list] label.is-added').first(), 'Greyed out and marked "Added".', 2600);
            await unspot();
            await click(page.locator('#libraryPickerModal .modal-footer').getByRole('button', { name: 'Cancel' }), { after: 600 });
        });
    },

    async exclusions() {
        return chapter('exclusions', async () => {
            await say('In "Not included in this package", press "Add saved".', 2000);
            await click(page.locator('[data-open-picker="exclusions"]'), { after: 800 });
            for (const name of demo.exclusions_saved) {
                await click(pickerLabel(name).first(), { after: 400 });
            }
            await click(page.locator('#libraryPickerModal').getByRole('button', { name: 'Add selected' }), { after: 900 });
            await spot(page.locator('#step-services .service-panel.is-excluded'), 'The ticket and the Qurbani cost are listed as not included.', 3000);
            await unspot();
        });
    },

    async upgrades() {
        return chapter('upgrades', async () => {
            await say('Step 10 — Additional options: extras customers may choose at extra cost.', 2400);
            await openStep('extras');
            await click(page.getByRole('button', { name: 'Add saved option' }), { after: 800 });
            await say('Two saved upgrades share the name "Kaba View Supplement". Pick the one at USD 1,050 — the price under the name tells them apart.', 3800);
            const kaaba = page.locator('#libraryPickerModal [data-picker-list] label').filter({ hasText: 'Kaba View Supplement' }).filter({ hasText: '1,050' });
            await click(kaaba.first(), { after: 500 });
            for (const upgrade of demo.upgrades_saved.slice(1)) {
                await click(pickerLabel(upgrade.name).first(), { after: 400 });
            }
            await click(page.locator('#libraryPickerModal').getByRole('button', { name: 'Add selected' }), { after: 900 });
            await spot(page.locator('[data-rows="upgrades"]'), 'Four additional options. An empty price shows as "on request".', 3000);
            await unspot();
        });
    },

    async notes() {
        return chapter('notes', async () => {
            await say('Step 11 — Notes & policies.', 1600);
            await openStep('notes');
            await spot(page.locator('.note-kinds'), 'There are four kinds of notes. Only the internal admin note is private.', 3400);
            await unspot();
            await say('Press "Add saved note" and tick the notes this package uses.', 2200);
            await click(page.getByRole('button', { name: 'Add saved note' }), { after: 800 });
            for (const text of demo.notes_saved) {
                await click(page.locator('#libraryPickerModal [data-picker-list] label').filter({ hasText: text }).first(), { after: 380 });
            }
            await click(page.locator('#libraryPickerModal').getByRole('button', { name: 'Add selected' }), { after: 900 });
            await say('Press "Write a note" for a note that belongs to this package only.', 2200);
            await click(page.getByRole('button', { name: 'Write a note' }), { after: 700 });
            const row = page.locator('[data-row="notes"]').last();
            await choose(row.locator('[data-field="note_type"]'), demo.package_note.kind);
            await type(row.locator('[data-field="content"]'), demo.package_note.content, { delay: 30 });
            await spot(page.locator('.internal-note-card'), 'Internal admin notes are never shown to website visitors. Use them for office reminders.', 3600);
            await unspot();
            await type(page.locator('#pkg-internal-notes'), demo.internal_note, { delay: 22 });
        });
    },

    async images() {
        return chapter('images', async () => {
            await say('Step 12 — Photos & search engines.', 1600);
            await openStep('media');
            await say('In "Main photo", choose a wide photograph from your computer.', 2400);
            await moveTo(page.locator('#pkg-cover_image'));
            await page.locator('#pkg-cover_image').setInputFiles(photo(demo.images.main_photo));
            await sleep(900);
            await spot(page.locator('#pkg-cover_image').locator('xpath=ancestor::div[@data-image-field]'), 'A preview appears. It is uploaded when you save.', 2600);
            await unspot();
            await say('Press "Add photo" for a gallery photo, and describe it.', 2200);
            await click(page.getByRole('button', { name: 'Add photo' }), { after: 700 });
            const row = page.locator('[data-row="media"]').last();
            await moveTo(row.locator('input[type="file"]'));
            await row.locator('input[type="file"]').setInputFiles(photo(demo.images.gallery_photo));
            await sleep(700);
            await type(row.locator('[data-field="alt_text"]'), demo.images.gallery_description, { delay: 35 });
        });
    },

    async seo() {
        return chapter('seo', async () => {
            await say('Search engines: the title and description Google shows.', 2000);
            await type(page.locator('#pkg-meta-title'), demo.seo.meta_title, { delay: 30 });
            await type(page.locator('#pkg-meta-description'), demo.seo.meta_description, { delay: 18 });
            await spot(page.locator('.seo-preview'), 'The preview shows how the result will look. Leave "Web address" empty to make it from the title.', 3600);
            await unspot();
            await page.locator('[data-step-button="media"].is-done').waitFor();
        });
    },

    async review() {
        return chapter('review', async () => {
            await say('Step 13 — Review everything. The whole package on one page.', 2200);
            await openStep('review');
            await page.locator('#step-review .review-status').waitFor();
            await sleep(1200);
            await spot(page.locator('#step-review .review-status'), 'Red problems must be fixed before publishing. Here nothing blocks publishing.', 3400);
            await unspot();
            await spot(page.locator('#step-review .review-checklist'), 'The checklist: every item is ticked. "Final review completed" ticks while you look at this step.', 4000);
            await unspot();
            for (let i = 0; i < 6; i++) await scrollBy(520, 1500);
            await say('Read each section against the brochure. "Edit" beside a section opens its step.', 2800);
            await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
            await sleep(900);
        });
    },

    async 'save-draft'() {
        return chapter('save-draft', async () => {
            await say('Press "Save draft" in the bar at the bottom.', 2000);
            await clickAndWait(page.locator('.builder-actionbar button[value="draft"]'), /\/admin\/hajj-packages\/\d+\/edit/);
            await spot(page.getByRole('status').filter({ hasText: 'Draft saved' }), 'Draft saved. It is not visible on the website, and the photos are uploaded.', 3200);
            await unspot();
            await say('Open Hajj Packages: the draft shows how complete it is, with a Continue button.', 2400);
            await clickAndWait(page.locator('aside.admin-sidebar a.admin-nav-link', { hasText: /^\s*Hajj Packages\s*$/ }), /\/admin\/hajj-packages$/);
            const row = page.getByRole('row', { name: new RegExp(b.code) }).first();
            const percent = (await row.locator('.completion-inline small').innerText()).trim();
            await spot(row, `Draft, ${percent}, with a "Continue" button.`, 3000);
            await unspot();
            await clickAndWait(row.getByRole('link', { name: /Continue/ }), /\/edit/);
            await page.locator('#step-review').waitFor({ state: 'visible' });
            await spot(step('review'), 'It reopens on the step where you saved — Review everything.', 3000);
            await unspot();
        });
    },

    async preview() {
        return chapter('preview', async () => {
            await say('Press "Save & preview" to see the page exactly as a visitor would.', 2200);
            await clickAndWait(page.locator('.builder-actionbar button[value="preview"]'), /\/preview/);
            await spot(page.locator('.admin-preview-banner'), 'The yellow bar means this is a preview. Only signed-in administrators can open it.', 3400);
            await unspot();
            await scrollBy(560, 1600);
            await say('Check the options and prices. Switch the currency and compare with the brochure.', 2400);
            const switcher = page.locator('#currency-switcher');
            await inView(switcher);
            await click(switcher.locator('[data-currency="SAR"]'), { after: 1800 });
            await click(switcher.locator('[data-currency="PKR"]'), { after: 1800 });
            await click(switcher.locator('[data-currency="USD"]'), { after: 1200 });
            for (let i = 0; i < 4; i++) await scrollBy(600, 1500);
            await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
            await sleep(900);
            await say('Press "Back to editing" to return to the builder.', 1800);
            await clickAndWait(page.locator('.admin-preview-banner a', { hasText: 'Back to editing' }), /\/edit/);
        });
    },

    async validation() {
        return chapter('validation', async () => {
            await say('A common mistake: the package code has been left empty.', 2200);
            await openStep('basics');
            const code = page.locator('#pkg-code');
            await click(code, { after: 200 });
            await code.press('Control+A');
            await code.press('Delete');
            await sleep(1600);
            await openStep('publish');
            await page.locator('#step-publish [data-publish-blockers]:not([hidden]) a', { hasText: 'package code' }).waitFor();
            await sleep(800);
            await spot(page.locator('#step-publish .finish-card').last(), '"Publish package" is greyed out, and "Fix these first" says what is missing.', 3800);
            await unspot();
            await say('Press the link to go straight to the step that fixes it.', 2000);
            await click(page.locator('#step-publish [data-publish-blockers] a').first(), { after: 900 });
            await type(code, b.code, { delay: 110 });
            await openStep('review');
            await page.locator('#step-review .review-status.is-ready').waitFor();
            await spot(page.locator('#step-review .review-status'), 'Fixed: nothing blocks publishing again.', 2800);
            await unspot();
        });
    },

    async publish() {
        return chapter('publish', async () => {
            await say('Step 14 — Save, preview & publish.', 1800);
            await openStep('publish');
            await spot(page.locator('#step-publish .finish-cards'), 'Three ways to finish: save a draft, preview, or publish.', 3000);
            await unspot();
            await click(page.locator('#step-publish').getByRole('button', { name: 'Publish package' }), { after: 400 });
            await say('Read the question, then press "Publish now".', 1000);
            await confirmDialog(2600);
            await page.getByRole('status').filter({ hasText: 'published and live' }).waitFor({ timeout: 30000 });
            await page.waitForLoadState('networkidle').catch(() => {});
            await spot(page.getByRole('status').filter({ hasText: 'published and live' }), 'The package is published and live on the website.', 3200);
            await unspot();
            await spot(page.locator('.status-pill-success').first(), 'To hide it later, use More → Move to draft.', 2600);
            await unspot();
        });
    },

    async 'public-page'() {
        return chapter('public-page', async () => {
            await say('Open More → View on website. (It opens in a new tab.)', 2200);
            await click(page.locator('.admin-topbar').getByRole('button', { name: 'More' }), { after: 700 });
            const view = page.locator('.dropdown-menu a', { hasText: 'View on website' });
            await moveTo(view);
            await sleep(700);
            const href = await view.getAttribute('href');
            await page.goto(href);
            await page.waitForLoadState('networkidle').catch(() => {});
            await sleep(800);
            const text = await page.locator('body').innerText();
            if (text.includes(demo.internal_note)) throw new Error('Privacy check failed: the internal note is on the public page');
            await say('This is the live page customers see: the title, the "From" price and the options.', 2800);
            await scrollBy(560, 1500);
            const switcher = page.locator('#currency-switcher');
            await inView(switcher);
            await say('Switch the prices to PKR and check them.', 1800);
            await click(switcher.locator('[data-currency="PKR"]'), { after: 2200 });
            for (let i = 0; i < 3; i++) await scrollBy(620, 1500);
            await say('The journey plan, what is included, and the notes follow.', 2200);
            for (let i = 0; i < 4; i++) await scrollBy(620, 1400);
            await say('The internal admin note does not appear anywhere on this page.', 2800);
            await click(switcher.locator('[data-currency="USD"]').first(), { after: 300 }).catch(() => {});
        });
    },

    async 'edit-package'() {
        return chapter('edit-package', async () => {
            await say('Back in the admin, open Hajj Packages and search for the code.', 2200);
            await page.goto(`${BASE}/admin/hajj-packages`);
            await type(page.locator('#filter-q'), b.code, { delay: 100 });
            await clickAndWait(page.locator('.admin-toolbar button', { hasText: 'Search' }), new RegExp(`q=${b.code}`));
            await clickAndWait(page.getByRole('row', { name: new RegExp(b.code) }).first().getByRole('link', { name: 'Edit' }), /\/edit/);
            await say('A live package opens on Step 1. The office decides to feature this package.', 2600);
            await click(page.locator('label[for="pkg-featured"]'), { after: 700 });
            await say('Press "Save changes".', 1600);
            await clickAndWait(page.locator('.builder-actionbar button[value="save"]'), /\/edit/);
            await spot(page.getByRole('status').filter({ hasText: 'live on the website' }), 'Changes to a live package appear on the website straight away.', 3000);
            await unspot();
            await page.goto(`${BASE}/admin/hajj-packages?q=${b.code}`);
            await sleep(600);
            await spot(page.getByRole('row', { name: new RegExp(b.code) }).first().locator('.star-toggle'), 'The star shows it is featured.', 2400);
            await unspot();
        });
    },

    async duplicate() {
        return chapter('duplicate', async () => {
            const row = page.getByRole('row', { name: new RegExp(b.code) }).first();
            await say('To start a similar package, open the "…" menu and press Duplicate.', 2400);
            await click(row.getByRole('button', { name: /More actions/ }), { after: 700 });
            await click(row.getByRole('button', { name: 'Duplicate' }), { after: 300 });
            await confirmDialog(3000);
            await page.getByRole('status').filter({ hasText: 'A copy was created' }).waitFor({ timeout: 30000 });
            await page.waitForLoadState('networkidle').catch(() => {});
            await spot(page.getByRole('status').filter({ hasText: 'A copy was created' }), 'A draft copy was created. The original is not changed.', 3000);
            await unspot();
            await spot(page.locator('#pkg-code'), 'The copy gets its own code, and "(Copy)" in its title.', 2800);
            await unspot();
            await say('Change the title, code, dates and prices for the new package before publishing it.', 2800);
        });
    },

    async 'final-checklist'() {
        return chapter('final-checklist', async () => {
            await say('Open Help → Admin Guide and press "Package checklist".', 2200);
            await clickAndWait(page.locator('aside.admin-sidebar a.admin-nav-link', { hasText: 'Admin Guide' }), /\/admin\/guide$/);
            await clickAndWait(page.locator('.guide-hero').getByRole('link', { name: 'Package checklist' }), /checklist$/);
            await spot(page.locator('.admin-topbar').getByRole('button', { name: 'Print checklist' }), 'Print it, or download it to keep a copy.', 2600);
            await unspot();
            const boxes = page.locator('.training-checklist input[type="checkbox"]');
            for (let i = 0; i < 4; i++) await click(boxes.nth(i), { after: 350 });
            await say('Tick each item while you build a package.', 2000);
            await say('Save drafts often. Never guess a price — leave it empty.', 2600);
            await say('Preview before publishing. Keep private information in Internal admin notes.', 2800);
        });
    },

    async 'reusable-information'() {
        return chapter('reusable-information', async () => {
            await say('Reusable Content holds information saved once and shared by many packages.', 2400);
            await clickAndWait(page.locator('aside.admin-sidebar a.admin-nav-link', { hasText: 'Hotels & Accommodation' }), /library\/hotels/);
            const row = page.getByRole('row', { name: /Dar Al Taqwa/ }).filter({ hasNotText: 'Hilton' }).first();
            await spot(row, 'Each hotel shows how many packages use it.', 2600);
            await unspot();
            await clickAndWait(row.getByRole('link', { name: 'Edit' }), /\/edit/);
            await spot(page.locator('#shared-record-note'), 'Editing shared information shows how many packages use it.', 3000);
            await unspot();
            await spot(page.getByRole('button', { name: 'Update shared record' }), '"Update shared record" changes the saved hotel. Packages keep their details until you press "Update packages".', 4200);
            await unspot();
            await spot(page.getByRole('button', { name: 'Save as a new separate record' }), '"Save as a new separate record" leaves the original and its packages exactly as they are.', 3800);
            await unspot();
            await say('Nothing needs changing, so press Cancel.', 1800);
            await clickAndWait(page.locator('.admin-card-footer').getByRole('link', { name: 'Cancel' }), /library\/hotels$/);
        });
    },

    async templates() {
        return chapter('templates', async () => {
            await say('To keep a finished package as a starting point, save it as a template.', 2400);
            await page.goto(`${BASE}/admin/hajj-packages?q=${b.code}`);
            await clickAndWait(page.getByRole('row', { name: new RegExp(`${b.code} `) }).filter({ hasNotText: 'COPY' }).first().getByRole('link', { name: 'Edit' }), /\/edit/);
            await click(page.locator('.admin-topbar').getByRole('button', { name: 'More' }), { after: 700 });
            await click(page.locator('.dropdown-menu').getByRole('button', { name: 'Save as template' }), { after: 900 });
            const modal = page.locator('#saveTemplateModal');
            await modal.waitFor({ state: 'visible' });
            await say('A template never contains the code, web address, photos or internal notes.', 2600);
            await type(modal.locator('#templateName'), 'Flex 14 Days — Madinah first, with Aziziya (Training)', { delay: 30 });
            await clickAndWait(modal.getByRole('button', { name: 'Save template' }), /\/edit/);
            await say('Open Hajj Packages → Package Templates.', 1800);
            await clickAndWait(page.locator('aside.admin-sidebar a.admin-nav-link', { hasText: 'Package Templates' }), /package-templates/);
            const tRow = page.getByRole('row', { name: /\(Training\)/ }).first();
            await spot(tRow, 'The template is listed. Press "Use" to start a new package from it.', 2800);
            await unspot();
            await clickAndWait(tRow.getByRole('link', { name: 'Use' }), /create\?template=/);
            await spot(page.locator('.alert', { hasText: 'Started from the template' }), 'The builder opens with the template\'s content. Add the title, code and dates, then save.', 3600);
            await unspot();
            await say('That completes the training. Use the checklist and the videos whenever you need them.', 3200);
        });
    },
};

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------
let failed = null;
try {
    for (const key of chapterKeys) {
        const stop = await script[key]();
        if (stop) break;
    }
} catch (error) {
    failed = error;
    console.error(`Recording stopped in chapter "${current?.key}":`, error.message);
    await page.screenshot({ path: path.join(WORK, 'failure.png') }).catch(() => {});
} finally {
    await cdp.send('Page.stopScreencast').catch(() => {});
    await sleep(300);
    timeline.frames = frames;
    timeline.end = now();
    timeline.recorded_at = new Date().toISOString();
    fs.writeFileSync(path.join(WORK, 'timeline.json'), JSON.stringify(timeline, null, 2));
    await browser.close();
    // artisan serve starts a child PHP process; stop the whole tree.
    if (process.platform === 'win32') spawnSync('taskkill', ['/pid', String(server.pid), '/T', '/F']); else server.kill();
    try { artisan(['training:recording', 'cleanup']); } catch (e) { console.error(e.message); }
}

if (failed) process.exit(1);
console.log(`Captured ${frames.length} frames over ${Math.round(timeline.end / 1000)} s. Next: node tools/training-video/encode.mjs`);
