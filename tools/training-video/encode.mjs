// Turns a recording (work/timeline.json + work/frames/) into one WebM video,
// one WebVTT caption file and one thumbnail per chapter, plus manifest.json,
// in the training video folder (config/training.php).
//
//   node tools/training-video/encode.mjs
//
// Uses the ffmpeg build that ships with Playwright (VP8/WebM only, no audio);
// set FFMPEG_BIN to use another. Output folder: TRAINING_VIDEO_PATH or
// storage/app/private/training-videos.

import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(HERE, '..', '..');
const WORK = path.join(HERE, 'work');
const OUT = process.env.TRAINING_VIDEO_PATH || path.join(ROOT, 'storage', 'app', 'private', 'training-videos');
const FPS = 25;
const FRAME_MS = 1000 / FPS;

function findFfmpeg() {
    if (process.env.FFMPEG_BIN) return process.env.FFMPEG_BIN;
    const base = process.platform === 'win32'
        ? path.join(process.env.LOCALAPPDATA || '', 'ms-playwright')
        : path.join(os.homedir(), process.platform === 'darwin' ? 'Library/Caches/ms-playwright' : '.cache/ms-playwright');
    const dir = fs.existsSync(base) ? fs.readdirSync(base).filter((d) => d.startsWith('ffmpeg')).sort().pop() : null;
    const exe = dir && fs.readdirSync(path.join(base, dir)).find((f) => f.startsWith('ffmpeg'));
    if (!exe) throw new Error('ffmpeg not found — install Playwright browsers (npx playwright install) or set FFMPEG_BIN');
    return path.join(base, dir, exe);
}

const FFMPEG = findFfmpeg();
const timeline = JSON.parse(fs.readFileSync(path.join(WORK, 'timeline.json'), 'utf8'));
const chaptersMeta = JSON.parse(fs.readFileSync(path.join(HERE, 'chapters.json'), 'utf8'));
const frameTimes = timeline.frames;
const frameFile = (i) => path.join(WORK, 'frames', `${String(i).padStart(6, '0')}.jpg`);

const vttTime = (ms) => {
    const t = Math.max(0, Math.round(ms));
    const h = Math.floor(t / 3600000);
    const m = Math.floor((t % 3600000) / 60000);
    const s = Math.floor((t % 60000) / 1000);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${String(t % 1000).padStart(3, '0')}`;
};

async function encodeChapter(chapter, file) {
    const ticks = Math.ceil((chapter.end - chapter.start) / FRAME_MS);
    const ffmpeg = spawn(FFMPEG, [
        '-hide_banner', '-loglevel', 'error', '-y',
        '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'mjpeg', '-i', 'pipe:0',
        '-vf', `scale=${timeline.viewport.width}:${timeline.viewport.height}`, '-pix_fmt', 'yuv420p',
        '-c:v', 'libvpx', '-b:v', '1800k', '-crf', '8', '-qmin', '2', '-qmax', '40',
        '-deadline', 'good', '-cpu-used', '2', '-g', String(FPS * 4), '-an',
        '-f', 'webm', file,
    ], { stdio: ['pipe', 'inherit', 'inherit'] });
    const done = new Promise((resolve, reject) => ffmpeg.on('close', (code) => (code === 0 ? resolve() : reject(new Error(`ffmpeg exited ${code}`)))));

    // Each output frame shows the newest captured frame at that moment —
    // Chrome only sends a frame when the screen changes.
    let index = Math.max(0, frameTimes.findIndex((t) => t > chapter.start) - 1);
    let lastBuffer = null;
    let lastIndex = -1;
    for (let k = 0; k < ticks; k++) {
        const at = chapter.start + k * FRAME_MS;
        while (index + 1 < frameTimes.length && frameTimes[index + 1] <= at) index++;
        if (index !== lastIndex) {
            lastBuffer = fs.readFileSync(frameFile(index));
            lastIndex = index;
        }
        if (!ffmpeg.stdin.write(lastBuffer)) await new Promise((r) => ffmpeg.stdin.once('drain', r));
    }
    ffmpeg.stdin.end();
    await done;
    return ticks / FPS;
}

function writeCaptions(chapter, file) {
    const cues = chapter.captions
        .filter((c) => c.text)
        .map((c, i) => `${i + 1}\n${vttTime(c.start - chapter.start)} --> ${vttTime(Math.max(c.start + 800, c.end) - chapter.start)}\n${c.text}`);
    fs.writeFileSync(file, `WEBVTT\n\n${cues.join('\n\n')}\n`);
}

/** Proves the file is a complete, seekable video: decode its last second to a PNG. */
function verify(file, seconds) {
    const probe = path.join(WORK, 'verify.png');
    const result = spawnSync(FFMPEG, ['-hide_banner', '-loglevel', 'error', '-y', '-ss', String(Math.max(0, seconds - 1)), '-i', file, '-frames:v', '1', '-f', 'image2', probe]);
    const ok = result.status === 0 && fs.existsSync(probe) && fs.statSync(probe).size > 1000;
    fs.rmSync(probe, { force: true });
    if (!ok) throw new Error(`Verification failed for ${path.basename(file)}: ${result.stderr?.toString()}`);
}

fs.mkdirSync(OUT, { recursive: true });
const manifest = {
    generated_at: new Date().toISOString(),
    recorded_at: timeline.recorded_at,
    source: 'tools/training-video (record.mjs + encode.mjs)',
    viewport: timeline.viewport,
    fps: FPS,
    chapters: {},
};

for (const chapter of timeline.chapters) {
    const meta = chaptersMeta[chapter.key];
    const base = `${String(meta.number).padStart(2, '0')}-${chapter.key}`;
    const video = `${base}.webm`;
    const captions = `${base}.vtt`;
    const poster = `${base}.jpg`;

    const seconds = await encodeChapter(chapter, path.join(OUT, video));
    verify(path.join(OUT, video), seconds);
    writeCaptions(chapter, path.join(OUT, captions));
    fs.copyFileSync(path.join(WORK, 'posters', poster), path.join(OUT, poster));

    manifest.chapters[chapter.key] = {
        number: meta.number,
        title: meta.title,
        file: video,
        duration: Math.round(seconds),
        captions,
        poster,
        bytes: fs.statSync(path.join(OUT, video)).size,
        caption_count: chapter.captions.length,
    };
    console.log(`${base}: ${Math.round(seconds)} s, ${(manifest.chapters[chapter.key].bytes / 1048576).toFixed(1)} MB`);
}

fs.writeFileSync(path.join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 2));
const total = Object.values(manifest.chapters).reduce((s, c) => s + c.duration, 0);
const bytes = Object.values(manifest.chapters).reduce((s, c) => s + c.bytes, 0);
console.log(`Wrote ${Object.keys(manifest.chapters).length} chapters, ${Math.floor(total / 60)} min ${total % 60} s, ${(bytes / 1048576).toFixed(1)} MB → ${OUT}`);
