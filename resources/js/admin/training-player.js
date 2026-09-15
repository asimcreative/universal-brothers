// The Video Training player (admin/guide/videos/show.blade.php).
//
// A native <video controls> element does the playing, so keyboard use, volume,
// full screen and the browser's own speed menu all work as users expect. This
// adds what native controls lack everywhere: 10-second skips, a speed menu,
// a captions switch, replay, clickable "In this video" moments, resuming where
// this admin stopped, and saving progress to their account. Never autoplays.

const SAVE_EVERY_MS = 5000;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

const clock = (seconds) => {
    const s = Math.max(0, Math.floor(seconds || 0));
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
};

export function initTrainingPlayer() {
    document.querySelector('[data-print-checklist]')?.addEventListener('click', () => window.print());

    const root = document.querySelector('[data-training-player]');
    if (!root) return;

    const video = root.querySelector('[data-training-video]');
    const toggle = root.querySelector('[data-training-toggle]');
    const toggleLabel = root.querySelector('[data-training-toggle-label]');
    const time = root.querySelector('[data-training-time]');
    const status = document.querySelector('[data-training-status]');
    const overall = document.querySelector('[data-training-overall]');
    let completed = root.dataset.completed === '1';
    let lastSaved = 0;
    let lastSavedPosition = -1;

    // --- Saving progress -------------------------------------------------
    function payload() {
        const body = new FormData();
        body.append('_token', csrfToken());
        body.append('position', String(Math.floor(video.currentTime || 0)));
        if (Number.isFinite(video.duration) && video.duration > 0) body.append('duration', String(Math.round(video.duration)));
        return body;
    }

    async function save(force = false) {
        const position = Math.floor(video.currentTime || 0);
        if (!force && (Date.now() - lastSaved < SAVE_EVERY_MS || position === lastSavedPosition)) return;
        lastSaved = Date.now();
        lastSavedPosition = position;
        try {
            const response = await fetch(root.dataset.progressUrl, {
                method: 'POST', body: payload(), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.completed && !completed) markCompleted(data.summary);
        } catch (e) {
            // Offline for a moment: the next save catches up.
        }
    }

    function markCompleted(summary) {
        completed = true;
        if (status) {
            status.classList.add('is-done');
            status.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i>Completed';
        }
        if (overall && summary) overall.textContent = `${summary.completed} of ${summary.total} completed`;
        root.dataset.completed = '1';
    }

    // Leaving the page mid-video still records where the admin stopped.
    const saveOnLeave = () => {
        if (!video.currentTime) return;
        navigator.sendBeacon?.(root.dataset.progressUrl, payload());
    };
    window.addEventListener('pagehide', saveOnLeave);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') saveOnLeave(); });

    // --- Resume ------------------------------------------------------------
    const resumeAt = Number(root.dataset.resume || 0);
    const resume = () => {
        if (time) time.textContent = `${clock(0)} / ${clock(video.duration)}`;
        if (resumeAt > 3 && Number.isFinite(video.duration) && resumeAt < video.duration - 3) {
            video.currentTime = resumeAt;
            const notice = root.querySelector('[data-training-resume]');
            if (notice) {
                notice.querySelector('[data-training-resume-time]').textContent = clock(resumeAt);
                notice.hidden = false;
            }
        }
    };
    // The metadata may already be loaded (from the browser cache) before this
    // script runs, in which case the event has been and gone.
    if (video.readyState >= 1) resume();
    else video.addEventListener('loadedmetadata', resume, { once: true });

    root.querySelector('[data-training-restart]')?.addEventListener('click', () => {
        video.currentTime = 0;
        root.querySelector('[data-training-resume]').hidden = true;
        save(true);
    });

    // --- Controls ------------------------------------------------------------
    const syncToggle = () => {
        const playing = !video.paused && !video.ended;
        toggle.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        toggle.querySelector('i').className = `bi ${playing ? 'bi-pause-fill' : 'bi-play-fill'}`;
        toggleLabel.textContent = playing ? 'Pause' : 'Play';
    };

    toggle.addEventListener('click', () => (video.paused || video.ended ? video.play() : video.pause()));
    video.addEventListener('play', syncToggle);
    video.addEventListener('pause', () => { syncToggle(); save(true); });
    video.addEventListener('seeked', () => save(true));
    video.addEventListener('timeupdate', () => {
        if (time) time.textContent = `${clock(video.currentTime)} / ${clock(video.duration)}`;
        if (!video.paused) save();
    });
    video.addEventListener('ended', () => {
        syncToggle();
        save(true);
        const next = root.querySelector('[data-training-next]');
        if (next) next.hidden = false;
    });

    root.querySelectorAll('[data-training-seek]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = video.currentTime + Number(button.dataset.trainingSeek);
            video.currentTime = Math.min(Math.max(target, 0), Number.isFinite(video.duration) ? video.duration : target);
        });
    });

    root.querySelector('[data-training-speed]')?.addEventListener('change', (event) => {
        video.playbackRate = Number(event.target.value) || 1;
    });
    video.addEventListener('ratechange', () => {
        const select = root.querySelector('[data-training-speed]');
        if (select && String(video.playbackRate) !== select.value && select.querySelector(`option[value="${video.playbackRate}"]`)) {
            select.value = String(video.playbackRate);
        }
    });

    const captionsButton = root.querySelector('[data-training-captions]');
    captionsButton?.addEventListener('click', () => {
        const track = video.textTracks?.[0];
        if (!track) return;
        track.mode = track.mode === 'showing' ? 'hidden' : 'showing';
        captionsButton.setAttribute('aria-pressed', String(track.mode === 'showing'));
    });

    root.querySelector('[data-training-replay]')?.addEventListener('click', () => {
        video.currentTime = 0;
        root.querySelector('[data-training-next]').hidden = true;
        video.play();
    });

    root.querySelector('[data-training-fullscreen]')?.addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen?.();
        else (video.requestFullscreen || video.webkitRequestFullscreen || video.webkitEnterFullscreen)?.call(video);
    });

    // "In this video" moments jump to that point.
    document.querySelectorAll('[data-training-jump]').forEach((button) => {
        button.addEventListener('click', () => {
            video.currentTime = Number(button.dataset.trainingJump) || 0;
            video.scrollIntoView({ behavior: 'smooth', block: 'center' });
            video.focus({ preventScroll: true });
        });
    });

    // A browser that cannot play WebM says so instead of showing a dead player.
    const showError = () => { root.querySelector('[data-training-error]').hidden = false; };
    video.addEventListener('error', showError);
    video.querySelector('source')?.addEventListener('error', showError);
    if (video.canPlayType && video.canPlayType('video/webm') === '') showError();

    // Mark as Completed without leaving the page.
    const completeForm = document.querySelector('[data-training-complete-form]');
    completeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const response = await fetch(root.dataset.completeUrl, {
                method: 'POST', body: new FormData(completeForm), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (response.ok) {
                markCompleted(null);
                completeForm.innerHTML = '<span class="small text-success"><i class="bi bi-check2" aria-hidden="true"></i> Saved</span>';
            }
        } catch (e) {
            completeForm.submit();
        }
    });
}
