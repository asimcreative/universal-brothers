# Admin Video Training — Privacy and Data-Safety Audit

**Issue:** [#12](https://github.com/asimcreative/universal-brothers/issues/12) · **Date:** 2026-09-15 · **Scope:**
- the 30 recorded training videos, their caption files, thumbnails and `manifest.json`;
- the demonstration package;
- the recording process;
- the Video Training pages;
- what is committed to the (public) repository.

**Result: no secret, personal information, private enquiry, internal note of a real package, or server credential appears in the videos, captions, thumbnails, scripts or committed files. The training package never existed outside a separate, disposable recording database.**

---

## 1. Where the recording happened

| Control | How it is enforced | Evidence |
|---|---|---|
| Never production | Recording runs only against the local `php artisan serve --env=recording` on port 8130. No production host, credential or token is used anywhere in `tools/training-video`. | `record.mjs` (`BASE = http://127.0.0.1:8130`) |
| Never the testing or developer database | `.env.recording` → `database/training-recording.sqlite`. `training:recording` **refuses** to run unless that exact file is the connection. | `PrepareTrainingRecording::isRecordingDatabase()`; test `test_the_recording_command_refuses_any_database_but_the_training_one` |
| The web server really uses that database | `APP_ENV=recording`, so the server process loads `.env.recording`. The training admin exists only in the training database, so chapter 1's sign-in would fail against any other database and stop the recording. | Found and fixed during the dry run: with `APP_ENV=local` the sign-in failed, before anything was changed |
| No real API keys | `OPENAI_API_KEY` empty and the AI assistant switched off in `.env.recording` | `.env.recording` (not committed) |
| Approved data only | Fresh `migrate:fresh --seed` from the committed brochure seeders | Structure doc §4 |

## 2. What was removed before filming

`php artisan training:recording prepare --env=recording` runs before every recording. It:

- **deletes all enquiries, AI conversations and messages, admin activity, guide completions and training progress** — so no customer name, phone, email or message can appear on screen. The dashboard in chapter 2 shows "0 New Inquiries".
- **deactivates every other account** and creates `Training Admin` / `training-admin@example.test`. That address uses the reserved `.test` domain and cannot receive mail.
- sets the password from a **random value generated for that one run**. It is passed to the command in an environment variable, typed as dots on screen, and never printed, logged or saved.
- removes earlier training packages (`TRN…`) and training templates ("… (Training)").

## 3. What the videos show

| Chapter area | Screens shown | Private data check |
|---|---|---|
| Sign-in (1) | Admin login | Email is the fake training address. The password is visible only as dots (frame checked at 24.5 s). "Show password" is never pressed. |
| Dashboard (2) | Stat cards, recent packages, quick actions | 0 enquiries; recent activity empty; no user list |
| Navigation (3) | Menu, package list, search for UB015 | Only public brochure packages |
| Builder (4–20, 23, 24) | The training package TRN015 only | All values are the approved public brochure values of UB015. The internal note typed is a harmless marker: "Admin Training Example — made only in the training database…". |
| Preview / public page (22, 25) | TRN015 preview and public page | Header contact details are the public site settings already on the live website. The recorder **aborts if the internal note text appears on the public page** (it did not). |
| Edit, duplicate (26, 27) | TRN015 and its draft copy | — |
| Checklist (28) | Printable checklist | No data |
| Reusable information (29) | Hotels list, Dar Al Taqwa edit screen (Cancel pressed, nothing saved) | Hotel name, stars and description are public. "Notes for administrators" for that hotel is empty in the seed data. |
| Templates (30) | Save as template from TRN015; Package Templates; Use | Templates never carry internal notes |

**Deliberately never shown:**
- Site Settings: it has a reCAPTCHA secret key field and analytics IDs. Chapter 3 only points at the menu item.
- AI Assistant settings and conversations.
- Enquiries.
- Users & Roles.
- Any real package's Notes step. UB004's internal note does not appear in any caption, script or document (searched: 0 matches).

## 4. Captions, thumbnails and manifest

- **Captions (`*.vtt`):** written by the recorder from its own on-screen text. They were scanned for API key patterns, passwords, tokens and e-mail addresses. The only match is the instruction "Type your password. It is shown as dots.", which contains no value.
- **Thumbnails:** each is the chapter title card (title and description only).
- **`manifest.json`:** file names, lengths, sizes and titles only.

## 5. Storage and access

| Control | Evidence |
|---|---|
| Files sit outside the web root (`storage/app/private/training-videos`). `storage:link` exposes only `storage/app/public`. | `config/training.php` |
| Served only to signed-in admins, through `TrainingController`. Guests are redirected to login. | Tests `test_videos_captions_and_thumbnails_are_served_only_to_signed_in_admins`; E2E `video files are only served to signed-in admins…` |
| A manifest cannot point outside the folder (plain file names with known extensions only) | `TrainingCatalog::safeFile()`; test `test_a_manifest_can_never_point_outside_the_video_folder` |
| `X-Robots-Tag: noindex, nofollow` and `Cache-Control: private` on every file | `TrainingController::file()` |
| Not committed to the public repository: the videos (`storage/app/private/.gitignore`), `.env.recording`, the recording database (`database/.gitignore`) and the work frames (`/tools/training-video/work/`) | `git check-ignore` output recorded in the test report |
| Committed tools, chapters and docs contain no secrets | Pattern scan of `tools/training-video`, `docs/training`, `resources/data/admin-video-training.php` and `training-demo-package.json`: no matches apart from the documentation line "`OPENAI_API_KEY=` (empty)" |

## 6. The training package and public listings

- **It existed only in the recording database.** After recording, `training:recording cleanup` removed it, its draft copy, their uploaded photos and the training template ("Removed 2 training packages").
- **Seeders never create a training package.** PHPUnit `test_the_demonstration_package_is_marked_as_training_and_copies_approved_brochure_data` seeds the database and finds 0 `TRN…` packages. It also proves every price used in the video equals UB015's approved price.
- **The testing website's public Hajj page contains neither `TRN015` nor "Admin Training Example".** E2E test.
- **Production is untouched:** no data or package was sent there. Deploying the Video Training code adds only the `admin_training_progress` table.

## 7. Training progress data

- One row per admin per chapter: position, furthest point, length, completed time and last watched time.
- No content, no IP address, no device information.
- Visible only to that admin. Reset Progress removes their rows only (test `test_progress_is_private_to_each_admin_and_can_be_reset`).

## 8. Residual notes

- The browser's own video controls may offer "Download". A downloaded file contains only what is described above.
- If a future re-recording shows other screens, repeat §3 and §4 before uploading. At minimum: scan the new `.vtt` files and look at frames from each changed chapter.
