// The image picker: upload a new image or choose one from the media library,
// with its description (alt text) and caption. Used by image fields in the page
// builder (`[data-media-field]`) and by the rich text editor's image button.
//
// openMediaPicker() resolves with the chosen image ({ id, url, path, alt,
// caption, title, width, height }) or null when the admin closes the picker.

import { askConfirm, csrfToken, escapeHtml, toast } from './ui';

let modalEl = null;
let state = null;

function endpoints() {
    const body = document.body.dataset;
    return { list: body.mediaLibraryUrl, upload: body.mediaLibraryUrl, update: body.mediaLibraryUpdateUrl };
}

function buildModal() {
    modalEl = document.createElement('div');
    modalEl.className = 'modal fade media-picker-modal';
    modalEl.id = 'mediaPickerModal';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-labelledby', 'mediaPickerTitle');
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.innerHTML = `
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="mediaPickerTitle">Choose an image</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close without choosing"></button>
                </div>
                <nav class="admin-tabs media-picker-tabs" role="tablist" aria-label="How to add an image">
                    <a href="#" role="tab" id="mp-tab-library" aria-controls="mp-pane-library" aria-selected="true" class="active" data-mp-tab="library"><i class="bi bi-images" aria-hidden="true"></i>Media library</a>
                    <a href="#" role="tab" id="mp-tab-upload" aria-controls="mp-pane-upload" aria-selected="false" tabindex="-1" data-mp-tab="upload"><i class="bi bi-upload" aria-hidden="true"></i>Upload a new image</a>
                </nav>
                <div class="modal-body">
                    <div class="media-picker-layout">
                        <div class="media-picker-main">
                            <section id="mp-pane-library" role="tabpanel" aria-labelledby="mp-tab-library" data-mp-pane="library">
                                <div class="admin-toolbar-search mb-3">
                                    <label for="mp-search" class="visually-hidden">Search images</label>
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" id="mp-search" class="form-control" placeholder="Search by title or description" data-mp-search>
                                </div>
                                <div class="media-picker-grid" data-mp-grid aria-live="polite"></div>
                                <p class="text-muted small mt-3 mb-0" data-mp-empty hidden>No images found. Upload a new image instead.</p>
                                <div class="text-center mt-3"><button type="button" class="btn btn-outline-secondary btn-sm" data-mp-more hidden>Show more images</button></div>
                            </section>
                            <section id="mp-pane-upload" role="tabpanel" aria-labelledby="mp-tab-upload" data-mp-pane="upload" hidden>
                                <label class="media-picker-drop" for="mp-file" data-mp-drop>
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                    <strong>Drag a photo here, or click to choose one</strong>
                                    <span>JPG, PNG, WebP or GIF · up to 5 MB</span>
                                </label>
                                <input type="file" id="mp-file" class="visually-hidden" accept="image/jpeg,image/png,image/webp,image/gif" data-mp-file>
                                <div class="alert alert-danger admin-alert mt-3 mb-0" role="alert" data-mp-upload-error hidden>
                                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><div class="admin-alert-body" data-mp-upload-error-text></div>
                                </div>
                            </section>
                        </div>
                        <aside class="media-picker-details" data-mp-details hidden aria-label="Chosen image">
                            <img src="" alt="" class="media-picker-preview" data-mp-preview>
                            <p class="small text-muted mb-3" data-mp-meta></p>
                            <div class="mb-3">
                                <label for="mp-alt" class="form-label">Image description (alt text) <span class="required-mark" data-mp-alt-required hidden aria-hidden="true">*</span></label>
                                <input type="text" id="mp-alt" class="form-control" maxlength="255" data-mp-alt aria-describedby="mp-alt-help">
                                <div class="form-help" id="mp-alt-help">What does the photo show? For example “Pilgrims performing tawaf around the Kaaba”. Read aloud to visitors who cannot see it.</div>
                            </div>
                            <div class="mb-3">
                                <label for="mp-caption" class="form-label">Caption (optional)</label>
                                <input type="text" id="mp-caption" class="form-control" maxlength="500" data-mp-caption>
                            </div>
                            <p class="small text-danger" role="alert" data-mp-detail-error hidden></p>
                        </aside>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="me-auto small text-muted" data-mp-status aria-live="polite"></span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-mp-upload hidden><i class="bi bi-upload me-1" aria-hidden="true"></i>Upload image</button>
                    <button type="button" class="btn btn-primary" data-mp-use disabled>Use this image</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(modalEl);
    wireModal();
}

function $(selector) {
    return modalEl.querySelector(selector);
}

function wireModal() {
    modalEl.querySelectorAll('[data-mp-tab]').forEach((tab) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();
            showTab(tab.dataset.mpTab);
        });
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const next = tab.dataset.mpTab === 'library' ? 'upload' : 'library';
            showTab(next);
            modalEl.querySelector(`[data-mp-tab="${next}"]`).focus();
        });
    });

    let searchTimer = null;
    $('[data-mp-search]').addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadLibrary({ reset: true }), 300);
    });
    $('[data-mp-more]').addEventListener('click', () => loadLibrary({ reset: false }));

    const fileInput = $('[data-mp-file]');
    fileInput.addEventListener('change', () => chooseFile(fileInput.files[0]));
    const drop = $('[data-mp-drop]');
    ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (event) => { event.preventDefault(); drop.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, (event) => { event.preventDefault(); drop.classList.remove('is-over'); }));
    drop.addEventListener('drop', (event) => chooseFile(event.dataTransfer.files[0]));

    $('[data-mp-upload]').addEventListener('click', upload);
    $('[data-mp-use]').addEventListener('click', useSelected);
    $('[data-mp-alt]').addEventListener('input', refreshUseButton);

    modalEl.addEventListener('hidden.bs.modal', () => {
        if (state?.resolve) state.resolve(state.result || null);
        if (state?.pendingPreviewUrl) URL.revokeObjectURL(state.pendingPreviewUrl);
        state = null;
    });
}

function showTab(name) {
    modalEl.querySelectorAll('[data-mp-tab]').forEach((tab) => {
        const active = tab.dataset.mpTab === name;
        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.tabIndex = active ? 0 : -1;
    });
    modalEl.querySelectorAll('[data-mp-pane]').forEach((pane) => { pane.hidden = pane.dataset.mpPane !== name; });
    state.tab = name;
    $('[data-mp-upload]').hidden = name !== 'upload' || !state.pendingFile;
    $('[data-mp-use]').hidden = name === 'upload' && !!state.pendingFile;
    if (name === 'upload' && !state.pendingFile) clearDetails();
    if (name === 'library' && state.selected) showDetails(state.selected);
}

async function loadLibrary({ reset }) {
    if (reset) {
        state.page = 1;
        $('[data-mp-grid]').innerHTML = '';
    }
    const query = new URLSearchParams({ page: state.page, q: $('[data-mp-search]').value.trim() });
    $('[data-mp-status]').textContent = 'Loading images…';

    try {
        const response = await fetch(`${endpoints().list}?${query}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();
        const data = await response.json();
        data.items.forEach((item) => $('[data-mp-grid]').appendChild(tile(item)));
        state.page = data.next_page || state.page;
        $('[data-mp-more]').hidden = !data.next_page;
        $('[data-mp-empty]').hidden = $('[data-mp-grid]').children.length > 0;
        $('[data-mp-status]').textContent = `${data.total} ${data.total === 1 ? 'image' : 'images'} in the library`;
    } catch {
        $('[data-mp-status]').textContent = 'The media library could not be loaded. Check your connection and try again.';
    }
}

