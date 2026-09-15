// Shared admin interface pieces for scripts: the confirmation dialog as a
// promise, small toast messages (with an optional action such as "Undo"), and
// a polite live region so screen readers hear what just changed.

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/**
 * The admin's confirmation dialog (layouts/admin.blade.php) for decisions made
 * in script rather than by submitting a form.
 */
export function askConfirm({ title, message, button = 'Confirm', tone = 'danger' }) {
    const modalEl = document.getElementById('adminConfirmModal');
    if (!modalEl || !window.bootstrap) {
        return Promise.resolve(window.confirm(`${title ? `${title}\n\n` : ''}${message}`));
    }

    return new Promise((resolve) => {
        const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        const accept = modalEl.querySelector('[data-confirm-accept]');
        let accepted = false;

        modalEl.querySelector('[data-confirm-title]').textContent = title || 'Are you sure?';
        modalEl.querySelector('[data-confirm-message]').textContent = message;
        modalEl.querySelector('[data-confirm-icon]').classList.toggle('is-primary', tone === 'primary');
        accept.textContent = button;
        accept.className = `btn btn-${tone}`;

        // Capture phase, stopped there: the form-confirmation listener in
        // admin.js watches the same button.
        const onAccept = (event) => {
            event.stopImmediatePropagation();
            accepted = true;
            modal.hide();
        };
        const onHidden = () => {
            accept.removeEventListener('click', onAccept, true);
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            resolve(accepted);
        };

        accept.addEventListener('click', onAccept, true);
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        modal.show();
    });
}

/** Says `message` to screen reader users without moving their focus. */
export function announce(message) {
    const region = document.getElementById('admin-live-region');
    if (!region) return;
    region.textContent = '';
    window.setTimeout(() => { region.textContent = message; }, 60);
}

/**
 * A short message in the corner. `action` adds a button (for example Undo);
 * the toast stays until dismissed or `timeout` milliseconds pass.
 */
export function toast(message, { tone = 'success', action = null, timeout = 7000 } = {}) {
    const holder = document.getElementById('admin-toasts');
    if (!holder) {
        announce(message);
        return { close() {} };
    }

    const icons = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill', warning: 'bi-exclamation-circle-fill', info: 'bi-info-circle-fill' };
    const el = document.createElement('div');
    el.className = `admin-toast admin-toast--${tone}`;
    el.setAttribute('role', tone === 'danger' ? 'alert' : 'status');
    el.innerHTML = `
        <i class="bi ${icons[tone] || icons.info}" aria-hidden="true"></i>
        <div class="admin-toast-body">${escapeHtml(message)}</div>
        ${action ? `<button type="button" class="btn btn-sm btn-link admin-toast-action">${escapeHtml(action.label)}</button>` : ''}
        <button type="button" class="btn-close btn-close-sm" aria-label="Dismiss message"></button>`;

    let timer = null;
    const close = () => {
        window.clearTimeout(timer);
        el.classList.add('is-leaving');
        window.setTimeout(() => el.remove(), 200);
    };

    el.querySelector('.btn-close').addEventListener('click', close);
    if (action) {
        el.querySelector('.admin-toast-action').addEventListener('click', () => {
            close();
            action.onClick();
        });
    }

    holder.appendChild(el);
    if (timeout) timer = window.setTimeout(close, timeout);

    return { close };
}

/** Hides a Bootstrap modal and resolves once it has fully closed. */
export function hideModal(modalEl) {
    return new Promise((resolve) => {
        if (!modalEl.classList.contains('show')) { resolve(); return; }
        modalEl.addEventListener('hidden.bs.modal', () => resolve(), { once: true });
        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });
}
