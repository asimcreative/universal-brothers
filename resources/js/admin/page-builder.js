// The page builder: arranging a page's sections.
//
// Every section is a server-rendered card (admin/pages/partials/section) whose
// fields are named sections[<id>][...]. The browser submits them in document
// order, so moving a card in the page IS the new order — nothing is renumbered
// on submit. Reordering works by drag and drop, by the up/down buttons, and
// from the keyboard on a section's handle.
//
// Moves, hiding, duplicating, adding and deleting can be undone with the Undo
// button (or Ctrl+Z when the cursor is not in a text box).

import Sortable from 'sortablejs';
import { isValidLink, linkSuggestion, videoEmbedUrl } from './links';
import { announce, askConfirm, hideModal, toast } from './ui';
import './media-picker';

function newSectionId() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let id = 's_';
    for (let i = 0; i < 10; i += 1) id += chars[Math.floor(Math.random() * chars.length)];
    return id;
}

export function initPageBuilder() {
    const form = document.querySelector('[data-page-builder]');
    if (!form) return;

    const list = form.querySelector('[data-sections]');
    const undoButton = form.querySelector('[data-builder-undo]');
    const dirtyStatus = form.querySelector('[data-dirty-status]');
    const libraryModal = document.getElementById('pbLibraryModal');
    const previewModal = document.getElementById('pbPreviewModal');
    const saveBlockModal = document.getElementById('pbSaveBlockModal');
    const scheduleModal = document.getElementById('pbScheduleModal');
    const history = [];
    let dirty = false;
    let submitting = false;
    let insertAfter = null;
    let saveBlockTarget = null;

    const sections = () => Array.from(list.querySelectorAll(':scope > [data-section]'));
    const nameOf = (section) => section.dataset.sectionName || 'Section';

    /* ---------------------------------------------------------------- state */

    function markDirty() {
        if (!dirty) {
            dirty = true;
            dirtyStatus.innerHTML = '<i class="bi bi-circle-fill text-warning" aria-hidden="true"></i>Unsaved changes';
        }
    }

    function remember(label, undo) {
        history.push({ label, undo });
        if (history.length > 30) history.shift();
        undoButton.disabled = false;
        undoButton.title = `Undo: ${label}`;
        markDirty();
    }

    function undoLast() {
        const step = history.pop();
        if (!step) return;
        step.undo();
        undoButton.disabled = history.length === 0;
        undoButton.title = history.length ? `Undo: ${history[history.length - 1].label}` : '';
        renumber();
        markDirty();
        announce(`Undone: ${step.label}`);
        toast(`Undone: ${step.label}`, { tone: 'info', timeout: 3000 });
    }

    function renumber() {
        const all = sections();
        all.forEach((section, i) => {
            const position = section.querySelector('[data-section-position]');
            if (position) position.textContent = `Section ${i + 1}`;
            const up = section.querySelector('[data-section-action="up"]');
            const down = section.querySelector('[data-section-action="down"]');
            if (up) up.disabled = i === 0;
            if (down) down.disabled = i === all.length - 1;
        });
        form.querySelectorAll('[data-section-count]').forEach((el) => { el.textContent = all.length; });
        const empty = form.querySelector('[data-sections-empty]');
        if (empty) empty.hidden = all.length > 0;
    }

    /* ---------------------------------------------------------------- moving */

    function move(section, delta, { focus = null } = {}) {
        const all = sections();
        const from = all.indexOf(section);
        const to = from + delta;
        if (to < 0 || to >= all.length) return;

        const reference = delta < 0 ? all[to] : all[to].nextElementSibling;
        list.insertBefore(section, reference);
        renumber();
        remember(`move ${nameOf(section)}`, () => {
            const others = sections().filter((s) => s !== section);
            list.insertBefore(section, others[from] ?? null);
        });
        announce(`${nameOf(section)} moved to position ${to + 1} of ${all.length}`);
        (focus || section.querySelector('[data-drag-handle]'))?.focus();
    }

    Sortable.create(list, {
        handle: '[data-drag-handle]',
        draggable: '[data-section]',
        animation: 150,
        // Pointer-event dragging rather than the browser's own drag and drop:
        // the same behaviour with a mouse, a finger or a pen.
        forceFallback: true,
        fallbackTolerance: 4,
        ghostClass: 'pb-sortable-ghost',
        chosenClass: 'pb-sortable-chosen',
        onStart: (event) => { event.item.dataset.dragFrom = String(event.oldIndex); },
        onEnd: (event) => {
            const from = Number(event.item.dataset.dragFrom);
            const to = event.newIndex;
            if (from === to) return;
            const section = event.item;
            renumber();
            remember(`move ${nameOf(section)}`, () => {
                const current = sections().filter((s) => s !== section);
                list.insertBefore(section, current[from] ?? null);
            });
            announce(`${nameOf(section)} moved to position ${to + 1} of ${sections().length}`);
        },
    });

    /* ------------------------------------------------------------ open/close */

    function setOpen(section, open) {
        const toggle = section.querySelector('[data-section-toggle]');
        const body = section.querySelector('.pb-section-body');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.hidden = !open;
        section.classList.toggle('is-open', open);
    }

    /* ------------------------------------------------------------ visibility */

    function setVisible(section, visible) {
        section.querySelector('[data-section-visible]').value = visible ? '1' : '0';
        section.classList.toggle('is-hidden', !visible);
        section.querySelector('[data-hidden-badge]').hidden = visible;
        const button = section.querySelector('[data-section-action="visibility"]');
        button.setAttribute('aria-pressed', visible ? 'false' : 'true');
        button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${nameOf(section)}`);
        button.title = visible ? 'Hide from visitors' : 'Show to visitors';
        button.innerHTML = `<i class="bi ${visible ? 'bi-eye' : 'bi-eye-slash'}" aria-hidden="true"></i>`;
    }

    /* ------------------------------------------------------------- duplicate */

    function retag(root, oldId, newId) {
        const nodes = [root, ...root.querySelectorAll('*')];
        nodes.forEach((node) => {
            Array.from(node.attributes).forEach((attr) => {
                if (attr.value.includes(oldId)) node.setAttribute(attr.name, attr.value.split(oldId).join(newId));
            });
            if (node.tagName === 'TEMPLATE') node.innerHTML = node.innerHTML.split(oldId).join(newId);
        });
    }

    function duplicate(section) {
        const oldId = section.dataset.sectionId;
        const newId = newSectionId();
        const copy = section.cloneNode(true);

        // Carry over what the admin has typed or chosen, which cloneNode does
        // not always copy (select choices, ticked boxes).
        const originals = section.querySelectorAll('select, input[type="checkbox"], input[type="radio"]');
        copy.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach((field, i) => {
            if (field.tagName === 'SELECT') field.value = originals[i].value;
            else field.checked = originals[i].checked;
        });
        const originalAreas = section.querySelectorAll('textarea');
        copy.querySelectorAll('textarea').forEach((area, i) => { area.value = originalAreas[i].value; });

        retag(copy, oldId, newId);
        // The copy is made while the original's actions menu is still open;
        // without this the copy shows an open menu that nothing can close.
        copy.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
            toggle.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        });
        copy.querySelectorAll('.dropdown-menu').forEach((menu) => {
            menu.classList.remove('show');
            menu.removeAttribute('style');
            menu.removeAttribute('data-popper-placement');
        });
        section.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => window.bootstrap?.Dropdown.getInstance(toggle)?.hide());
        copy.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        copy.querySelectorAll('.invalid-feedback').forEach((el) => el.remove());
        copy.classList.remove('has-errors');

        section.insertAdjacentElement('afterend', copy);
        setOpen(copy, true);
        renumber();
        remember(`duplicate ${nameOf(section)}`, () => copy.remove());
        announce(`${nameOf(section)} duplicated. The copy is below the original.`);
        copy.focus();
        copy.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------------------------------------------------------- delete */

    async function remove(section) {
        const ok = await askConfirm({
            title: `Delete this ${nameOf(section).toLowerCase()} section?`,
            message: 'The section and everything in it is removed from this page. You can undo straight afterwards, and nothing changes on the website until you save and publish.',
            button: 'Delete section',
        });
        if (!ok) return;

        const next = section.nextElementSibling;
        const neighbour = next || section.previousElementSibling;
        section.remove();
        renumber();

        const restore = () => {
            list.insertBefore(section, next && next.parentElement === list ? next : null);
            section.focus();
        };
        remember(`delete ${nameOf(section)}`, restore);
        toast(`${nameOf(section)} deleted.`, {
            tone: 'warning',
            action: { label: 'Undo', onClick: undoLast },
            timeout: 10000,
        });
        (neighbour?.querySelector('[data-drag-handle]') || form.querySelector('[data-open-library]'))?.focus();
    }

    /* ------------------------------------------------------------ adding */

    async function insertSection(query) {
        try {
            const response = await fetch(`${form.dataset.sectionUrl}?${new URLSearchParams(query)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'The section could not be added.');
            const data = await response.json();

            const holder = document.createElement('div');
            holder.innerHTML = data.html.trim();
            const section = holder.firstElementChild;

            if (insertAfter && insertAfter.parentElement === list) insertAfter.insertAdjacentElement('afterend', section);
            else list.appendChild(section);

            await hideModal(libraryModal);
            renumber();
            remember(`add ${nameOf(section)}`, () => section.remove());
            announce(`${nameOf(section)} added as section ${sections().indexOf(section) + 1}.`);
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            section.focus();
        } catch (error) {
            toast(error.message || 'The section could not be added. Check your connection and try again.', { tone: 'danger' });
        } finally {
            insertAfter = null;
        }
    }

    function openLibrary(after = null) {
        insertAfter = after;
        window.bootstrap.Modal.getOrCreateInstance(libraryModal).show();
    }

    libraryModal?.addEventListener('shown.bs.modal', () => libraryModal.querySelector('[data-library-search]')?.focus());

    libraryModal?.addEventListener('click', (event) => {
        const add = event.target.closest('[data-add-block]');
        const insert = event.target.closest('[data-insert-block]');
        if (add) insertSection({ type: add.dataset.addBlock });
        if (insert) insertSection({ block: insert.dataset.insertBlock, mode: insert.dataset.mode });
    });

    libraryModal?.querySelector('[data-library-search]')?.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        let shown = 0;
        libraryModal.querySelectorAll('[data-library-group]').forEach((group) => {
            let groupShown = 0;
            group.querySelectorAll('[data-add-block]').forEach((card) => {
                const match = !term || card.dataset.search.includes(term);
                card.hidden = !match;
                if (match) groupShown += 1;
            });
            group.hidden = groupShown === 0;
            shown += groupShown;
        });
        libraryModal.querySelector('[data-library-none]').hidden = shown > 0;
    });

    wireTabs(libraryModal, '[data-library-tab]', '[data-library-panel]');

    /* -------------------------------------------------- section actions */

    form.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-section-toggle]');
        const actionButton = event.target.closest('[data-section-action]');

        if (toggle) {
            const section = toggle.closest('[data-section]');
            setOpen(section, toggle.getAttribute('aria-expanded') !== 'true');
            return;
        }
        if (!actionButton) return;

        const section = actionButton.closest('[data-section]');
        switch (actionButton.dataset.sectionAction) {
            case 'up': move(section, -1, { focus: actionButton }); break;
            case 'down': move(section, 1, { focus: actionButton }); break;
            case 'visibility': {
                const visible = section.querySelector('[data-section-visible]').value !== '1';
                setVisible(section, visible);
                remember(`${visible ? 'show' : 'hide'} ${nameOf(section)}`, () => setVisible(section, !visible));
                announce(`${nameOf(section)} is now ${visible ? 'shown to visitors' : 'hidden from visitors'}.`);
                break;
            }
            case 'duplicate': duplicate(section); break;
            case 'delete': remove(section); break;
            case 'add-below': openLibrary(section); break;
            case 'save-block': {
                saveBlockTarget = section;
                const heading = section.querySelector('[data-summary-source="heading"]')?.value || '';
                saveBlockModal.querySelector('#pb-block-name').value = heading;
                saveBlockModal.querySelector('#pb-block-name-error').hidden = true;
                window.bootstrap.Modal.getOrCreateInstance(saveBlockModal).show();
                break;
            }
            default: break;
        }
    });

    form.querySelectorAll('[data-open-library]').forEach((button) => button.addEventListener('click', () => openLibrary(null)));

    form.querySelectorAll('[data-builder-expand]').forEach((button) => button.addEventListener('click', () => {
        sections().forEach((section) => setOpen(section, button.dataset.builderExpand === 'open'));
    }));

    // Keyboard moves on a section's handle.
    list.addEventListener('keydown', (event) => {
        const handle = event.target.closest('[data-drag-handle]');
        if (!handle || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        move(handle.closest('[data-section]'), event.key === 'ArrowUp' ? -1 : 1);
    });

    undoButton.addEventListener('click', undoLast);
    document.addEventListener('keydown', (event) => {
        if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'z' || event.shiftKey) return;
        if (event.target.closest('input, textarea, select, [contenteditable="true"], .modal')) return;
        if (!history.length) return;
        event.preventDefault();
        undoLast();
    });

    /* ------------------------------------------- live summaries and fields */

    form.addEventListener('input', (event) => {
        const field = event.target;
        if (!field.closest('[data-builder-tab]')) markDirty();

        const section = field.closest('[data-section]');
        const source = field.dataset?.summarySource;
        if (section && source && ['heading', 'left_heading', 'button_text'].includes(source) && !field.closest('[data-item]')) {
            const summary = section.querySelector('[data-section-summary]');
            if (summary && field.value.trim()) summary.textContent = field.value.trim();
        }
    });

    form.addEventListener('change', () => markDirty());

    initSectionFields(form, { remember });

    /* -------------------------------------------------- tabs and jumping */

    function wireTabs(root, tabSelector, panelSelector) {
        if (!root) return null;
        const tabs = Array.from(root.querySelectorAll(tabSelector));
        const show = (tab, focus = false) => {
            tabs.forEach((t) => {
                const active = t === tab;
                t.classList.toggle('active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                t.tabIndex = active ? 0 : -1;
                const panel = root.querySelector(t.getAttribute('href'));
                if (panel) panel.hidden = !active;
            });
            if (focus) tab.focus();
        };
        tabs.forEach((tab, i) => {
            tab.addEventListener('click', (event) => { event.preventDefault(); show(tab); });
            tab.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (i + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                show(tabs[next], true);
            });
        });
        root.querySelectorAll(panelSelector).forEach((panel, i) => { panel.hidden = i !== 0; });
        return show;
    }

    const showBuilderTab = wireTabs(form, '[data-builder-tab]', '[data-builder-panel]');

    function reveal(targetId) {
        const target = document.getElementById(targetId);
        if (!target) return;
        const panel = target.closest('[data-builder-panel]');
        if (panel && panel.hidden) {
            const tab = form.querySelector(`[data-builder-tab][href="#${panel.id}"]`);
            if (tab) showBuilderTab(tab);
        }
        const section = target.matches('[data-section]') ? target : target.closest('[data-section]');
        if (section) setOpen(section, true);
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const focusable = section?.querySelector('.is-invalid, .rt-shell.is-invalid .rt-editable') || target;
        window.setTimeout(() => (focusable.focus ? focusable.focus({ preventScroll: true }) : null), 350);
    }

    document.addEventListener('click', (event) => {
        const jump = event.target.closest('[data-jump-to]');
        if (!jump) return;
        event.preventDefault();
        reveal(jump.dataset.jumpTo);
    });

    // After a failed publish, open the first tab that has a problem.
    const firstInvalid = form.querySelector('.is-invalid, .has-errors');
    if (firstInvalid) {
        const panel = firstInvalid.closest('[data-builder-panel]');
        const tab = panel && form.querySelector(`[data-builder-tab][href="#${panel.id}"]`);
        if (tab) showBuilderTab(tab);
    }
    document.querySelector('[data-validation-summary]')?.focus();

    /* -------------------------------------------------- title and address */

    const slugInput = form.querySelector('[data-page-slug]');
    slugInput?.addEventListener('input', () => {
        const preview = form.querySelector('[data-slug-preview]');
        if (preview) preview.textContent = `${form.dataset.publicUrl}/${slugInput.value}`;
        const warning = form.querySelector('[data-slug-warning]');
        if (warning) warning.hidden = slugInput.value === slugInput.dataset.liveSlug;
    });

    /* ------------------------------------------------ saving and publishing */

    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const intent = event.submitter?.dataset.intentButton;
        if (intent) form.querySelector('[data-intent]').value = intent;
        submitting = true;
    });

    saveBlockModal?.querySelector('[data-save-block-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const name = saveBlockModal.querySelector('#pb-block-name');
        if (!name.value.trim()) {
            saveBlockModal.querySelector('#pb-block-name-error').hidden = false;
            name.focus();
            return;
        }
        form.querySelector('[data-block-field="name"]').value = name.value.trim();
        form.querySelector('[data-block-field="description"]').value = saveBlockModal.querySelector('#pb-block-description').value.trim();
        form.querySelector('[data-block-field="category"]').value = saveBlockModal.querySelector('#pb-block-category').value;
        form.querySelector('[data-intent]').value = `save_block:${saveBlockTarget.dataset.sectionId}`;
        submitting = true;
        form.submit();
    });

    scheduleModal?.querySelector('[data-schedule-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = scheduleModal.querySelector('#pb-schedule-at');
        const when = input.value ? new Date(input.value) : null;
        if (!when || Number.isNaN(when.getTime()) || when.getTime() < Date.now() + 60 * 1000) {
            scheduleModal.querySelector('#pb-schedule-error').hidden = false;
            input.focus();
            return;
        }
        form.querySelector('[data-schedule-field]').value = when.toISOString();
        form.querySelector('[data-intent]').value = 'schedule';
        submitting = true;
        form.submit();
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });

    /* ---------------------------------------------------------------- preview */

    const frame = previewModal?.querySelector('[data-preview-frame]');
    previewModal?.addEventListener('show.bs.modal', () => {
        previewModal.querySelector('[data-preview-unsaved]').hidden = !dirty;
        if (!frame.getAttribute('src')) frame.src = form.dataset.previewUrl;
        else frame.contentWindow?.location.reload();
    });
    previewModal?.querySelectorAll('[data-preview-width]').forEach((button) => button.addEventListener('click', () => {
        previewModal.querySelectorAll('[data-preview-width]').forEach((b) => {
            b.classList.toggle('active', b === button);
            b.setAttribute('aria-pressed', b === button ? 'true' : 'false');
        });
        frame.style.width = button.dataset.previewWidth;
        announce(`Preview width: ${button.textContent.trim()}`);
    }));

    if (form.dataset.openPreview === '1' && previewModal) {
        window.bootstrap.Modal.getOrCreateInstance(previewModal).show();
    }

    /* ------------------------------------------------------ search preview */

    initSeoFields(form);

    renumber();
}