function tile(item) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'media-picker-tile';
    button.setAttribute('aria-pressed', 'false');
    button.innerHTML = `<img src="${escapeHtml(item.url)}" alt="" loading="lazy"><span>${escapeHtml(item.title)}</span>${item.alt ? '' : '<span class="media-picker-flag">No description</span>'}`;
    button.setAttribute('aria-label', `${item.title}${item.alt ? ` — ${item.alt}` : ' — no description yet'}`);
    button.addEventListener('click', () => {
        modalEl.querySelectorAll('.media-picker-tile[aria-pressed="true"]').forEach((b) => b.setAttribute('aria-pressed', 'false'));
        button.setAttribute('aria-pressed', 'true');
        state.selected = item;
        showDetails(item);
    });
    button.addEventListener('dblclick', () => useSelected());
    return button;
}

function showDetails(item) {
    $('[data-mp-details]').hidden = false;
    $('[data-mp-preview]').src = item.url;
    $('[data-mp-meta]').textContent = [item.title, item.width ? `${item.width} × ${item.height} px` : '', item.size].filter(Boolean).join(' · ');
    $('[data-mp-alt]').value = item.alt || state.suggestedAlt || '';
    $('[data-mp-caption]').value = item.caption || '';
    $('[data-mp-detail-error]').hidden = true;
    refreshUseButton();
}

