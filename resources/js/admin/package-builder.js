// The step-by-step Hajj package builder (admin/hajj-packages/form.blade.php).
//
// Everything here is an enhancement of a normal HTML form: every row the
// server knows about is rendered by Blade, every input has a real name, and
// saving is a plain POST. This file adds steps, repeaters, option boxes,
// pickers, copying and safety nets on top.
//
// DOM contract (see the Blade partials):
//   [data-step="key"]            a step panel; [data-step-button="key"] opens it
//   [data-rows="name"]           a repeater container; rows are [data-row="name"][data-index]
//   <template id="tpl-name">     a blank row, with __INDEX__ in its names
//   [data-field="x"]             an input inside a row, for filling rows from data
//   [data-option-group="name"]   the box holding one hotel option's rows

const REPEATERS = [
    'variants', 'room_options', 'accommodations', 'aziziya_room_options', 'aziziya_services',
    'itinerary', 'transportation', 'inclusions', 'exclusions', 'upgrades', 'notes', 'media',
];

const ROOM_TYPES = {
    quad: { label: 'Quad Sharing', occupancy: 4 },
    triple: { label: 'Triple Sharing', occupancy: 3 },
    double: { label: 'Double Sharing', occupancy: 2 },
    sharing_room: { label: 'Sharing Room', occupancy: '' },
    twin: { label: 'Twin Sharing', occupancy: 2 },
    single: { label: 'Single Room', occupancy: 1 },
};

const truthy = (value) => value === true || value === 1 || value === '1' || value === 'true';

/**
 * YYYY-MM-DD in the admin's own calendar. Date#toISOString() converts to UTC,
 * which in Pakistan (UTC+5) turns local midnight into the previous day — the
 * "next day" would repeat the same date.
 */
const localIsoDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/**
 * The admin's shared confirmation dialog as a promise, for decisions made in
 * script (removing an option, replacing a section) rather than by a form.
 */