/* -------------------------------------------------------------------------
 * Section fields: repeating lists, conditional fields, icon choices and link
 * checks. Shared by the page builder and the saved-section form.
 * ---------------------------------------------------------------------- */

function refreshItems(container) {
    const items = Array.from(container.querySelectorAll(':scope > [data-items-list] > [data-item]'));
    const max = Number(container.dataset.max || 12);
    const label = (container.dataset.itemLabel || 'Item').toLowerCase();
    items.forEach((item, i) => {
        const number = item.querySelector(':scope > .pb-item-head [data-item-number]');
        if (number) number.textContent = i + 1;
        item.querySelectorAll(':scope > .pb-item-head [data-item-action]').forEach((button) => {
            const verb = { up: 'Move', down: 'Move', remove: 'Remove' }[button.dataset.itemAction];
            const suffix = { up: ' up', down: ' down', remove: '' }[button.dataset.itemAction];
            button.setAttribute('aria-label', `${verb} ${label} ${i + 1}${suffix}`);
        });
        const up = item.querySelector(':scope > .pb-item-head [data-item-action="up"]');
        const down = item.querySelector(':scope > .pb-item-head [data-item-action="down"]');
        if (up) up.disabled = i === 0;
        if (down) down.disabled = i === items.length - 1;
    });
    const count = container.querySelector(':scope > .d-flex [data-items-count]');
    if (count) count.textContent = `${items.length} of up to ${max}`;
    const add = container.querySelector(':scope > .d-flex [data-item-add]');
    if (add) add.disabled = items.length >= max;
}

