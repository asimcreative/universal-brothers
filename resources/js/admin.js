// Admin-only behaviour. Loaded by layouts/admin.blade.php after app.js (which
// provides window.bootstrap), and never by the public site.

import { initPackageBuilder } from './admin/package-builder';
import { initTour } from './admin/tour';

/**
 * One confirmation dialog for the whole admin.
 *
 * A form with `data-confirm="Message"` asks before it submits — or only when a
 * particular submit button carrying the same attributes is pressed. Optional:
 *   data-confirm-title   heading (default "Are you sure?")
 *   data-confirm-button  label for the confirm button (default "Confirm")
 *   data-confirm-tone    "danger" (default) or "primary"
 *
 * Replaces the browser's own confirm(), which cannot be styled, cannot explain
 * consequences in more than one line, and reads like an error.
 */
function initConfirmDialog() {
    const modalEl = document.getElementById('adminConfirmModal');
    if (!modalEl || !window.bootstrap) return;

    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    const title = modalEl.querySelector('[data-confirm-title]');
    const message = modalEl.querySelector('[data-confirm-message]');
    const accept = modalEl.querySelector('[data-confirm-accept]');
    const icon = modalEl.querySelector('[data-confirm-icon]');
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const settings = event.submitter?.dataset.confirm ? event.submitter.dataset : form.dataset;
        if (!settings.confirm) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        pending = { form, submitter: event.submitter || null };

        const tone = settings.confirmTone === 'primary' ? 'primary' : 'danger';
        title.textContent = settings.confirmTitle || 'Are you sure?';
        message.textContent = settings.confirm;
        accept.textContent = settings.confirmButton || 'Confirm';
        accept.className = `btn btn-${tone}`;
        icon.classList.toggle('is-primary', tone === 'primary');
        modal.show();
    }, true);

    // From here the styled dialog answers; the browser-confirm fallback in the
    // admin layout's <head> steps aside.
    window.ubAdminConfirmReady = true;

    accept.addEventListener('click', () => {
        if (!pending) return;
        const { form, submitter } = pending;
        pending = null;
        form.dataset.confirmed = '1';
        modal.hide();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
        } else {
            form.submit();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => { pending = null; });
}

/**
 * Loading state: once a form really submits, its buttons are disabled and the
 * one that was pressed shows a spinner, so a slow save is never clicked twice.
 * Disabled on the next tick — disabling the submitter during the submit event
 * would drop its name/value (the save "intent") from the request.
 */
function initSubmitLoading() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.dataset.noLoading !== undefined) return;

        const submitter = event.submitter;
        window.setTimeout(() => {
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                button.disabled = true;
            });
            if (submitter && submitter.tagName === 'BUTTON' && !submitter.querySelector('.spinner-border')) {
                submitter.insertAdjacentHTML('afterbegin', '<span class="spinner-border me-1" role="status" aria-hidden="true"></span>');
            }
        }, 0);
    });

    // Returning with the back button restores a page from cache with its
    // buttons still disabled.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        document.querySelectorAll('button[disabled]').forEach((button) => {
            button.disabled = false;
            button.querySelector('.spinner-border')?.remove();
        });
    });
}

/** `data-char-count="160"` on an input shows "42 / 160" in its `[data-char-count-for]` element. */
function initCharCounters() {
    document.querySelectorAll('[data-char-count]').forEach((input) => {
        const output = document.querySelector(`[data-char-count-for="${input.id}"]`);
        if (!output) return;
        const limit = Number(input.dataset.charCount);
        const update = () => {
            const length = input.value.length;
            output.textContent = `${length} / ${limit}`;
            output.classList.toggle('is-over', length > limit);
        };
        input.addEventListener('input', update);
        update();
    });
}

/** Filter selects in a `[data-autosubmit]` form apply as soon as they change. */
function initAutoSubmitFilters() {
    document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit ? form.requestSubmit() : form.submit());
        });
    });
}

/** Shows the chosen image before upload, next to `[data-image-preview-for="inputId"]`. */
function initImagePreviews() {
    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;
        const target = document.querySelector(`[data-image-preview-for="${input.id}"]`)
            || input.closest('[data-image-field]')?.querySelector('[data-image-preview]');
        if (!target) return;

        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = () => {
            target.src = reader.result;
            target.hidden = false;
            target.closest('[hidden]')?.removeAttribute('hidden');
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Ties each error message to its field: the field is marked invalid and
 * described by the message, so assistive technology reads "Package code —
 * This code is already used" together rather than the message on its own.
 */
function initFieldErrors() {
    document.querySelectorAll('.invalid-feedback[id]').forEach((message) => {
        const container = message.parentElement;
        const field = container?.querySelector('.is-invalid, input:not([type="hidden"]), select, textarea');
        if (!field) return;
        field.setAttribute('aria-invalid', 'true');
        const ids = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        ids.add(message.id);
        field.setAttribute('aria-describedby', Array.from(ids).join(' '));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    [initConfirmDialog, initSubmitLoading, initCharCounters, initAutoSubmitFilters, initImagePreviews, initFieldErrors, initPackageBuilder, initTour].forEach((fn) => {
        try {
            fn();
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error(`[ub-admin] ${fn.name} failed`, error);
        }
    });
});