function askConfirm({ title, message, button = 'Confirm', tone = 'danger' }) {
    const modalEl = document.getElementById('adminConfirmModal');
    if (!modalEl || !window.bootstrap) {
        return Promise.resolve(window.confirm(message));
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

        // Capture phase, and stopped there: the admin-wide form confirmation
        // listens on the same button. Removed again however the dialog closes,
        // so a dismissed question can never answer the next one.
        const onAccept = (e) => {
            e.stopImmediatePropagation();
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

/** Hides a Bootstrap modal and resolves once it has fully closed. */
function hideModal(modalEl) {
    return new Promise((resolve) => {
        if (!modalEl.classList.contains('show')) { resolve(); return; }
        modalEl.addEventListener('hidden.bs.modal', () => resolve(), { once: true });
        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });
}

export function initPackageBuilder() {
    const form = document.getElementById('package-builder');
    if (!form) return;

    const library = JSON.parse(document.getElementById('builder-library')?.textContent || '{}');
    const counters = {};
    let dirty = false;
    let silent = 0; // > 0 while the script itself is changing fields

    // ---------------------------------------------------------------------
    // Repeaters
    // ---------------------------------------------------------------------
    REPEATERS.forEach((name) => {
        const indexes = Array.from(form.querySelectorAll(`[data-row="${name}"]`)).map((row) => Number(row.dataset.index) || 0);
        counters[name] = indexes.length ? Math.max(...indexes) + 1 : 0;
    });

    const containerFor = (name, code = null) => {
        if (code !== null) {
            const group = form.querySelector(`[data-option-group="${name}"][data-option-code="${CSS.escape(code)}"]`)
                || form.querySelector(`[data-option-group="${name}"][data-option-code=""]`);
            if (group) return group.querySelector(`[data-rows="${name}"]`);
        }
        return form.querySelector(`[data-rows="${name}"]`);
    };

    const refreshEmpty = (container) => {
        const empty = container?.parentElement?.querySelector(':scope > [data-rows-empty]');
        if (empty) empty.hidden = container.querySelector('[data-row]') !== null;
    };

    function addRow(name, { values = {}, container = null, index = null, after = null } = {}) {
        const template = document.getElementById(`tpl-${name}`);
        const target = container || containerFor(name, values.variant_code !== undefined ? String(values.variant_code || '').toUpperCase() : null);
        if (!template || !target) return null;

        const i = index ?? counters[name]++;
        if (index !== null && index >= counters[name]) counters[name] = index + 1;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', i);
        const row = wrapper.firstElementChild;

        if (after) after.after(row); else target.appendChild(row);

        const group = target.closest('[data-option-group]');
        if (group && row.querySelector('[data-field="variant_code"]') && values.variant_code === undefined) {
            values = { ...values, variant_code: group.dataset.optionCode };
        }

        fillRow(row, values);
        enhanceRow(row);
        refreshEmpty(target);
        return row;
    }

    function fillRow(row, values) {
        silent++;
        Object.entries(values).forEach(([key, value]) => {
            const field = row.querySelector(`[data-field="${key}"]`);
            if (!field) return;

            if (field.type === 'checkbox') {
                field.checked = truthy(value);
            } else if (field.tagName === 'SELECT') {
                const wanted = value === null || value === undefined ? '' : String(value);
                if (wanted !== '' && !Array.from(field.options).some((o) => o.value === wanted)) {
                    field.add(new Option(wanted, wanted));
                }
                field.value = wanted;
            } else {
                field.value = value === null || value === undefined ? '' : value;
            }
        });

        if (row.dataset.row === 'room_options') syncRoomTypeSelect(row);
        if (row.dataset.row === 'accommodations') rebuildHotelPicker(row, values.hotel_id);
        row.querySelectorAll('[data-extra-toggle]').forEach(toggleExtra);
        refreshLibBadge(row);
        silent--;
    }

    // ---------------------------------------------------------------------
    // Saved (shared) content vs. this package's own copy
    // ---------------------------------------------------------------------
    // A row picked from Reusable Content keeps a link to it. The badge says
    // whether the row still matches the saved record or has been changed for
    // this package only — the saved record itself is never changed from here.
    const LINKED_ROWS = {
        accommodations: { link: 'hotel_id', source: 'hotels', compare: { hotel_name: 'name', star_rating: 'star_rating' } },
        inclusions: { link: 'service_item_id', source: 'inclusions', compare: { description: 'description' } },
        exclusions: { link: 'service_item_id', source: 'exclusions', compare: { description: 'description' } },
        notes: { link: 'note_template_id', source: 'notes', compare: { content: 'content' } },
        transportation: { link: 'transport_option_id', source: 'transport', compare: { from_location: 'from_location', to_location: 'to_location', price: 'price' } },
        upgrades: { link: 'upgrade_option_id', source: 'upgrades', compare: { name: 'name', price: 'price' } },
    };

    const sameValue = (a, b) => {
        const norm = (v) => {
            const s = String(v ?? '').trim();
            return s !== '' && !Number.isNaN(Number(s)) ? String(Number(s)) : s;
        };
        return norm(a) === norm(b);
    };

    const usedIn = (count) => (count ? ` · used in ${count} ${count === 1 ? 'package' : 'packages'}` : '');

    function refreshLibBadge(row) {
        const config = LINKED_ROWS[row.dataset.row];
        if (!config) return;
        const id = row.querySelector(`[data-field="${config.link}"]`)?.value;
        const item = id ? (library[config.source] || []).find((x) => String(x.id) === String(id)) : null;
        let badge = row.querySelector('[data-lib-badge]');

        if (!item) { badge?.remove(); return; }

        const changed = Object.entries(config.compare).some(([field, key]) => {
            const input = row.querySelector(`[data-field="${field}"]`);
            return input ? !sameValue(input.value, item[key]) : false;
        });

        if (!badge) {
            badge = document.createElement('span');
            badge.dataset.libBadge = '';
            const wrap = row.querySelector('.row-actions-wrap');
            const firstField = row.querySelector(`[data-field="${Object.keys(config.compare)[0]}"]`);
            if (wrap) wrap.prepend(badge); else firstField?.parentElement.append(badge);
        }
        badge.className = `lib-badge mb-1${changed ? ' is-changed' : ''}`;
        badge.innerHTML = changed
            ? '<i class="bi bi-pencil-square" aria-hidden="true"></i>Changed for this package only'
            : `<i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved content${escapeHtml(usedIn(item.used))}`;
    }

    function enhanceRow(row) {
        row.classList.add('is-flash');
        window.setTimeout(() => row.classList.remove('is-flash'), 1300);
    }

    function rowValues(row) {
        const values = {};
        row.querySelectorAll('[data-field]').forEach((field) => {
            values[field.dataset.field] = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
        });
        return values;
    }

    function clearRows(name) {
        form.querySelectorAll(`[data-row="${name}"]`).forEach((row) => {
            const container = row.parentElement;
            row.remove();
            refreshEmpty(container);
        });
    }

    const rowLabels = {
        variants: 'option', room_options: 'room type', accommodations: 'hotel', itinerary: 'day',
        transportation: 'transport', inclusions: 'item', exclusions: 'item', upgrades: 'additional option',
        notes: 'note', media: 'photo', aziziya_room_options: 'Aziziya room', aziziya_services: 'service',
    };

    form.addEventListener('click', async (event) => {
        const add = event.target.closest('[data-add-row]');
        if (add) {
            const name = add.dataset.addRow;
            const group = add.closest('[data-option-group]');
            const container = group ? group.querySelector(`[data-rows="${name}"]`) : containerFor(name);
            let values = {};
            if (name === 'itinerary') values = nextDayValues();
            const row = addRow(name, { container, values });
            row?.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
            markDirty();
            return;
        }

        const action = event.target.closest('[data-row-action]');
        if (action) {
            const row = action.closest('[data-row]');
            if (!row) return;
            const container = row.parentElement;

            switch (action.dataset.rowAction) {
                case 'up':
                    if (row.previousElementSibling) { row.previousElementSibling.before(row); enhanceRow(row); }
                    break;
                case 'down':
                    if (row.nextElementSibling) { row.nextElementSibling.after(row); enhanceRow(row); }
                    break;
                case 'duplicate': {
                    const values = rowValues(row);
                    if (row.dataset.row === 'itinerary') {
                        values.day_number = '';
                    }
                    addRow(row.dataset.row, { container, values, after: row });
                    break;
                }
                case 'remove':
                    if (row.dataset.row === 'variants') {
                        await removeOption(row);
                    } else {
                        row.remove();
                        refreshEmpty(container);
                    }
                    break;
            }
            markDirty();
            updateDerived();
            return;
        }

        if (event.target.closest('[data-add-standard-rooms]')) {
            const group = event.target.closest('[data-option-group]');
            const container = group.querySelector('[data-rows="room_options"]');
            const present = new Set(Array.from(container.querySelectorAll('[data-field="sharing_type"]')).map((f) => f.value));
            ['quad', 'triple', 'double'].filter((type) => !present.has(type)).forEach((type) => {
                addRow('room_options', { container, values: { sharing_type: type, occupancy: ROOM_TYPES[type].occupancy, display_label: ROOM_TYPES[type].label, is_available: '1' } });
            });
            markDirty();
            updateDerived();
        }
    });

    // ---------------------------------------------------------------------
    // Steps
    // ---------------------------------------------------------------------
    const panels = Array.from(form.querySelectorAll('[data-step]'));
    const stepKeys = panels.map((p) => p.dataset.step);
    const stepInput = form.querySelector('[data-current-step]');

    function showStep(key, { focus = true } = {}) {
        if (!stepKeys.includes(key)) key = stepKeys[0];
        panels.forEach((panel) => { panel.hidden = panel.dataset.step !== key; });
        form.querySelectorAll('[data-step-button]').forEach((button) => {
            const active = button.dataset.stepButton === key;
            button.classList.toggle('active', active);
            if (active) {
                button.setAttribute('aria-current', 'step');
                button.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            } else {
                button.removeAttribute('aria-current');
            }
        });
        if (stepInput) stepInput.value = key;

        const index = stepKeys.indexOf(key);
        if (stepsToggle) {
            const label = form.querySelector(`[data-step-button="${key}"] .step-label`)?.textContent || '';
            stepsToggle.querySelector('[data-steps-toggle-number]').textContent = index + 1;
            stepsToggle.querySelector('[data-steps-toggle-index]').textContent = index + 1;
            stepsToggle.querySelector('[data-steps-toggle-label]').textContent = label;
            stepsAside.classList.remove('is-open');
            stepsToggle.setAttribute('aria-expanded', 'false');
        }

        // Looking at the Review step is what "Final review completed" means;
        // it is fresh the moment it opens.
        if (key === 'review' && reviewedInput) {
            reviewedInput.value = '1';
            assessNow();
        }

        const url = new URL(window.location.href);
        url.searchParams.set('step', key);
        window.history.replaceState(null, '', url);

        if (focus) {
            const panel = panels.find((p) => p.dataset.step === key);
            panel?.focus({ preventScroll: true });
            const top = form.getBoundingClientRect().top + window.scrollY - 90;
            if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
        }
    }

    form.addEventListener('click', (event) => {
        const button = event.target.closest('[data-step-button]');
        if (button) { showStep(button.dataset.stepButton); return; }

        const go = event.target.closest('[data-step-go]');
        if (go) {
            const current = stepKeys.indexOf(stepInput.value);
            showStep(stepKeys[current + (go.dataset.stepGo === 'next' ? 1 : -1)]);
        }
    });

    // Delegated: the Review step and the checklist are redrawn as the admin
    // works, so their "Edit" links do not exist yet when the page loads.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-step-link]');
        if (!link) return;
        event.preventDefault();
        showStep(link.dataset.stepLink);
    });

    // Phones: the step list folds into one "Step 4 of 14 · Room prices" line.
    const stepsToggle = form.querySelector('[data-steps-toggle]');
    const stepsAside = form.querySelector('.builder-steps');
    stepsToggle?.addEventListener('click', () => {
        const open = !stepsAside.classList.contains('is-open');
        stepsAside.classList.toggle('is-open', open);
        stepsToggle.setAttribute('aria-expanded', String(open));
    });

    // ---------------------------------------------------------------------
    // Hotel options (A / B / C)
    // ---------------------------------------------------------------------
    const optionRows = () => Array.from(form.querySelectorAll('[data-row="variants"]'));
    const optionCode = (row) => (row.querySelector('[data-field="code"]').value || '').trim().toUpperCase();

    function nextOptionLetter() {
        const used = new Set(optionRows().map(optionCode));
        return 'ABCDEFGHIJ'.split('').find((l) => !used.has(l)) || '';
    }

    function syncOptionGroups() {
        const options = optionRows().map((row) => ({
            uid: row.dataset.optionUid || (row.dataset.optionUid = `opt-new-${row.dataset.index}`),
            code: optionCode(row),
            label: (row.querySelector('[data-field="label"]').value || '').trim(),
            row,
        }));

        options.forEach(({ row, code }) => {
            row.className = row.className.replace(/option-tone-\S+/g, '').trim() + ` option-tone-${['A', 'B', 'C', 'D', 'E'].includes(code) ? code : 'shared'}`;
        });

        ['room_options', 'accommodations'].forEach((name) => {
            const wrapper = form.querySelector(`[data-option-groups="${name}"]`);
            if (!wrapper) return;
            const template = document.getElementById(`tpl-group-${name}`);

            options.forEach(({ uid, code, label }) => {
                let group = wrapper.querySelector(`[data-option-group="${name}"][data-option-uid="${uid}"]`);
                if (!group && code) {
                    const holder = document.createElement('div');
                    holder.innerHTML = template.innerHTML.replaceAll('__UID__', uid).replaceAll('__CODE__', escapeHtml(code)).replaceAll('__LABEL__', '');
                    group = holder.firstElementChild;
                    wrapper.appendChild(group);
                }
                if (!group) return;

                const previous = group.dataset.optionCode;
                group.dataset.optionCode = code;
                group.className = group.className.replace(/option-tone-\S+/g, '').trim() + ` option-tone-${['A', 'B', 'C', 'D', 'E'].includes(code) ? code : 'shared'}`;
                const chip = group.querySelector('[data-option-chip]');
                if (chip) chip.textContent = code || '?';
                const title = group.querySelector('[data-option-title]');
                if (title) title.textContent = `Option ${code || '?'}${label ? ` — ${label}` : ''}`;
                if (previous !== code) {
                    group.querySelectorAll('[data-field="variant_code"]').forEach((field) => { field.value = code; });
                }
                wrapper.appendChild(group); // keep boxes in option order
            });

            // Boxes whose option row is gone.
            wrapper.querySelectorAll(`[data-option-group="${name}"]`).forEach((group) => {
                if (group.dataset.optionUid !== 'shared' && !options.some((o) => o.uid === group.dataset.optionUid)) group.remove();
            });
        });

        // "For option" selects on Aziziya rooms.
        form.querySelectorAll('[data-option-select]').forEach((select) => {
            const current = select.value;
            select.innerHTML = '<option value="">All</option>' + options.filter((o) => o.code).map((o) => `<option value="${escapeHtml(o.code)}">${escapeHtml(o.code)}</option>`).join('');
            select.value = options.some((o) => o.code === current) ? current : '';
        });

        // Journey plan: a second "stay" column once there are two options.
        const coded = options.filter((o) => o.code);
        const hasB = coded.length >= 2;
        form.querySelectorAll('[data-row="itinerary"]').forEach((row) => {
            row.classList.toggle('has-option-b', hasB);
            row.querySelector('[data-option-b-only]').hidden = !hasB;
            const a = row.querySelector('[data-option-a-label]');
            const b = row.querySelector('[data-option-b-label]');
            if (a) a.textContent = hasB ? `Stay — Option ${coded[0].code}` : 'Where they stay';
            if (b && hasB) b.textContent = `Stay — Option ${coded[1].code}`;
        });
        const dayTemplate = document.getElementById('tpl-itinerary');
        if (dayTemplate) {
            const holder = document.createElement('div');
            holder.innerHTML = dayTemplate.innerHTML;
            const templateRow = holder.firstElementChild;
            templateRow.classList.toggle('has-option-b', hasB);
            const bOnly = templateRow.querySelector('[data-option-b-only]');
            if (hasB) bOnly.removeAttribute('hidden'); else bOnly.setAttribute('hidden', '');
            templateRow.querySelector('[data-option-a-label]').textContent = hasB ? `Stay — Option ${coded[0].code}` : 'Where they stay';
            if (hasB) templateRow.querySelector('[data-option-b-label]').textContent = `Stay — Option ${coded[1].code}`;
            dayTemplate.innerHTML = templateRow.outerHTML;
        }

        const card = form.querySelector('[data-options-card]');
        const hasOptions = options.length > 0;
        if (card) card.hidden = !hasOptions && !form.querySelector('[data-has-options][value="1"]:checked');
    }

    function addOption(values = {}) {
        const row = addRow('variants', { values: { code: nextOptionLetter(), ...values } });
        if (row) row.dataset.optionUid = `opt-new-${row.dataset.index}`;
        syncOptionGroups();
        return row;
    }

    async function removeOption(row) {
        const uid = row.dataset.optionUid;
        const code = optionCode(row) || '?';
        const count = form.querySelectorAll(`[data-option-group][data-option-uid="${uid}"] [data-row]`).length;

        if (count > 0) {
            const ok = await askConfirm({
                title: `Remove Option ${code}?`,
                message: `Option ${code} has ${count} room price and hotel ${count === 1 ? 'row' : 'rows'}. They will be removed with it.`,
                button: `Remove Option ${code}`,
            });
            if (!ok) return;
        }

        const container = row.parentElement;
        row.remove();
        refreshEmpty(container);
        syncOptionGroups();
    }

    form.querySelector('[data-add-option]')?.addEventListener('click', () => {
        addOption();
        markDirty();
        updateDerived();
    });

    form.querySelectorAll('[data-has-options]').forEach((radio) => {
        radio.addEventListener('change', async () => {
            if (!radio.checked) return;
            const card = form.querySelector('[data-options-card]');

            if (radio.value === '1') {
                card.hidden = false;
                if (optionRows().length === 0) { addOption(); addOption(); }
            } else if (optionRows().length > 0) {
                const rows = form.querySelectorAll('[data-option-group]:not([data-option-uid="shared"]) [data-row]').length;
                const ok = rows === 0 || await askConfirm({
                    title: 'Remove all hotel options?',
                    message: `The options and their ${rows} room price and hotel rows will be removed. Prices shared by every option are kept.`,
                    button: 'Remove options',
                });
                if (!ok) {
                    form.querySelector('[data-has-options][value="1"]').checked = true;
                    return;
                }
                optionRows().forEach((row) => row.remove());
                refreshEmpty(form.querySelector('[data-rows="variants"]'));
                card.hidden = true;
                syncOptionGroups();
            } else {
                card.hidden = true;
            }
            markDirty();
            updateDerived();
        });
    });

    form.addEventListener('input', (event) => {
        if (event.target.closest('[data-row="variants"]')) {
            if (event.target.dataset.field === 'code') event.target.value = event.target.value.toUpperCase();
            syncOptionGroups();
        }
    });

    // ---------------------------------------------------------------------
    // Room types, hotels, meal plans
    // ---------------------------------------------------------------------
    function syncRoomTypeSelect(row) {
        const select = row.querySelector('[data-room-type]');
        const type = row.querySelector('[data-field="sharing_type"]').value;
        const label = row.querySelector('[data-field="display_label"]').value;
        const known = ROOM_TYPES[type];
        const value = !type && !label ? '' : (known && (!label || label.toLowerCase() === known.label.toLowerCase()) ? type : 'custom');
        select.value = value;
        row.querySelector('[data-room-custom]').hidden = value !== 'custom';
    }

    form.addEventListener('change', (event) => {
        const target = event.target;

        if (target.matches('[data-room-type]')) {
            const row = target.closest('[data-row]');
            const custom = row.querySelector('[data-room-custom]');
            const type = ROOM_TYPES[target.value];
            if (type) {
                row.querySelector('[data-field="sharing_type"]').value = target.value;
                row.querySelector('[data-field="occupancy"]').value = type.occupancy;
                row.querySelector('[data-field="display_label"]').value = type.label;
                custom.hidden = true;
            } else if (target.value === 'custom') {
                row.querySelector('[data-field="sharing_type"]').value = '';
                row.querySelector('[data-field="occupancy"]').value = '';
                custom.hidden = false;
                custom.querySelector('input').focus();
            }
        }

        if (target.matches('[data-row="room_options"] [data-field="is_available"]')) {
            const label = target.parentElement.querySelector('label');
            if (label) label.textContent = target.checked ? 'Yes' : 'No';
        }

        if (target.matches('[data-row="accommodations"] [data-field="location"]')) {
            rebuildHotelPicker(target.closest('[data-row]'));
        }

        if (target.matches('[data-hotel-picker]') && !silent) {
            const hotel = (library.hotels || []).find((h) => String(h.id) === target.value);
            const row = target.closest('[data-row]');
            if (hotel) {
                row.querySelector('[data-field="hotel_name"]').value = hotel.name;
                row.querySelector('[data-field="star_rating"]').value = hotel.star_rating || '';
            }
            refreshStays();
        }

        if (target.matches('[data-meal-picker]') && !silent) {
            const plan = (library.mealPlans || []).find((p) => String(p.id) === target.value);
            if (plan) target.closest('[data-row]').querySelector('[data-field="meal_plan"]').value = plan.name;
            renderMealSummary();
        }

        if (target.matches('[data-extra-toggle]')) toggleExtra(target);

        if (target.matches('[data-aziziya-status]')) {
            const card = form.querySelector('[data-aziziya-card]');
            if (card) card.hidden = !['included', 'optional'].includes(target.value);
        }

        if (target.matches('[data-mashaer-picker]') && !silent) applyMashaer(target);
    });

    function toggleExtra(toggle) {
        const row = toggle.closest('[data-row]');
        row?.querySelectorAll('[data-extra-only]').forEach((el) => { el.hidden = toggle.checked; });
    }

    function rebuildHotelPicker(row, selectId = undefined) {
        const picker = row.querySelector('[data-hotel-picker]');
        if (!picker) return;
        const location = row.querySelector('[data-field="location"]').value;
        const current = selectId !== undefined && selectId !== null ? String(selectId) : picker.value;
        const hotels = (library.hotels || []).filter((h) => h.location === location || String(h.id) === current);
        picker.innerHTML = '<option value="">Not in the list</option>' + hotels
            .map((h) => `<option value="${h.id}">${escapeHtml(h.name)}${h.archived ? ' (archived)' : ''}</option>`).join('');
        picker.value = hotels.some((h) => String(h.id) === current) ? current : '';
    }

    function refreshStays() {
        const list = form.querySelector('[data-stays-list]');
        if (!list) return;
        const names = new Set();
        form.querySelectorAll('[data-row="accommodations"] [data-field="hotel_name"]').forEach((f) => f.value && names.add(f.value));
        ['mina', 'arafat'].forEach((place) => names.add(place === 'mina' ? 'Mina camp' : 'Arafat'));
        names.add('Departure to airport');
        list.innerHTML = Array.from(names).map((n) => `<option value="${escapeHtml(n)}">`).join('');
    }

    function renderMealSummary() {
        const summary = form.querySelector('[data-meal-summary]');
        if (!summary) return;
        const rows = Array.from(form.querySelectorAll('[data-row="accommodations"]'));
        if (!rows.length) {
            summary.innerHTML = '<p class="form-help mb-0">Add hotels in the Hotels step first.</p>';
            return;
        }
        summary.innerHTML = '<ul class="list-unstyled mb-0">' + rows.map((row) => {
            const name = row.querySelector('[data-field="hotel_name"]').value || 'Hotel without a name';
            const meal = row.querySelector('[data-field="meal_plan"]').value;
            const code = row.querySelector('[data-field="variant_code"]').value;
            return `<li class="py-1 border-bottom"><strong>${escapeHtml(name)}</strong>${code ? ` <span class="text-muted">(Option ${escapeHtml(code)})</span>` : ''}: ${meal ? escapeHtml(meal) : '<span class="text-warning-emphasis">no meal plan</span>'}</li>`;
        }).join('') + '</ul>';
    }

    form.querySelector('[data-apply-meal-all]')?.addEventListener('click', () => {
        const select = form.querySelector('[data-meal-all]');
        const plan = (library.mealPlans || []).find((p) => String(p.id) === select.value);
        if (!plan) { select.focus(); return; }
        form.querySelectorAll('[data-row="accommodations"]').forEach((row) => {
            if (!['makkah', 'medinah'].includes(row.querySelector('[data-field="location"]').value)) return;
            fillRow(row, { meal_plan_id: plan.id, meal_plan: plan.name });
        });
        renderMealSummary();
        markDirty();
    });

    // New hotel, saved to the library from inside the builder.
    const newHotelModal = document.getElementById('newHotelModal');
    let newHotelForRow = null;
    form.addEventListener('focusin', (event) => {
        if (event.target.matches('[data-hotel-picker]')) newHotelForRow = event.target.closest('[data-row]');
    });
    newHotelModal?.addEventListener('show.bs.modal', () => {
        newHotelModal.querySelector('[data-new-hotel-error]').hidden = true;
        const location = newHotelForRow?.querySelector('[data-field="location"]')?.value;
        if (location) newHotelModal.querySelector('[data-new-hotel="location"]').value = location;
    });
    newHotelModal?.querySelector('[data-new-hotel-accept]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const error = newHotelModal.querySelector('[data-new-hotel-error]');
        const get = (key) => newHotelModal.querySelector(`[data-new-hotel="${key}"]`).value.trim();
        if (!get('name')) {
            error.textContent = 'Enter the hotel name.';
            error.hidden = false;
            return;
        }

        const body = new FormData();
        body.append('name', get('name'));
        body.append('location', get('location'));
        body.append('star_rating', get('star_rating'));
        body.append('is_active', '1');
        body.append('sort_order', '0');

        button.disabled = true;
        try {
            const response = await fetch(form.dataset.hotelStoreUrl, {
                method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() }, credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'The hotel could not be saved.');

            const hotel = { id: data.id, name: data.record.name, location: data.record.location, star_rating: data.record.star_rating, archived: false };
            library.hotels = [...(library.hotels || []), hotel];

            let row = newHotelForRow && document.body.contains(newHotelForRow) ? newHotelForRow : null;
            if (!row) {
                row = addRow('accommodations', { container: form.querySelector('[data-option-group="accommodations"][data-option-uid="shared"] [data-rows]') });
            }
            fillRow(row, { location: hotel.location, hotel_id: hotel.id, hotel_name: hotel.name, star_rating: hotel.star_rating || '' });
            form.querySelectorAll('[data-row="accommodations"]').forEach((r) => r !== row && rebuildHotelPicker(r));

            window.bootstrap.Modal.getOrCreateInstance(newHotelModal).hide();
            newHotelModal.querySelectorAll('[data-new-hotel="name"]').forEach((f) => { f.value = ''; });
            markDirty();
            updateDerived();
        } catch (e) {
            error.textContent = e.message;
            error.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    // Find a saved hotel: search, see its details, add it to an option.
    const findHotelModal = document.getElementById('findHotelModal');
    let findHotelChosen = null;

    function renderFindHotel() {
        const list = findHotelModal.querySelector('[data-find-hotel-list]');
        const term = findHotelModal.querySelector('[data-find-hotel-search]').value.trim().toLowerCase();
        const city = findHotelModal.querySelector('[data-find-hotel-city]').value;
        const hotels = (library.hotels || []).filter((h) => !h.archived
            && (!city || h.location === city)
            && (!term || `${h.name} ${h.city || ''} ${h.address || ''}`.toLowerCase().includes(term)));

        if (!hotels.length) {
            list.innerHTML = '<p class="p-3 mb-0 text-muted">No saved hotel matches. Use "New hotel" to add it.</p>';
            return;
        }
        list.innerHTML = hotels.map((h) => `<button type="button" role="option" class="find-hotel-item ${findHotelChosen?.id === h.id ? 'is-chosen' : ''}" aria-selected="${findHotelChosen?.id === h.id}" data-find-hotel-id="${h.id}">
            <strong>${escapeHtml(h.name)}</strong>
            <small>${escapeHtml(h.location_label || h.location)}${h.star_rating ? ` · ${h.star_rating} stars` : ''} · ${h.used ? `used in ${h.used} ${h.used === 1 ? 'package' : 'packages'}` : 'not used yet'}</small>
        </button>`).join('');
    }

    function renderFindHotelDetails() {
        const box = findHotelModal.querySelector('[data-find-hotel-details]');
        const accept = findHotelModal.querySelector('[data-find-hotel-accept]');
        accept.disabled = !findHotelChosen;
        if (!findHotelChosen) {
            box.innerHTML = '<p class="text-muted mb-0">Choose a hotel to see its details.</p>';
            return;
        }
        const h = findHotelChosen;
        const fact = (label, value) => (value ? `<div><dt>${label}</dt><dd>${value}</dd></div>` : '');
        box.innerHTML = `<h3 class="h6">${escapeHtml(h.name)}</h3>
            <dl class="review-facts">
                ${fact('City / place', escapeHtml(h.location_label || h.location))}
                ${fact('Stars', h.star_rating ? `${h.star_rating} ★` : '')}
                ${fact('Address', escapeHtml(h.address || ''))}
                ${fact('Used in', `${h.used} ${h.used === 1 ? 'package' : 'packages'}`)}
                ${fact('About', escapeHtml(h.description || ''))}
                ${fact('Links', [h.map_url ? `<a href="${escapeHtml(h.map_url)}" target="_blank" rel="noopener">Map</a>` : '', h.website_url ? `<a href="${escapeHtml(h.website_url)}" target="_blank" rel="noopener">Website</a>` : ''].filter(Boolean).join(' · '))}
            </dl>
            <p class="form-help mb-0">This is the saved hotel, shared by every package that uses it. What you change in the package afterwards stays in that package.</p>`;
    }

    findHotelModal?.addEventListener('show.bs.modal', () => {
        findHotelChosen = null;
        const select = findHotelModal.querySelector('[data-find-hotel-for]');
        const options = optionRows().map(optionCode).filter(Boolean);
        select.innerHTML = '<option value="">Every option</option>' + options.map((c) => `<option value="${escapeHtml(c)}">Option ${escapeHtml(c)}</option>`).join('');
        findHotelModal.querySelector('[data-find-hotel-search]').value = '';
        renderFindHotel();
        renderFindHotelDetails();
    });
    findHotelModal?.addEventListener('shown.bs.modal', () => findHotelModal.querySelector('[data-find-hotel-search]').focus());
    findHotelModal?.querySelector('[data-find-hotel-search]').addEventListener('input', renderFindHotel);
    findHotelModal?.querySelector('[data-find-hotel-city]').addEventListener('change', renderFindHotel);
    findHotelModal?.querySelector('[data-find-hotel-list]').addEventListener('click', (event) => {
        const item = event.target.closest('[data-find-hotel-id]');
        if (!item) return;
        findHotelChosen = (library.hotels || []).find((h) => String(h.id) === item.dataset.findHotelId) || null;
        renderFindHotel();
        renderFindHotelDetails();
    });
    findHotelModal?.querySelector('[data-find-hotel-accept]').addEventListener('click', () => {
        if (!findHotelChosen) return;
        const code = findHotelModal.querySelector('[data-find-hotel-for]').value;
        const group = code
            ? form.querySelector(`[data-option-group="accommodations"][data-option-code="${CSS.escape(code)}"]`)
            : form.querySelector('[data-option-group="accommodations"][data-option-uid="shared"]');
        const row = addRow('accommodations', {
            container: group?.querySelector('[data-rows="accommodations"]'),
            values: { location: findHotelChosen.location, hotel_id: findHotelChosen.id, hotel_name: findHotelChosen.name, star_rating: findHotelChosen.star_rating || '' },
        });
        window.bootstrap.Modal.getOrCreateInstance(findHotelModal).hide();
        row?.querySelector('[data-field="nights"]')?.focus();
        markDirty();
        updateDerived();
    });

    // ---------------------------------------------------------------------
    // Mina / Arafat / Muzdalifah
    // ---------------------------------------------------------------------
    const MASHAER_FIELDS = ['maktab', 'category', 'zone', 'tent_type', 'accommodation_type', 'meal_plan', 'bathroom', 'air_conditioning', 'transportation', 'other_services', 'notes'];

    async function applyMashaer(picker) {
        const card = picker.closest('[data-mashaer-card]');
        const record = (library.mashaer || []).find((m) => String(m.id) === picker.value);
        if (!record) return;

        const hasValues = MASHAER_FIELDS.some((f) => card.querySelector(`[data-mashaer-field="${f}"]`).value.trim() !== '');
        if (hasValues) {
            const ok = await askConfirm({
                title: 'Replace this card?',
                message: `The boxes will be filled from "${record.name}". What is typed there now will be replaced.`,
                button: 'Replace', tone: 'primary',
            });
            if (!ok) return;
        }
        MASHAER_FIELDS.forEach((f) => { card.querySelector(`[data-mashaer-field="${f}"]`).value = record[f] ?? ''; });
        markDirty();
        updateDerived();
    }

    form.querySelectorAll('[data-mashaer-clear]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('[data-mashaer-card]');
            card.querySelector('[data-mashaer-picker]').value = '';
            MASHAER_FIELDS.forEach((f) => { card.querySelector(`[data-mashaer-field="${f}"]`).value = ''; });
            markDirty();
            updateDerived();
        });
    });

    // ---------------------------------------------------------------------
    // Pick saved items
    // ---------------------------------------------------------------------
    const PICKERS = {
        transportation: {
            title: 'Add saved transport', source: 'transport', link: 'transport_option_id',
            describe: (t) => [t.name, [t.from_location, t.to_location].filter(Boolean).join(' → '), t.is_included ? 'Included' : `Extra${t.price !== null ? ` ${t.currency} ${Number(t.price).toLocaleString()}` : ''}`],
            values: (t) => ({ transport_option_id: t.id, from_location: t.from_location, to_location: t.to_location, transport_type: t.transport_type, is_included: t.is_included, price: t.price ?? '', currency: t.currency || 'USD', price_basis: t.price_basis, notes: t.notes }),
        },
        inclusions: {
            title: 'Add saved included services', source: 'inclusions', link: 'service_item_id',
            describe: (s) => [s.title, s.description !== s.title ? s.description : ''],
            values: (s) => ({ service_item_id: s.id, description: s.description }),
        },
        exclusions: {
            title: 'Add saved "not included" items', source: 'exclusions', link: 'service_item_id',
            describe: (s) => [s.title, s.description !== s.title ? s.description : ''],
            values: (s) => ({ service_item_id: s.id, description: s.description }),
        },
        upgrades: {
            title: 'Add saved additional options', source: 'upgrades', link: 'upgrade_option_id',
            describe: (u) => [u.name, u.price !== null ? `${u.currency} ${Number(u.price).toLocaleString()}${u.price_basis ? ` ${u.price_basis}` : ''}` : 'Price on request'],
            values: (u) => ({ upgrade_option_id: u.id, name: u.name, description: u.description, price: u.price ?? '', currency: u.currency || 'USD', price_basis: u.price_basis, is_included: u.is_included, notes: u.notes }),
        },
        notes: {
            title: 'Add saved notes', source: 'notes', link: 'note_template_id',
            describe: (n) => [n.title, n.content !== n.title ? n.content : ''],
            values: (n) => ({ note_template_id: n.id, note_type: n.note_type, title: n.heading || '', content: n.content, is_important: n.is_important }),
        },
    };

    const pickerModal = document.getElementById('libraryPickerModal');
    let pickerFor = null;

    function renderPicker() {
        const config = PICKERS[pickerFor];
        const list = pickerModal.querySelector('[data-picker-list]');
        const search = pickerModal.querySelector('[data-picker-search]').value.trim().toLowerCase();
        const present = new Set(Array.from(form.querySelectorAll(`[data-row="${pickerFor}"] [data-field="${config.link}"]`)).map((f) => f.value).filter(Boolean));
        const presentText = new Set(Array.from(form.querySelectorAll(`[data-row="${pickerFor}"] [data-field="description"], [data-row="${pickerFor}"] [data-field="content"]`)).map((f) => f.value.trim().toLowerCase()));
        const items = (library[config.source] || []).filter((item) => config.describe(item).join(' ').toLowerCase().includes(search));

        if (!items.length) {
            list.innerHTML = '<p class="p-3 mb-0 text-muted">Nothing saved matches. Add it under Reusable Content, or use "Add your own".</p>';
            return;
        }

        list.innerHTML = items.map((item) => {
            const [title, detail] = config.describe(item);
            const text = (item.description || item.content || '').trim().toLowerCase();
            const added = present.has(String(item.id)) || (text && presentText.has(text));
            return `<label class="${added ? 'is-added' : ''}">
                <input class="form-check-input" type="checkbox" value="${item.id}" ${added ? 'disabled' : ''}>
                <span><strong>${escapeHtml(title)}</strong>${added ? ' <span class="badge text-bg-light">Added</span>' : ''}<span class="usage-note">${item.used ? `Used in ${item.used} ${item.used === 1 ? 'package' : 'packages'}` : 'Not used in any package yet'}</span>${detail ? `<small>${escapeHtml(detail)}</small>` : ''}</span>
            </label>`;
        }).join('');
    }

    form.addEventListener('click', (event) => {
        const open = event.target.closest('[data-open-picker]');
        if (!open || !pickerModal) return;
        pickerFor = open.dataset.openPicker;
        pickerModal.querySelector('[data-picker-title]').textContent = PICKERS[pickerFor].title;
        pickerModal.querySelector('[data-picker-search]').value = '';
        renderPicker();
        window.bootstrap.Modal.getOrCreateInstance(pickerModal).show();
    });

    pickerModal?.querySelector('[data-picker-search]').addEventListener('input', renderPicker);
    pickerModal?.querySelector('[data-picker-accept]').addEventListener('click', () => {
        const config = PICKERS[pickerFor];
        const chosen = Array.from(pickerModal.querySelectorAll('[data-picker-list] input:checked')).map((i) => i.value);
        chosen.forEach((id) => {
            const item = (library[config.source] || []).find((x) => String(x.id) === id);
            if (item) addRow(pickerFor, { values: config.values(item) });
        });
        window.bootstrap.Modal.getOrCreateInstance(pickerModal).hide();
        if (chosen.length) { markDirty(); updateDerived(); }
    });

    // ---------------------------------------------------------------------
    // Copy from another package
    // ---------------------------------------------------------------------
    const copyModal = document.getElementById('copyFromModal');

    form.addEventListener('click', (event) => {
        const open = event.target.closest('[data-open-copy]');
        if (!open || !copyModal) return;
        copyModal.querySelectorAll('[data-copy-section]').forEach((box) => { box.checked = box.value === open.dataset.openCopy; });
        copyModal.querySelector('[data-copy-error]').hidden = true;
        window.bootstrap.Modal.getOrCreateInstance(copyModal).show();
    });

    copyModal?.querySelector('[data-copy-accept]').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const error = copyModal.querySelector('[data-copy-error]');
        const url = copyModal.querySelector('[data-copy-package]').value;
        const sections = Array.from(copyModal.querySelectorAll('[data-copy-section]:checked')).map((b) => b.value);
        error.hidden = true;

        if (!url || !sections.length) {
            error.textContent = !url ? 'Choose the package to copy from.' : 'Tick at least one thing to copy.';
            error.hidden = false;
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('That package could not be loaded.');
            const data = await response.json();
            await hideModal(copyModal);

            const ok = await askConfirm({
                title: `Copy from ${data.code || data.name}?`,
                message: describeCopy(data.content, sections),
                button: 'Copy', tone: 'primary',
            });
            if (!ok) return;

            applyContent(data.content, sections);
            markDirty();
            updateDerived();
            showStep(stepInput.value, { focus: false });
        } catch (e) {
            error.textContent = e.message;
            error.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    /**
     * Exactly what a copy brings in and what it replaces, counted, so nothing
     * in this form is ever overwritten without the admin reading it first.
     */
    function describeCopy(content, sections) {
        const n = (count, word) => `${count} ${word}${count === 1 ? '' : 's'}`;
        const here = (name) => form.querySelectorAll(`[data-row="${name}"]`).length;
        const lines = [];
        const want = (s) => sections.includes(s);

        if (want('pricing') || want('hotels')) {
            const codes = (content.variants || []).map((v) => v.code).filter(Boolean);
            lines.push(`Hotel options: ${codes.length ? codes.join(', ') : 'none'} (replaces ${n(optionRows().length, 'option')} here)`);
        }
        if (want('pricing')) lines.push(`Room prices: ${n((content.room_options || []).length, 'row')} (replaces ${here('room_options')})`);
        if (want('hotels')) lines.push(`Hotels: ${n((content.accommodations || []).length, 'hotel')} and the Aziziya details (replaces ${here('accommodations')})`);
        if (want('meals') && !want('hotels')) lines.push('Meal plans: set on hotels here that match by city and option');
        if (want('journey')) lines.push(`Journey plan: ${n((content.itinerary || []).length, 'day')} (replaces ${here('itinerary')})`);
        if (want('mashaer')) {
            const places = ['mina', 'arafat', 'muzdalifah'].filter((p) => Object.values(content.mashaer?.[p] || {}).some((v) => v !== null && v !== ''));
            lines.push(`Mina, Arafat & Muzdalifah: ${places.length ? places.map((p) => p[0].toUpperCase() + p.slice(1)).join(', ') : 'nothing described'} (replaces all three cards)`);
        }
        [['transport', 'transportation', 'Transport', 'line'], ['inclusions', 'inclusions', 'Included', 'line'], ['exclusions', 'exclusions', 'Not included', 'line'], ['extras', 'upgrades', 'Additional options', 'option'], ['notes', 'notes', 'Notes', 'note']]
            .forEach(([section, name, label, word]) => {
                if (want(section)) lines.push(`${label}: ${n((content[name] || []).length, word)} (replaces ${here(name)})`);
            });

        return `This will copy:\n• ${lines.join('\n• ')}\n\nThe title, code, photos and internal notes are not copied. Nothing is saved until you press a save button.`;
    }

    function replaceOptions(variants) {
        optionRows().forEach((row) => row.remove());
        (variants || []).forEach((v) => addOption({ code: v.code, label: v.label }));
        refreshEmpty(form.querySelector('[data-rows="variants"]'));
        const has = (variants || []).length > 0;
        const radio = form.querySelector(`[data-has-options][value="${has ? 1 : 0}"]`);
        if (radio) radio.checked = true;
        syncOptionGroups();
    }

    function applyContent(content, sections) {
        const want = (s) => sections.includes(s);

        if (want('pricing') || want('hotels')) {
            replaceOptions(content.variants);
        }
        if (want('pricing')) {
            clearRows('room_options');
            (content.room_options || []).forEach((row) => addRow('room_options', { values: row }));
        }
        if (want('hotels')) {
            clearRows('accommodations');
            (content.accommodations || []).forEach((row) => addRow('accommodations', { values: row }));
            const status = content.aziziya?.status;
            if (status) {
                const radio = form.querySelector(`[data-aziziya-status][value="${status}"]`);
                if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); }
            }
            ['accommodation_name', 'location_note', 'walk_distance', 'duration_days', 'average_occupancy', 'description', 'notes'].forEach((f) => {
                const field = form.querySelector(`[name="aziziya[${f}]"]`);
                if (field) field.value = content.aziziya?.[f] ?? '';
            });
            clearRows('aziziya_room_options');
            (content.aziziya_room_options || []).forEach((row) => addRow('aziziya_room_options', { values: row }));
            clearRows('aziziya_services');
            (content.aziziya_services || []).forEach((row) => addRow('aziziya_services', { values: row }));
        }
        if (want('meals') && !want('hotels')) {
            const source = content.accommodations || [];
            form.querySelectorAll('[data-row="accommodations"]').forEach((row) => {
                const location = row.querySelector('[data-field="location"]').value;
                const code = row.querySelector('[data-field="variant_code"]').value;
                const match = source.find((s) => s.location === location && (s.variant_code || '') === code) || source.find((s) => s.location === location);
                if (match && (match.meal_plan || match.meal_plan_id)) fillRow(row, { meal_plan_id: match.meal_plan_id || '', meal_plan: match.meal_plan || '' });
            });
        }
        if (want('journey')) {
            clearRows('itinerary');
            (content.itinerary || []).forEach((row) => addRow('itinerary', { values: row }));
        }
        if (want('mashaer')) {
            ['mina', 'arafat', 'muzdalifah'].forEach((place) => {
                const card = form.querySelector(`[data-mashaer-card="${place}"]`);
                if (!card) return;
                const row = content.mashaer?.[place] || {};
                silent++;
                const picker = card.querySelector('[data-mashaer-picker]');
                picker.value = Array.from(picker.options).some((o) => o.value === String(row.mashaer_location_id ?? '')) ? String(row.mashaer_location_id ?? '') : '';
                silent--;
                MASHAER_FIELDS.forEach((f) => { card.querySelector(`[data-mashaer-field="${f}"]`).value = row[f] ?? ''; });
            });
        }
        const lists = { transport: 'transportation', inclusions: 'inclusions', exclusions: 'exclusions', extras: 'upgrades', notes: 'notes' };
        Object.entries(lists).forEach(([section, name]) => {
            if (!want(section)) return;
            clearRows(name);
            (content[name] || []).forEach((row) => addRow(name, { values: row }));
        });
    }

    // ---------------------------------------------------------------------
    // Journey plan tools
    // ---------------------------------------------------------------------
    const dayRows = () => Array.from(form.querySelectorAll('[data-row="itinerary"]'));

    function nextDayValues() {
        const rows = dayRows();
        const last = rows[rows.length - 1];
        if (!last) return { day_number: 1 };
        const values = { day_number: (Number(last.querySelector('[data-field="day_number"]').value) || rows.length) + 1 };
        const date = last.querySelector('[data-field="date_gregorian"]').value;
        if (date) {
            const next = new Date(`${date}T00:00:00`);
            next.setDate(next.getDate() + 1);
            values.date_gregorian = localIsoDate(next);
        }
        return values;
    }

    form.querySelector('[data-renumber-days]')?.addEventListener('click', () => {
        dayRows().forEach((row, i) => { row.querySelector('[data-field="day_number"]').value = i + 1; });
        markDirty();
    });

    form.querySelector('[data-fill-dates]')?.addEventListener('click', () => {
        const start = form.querySelector('[data-fill-dates-start]');
        if (!start.value) { start.focus(); return; }
        const base = new Date(`${start.value}T00:00:00`);
        dayRows().forEach((row, i) => {
            const d = new Date(base);
            d.setDate(base.getDate() + i);
            row.querySelector('[data-field="date_gregorian"]').value = localIsoDate(d);
        });
        markDirty();
    });

    form.querySelector('[data-apply-journey-template]')?.addEventListener('click', async () => {
        const select = form.querySelector('[data-journey-template]');
        const template = (library.journeyTemplates || []).find((t) => String(t.id) === select.value);
        if (!template) { select.focus(); return; }
        if (dayRows().length) {
            const ok = await askConfirm({
                title: `Apply "${template.name}"?`,
                message: `The ${dayRows().length} days in this journey plan will be replaced by the template's ${template.days.length} days.`,
                button: 'Replace days', tone: 'primary',
            });
            if (!ok) return;
        }
        clearRows('itinerary');
        (template.days || []).forEach((day) => addRow('itinerary', { values: day }));
        markDirty();
        updateDerived();
    });

    const saveJourneyModal = document.getElementById('saveJourneyModal');
    saveJourneyModal?.querySelector('[data-save-journey-accept]').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const error = saveJourneyModal.querySelector('[data-save-journey-error]');
        const name = saveJourneyModal.querySelector('[data-save-journey-name]').value.trim();
        error.hidden = true;

        if (!name) { error.textContent = 'Enter a name for the template.'; error.hidden = false; return; }
        if (!dayRows().length) { error.textContent = 'Add at least one day first.'; error.hidden = false; return; }

        const body = new FormData();
        body.append('name', name);
        body.append('is_active', '1');
        body.append('sort_order', '0');
        dayRows().forEach((row, i) => {
            Object.entries(rowValues(row)).forEach(([key, value]) => body.append(`days[${i}][${key}]`, value));
        });

        button.disabled = true;
        try {
            const response = await fetch(form.dataset.journeyStoreUrl, {
                method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() }, credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'The template could not be saved.');

            library.journeyTemplates = [...(library.journeyTemplates || []), { id: data.id, name: data.title, days: data.record.days }];
            form.querySelector('[data-journey-template]')?.add(new Option(`${data.title} (${data.record.days.length} days)`, data.id));
            window.bootstrap.Modal.getOrCreateInstance(saveJourneyModal).hide();
            saveJourneyModal.querySelector('[data-save-journey-name]').value = '';
            flash(data.message);
        } catch (e) {
            error.textContent = e.message;
            error.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    function flash(message) {
        const main = document.getElementById('admin-main-content');
        if (!main || !message) return;
        const alert = document.createElement('div');
        alert.className = 'alert alert-success admin-alert alert-dismissible fade show';
        alert.setAttribute('role', 'status');
        alert.innerHTML = `<i class="bi bi-check-circle-fill" aria-hidden="true"></i><div class="admin-alert-body">${escapeHtml(message)}</div><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>`;
        main.prepend(alert);
    }

    // ---------------------------------------------------------------------
    // Small helpers: suggestions, SEO preview
    // ---------------------------------------------------------------------
    form.querySelectorAll('[data-suggest-from]').forEach((target) => {
        const source = document.getElementById(target.dataset.suggestFrom);
        if (!source) return;
        let lastSuggestion = target.value;
        source.addEventListener('input', () => {
            if (target.value !== '' && target.value !== lastSuggestion) return; // the admin typed their own
            lastSuggestion = source.value ? target.dataset.suggestPattern.replace('{n}', source.value) : '';
            target.value = lastSuggestion;
        });
    });

    const seo = {
        title: form.querySelector('[data-seo-title]'),
        description: form.querySelector('[data-seo-description]'),
        slug: form.querySelector('[data-seo-slug]'),
        name: form.querySelector('#pkg-name'),
        summary: form.querySelector('#pkg-summary'),
    };
    function renderSeo() {
        const t = form.querySelector('[data-seo-preview-title]');
        if (!t) return;
        t.textContent = seo.title?.value || seo.name?.value || 'Package title';
        form.querySelector('[data-seo-preview-description]').textContent = seo.description?.value || seo.summary?.value || 'The short description appears here.';
        const slug = seo.slug?.value || (seo.name?.value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        form.querySelector('[data-seo-preview-slug]').textContent = slug;
    }
    Object.values(seo).forEach((el) => el?.addEventListener('input', renderSeo));

    // ---------------------------------------------------------------------
    // Progress marks
    // ---------------------------------------------------------------------
    const filled = (selector) => Array.from(form.querySelectorAll(selector)).some((f) => f.value.trim() !== '');

    function stepDone() {
        const priced = Array.from(form.querySelectorAll('[data-row="room_options"]')).filter((row) => row.querySelector('[data-field="is_available"]').checked
            && ['price_usd', 'price_sar', 'price_pkr'].some((f) => row.querySelector(`[data-field="${f}"]`).value !== ''));
        const sharedPriced = priced.some((row) => !row.querySelector('[data-field="variant_code"]').value);
        const codes = optionRows().map(optionCode).filter(Boolean);
        const everyOptionPriced = sharedPriced || codes.every((code) => priced.some((row) => row.querySelector('[data-field="variant_code"]').value.toUpperCase() === code));

        return {
            basics: form.dataset.mode === 'template'
                ? filled('[name="template_name"]')
                : filled('#pkg-name') && filled('#pkg-code') && filled('#pkg-days'),
            setup: !!form.querySelector('[data-aziziya-status]:checked'),
            options: true,
            pricing: priced.length > 0 && everyOptionPriced,
            hotels: filled('[data-row="accommodations"] [data-field="hotel_name"]'),
            journey: dayRows().length > 0,
            mashaer: filled('[data-mashaer-field]'),
            transport: form.querySelector('[data-row="transportation"]') !== null,
            services: form.querySelector('[data-row="inclusions"], [data-row="exclusions"]') !== null,
            extras: form.querySelector('[data-row="upgrades"]') !== null,
            notes: form.querySelector('[data-row="notes"]') !== null,
            media: filled('[data-seo-title]') || filled('[data-seo-description]'),
        };
    }

    function markSteps(done) {
        let count = 0;
        form.querySelectorAll('[data-step-button]').forEach((button) => {
            const isDone = !!done[button.dataset.stepButton];
            if (isDone) count++;
            button.classList.toggle('is-done', isDone);
            if (!button.classList.contains('has-error')) {
                button.querySelector('.step-state').innerHTML = isDone ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
            }
        });
        return count;
    }

    function setProgress(text, value, max) {
        const label = form.querySelector('[data-progress-text]');
        const meter = form.querySelector('[data-progress-meter]');
        if (label) label.textContent = text;
        if (meter) {
            meter.querySelector('[data-progress-bar]').style.width = `${Math.round((value / max) * 100)}%`;
            meter.setAttribute('aria-valuenow', value);
        }
    }

    function updateDerived({ assess = true } = {}) {
        if (assessUrl) {
            if (assess) scheduleAssess();
        } else {
            // Templates: no publishing, so a simple local count is enough.
            const count = markSteps(stepDone());
            const total = form.querySelectorAll('[data-step-button]').length;
            setProgress(`${count} of ${total} steps filled in`, count, total);
        }
        form.querySelectorAll('[data-row]').forEach(refreshLibBadge);
        refreshStays();
        renderMealSummary();
        renderSeo();
        renderOptionSummaries();
        renderPriceSummary();
        refreshMashaerShared();
    }

    // ---------------------------------------------------------------------
    // The server's verdict: checklist, step ticks, review, publish button
    // ---------------------------------------------------------------------
    // Asked as the admin types (debounced), from the unsaved form. The server
    // owns the rules, so the checklist can never disagree with what publishing
    // allows — see App\Support\Packages\PackageReview.
    const assessUrl = form.dataset.assessUrl || null;
    const reviewedInput = form.querySelector('[data-reviewed]');
    const reviewedAtLoad = form.dataset.reviewedSaved === '1';
    let assessTimer = null;
    let assessController = null;

    function scheduleAssess() {
        window.clearTimeout(assessTimer);
        assessTimer = window.setTimeout(assessNow, 600);
    }

    async function assessNow() {
        if (!assessUrl) return;
        window.clearTimeout(assessTimer);
        assessController?.abort();
        assessController = new AbortController();

        const body = new FormData();
        let newMedia = 0;
        new FormData(form).forEach((value, key) => {
            if (value instanceof File) {
                if (value.size === 0) return;
                if (key === 'cover_image') body.append('_new_cover', '1');
                else if (key.startsWith('media[')) newMedia++;
                return;
            }
            if (key === '_method' || key === '_reviewed') return;
            body.append(key, value);
        });
        body.append('_new_media', String(newMedia));
        const reviewed = reviewedInput?.value === '1' || (reviewedAtLoad && !dirty);
        body.append('_reviewed', reviewed ? '1' : '0');

        try {
            const response = await fetch(assessUrl, {
                method: 'POST', body, signal: assessController.signal, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (!response.ok) return;
            applyAssessment(await response.json());
        } catch (e) {
            // Aborted by a newer check, or offline: the last verdict stays on screen.
        }
    }

    function applyAssessment(result) {
        markSteps(result.steps || {});
        setProgress(`${result.percent}% complete`, result.percent, 100);

        const list = form.querySelector('[data-checklist]');
        if (list) {
            list.innerHTML = result.checklist.map((item) => `<li class="${item.done ? 'is-done' : ''}">
                <a href="#step-${item.step}" data-step-link="${item.step}"><i class="bi ${item.done ? 'bi-check-circle-fill' : 'bi-circle'}" aria-hidden="true"></i>${escapeHtml(item.label)}<span class="visually-hidden">: ${item.done ? 'done' : 'not done'}</span></a>
            </li>`).join('');
            const count = form.querySelector('[data-checklist-count]');
            if (count) count.textContent = `${result.checklist.filter((i) => i.done).length} of ${result.checklist.length}`;
        }

        const reviewBody = form.querySelector('[data-review-body]');
        if (reviewBody && result.review_html) {
            reviewBody.innerHTML = result.review_html;
            const announce = form.querySelector('[data-review-announce]');
            if (announce && stepInput.value === 'review') {
                const p = result.problems.length;
                const w = result.warnings.length;
                announce.textContent = `Review updated: ${p} ${p === 1 ? 'problem' : 'problems'}, ${w} ${w === 1 ? 'suggestion' : 'suggestions'}.`;
            }
        }

        const publish = form.querySelector('[data-publish-button]');
        const blockers = form.querySelector('[data-publish-blockers]');
        if (publish) {
            publish.disabled = !result.can_publish;
            if (result.can_publish) publish.removeAttribute('aria-describedby'); else publish.setAttribute('aria-describedby', 'publish-blockers');
        }
        if (blockers) {
            blockers.hidden = result.can_publish;
            blockers.querySelector('ul').innerHTML = result.problems
                .map((p) => `<li><a href="#step-${p.step}" data-step-link="${p.step}">${escapeHtml(p.message)}</a></li>`).join('');
        }
    }

    // ---------------------------------------------------------------------
    // Live summaries: each option, the prices, shared Mashaer arrangements
    // ---------------------------------------------------------------------
    const money = (currency, value) => (value === '' || value === null || value === undefined
        ? '<span class="review-na">N/A</span>'
        : `${currency} ${Number(value).toLocaleString('en-US')}`);

    function roomRowsFor(code) {
        return Array.from(form.querySelectorAll('[data-row="room_options"]'))
            .filter((row) => (row.querySelector('[data-field="variant_code"]').value || '').toUpperCase() === code);
    }

    function renderOptionSummaries() {
        const holder = form.querySelector('[data-option-summaries]');
        if (!holder) return;
        const options = optionRows().map((row) => ({ code: optionCode(row), label: (row.querySelector('[data-field="label"]').value || '').trim() })).filter((o) => o.code);
        const card = form.querySelector('[data-options-card-summary]');
        if (card) card.hidden = options.length === 0;

        const sharedRooms = roomRowsFor('').filter((row) => row.querySelector('[data-field="is_available"]').checked);
        const sharedHotels = Array.from(form.querySelectorAll('[data-option-group="accommodations"][data-option-uid="shared"] [data-field="hotel_name"]')).map((f) => f.value.trim()).filter(Boolean);

        holder.innerHTML = options.map(({ code, label }) => {
            const rooms = roomRowsFor(code).filter((row) => row.querySelector('[data-field="is_available"]').checked);
            const hotels = Array.from(form.querySelectorAll(`[data-option-group="accommodations"][data-option-code="${CSS.escape(code)}"] [data-field="hotel_name"]`)).map((f) => f.value.trim()).filter(Boolean);
            const usd = [...rooms, ...sharedRooms].map((row) => row.querySelector('[data-field="price_usd"]').value).filter((v) => v !== '').map(Number);
            const problems = [];
            if (!label) problems.push('No name yet');
            if (!rooms.length && !sharedRooms.length) problems.push('No room price yet');
            if (!hotels.length) problems.push('No hotel of its own yet');
            const tone = ['A', 'B', 'C', 'D', 'E'].includes(code) ? code : 'shared';
            return `<div class="option-summary option-tone-${tone}">
                <div class="option-summary-head"><span class="option-chip">${escapeHtml(code)}</span><strong>${label ? escapeHtml(label) : 'Option ' + escapeHtml(code)}</strong></div>
                <dl>
                    <div><dt>Hotels</dt><dd>${hotels.length ? escapeHtml(hotels.join(', ')) : '—'}${sharedHotels.length ? ` <small>+ ${sharedHotels.length} shared</small>` : ''}</dd></div>
                    <div><dt>Room types</dt><dd>${rooms.length}${sharedRooms.length ? ` <small>+ ${sharedRooms.length} shared</small>` : ''}</dd></div>
                    <div><dt>From</dt><dd>${usd.length ? money('USD', Math.min(...usd)) : '—'}</dd></div>
                </dl>
                ${problems.length ? `<p class="option-summary-todo"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>${escapeHtml(problems.join(' · '))}</p>` : '<p class="option-summary-ok"><i class="bi bi-check-circle" aria-hidden="true"></i>Has a name, prices and a hotel</p>'}
            </div>`;
        }).join('');
    }

    function renderPriceSummary() {
        const holder = form.querySelector('[data-price-summary]');
        if (!holder) return;
        const rows = Array.from(form.querySelectorAll('[data-row="room_options"]'));
        if (!rows.length) {
            holder.innerHTML = '<p class="form-help mb-0">No room prices yet. Add a room type above and its prices appear here.</p>';
            return;
        }

        const available = [];
        const body = rows.map((row) => {
            const get = (f) => row.querySelector(`[data-field="${f}"]`).value;
            const on = row.querySelector('[data-field="is_available"]').checked;
            const label = get('display_label') || get('sharing_type') || 'Room without a name';
            const code = get('variant_code');
            if (on && get('price_usd') !== '') available.push(Number(get('price_usd')));
            const missing = on && ['price_usd', 'price_sar', 'price_pkr'].every((f) => get(f) === '');
            return `<tr class="${on ? '' : 'text-muted'}">
                <td>${escapeHtml(label)}${missing ? ' <span class="badge text-bg-warning">No price</span>' : ''}</td>
                <td>${code ? `Option ${escapeHtml(code)}` : 'Every option'}</td>
                <td>${money('USD', get('price_usd'))}</td><td>${money('SAR', get('price_sar'))}</td><td>${money('PKR', get('price_pkr'))}</td>
                <td>${on ? 'Available' : 'Not offered'}</td>
            </tr>`;
        }).join('');

        holder.innerHTML = `<p class="mb-2"><strong>"From" price on the website:</strong> ${available.length ? money('USD', Math.min(...available)) : '<span class="review-missing">none yet — add an available USD price</span>'}</p>
            <div class="table-responsive"><table class="table table-sm review-table mb-0">
                <thead><tr><th scope="col">Room</th><th scope="col">For</th><th scope="col">USD</th><th scope="col">SAR</th><th scope="col">PKR</th><th scope="col">Status</th></tr></thead>
                <tbody>${body}</tbody>
            </table></div>`;
    }

    function refreshMashaerShared() {
        form.querySelectorAll('[data-mashaer-shared]').forEach((note) => {
            const card = note.closest('[data-mashaer-card]');
            const record = (library.mashaer || []).find((m) => String(m.id) === card.querySelector('[data-mashaer-picker]').value);
            note.hidden = !record;
            if (!record) return;
            note.querySelector('[data-mashaer-shared-name]').textContent = `"${record.name}"`;
            note.querySelector('[data-mashaer-shared-count]').textContent = record.used ? `used in ${record.used} ${record.used === 1 ? 'package' : 'packages'}` : 'not used by any package yet';
            note.querySelector('[data-mashaer-shared-link]').href = record.edit_url;
        });
    }

    // ---------------------------------------------------------------------
    // Unsaved changes, and a copy kept in this browser
    // ---------------------------------------------------------------------
    const storageKey = form.dataset.storageKey;
    const dirtyFlag = form.querySelector('[data-dirty-flag]');
    const savedFlag = form.querySelector('[data-saved-flag]');
    let saveTimer = null;

    function markDirty() {
        if (silent) return;
        dirty = true;
        // A change after looking at the review means it needs another look.
        if (reviewedInput && stepInput.value !== 'review') reviewedInput.value = '0';
        if (dirtyFlag) dirtyFlag.hidden = false;
        if (savedFlag) savedFlag.hidden = true;
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveLocalCopy, 1500);
    }

    let derivedTimer = null;
    form.addEventListener('input', (event) => {
        if (event.target.closest('[data-no-dirty]') || event.target.matches('[data-no-dirty]')) return;
        markDirty();
        // Progress marks follow typing, not only leaving a box.
        window.clearTimeout(derivedTimer);
        derivedTimer = window.setTimeout(updateDerived, 250);
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('[data-no-dirty]')) return;
        markDirty();
        updateDerived();
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    function saveLocalCopy() {
        if (!dirty) return;
        try {
            const entries = [];
            new FormData(form).forEach((value, key) => {
                if (typeof value !== 'string' || key === '_token' || key === '_method') return;
                entries.push([key, value]);
            });
            window.localStorage.setItem(storageKey, JSON.stringify({ savedAt: Date.now(), base: form.dataset.baseVersion, entries }));
        } catch (e) {
            // Storage full or disabled: the warning on leaving still protects the work.
        }
    }

    function restoreEntries(entries) {
        silent++;
        const rowPattern = /^([a-z_]+)\[(\d+)\]\[([a-z_]+)\]$/;
        const rows = {};
        const simple = [];

        entries.forEach(([key, value]) => {
            const match = key.match(rowPattern);
            if (match && REPEATERS.includes(match[1])) {
                const [, name, index, field] = match;
                rows[name] ??= new Map();
                if (!rows[name].has(index)) rows[name].set(index, {});
                rows[name].get(index)[field] = value; // last value wins, as on the server
            } else {
                simple.push([key, value]);
            }
        });

        // Photos cannot be restored from the browser; keep the saved ones.
        const order = ['variants', ...REPEATERS.filter((n) => n !== 'variants' && n !== 'media')];
        order.forEach((name) => {
            clearRows(name);
            (rows[name] || new Map()).forEach((values, index) => {
                const row = addRow(name, { values, index: Number(index) });
                if (name === 'variants' && row) row.dataset.optionUid = `opt-new-${row.dataset.index}`;
            });
            if (name === 'variants') {
                syncOptionGroups();
                const radio = form.querySelector(`[data-has-options][value="${rows.variants?.size ? 1 : 0}"]`);
                if (radio) radio.checked = true;
            }
        });

        const byName = new Map();
        simple.forEach(([key, value]) => byName.set(key, value));
        byName.forEach((value, key) => {
            form.querySelectorAll(`[name="${CSS.escape(key)}"]`).forEach((field) => {
                // Hidden inputs outside rows are fixed (the "0" behind a
                // checkbox, the current step); files cannot be restored.
                if (field.type === 'file' || field.type === 'hidden') return;
                if (field.type === 'radio') field.checked = field.value === value;
                else if (field.type === 'checkbox') field.checked = value === field.value;
                else field.value = value;
            });
        });

        form.querySelectorAll('[data-aziziya-status]:checked').forEach((radio) => radio.dispatchEvent(new Event('change', { bubbles: true })));
        silent--;
        updateDerived();
        markDirty();
    }

    function offerRestore() {
        const banner = document.querySelector('[data-restore-banner]');
        if (!banner) return;

        let stored = null;
        try {
            stored = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
        } catch (e) {
            stored = null;
        }
        if (!stored) return;

        // A save since then makes the browser copy stale; and after a failed
        // save the form already shows what was typed.
        if (stored.base !== form.dataset.baseVersion || form.dataset.hasErrors === '1') {
            window.localStorage.removeItem(storageKey);
            return;
        }

        banner.querySelector('[data-restore-time]').textContent = new Date(stored.savedAt).toLocaleString();
        banner.hidden = false;
        banner.querySelector('[data-restore-accept]').addEventListener('click', () => {
            restoreEntries(stored.entries || []);
            banner.hidden = true;
        });
        banner.querySelector('[data-restore-discard]').addEventListener('click', () => {
            window.localStorage.removeItem(storageKey);
            banner.hidden = true;
        });
    }

    // ---------------------------------------------------------------------
    // Submitting
    // ---------------------------------------------------------------------
    form.addEventListener('submit', async (event) => {
        // A required box on a hidden step would make the browser refuse to
        // submit without saying why. Open that step and point at the box.
        const invalid = Array.from(form.elements).find((el) => el.willValidate && !el.checkValidity());
        if (invalid) {
            event.preventDefault();
            const panel = invalid.closest('[data-step]');
            if (panel) showStep(panel.dataset.step, { focus: false });
            window.setTimeout(() => { invalid.reportValidity(); invalid.focus(); }, 50);
            return;
        }

        const submitter = event.submitter;
        if (submitter?.dataset.confirmPublish && form.dataset.publishConfirmed !== '1') {
            event.preventDefault();
            const ok = await askConfirm({ title: 'Publish this package?', message: submitter.dataset.confirmPublish, button: 'Publish now', tone: 'primary' });
            if (!ok) return;
            form.dataset.publishConfirmed = '1';
            form.requestSubmit(submitter);
            return;
        }

        dirty = false;
        try { window.localStorage.removeItem(storageKey); } catch (e) { /* ignore */ }
    });

    // ---------------------------------------------------------------------
    // Start
    // ---------------------------------------------------------------------
    form.querySelectorAll('[data-row="room_options"]').forEach(syncRoomTypeSelect);
    syncOptionGroups();
    // The page arrives with the server's verdict already drawn; no need to ask again.
    updateDerived({ assess: false });
    showStep(form.dataset.initialStep, { focus: false });
    offerRestore();

    if (form.dataset.hasErrors === '1') {
        document.querySelector('[data-error-summary]')?.focus();
    }
}