function applyShowWhen(container) {
    if (!container) return;
    const values = {};
    container.querySelectorAll(':scope > div > select[data-field-name], :scope > div > div > select[data-field-name]').forEach((select) => {
        values[select.dataset.fieldName] = select.value;
    });
    container.querySelectorAll(':scope > [data-show-when]').forEach((col) => {
        const rules = JSON.parse(col.dataset.showWhen || '{}');
        col.hidden = !Object.entries(rules).every(([name, wanted]) => String(values[name] ?? '') === String(wanted));
    });
}

function itemHasContent(item) {
    return Array.from(item.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), textarea, input[data-media-path]'))
        .some((field) => field.value.trim() !== '');
}

/** The highest list position used by any field in the list, so a new entry never reuses a name. */
function nextItemIndex(listEl) {
    let highest = -1;
    listEl.querySelectorAll(':scope > [data-item]').forEach((item) => {
        const named = item.querySelector('[name]');
        const found = named ? named.name.match(/\[(\d+)\]\[[^\]]+\](?:\[(?:path|alt)\])?$/) : null;
        if (found) highest = Math.max(highest, Number(found[1]));
    });
    return highest + 1;
}

export function initSectionFields(root, { remember = () => {} } = {}) {
    if (!root || root.dataset.sectionFieldsReady) return;
    root.dataset.sectionFieldsReady = '1';

    function addItem(container) {
        const template = container.querySelector(':scope > template[data-item-template]');
        const listEl = container.querySelector(':scope > [data-items-list]');
        const index = nextItemIndex(listEl);
        const holder = document.createElement('div');
        holder.innerHTML = template.innerHTML.split('__ITEM__').join(String(index)).trim();
        const item = holder.firstElementChild;
        listEl.appendChild(item);
        refreshItems(container);
        applyShowWhen(item.querySelector('.pb-fields'));
        remember(`add ${(container.dataset.itemLabel || 'item').toLowerCase()}`, () => { item.remove(); refreshItems(container); });
        item.querySelector('input:not([type="hidden"]), select, textarea, button[data-media-choose]')?.focus();
        item.dispatchEvent(new Event('change', { bubbles: true }));
        announce(`${container.dataset.itemLabel || 'Item'} added.`);
    }

    root.addEventListener('click', async (event) => {
        const add = event.target.closest('[data-item-add]');
        const action = event.target.closest('[data-item-action]');

        if (add) {
            addItem(add.closest('[data-items]'));
            return;
        }
        if (!action) return;

        const item = action.closest('[data-item]');
        const container = item.closest('[data-items]');
        const label = container.dataset.itemLabel || 'Item';

        if (action.dataset.itemAction === 'remove') {
            if (itemHasContent(item)) {
                const ok = await askConfirm({ title: `Remove this ${label.toLowerCase()}?`, message: 'What you entered for it is removed.', button: 'Remove' });
                if (!ok) return;
            }
            const next = item.nextElementSibling;
            const parent = item.parentElement;
            item.remove();
            refreshItems(container);
            remember(`remove ${label.toLowerCase()}`, () => { parent.insertBefore(item, next); refreshItems(container); });
            container.dispatchEvent(new Event('change', { bubbles: true }));
            announce(`${label} removed.`);
            container.querySelector('[data-item-add]')?.focus();
            return;
        }

        const sibling = action.dataset.itemAction === 'up' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (action.dataset.itemAction === 'up') item.parentElement.insertBefore(item, sibling);
        else item.parentElement.insertBefore(sibling, item);
        refreshItems(container);
        item.dispatchEvent(new Event('change', { bubbles: true }));
        action.focus();
        announce(`${label} moved ${action.dataset.itemAction}.`);
    });

    root.addEventListener('change', (event) => {
        const field = event.target;
        if (!(field instanceof Element)) return;
        if (field.matches('[data-icon-select]')) {
            const preview = field.closest('.pb-icon-select')?.querySelector('[data-icon-preview]');
            if (preview) preview.className = `bi ${field.value}`;
        }
        if (field.matches('select[data-field-name]')) applyShowWhen(field.closest('.pb-fields'));
    });

    root.addEventListener('focusout', (event) => {
        const field = event.target;
        if (!(field instanceof Element) || !field.matches('[data-link-field], [data-video-field]')) return;
        const feedback = field.parentElement?.querySelector('[data-field-feedback]');
        if (!feedback) return;
        const value = field.value.trim();
        let message = '';
        if (field.matches('[data-link-field]') && value && !isValidLink(value)) {
            message = `“${value}” is not a link the website can open. ${linkSuggestion(value)}`;
        } else if (field.matches('[data-video-field]') && value && !videoEmbedUrl(value)) {
            message = 'This is not a YouTube or Vimeo video link. Copy the address of the video page and paste it here.';
        }
        feedback.textContent = message;
        feedback.hidden = !message;
        field.classList.toggle('is-invalid', !!message);
    });

    root.querySelectorAll('[data-items]').forEach(refreshItems);
    root.querySelectorAll('.pb-fields').forEach(applyShowWhen);
}

