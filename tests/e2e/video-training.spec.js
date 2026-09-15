import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { clickAndConfirm } from './helpers/confirm.js';

// Runs under 'admin-chromium' (pre-authenticated) against database/testing.sqlite.
//
// Admin Guide → Video Training (issue #12) in a real browser: the chapter
// index and search, the player with the recorded videos (plays, seeks,
// speed, captions, moments, resume, completion), every recorded file being a
// valid video with its thumbnail and captions, the phone layout, the
// contextual Watch Guide links, admin-only files, and no training package on
// the public site.

const manifestPath = path.resolve('storage/app/private/training-videos/manifest.json');
const manifest = fs.existsSync(manifestPath) ? JSON.parse(fs.readFileSync(manifestPath, 'utf8')) : null;
const recorded = manifest ? Object.keys(manifest.chapters) : [];

async function noSidewaysScroll(page, label) {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow, `sideways scroll: ${label}`).toBeLessThanOrEqual(1);
}

test.describe('Video Training', () => {
    test('the index lists every chapter by category and can be searched', async ({ page }) => {
        await page.goto('/admin/guide/videos');
        await expect(page.getByRole('heading', { name: 'Video Training', level: 1 })).toBeVisible();
        await expect(page.locator('.training-card')).toHaveCount(30);
        for (const category of ['Getting Started', 'Hajj Package Creation', 'Review and Publishing', 'Advanced Management']) {
            await expect(page.getByRole('heading', { name: category })).toBeVisible();
        }
        await expect(page.locator('.training-card').first()).toContainText('Chapter 1');
        await noSidewaysScroll(page, 'training index');

        await page.getByLabel('Search the video training').fill('room pricing');
        await page.locator('.training-hero').getByRole('button', { name: 'Search' }).click();
        await expect(page.getByRole('link', { name: 'Adding Room Types and Prices', exact: true })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Understanding the Dashboard', exact: true })).toHaveCount(0);
    });

    test('every recorded video, thumbnail and caption file is valid and matches the manifest', async ({ page }) => {
        test.skip(!manifest, 'No recorded videos on this machine');
        test.setTimeout(240_000);
        expect(recorded).toHaveLength(30);

        await page.goto('/admin/guide/videos');
        for (const [key, entry] of Object.entries(manifest.chapters)) {
            const result = await page.evaluate(async (chapterKey) => {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.muted = true;
                const loaded = new Promise((resolve, reject) => {
                    video.onloadedmetadata = () => resolve();
                    video.onerror = () => reject(new Error('video error'));
                });
                video.src = `/admin/guide/videos/${chapterKey}/video`;
                await loaded;
                const poster = await fetch(`/admin/guide/videos/${chapterKey}/poster`);
                const blob = await poster.blob();
                const bitmap = await createImageBitmap(blob);
                const captions = await (await fetch(`/admin/guide/videos/${chapterKey}/captions`)).text();
                return { duration: video.duration, width: video.videoWidth, height: video.videoHeight, posterWidth: bitmap.width, captions };
            }, key);

            expect(Math.abs(result.duration - entry.duration), `${key} duration`).toBeLessThanOrEqual(1.5);
            expect(result.width, `${key} width`).toBe(1280);
            expect(result.height, `${key} height`).toBe(720);
            expect(result.posterWidth, `${key} thumbnail`).toBeGreaterThan(0);
            expect(result.captions.startsWith('WEBVTT'), `${key} captions`).toBe(true);
            expect(result.captions, `${key} has no caption cues`).toContain('-->');
        }
    });

    test('the player plays, seeks, changes speed, shows captions, jumps to moments, resumes and completes', async ({ page }) => {
        test.skip(!recorded.includes('hotels'), 'No recorded videos on this machine');

        await page.goto('/admin/guide/videos/hotels');
        const video = page.locator('[data-training-video]');
        await expect(video).toBeVisible();
        await expect.poll(() => video.evaluate((v) => v.readyState)).toBeGreaterThanOrEqual(1);
        expect(await video.evaluate((v) => v.autoplay || !v.paused)).toBe(false);

        // Play, then pause.
        await video.evaluate((v) => { v.muted = true; });
        await page.locator('[data-training-toggle]').click();
        await expect.poll(() => video.evaluate((v) => v.currentTime)).toBeGreaterThan(0.5);
        await page.locator('[data-training-toggle]').click();
        await expect(page.locator('[data-training-toggle]')).toHaveAttribute('aria-label', 'Play');

        // Seek forward 10 seconds, change speed, captions on.
        const before = await video.evaluate((v) => v.currentTime);
        await page.getByRole('button', { name: 'Forward 10 seconds' }).click();
        await expect.poll(() => video.evaluate((v) => v.currentTime)).toBeGreaterThan(before + 9);
        await page.getByLabel('Playback speed').selectOption('1.5');
        expect(await video.evaluate((v) => v.playbackRate)).toBe(1.5);
        await page.locator('[data-training-captions]').click();
        await expect(page.locator('[data-training-captions]')).toHaveAttribute('aria-pressed', 'true');
        await expect.poll(() => video.evaluate((v) => v.textTracks[0].cues?.length || 0)).toBeGreaterThan(0);

        // An "In this video" moment jumps there.
        const moment = page.locator('[data-training-jump]').nth(1);
        const target = Number(await moment.getAttribute('data-training-jump'));
        await moment.click();
        await expect.poll(() => video.evaluate((v) => Math.round(v.currentTime))).toBe(target);

        // Leaving and coming back resumes there.
        await video.evaluate((v) => { v.currentTime = 25; });
        await page.locator('[data-training-toggle]').click();
        await page.waitForTimeout(600);
        await page.locator('[data-training-toggle]').click();
        await page.waitForTimeout(800);
        await page.reload();
        await expect(page.locator('[data-training-resume]')).toBeVisible();
        await expect(page.locator('[data-training-resume-time]')).toHaveText(/^0:2[5-6]$/);
        await page.goto('/admin/guide/videos');
        await expect(page.locator('.training-card', { hasText: 'Adding Hotels and Accommodation' })).toContainText('Stopped at');

        // Mark as completed, then undo and reset so the test leaves no progress.
        await page.goto('/admin/guide/videos/hotels');
        await page.getByRole('button', { name: 'Mark as Completed' }).click();
        await expect(page.locator('[data-training-status]')).toContainText('Completed');
        await page.goto('/admin/guide/videos');
        await expect(page.locator('.training-card.is-done', { hasText: 'Adding Hotels and Accommodation' })).toBeVisible();
        await clickAndConfirm(page, page.getByRole('button', { name: 'Reset Progress' }));
        await expect(page.getByRole('status').filter({ hasText: 'progress was reset' })).toBeVisible();
    });

    test('a video watched to the end counts as completed and offers the next chapter', async ({ page }) => {
        test.skip(!recorded.includes('exclusions'), 'No recorded videos on this machine');

        await page.goto('/admin/guide/videos/exclusions');
        const video = page.locator('[data-training-video]');
        await expect.poll(() => video.evaluate((v) => v.readyState)).toBeGreaterThanOrEqual(1);
        await video.evaluate((v) => { v.muted = true; v.currentTime = Math.max(0, v.duration - 1.5); return v.play(); });
        await expect(page.locator('[data-training-next]')).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('[data-training-next]')).toContainText('Adding Optional Upgrades');
        await expect(page.locator('[data-training-status]')).toContainText('Completed');

        await page.goto('/admin/guide/videos');
        await clickAndConfirm(page, page.getByRole('button', { name: 'Reset Progress' }));
    });

    test('on a phone the player and written guide fit the screen', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin/guide/videos/itinerary');
        await noSidewaysScroll(page, 'chapter page on a phone');
        if (recorded.includes('itinerary')) {
            const box = await page.locator('[data-training-video]').boundingBox();
            expect(box.width).toBeLessThanOrEqual(390);
            expect(box.width).toBeGreaterThan(300);
        }
        await expect(page.getByRole('heading', { name: 'Step-by-step instructions' })).toBeVisible();
        await page.goto('/admin/guide/videos');
        await noSidewaysScroll(page, 'training index on a phone');
        await page.goto('/admin/guide/videos/checklist');
        await noSidewaysScroll(page, 'checklist on a phone');
    });

    test('builder steps and reusable pages link straight to their video', async ({ page }) => {
        await page.goto('/admin/hajj-packages/create?step=pricing');
        const link = page.locator('#step-pricing .watch-guide-link');
        await expect(link).toContainText('Watch Guide: Adding Room Types and Prices');
        await expect(link).toHaveAttribute('target', '_blank');
        await expect(link).toHaveAttribute('href', /\/admin\/guide\/videos\/room-types$/);

        await page.goto('/admin/library/hotels');
        await page.locator('details.page-help summary').click();
        await expect(page.locator('details.page-help .watch-guide-link')).toContainText('Using Reusable Information');
    });

    test('video files are only served to signed-in admins, and no training package reaches the website', async ({ browser, baseURL }) => {
        const guest = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const response = await guest.request.get(`${baseURL}/admin/guide/videos/welcome/video`, { maxRedirects: 0 });
        expect(response.status()).toBe(302);
        expect(response.headers().location).toContain('/admin/login');

        const hajj = await guest.request.get(`${baseURL}/hajj`);
        const html = await hajj.text();
        expect(html).not.toContain('TRN015');
        expect(html).not.toContain('Admin Training Example');
        await guest.close();
    });
});