function clearDetails() {
    $('[data-mp-details]').hidden = true;
    $('[data-mp-use]').disabled = true;
}

function refreshUseButton() {
    const hasImage = !!state?.selected;
    const needsAlt = state?.requireAlt && !$('[data-mp-alt]').value.trim();
    $('[data-mp-use]').disabled = !hasImage || needsAlt;
    $('[data-mp-use]').title = needsAlt ? 'Add a description of the image first' : '';
}

function chooseFile(file) {
    if (!file) return;
    const errorBox = $('[data-mp-upload-error]');
    errorBox.hidden = true;

    if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) {
        showUploadError('This type of file cannot be used. Choose a JPG, PNG, WebP or GIF image.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showUploadError('This image is too large. Choose one smaller than 5 MB.');
        return;
    }

    state.pendingFile = file;
    if (state.pendingPreviewUrl) URL.revokeObjectURL(state.pendingPreviewUrl);
    state.pendingPreviewUrl = URL.createObjectURL(file);
    state.selected = null;
    $('[data-mp-details]').hidden = false;
    $('[data-mp-preview]').src = state.pendingPreviewUrl;
    $('[data-mp-meta]').textContent = `${file.name} · ${Math.max(1, Math.round(file.size / 1024))} KB`;
    $('[data-mp-alt]').value = state.suggestedAlt || '';
    $('[data-mp-caption]').value = '';
    $('[data-mp-upload]').hidden = false;
    $('[data-mp-use]').hidden = true;
    $('[data-mp-status]').textContent = 'Add a description, then upload the image.';
    $('[data-mp-alt]').focus();
}

function showUploadError(message) {
    $('[data-mp-upload-error-text]').textContent = message;
    $('[data-mp-upload-error]').hidden = false;
}

async function upload() {
    if (!state.pendingFile) return;
    if (state.requireAlt && !$('[data-mp-alt]').value.trim()) {
        $('[data-mp-detail-error]').textContent = 'Describe the image before uploading it.';
        $('[data-mp-detail-error]').hidden = false;
        $('[data-mp-alt]').focus();
        return;
    }

    const button = $('[data-mp-upload]');
    const form = new FormData();
    form.append('file', state.pendingFile);
    form.append('alt_text', $('[data-mp-alt]').value.trim());
    form.append('caption', $('[data-mp-caption]').value.trim());
    button.disabled = true;
    $('[data-mp-status]').textContent = 'Uploading…';

    try {
        const response = await fetch(endpoints().upload, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: form,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message = Object.values(data.errors || {}).flat()[0] || data.message || 'The image could not be uploaded. Please try again.';
            showUploadError(response.status === 413 ? 'This image is too large for the server. Choose a smaller file.' : message);
            $('[data-mp-status]').textContent = '';
            return;
        }

        state.pendingFile = null;
        state.selected = data.item;
        state.result = data.item;
        toast('Image uploaded to the media library.');
        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    } catch {
        showUploadError('The image could not be uploaded. Check your connection and try again.');
    } finally {
        button.disabled = false;
    }
}

