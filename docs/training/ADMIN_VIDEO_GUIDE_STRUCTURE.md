# Admin Video Training — Structure and Maintenance

**Issue:** [#12](https://github.com/asimcreative/universal-brothers/issues/12) · **In the admin:** Help → **Video Training** (`/admin/guide/videos`)

This document explains how the video training is built, where every file lives, and how to re-record, replace or add a chapter so the videos, the written guides and the script stay in step with the admin screens.

---

## 1. What the admin sees

| Page | Address | Contents |
|---|---|---|
| Video Training | `/admin/guide/videos` | Search; 30 chapter cards in 4 categories. Each card shows its thumbnail, chapter number, title, description, video length, difficulty, time to do it, completed / stopped-at status and a Watch or Resume button. Progress panel with Start/Continue Training, Restart Training, Package checklist and Reset Progress. |
| Chapter | `/admin/guide/videos/{chapter}` | The player (see §5), "In this video" moments, What you will learn, Step-by-step instructions with the example values, Important notes, Common mistakes, Check before moving on, Related admin section and written guide links, previous/next chapter, the chapter list and overall progress. |
| Package checklist | `/admin/guide/videos/checklist` | The printable package checklist, with Print and Download (plain text) buttons. |

Links into the training:
- Menu: Help → Video Training.
- Admin Guide: a Video Training button and a Package checklist button.
- Every builder step: "Watch Guide: …", opening in a new tab so unsaved work is kept.
- Guide section pages.
- The "Need help with this page?" panels.
- Reusable Content pages: link to "Using Reusable Information".

## 2. Files

| What | Where | In git? |
|---|---|---|
| Chapters: titles, categories, steps, fields, notes, mistakes, checks, related page, search words, checklist | `resources/data/admin-video-training.php` | Yes — **the source of truth** |
| The demonstration package's values (approved UB015 data + training markers) | `resources/data/training-demo-package.json` | Yes |
| Recording script document | `docs/training/ADMIN_PACKAGE_CREATION_VIDEO_SCRIPT.md` (generated) | Yes |
| Recorder chapter list | `tools/training-video/chapters.json` (generated) | Yes |
| Recorder and encoder | `tools/training-video/{record.mjs, encode.mjs, overlay.js}` | Yes |
| Recording work files (frames, timeline) | `tools/training-video/work/` | No (ignored) |
| Recording settings | `.env.recording` (local only; no real keys; AI assistant off) | No (ignored) |
| Recording database | `database/training-recording.sqlite` | No (ignored) |
| **Videos** `NN-key.webm`, **captions** `NN-key.vtt`, **thumbnails** `NN-key.jpg`, `manifest.json` | `storage/app/private/training-videos/` (change with `TRAINING_VIDEO_PATH`, see `config/training.php`) | No (ignored) — copied to the server separately |
| Progress | table `admin_training_progress` (one row per admin per chapter) | — |

**Why the videos are not in git:**
- The repository is public, and the files total roughly 100–150 MB.
- Every re-recording would add that again to the history.
- The files sit outside the web root, and `TrainingController` sends them only to signed-in admins. It answers Range requests, so seeking works.

## 3. Video storage and the manifest

`manifest.json` lists each recorded chapter:

```json
{ "chapters": { "room-types": { "number": 9, "title": "Adding Room Types and Prices",
  "file": "09-room-types.webm", "duration": 58, "captions": "09-room-types.vtt",
  "poster": "09-room-types.jpg", "bytes": 4123456, "caption_count": 4 } } }
```

`App\Support\Training\TrainingCatalog` reads it:
- Only plain file names are accepted: no folders, only `.webm/.vtt/.jpg/.png`. A manifest can therefore never serve a file from outside the folder.
- A chapter whose file is missing shows "The video for this chapter is not on this server yet". Its written guide still works.
- The index warns how many chapters have no video.

**Format:**
- WebM (VP8), 1280×720, 25 frames per second, no audio track.
- Plays in Chrome, Edge and Firefox on computers and Android phones.
- Safari support depends on its version: recent Macs play WebM; some iPhones and iPads may not.
- A browser that cannot play WebM gets a message pointing to the written steps.

## 4. Recording a new version

Needs Node, the project's `node_modules` (Playwright) and its Chromium (`npx playwright install chromium`). The ffmpeg used is the one Playwright installs.

```powershell
# 1. Fresh training database from the approved seed data (never production)
New-Item database/training-recording.sqlite -ItemType File -Force
php artisan migrate:fresh --seed --force --env=recording

# 2. Make sure the chapter list matches the chapters file
php artisan training:write-script

# 3. Build the assets the recording should show
npm run build

# 4. Record (about 25 minutes; the browser is invisible)
node tools/training-video/record.mjs
#    dry run of the first chapters:  node tools/training-video/record.mjs --until=basic-information

# 5. Cut chapters into videos, captions, thumbnails and manifest.json
node tools/training-video/encode.mjs

# 6. Write the recorded lengths into the script document
php artisan training:write-script
```

`.env.recording` is a copy of `.env.testing` with these differences:
- `APP_ENV=recording`
- `APP_URL=http://127.0.0.1:8130`
- `DB_DATABASE=database/training-recording.sqlite`
- `OPENAI_API_KEY=` (empty)
- `AI_ASSISTANT_ENABLED=false`

`APP_ENV` must be `recording`. `artisan serve` passes it to the web server process, which then loads `.env.recording`. With any other value the server would read `.env` instead.

### What `record.mjs` does

1. **Prepares the training database.** It runs `php artisan training:recording prepare --env=recording`. The command refuses to run unless the database file is `training-recording.sqlite`. It then:
   - deletes every enquiry, AI conversation, activity entry and training progress row;
   - removes earlier training packages (code `TRN…`) and training templates (name ending "(Training)");
   - deactivates the other accounts;
   - creates **Training Admin** (`training-admin@example.test`) with a random password generated for this run, which is never printed or saved.
2. **Starts the local server** on port 8130. The training admin exists only in the training database, so if the server ever read another database the sign-in in chapter 1 would fail and the recording would stop.
3. **Drives the real admin panel in Chromium** at 1280×720 and captures every screen frame through the browser's screencast. A page script (`overlay.js`) draws a visible cursor with click ripples, the chapter title cards, the captions and a highlight ring. It is injected into the recording browser only, never into the application.
   - Each chapter starts with a 5-second title card: "Chapter N of 30", title, description, category.
   - Captions describe every action and are recorded with their times.
   - Actions are human-paced: smooth mouse movement, visible typing, pauses to read.
4. **Checks privacy on the public page.** The recording stops if the internal admin note appears there.
5. **Cleans up.** It runs `training:recording cleanup`, which removes the training packages and their uploaded photos.

### What `encode.mjs` does

For each chapter it:
- rebuilds a constant 25 fps stream from the captured frames;
- encodes WebM (VP8, about 1.8 Mbit/s);
- **verifies the file** by seeking to its last second and decoding a frame;
- writes the WebVTT captions and copies the title-card thumbnail.

It then writes `manifest.json`.

### Putting the videos on the server

Copy the whole `training-videos` folder to `storage/app/private/training-videos/` in the application on the server (for example with cPanel File Manager or the WHM file API), keeping the file names. Nothing else is needed. The pages pick the files up at once.

## 5. The player

- **Native controls:** a `<video controls>` element with play/pause, seeking, volume and full screen. It never autoplays.
- **Extra controls** (`resources/js/admin/training-player.js`):
  - back and forward 10 seconds;
  - Play/Pause;
  - a playback speed menu (0.75×–2×);
  - a Captions switch (the WebVTT track; captions are also burned into the picture);
  - Replay and Full screen;
  - clickable "In this video" moments, built from the caption file.
- **Resume:** it continues where this admin stopped, shows "Resumed from 1:23 — Start from the beginning", and `?start=0` always starts at 0.
- **Progress:** the position is saved every 5 seconds while playing, and on pause, seek and leaving the page (`sendBeacon`).
  - A chapter counts as **completed** once 90% has been watched (`training.complete_ratio`), or when the admin presses "Mark as Completed".
  - "Mark as not completed" undoes it.
- **Progress endpoints:**

  | Endpoint | Does |
  |---|---|
  | `POST …/{chapter}/progress` | save the position |
  | `POST`/`DELETE …/{chapter}/complete` | mark or unmark completed |
  | `POST …/reset` | clear this admin's progress (asks first) |
  | `GET …/continue` | last unfinished chapter watched, else the first unfinished chapter |
  | `GET …/restart` | chapter 1 from the start |

  Progress never blocks anything in the admin.

## 6. Captions

- Captions are written by the recorder from the same text shown on screen. Each caption's start and end come from the recording.
- To correct a caption without re-recording, edit `NN-key.vtt` on the server. It is plain text:

  ```
  00:00:05.050 --> 00:00:08.400
  Type the package title.
  ```

  The "In this video" list updates from the same file.
- To change the words permanently, edit the text in `record.mjs`, then re-record that version.

## 7. Replacing one video

1. Produce the new `NN-key.webm` (re-record as in §4, or any 16:9 WebM of the same chapter).
2. Replace the file in `training-videos/`, together with its `.vtt` and `.jpg` if they changed.
3. Update that chapter's `duration` (seconds) in `manifest.json`.

The thumbnail is any 16:9 JPEG.

## 8. Adding a chapter

1. Add an entry to `resources/data/admin-video-training.php` in the right position. Give it:
   - title, category, description, difficulty, task_minutes;
   - objective, why, learn, steps, fields, notes, mistakes, check, expected;
   - related `[label, route, params]`, guide section, keywords.
2. Add a function with the same key to the `script` object in `tools/training-video/record.mjs`, following the steps exactly.
3. Run `php artisan training:write-script`.
4. Re-record and encode (§4). Chapter numbers come from the order in the file, so later file names shift. Copy the whole folder.
5. To link an admin page to it:
   - builder steps: add `video` in `resources/data/package-builder-help.php`;
   - guide sections: add `video` in `resources/data/admin-guide.php`;
   - any other page: add `@section('guide_video', 'key')`.
6. Update the title list in `tests/Feature/Admin/VideoTrainingTest.php` if the required chapters changed.

## 9. Keeping the training in step with the admin screens

Whenever a screen that appears in the training changes — a label, a button, a step:

1. Change the chapter's `steps` in `admin-video-training.php` and the actions in `record.mjs`.
2. Run `php artisan training:write-script`.
3. Re-record (§4). `record.mjs` stops with an error if a button or field it expects is missing, so a changed screen cannot be recorded silently wrong.
4. Run the tests:
   - `VideoTrainingTest::test_the_script_document_matches_the_chapters` fails when the script document is stale;
   - `admin-guide.spec.js` and `video-training.spec.js` check the pages in a browser.
5. Upload the new `training-videos` folder.

## 10. Known limits

- **No voice narration.** The ffmpeg available with Playwright cannot encode audio, and the Windows system voices are not clear enough for training. Every action is instead shown with on-screen captions and title cards, and a caption file is provided.
- **No MP4.** The available encoder produces WebM only. Browsers that cannot play WebM (some iPhone and iPad Safari versions) show a message pointing to the written steps, which cover the same actions. An MP4 copy can be added later with a full ffmpeg build.
- **Native pop-ups are not visible.** A headless browser does not draw the drop-down list of a `<select>` or the file chooser. The captions say what is being chosen, and the chosen value is visible.
- **Dates and data are from the recording day.** The video shows Hajj 2027 brochure data (UB015). If the brochure changes, the steps stay valid but the example values in the video will differ.
