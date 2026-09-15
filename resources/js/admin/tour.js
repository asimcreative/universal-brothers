// The guided tour of the admin (first-visit onboarding).
//
// Steps come from resources/data/admin-guide.php (`tour`), embedded by
// layouts/admin.blade.php as #admin-tour-data together with this admin's saved
// place in the tour. Each step points at an element marked data-tour="key".
//
// Designed not to trap anyone: every step has Back, Next and Skip; Escape
// skips; the page underneath stays usable, so following a link simply leaves
// the tour paused where it was. Progress is saved to the admin's account, so
// "Resume tour" works on any device.

const MOBILE = '(max-width: 767.98px)';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

export function initTour() {
    const dataEl = document.getElementById('admin-tour-data');
    if (!dataEl) return;

    const data = JSON.parse(dataEl.textContent || '{}');
    const steps = data.steps || [];
    if (!steps.length) return;

    let index = -1;
    let returnFocus = null;
    let ui = null;
    let frame = null;
    let openedMenu = false;

    const isMobile = () => window.matchMedia(MOBILE).matches;
    const mobileNav = () => document.getElementById('adminMobileNav');

    function save(status, step) {
        fetch(data.stateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ status, step }),
        }).catch(() => { /* the tour still works; only resuming elsewhere is affected */ });
        data.status = status;
        data.step = step;
    }

    // --- The phone menu -------------------------------------------------
    // Shown by class rather than through Bootstrap's Offcanvas, whose focus
    // trap would keep keyboard focus away from the tour's own buttons.
    function showMenu(show) {
        const nav = mobileNav();
        if (!nav) return;
        if (show && !nav.classList.contains('show')) {
            nav.classList.add('show', 'tour-menu-open');
            openedMenu = true;
        } else if (!show && openedMenu) {
            nav.classList.remove('show', 'tour-menu-open');
            openedMenu = false;
        }
    }

    function targetFor(step) {
        const candidates = Array.from(document.querySelectorAll(`[data-tour="${step.target}"]`));
        if (isMobile() && step.menu) {
            return candidates.find((el) => mobileNav()?.contains(el)) || null;
        }
        return candidates.find((el) => el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden' && !mobileNav()?.contains(el)) || null;
    }

    // --- Drawing ----------------------------------------------------------
    function build() {
        const highlight = document.createElement('div');
        highlight.className = 'tour-highlight';
        highlight.setAttribute('aria-hidden', 'true');

        const popover = document.createElement('div');
        popover.className = 'tour-popover';
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-modal', 'false');
        popover.setAttribute('aria-labelledby', 'tour-title');
        popover.setAttribute('aria-describedby', 'tour-text');
        popover.innerHTML = `
            <div class="tour-popover-top">
                <span class="tour-count" data-tour-count></span>
                <button type="button" class="btn-close" data-tour-skip aria-label="Skip the tour"></button>
            </div>
            <h2 class="tour-title" id="tour-title" tabindex="-1" data-tour-title></h2>
            <p class="tour-text" id="tour-text" data-tour-text></p>
            <div class="progress tour-progress" role="progressbar" aria-label="Tour progress" aria-valuemin="1" aria-valuemax="${steps.length}" data-tour-progress>
                <div class="progress-bar" data-tour-bar></div>
            </div>
            <div class="tour-actions">
                <button type="button" class="btn btn-link px-0" data-tour-skip>Skip tour</button>
                <span class="flex-grow-1"></span>
                <button type="button" class="btn btn-outline-secondary" data-tour-back>Back</button>
                <button type="button" class="btn btn-primary" data-tour-next>Next</button>
            </div>
            <p class="visually-hidden" aria-live="polite" data-tour-live></p>`;

        document.body.append(highlight, popover);

        popover.querySelectorAll('[data-tour-skip]').forEach((b) => b.addEventListener('click', skip));
        popover.querySelector('[data-tour-back]').addEventListener('click', () => go(index - 1));
        popover.querySelector('[data-tour-next]').addEventListener('click', () => (index === steps.length - 1 ? finish() : go(index + 1)));
        popover.addEventListener('keydown', onKeydown);
        document.addEventListener('keydown', onDocumentKeydown);
        window.addEventListener('resize', schedulePosition);
        window.addEventListener('scroll', schedulePosition, true);

        return { highlight, popover };
    }

    function destroy() {
        if (!ui) return;
        ui.highlight.remove();
        ui.popover.remove();
        document.removeEventListener('keydown', onDocumentKeydown);
        window.removeEventListener('resize', schedulePosition);
        window.removeEventListener('scroll', schedulePosition, true);
        document.querySelectorAll('.tour-target').forEach((el) => el.classList.remove('tour-target'));
        showMenu(false);
        ui = null;
        index = -1;
        returnFocus?.focus?.();
    }

    function schedulePosition() {
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(position);
    }

    function position() {
        if (!ui || index < 0) return;
        const step = steps[index];
        const target = targetFor(step);
        const { highlight, popover } = ui;
        const gap = 12;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        popover.classList.toggle('is-sheet', isMobile());

        if (!target) {
            highlight.hidden = true;
            popover.style.left = '';
            popover.style.top = '';
            popover.classList.add('is-centered');
            return;
        }
        popover.classList.remove('is-centered');

        const r = target.getBoundingClientRect();
        const pad = 6;
        highlight.hidden = false;
        Object.assign(highlight.style, {
            top: `${Math.max(r.top - pad, 0)}px`,
            left: `${Math.max(r.left - pad, 0)}px`,
            width: `${Math.min(r.width + pad * 2, vw)}px`,
            height: `${Math.min(r.height + pad * 2, vh)}px`,
        });

        if (isMobile()) {
            popover.style.left = '';
            popover.style.top = '';
            return; // bottom sheet, placed by CSS
        }

        const pw = popover.offsetWidth;
        const ph = popover.offsetHeight;
        let left;
        let top;
        if (r.right + gap + pw <= vw - 16) { // to the right (the sidebar)
            left = r.right + gap;
            top = r.top;
        } else if (r.bottom + gap + ph <= vh - 16) { // below
            left = r.left;
            top = r.bottom + gap;
        } else { // above
            left = r.left;
            top = r.top - gap - ph;
        }
        popover.style.left = `${Math.min(Math.max(left, 16), vw - pw - 16)}px`;
        popover.style.top = `${Math.min(Math.max(top, 16), vh - ph - 16)}px`;
    }

    function go(next) {
        if (next < 0 || next >= steps.length) return;
        index = next;
        const step = steps[index];

        showMenu(isMobile() && !!step.menu);
        document.querySelectorAll('.tour-target').forEach((el) => el.classList.remove('tour-target'));
        const target = targetFor(step);
        if (target) {
            target.classList.add('tour-target');
            target.scrollIntoView({ block: isMobile() ? 'start' : 'nearest', inline: 'nearest' });
        }

        const { popover } = ui;
        const count = `Step ${index + 1} of ${steps.length}`;
        popover.querySelector('[data-tour-count]').textContent = count;
        popover.querySelector('[data-tour-title]').textContent = step.title;
        popover.querySelector('[data-tour-text]').textContent = step.text;
        popover.querySelector('[data-tour-bar]').style.width = `${((index + 1) / steps.length) * 100}%`;
        popover.querySelector('[data-tour-progress]').setAttribute('aria-valuenow', index + 1);
        popover.querySelector('[data-tour-back]').disabled = index === 0;
        popover.querySelector('[data-tour-next]').textContent = index === steps.length - 1 ? 'Finish' : 'Next';
        popover.querySelector('[data-tour-live]').textContent = `${count}: ${step.title}`;

        position();
        popover.querySelector('[data-tour-title]').focus({ preventScroll: true });
        save('in_progress', index);
    }

    function start(at = 0) {
        if (ui) destroy();
        returnFocus = document.activeElement;
        ui = build();
        go(Math.min(Math.max(Number(at) || 0, 0), steps.length - 1));
    }

    function skip() {
        const at = index;
        save('paused', Math.max(at, 0));
        destroy();
        const button = document.querySelector('[data-tour-start]');
        if (button && at > 0) {
            button.dataset.tourStart = at;
            button.innerHTML = `<i class="bi bi-play-fill me-1" aria-hidden="true"></i>Resume tour (step ${at + 1} of ${steps.length})`;
        }
    }

    function finish() {
        save('completed', 0);
        destroy();
        const panel = document.querySelector('[data-onboarding-panel]');
        if (panel) {
            panel.innerHTML = `<div class="onboarding-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></div>
                <div class="onboarding-body" role="status">
                    <h2>You have finished the tour</h2>
                    <p class="mb-2">The guide has step-by-step help for everything, including creating a Hajj package. You can take the tour again from the account menu.</p>
                    <a href="${escapeHtml(data.guideUrl)}" class="btn btn-primary">Open the guide</a>
                </div>`;
        }
    }

    // --- Keyboard -------------------------------------------------------
    function onDocumentKeydown(event) {
        if (!ui) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            skip();
        }
    }

    function onKeydown(event) {
        if (event.key === 'ArrowRight' && !event.target.matches('input, textarea')) {
            event.preventDefault();
            if (index === steps.length - 1) finish(); else go(index + 1);
        } else if (event.key === 'ArrowLeft' && !event.target.matches('input, textarea')) {
            event.preventDefault();
            go(index - 1);
        } else if (event.key === 'Tab') {
            // Keep Tab inside the tour card while it is open.
            const focusable = Array.from(ui.popover.querySelectorAll('button:not([disabled])'));
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === ui.popover.querySelector('[data-tour-title]'))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }

    // --- Starting -------------------------------------------------------
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-tour-start]');
        if (!button) return;
        event.preventDefault();
        start(button.dataset.tourStart);
    });

    const url = new URL(window.location.href);
    const requested = url.searchParams.get('tour');
    if (requested) {
        url.searchParams.delete('tour');
        window.history.replaceState(null, '', url);
        if (requested === 'start') start(0);
        if (requested === 'resume') start(data.step || 0);
    }

    // For tests and the browser console.
    window.ubAdminTour = { start, skip, finish, get step() { return index; } };
}