async function useSelected() {
    const item = state?.selected;
    if (!item) return;
    const alt = $('[data-mp-alt]').value.trim();
    const caption = $('[data-mp-caption]').value.trim();

    if (state.requireAlt && !alt) {
        $('[data-mp-detail-error]').textContent = 'Describe the image before using it.';
        $('[data-mp-detail-error]').hidden = false;
        $('[data-mp-alt]').focus();
        return;
    }

    // Save a new description back to the library so the next page that uses
    // the image starts with it.
    if (alt !== (item.alt || '') || caption !== (item.caption || '')) {
        try {
            const response = await fetch(endpoints().update.replace('__ID__', item.id), {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ alt_text: alt, caption }),
            });
            if (response.ok) Object.assign(item, (await response.json()).item);
        } catch {
            // The chosen image still works; only the library copy of the description was not updated.
        }
    }

    state.result = { ...item, alt, caption };
    window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

export function openMediaPicker({ title = 'Choose an image', requireAlt = false, suggestedAlt = '' } = {}) {
    if (!window.bootstrap || !endpoints().list) return Promise.resolve(null);
    if (!modalEl) buildModal();

    return new Promise((resolve) => {
        state = { resolve, result: null, selected: null, pendingFile: null, page: 1, requireAlt, suggestedAlt, tab: 'library' };
        $('#mediaPickerTitle').textContent = title;
        $('[data-mp-alt-required]').hidden = !requireAlt;
        $('[data-mp-search]').value = '';
        $('[data-mp-upload-error]').hidden = true;
        clearDetails();
        showTab('library');
        loadLibrary({ reset: true });
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
}

/**
 * An image field: a hidden path, an alt text box, a preview and Choose /
 * Replace / Remove buttons. Markup comes from components/admin/image-picker.
 */
export function initMediaFields(root = document) {
    root.querySelectorAll('[data-media-field]:not([data-media-ready])').forEach((field) => {
        field.dataset.mediaReady = '1';
    });
}

document.addEventListener('click', async (event) => {
    const chooser = event.target.closest('[data-media-choose]');
    const remover = event.target.closest('[data-media-remove]');
    const field = (chooser || remover)?.closest('[data-media-field]');
    if (!field) return;
    event.preventDefault();

    const pathInput = field.querySelector('[data-media-path]');
    const altInput = field.querySelector('[data-media-alt]');
    const preview = field.querySelector('[data-media-preview]');
    const changed = () => {
        field.classList.toggle('has-image', !!pathInput.value);
        field.querySelector('[data-media-empty]')?.toggleAttribute('hidden', !!pathInput.value);
        field.querySelectorAll('[data-media-when-set]').forEach((el) => { el.hidden = !pathInput.value; });
        const chooseLabel = field.querySelector('[data-media-choose-label]');
        if (chooseLabel) chooseLabel.textContent = pathInput.value ? 'Replace image' : 'Choose image';
        pathInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    if (chooser) {
        const item = await openMediaPicker({
            title: field.dataset.mediaTitle || 'Choose an image',
            requireAlt: field.dataset.mediaAlt === '1',
            suggestedAlt: altInput?.value || '',
        });
        if (!item) {
            chooser.focus();
            return;
        }
        pathInput.value = item.path;
        if (preview) {
            preview.src = item.url;
            preview.alt = item.alt || '';
        }
        if (altInput && (item.alt || !altInput.value)) altInput.value = item.alt || '';
        changed();
        (altInput && !altInput.value ? altInput : chooser).focus();
    } else {
        const ok = await askConfirm({
            title: 'Remove this image?',
            message: 'The image is removed from this section only. It stays in the media library, so you can choose it again later.',
            button: 'Remove image',
        });
        if (!ok) return;
        pathInput.value = '';
        if (preview) preview.removeAttribute('src');
        if (altInput) altInput.value = '';
        changed();
        field.querySelector('[data-media-choose]')?.focus();
    }
});
