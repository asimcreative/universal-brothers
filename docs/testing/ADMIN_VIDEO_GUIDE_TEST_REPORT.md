# Admin Video Training — Test Report

**Issue:** [#12](https://github.com/asimcreative/universal-brothers/issues/12) · **Date:** 2026-09-15

**Environments:**
- PHPUnit ran on in-memory SQLite.
- Playwright ran against `php artisan serve --env=testing` (`database/testing.sqlite`).
- The recording ran against the separate `database/training-recording.sqlite` (`--env=recording`).
- **Nothing ran against production.**

## 1. Summary

| Check | Result |
|---|---|
| Videos recorded | **30 of 30 chapters, 17 min 29 s, 96 MB**. WebM (VP8), 1280×720, 25 fps. Each chapter has a WebVTT caption file and a JPEG thumbnail. |
| Encoder verification | Every file decoded from its last second (`encode.mjs` `verify()`): 30/30 |
| PHPUnit, full suite | **393 tests, 3,661 assertions — all pass** (372 before this work; 21 new in `VideoTrainingTest`) |
| Playwright, full suite (6 projects, 271 tests) | **265 passed, 4 skipped (existing phone skips), 2 flaky, 0 failed** |
| The 2 flaky tests | Both passed on retry, then were fixed at the cause and passed twice each without retries (§4) |
| New `tests/e2e/video-training.spec.js` | 7 tests pass |
| Visual check | Screenshots at 1440 px and 390 px: no JavaScript errors, no 4xx/5xx, no sideways overflow (§5) |
| Pint | Clean on every new or changed PHP file |
| `npm run build` | Builds |

## 2. Recording runs

| Run | Result | Cause | Fix |
|---|---|---|---|
| Dry run 1 | Stopped at sign-in | `.env.recording` had `APP_ENV=local`, so the web server loaded `.env` instead of the training settings. The training admin was not found; nothing was changed. | `APP_ENV=recording`. The sign-in now doubles as a guard that the server uses the training database. |
| Dry run 2 (chapters 1–5) | OK | — | Caption end times and the caption position above the save bar corrected |
| Full 1 | Stopped in chapter 6 | A text pattern could not match a choice card, because its heading and help text run together | Match the card's heading |
| Full 2 | Stopped in chapter 14 | A selector matched both the builder panel and the hidden Review copy | Scoped to Step 9 |
| Full 3 | Stopped in chapter 21 | The list's Continue link names the step, so no "where you saved" banner shows (by design) | Check the opened step instead |
| Full 4 | Stopped in chapter 23 | A two-line link was clicked in its middle, where the save bar covers it | Click the first line; keep elements clear of the save bar |
| **Full 5** | **30/30 recorded, 1,050 s, 14,620 frames**. Cleanup removed 2 training packages and the training template. | — | — |

Every stop happened because the recorder checks the screen at each step. None left data behind: cleanup runs after every run, and the training database was confirmed to have 0 `TRN…` packages and 0 training templates.

## 3. What the automated tests prove

### PHPUnit — `tests/Feature/Admin/VideoTrainingTest.php` (21)

| Area | Proven |
|---|---|
| Content | The 28 required titles, in order, plus "Using Reusable Information" and "Using Package Templates". The 4 categories. Every chapter has a description, objective, why, what you will learn, steps, common mistakes and expected result, a working related admin page and guide section. No technical words in the explanations. The checklist holds all 30 required items. |
| Script in step | `ADMIN_PACKAGE_CREATION_VIDEO_SCRIPT.md` contains every chapter heading and every step |
| Index | Every card shows its number, title, description, Watch button, length ("1:05"), difficulty, time to do it, not completed status and thumbnail. It warns about chapters without a video and offers Start and Restart Training. |
| Search | All 13 required terms find chapters. "room pricing" finds the right chapter only. Nonsense shows an empty state. |
| Chapter page | Native `<video controls>` with no autoplay; captions track; ±10 s, speed (Normal selected), Replay, Mark as Completed; moments from the caption file; What you will learn, Step-by-step, Important notes, Common mistakes, Related admin section, previous and next chapter. Without a video the written guide still shows. Unknown chapter: 404. |
| Files | Video `video/webm` with `Accept-Ranges: bytes`, and a **206 partial response** for seeking. Captions `text/vtt`; thumbnail `image/jpeg`. A chapter with no video: 404. **Guests redirected to login.** A manifest pointing outside the folder (`../secret.webm`) is refused. |
| Permissions | Content editors can use the training as well as super admins |
| Progress | Position, furthest point, length and last watched are saved. Seeking back keeps the furthest point. The page resumes at the saved position, and `?start=0` overrides it. The index shows "Stopped at 0:20" and "Continue Training". 90% watched completes a chapter; a position past the end is capped. Mark and unmark completed work. Progress is private per admin, and reset clears only the current admin. Continue goes to the last unfinished chapter, else the first unfinished one; Restart goes to chapter 1 at 0. Bad input returns 422, unknown chapter 404, guest 401. |
| Contextual help | Builder Room prices, Journey plan, Review and Publish link to their videos. The Hotels library links to "Using Reusable Information". A guide section links to its video. The menu has Video Training. |
| Training data safety | The demo code starts `TRN` and the internal note carries the training marker. **Every demo price equals UB015's approved USD, SAR and PKR price.** Seeding creates no training package. `training:recording` refuses the normal database (exit 1, no accounts changed). |

### Playwright — `tests/e2e/video-training.spec.js` (7)

| Test | Proven in a real browser |
|---|---|
| Index and search | 30 cards in 4 categories, no sideways scroll, search works |
| **Every recorded file** | For all 30 chapters: the video loads, its length matches the manifest (±1.5 s), it is 1280×720, the thumbnail decodes, and the captions are valid WebVTT with cues |
| Player | Play and pause; +10 s seek; 1.5× speed; captions on with cues loaded; a moment jumps to its time; the position survives a reload ("Resumed from 0:25"); the index shows "Stopped at"; Mark as Completed; Reset Progress (asks first) |
| Watched to the end | The chapter completes and offers "Next: Adding Optional Upgrades" |
| Phone (390 px) | The chapter page, index and checklist fit; the video is 300–390 px wide |
| Contextual links | Builder Room prices step → "Watch Guide: Adding Room Types and Prices" (new tab); Hotels library → "Using Reusable Information" |
| Access and public data | A guest gets a 302 to login for a video. The public Hajj page contains neither `TRN015` nor "Admin Training Example". |

## 4. Existing tests changed

| File | Change | Why |
|---|---|---|
| `playwright.config.js` | `video-training.spec.js` added to admin-chromium | New spec |
| `tests/e2e/admin-guide.spec.js` | The tour test waits for the "paused" save to reach the server before reloading | Flaky in the full run: an immediate reload could beat the save. Now passes 2/2 without retries. |
| `tests/e2e/package-builder.spec.js` | The "published and live" message may take up to 30 s | Flaky under full-suite load: publishing re-saves every section. Now passes 2/2 without retries. |

## 5. Bugs found and fixed during testing

| Found by | Bug | Fix |
|---|---|---|
| E2E | The player did not resume if the video's metadata loaded before the script ran (cached video) | Resume runs immediately when metadata is already available (`training-player.js`) |
| Screenshot | The speed menu showed "0.75×" at first while the video played at normal speed. PHP turns the `'1'` array key into a number, so the "selected" check failed. | Compare as text; PHPUnit asserts "Normal speed" is selected |
| Screenshot | On phones the Full screen button showed only an icon and lost its accessible name | `aria-label="Full screen"` |
| Dry run | Caption end times were all the chapter's end | The previous caption closes when the next starts |

## 6. Manual checks

- **Frames from 12 chapters inspected:** 01 at 21 s and 24.5 s (password as dots), 02, 05, 08, 10, 11, 13, 17, 20, 22, 23, 25, 27 and 30.
  - The title cards read "Chapter N of 30".
  - Captions are readable at 1280×720, and the cursor and highlight rings are visible.
  - The layout is as in the live admin.
- **Captions scanned** for secrets and e-mail addresses: none (see the privacy audit).
- **Video Training screenshots** at 1440 px (index, chapter, chapter playing with captions, checklist, guide index, builder with Watch Guide) and 390 px (index, chapter): no errors, no overflow.

## 7. Not covered

- Playback on real iOS and macOS Safari (not available here). The page shows a message if WebM cannot play.
- Screen-reader announcements with a real screen reader.
- Printing to paper. The print styles hide the admin chrome; checked in markup only.