/** Live Google and sharing previews, and simple tips for the main search phrase. */
export function initSeoFields(root = document) {
    const wrap = root.querySelector('[data-seo-fields]');
    if (!wrap) return;

    const field = (name) => wrap.querySelector(`[data-seo="${name}"]`);
    const preview = (name) => wrap.querySelector(`[data-seo-preview="${name}"]`);
    const titleInput = root.querySelector('[data-page-title]');
    const slugInput = root.querySelector('[data-page-slug]');

    const update = () => {
        const fallbackTitle = titleInput ? `${titleInput.value || 'Page title'} | Universal Brothers` : wrap.dataset.fallbackTitle;
        const title = field('title').value.trim() || fallbackTitle;
        const description = field('description').value.trim() || wrap.dataset.fallbackDescription;
        preview('title').textContent = title;
        preview('description').textContent = description;
        preview('og-title').textContent = field('og-title').value.trim() || title;
        preview('og-description').textContent = field('og-description').value.trim() || description;
        field('title').placeholder = fallbackTitle;
        if (slugInput) preview('url').textContent = `${root.dataset.publicUrl || ''}/${slugInput.value}`;

        const checks = wrap.querySelector('[data-seo-checks]');
        const keyword = field('keyword').value.trim().toLowerCase();
        if (!keyword) {
            checks.innerHTML = '';
            return;
        }
        const pageText = Array.from(root.querySelectorAll('[data-section] textarea, [data-section] input[type="text"]')).map((el) => el.value).join(' ').toLowerCase();
        const tips = [
            [title.toLowerCase().includes(keyword), 'The phrase is in the search title', 'Try including the phrase in the search title'],
            [description.toLowerCase().includes(keyword), 'The phrase is in the search description', 'Try including the phrase in the search description'],
            [pageText.includes(keyword), 'The phrase appears in the page text', 'The phrase does not appear in the page text yet'],
        ];
        checks.innerHTML = tips.map(([ok, good, bad]) => `<li class="${ok ? 'text-success' : 'text-warning-emphasis'}"><i class="bi ${ok ? 'bi-check-circle' : 'bi-lightbulb'} me-1" aria-hidden="true"></i>${ok ? good : bad}</li>`).join('');
    };

    wrap.addEventListener('input', update);
    titleInput?.addEventListener('input', update);
    slugInput?.addEventListener('input', update);

    wrap.addEventListener('input', (event) => {
        if (!event.target.matches('[data-media-path]')) return;
        const image = preview('image');
        const media = event.target.closest('[data-media-field]')?.querySelector('[data-media-preview]');
        image.style.backgroundImage = event.target.value && media?.src ? `url('${media.src}')` : '';
    });

    update();
}
