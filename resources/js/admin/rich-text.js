// The admin's rich text editor.
//
// Progressive enhancement over a plain <textarea>: the textarea keeps its name
// and stays the value the form submits (so saving, validation, the package
// builder's autosave and "restore my changes" all keep working). This script
// hides it and puts a visual editor in its place:
//
//   <div data-rich-text data-profile="full|standard|basic|inline" data-max="20000"
//        data-allow-source="1" data-label="Text">
//       <textarea name="…" id="…">…</textarea>
//   </div>
//
// Whatever the editor produces is cleaned again on the server
// (App\Support\Content\RichText), so nothing here is a security boundary.

import { Editor, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle, Color } from '@tiptap/extension-text-style';
import { TableKit } from '@tiptap/extension-table';
import Image from '@tiptap/extension-image';
import { CharacterCount, Placeholder } from '@tiptap/extensions';
import { isValidLink, linkSuggestion, videoEmbedUrl } from './links';
import { openMediaPicker } from './media-picker';
import { announce, escapeHtml } from './ui';

const COLORS = [
    ['', 'Default colour'],
    ['#101b45', 'Navy'],
    ['#7a5f14', 'Gold'],
    ['#4b5563', 'Grey'],
    ['#1e7e34', 'Green'],
    ['#a12626', 'Red'],
];

const PROFILE_LEVEL = { inline: 0, basic: 1, standard: 2, full: 3 };

/** A YouTube or Vimeo player inside the text. Stored as <div data-video-embed><iframe src="…"></iframe></div>. */
const VideoEmbed = Node.create({
    name: 'videoEmbed',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return {
            src: { default: null, parseHTML: (el) => el.querySelector('iframe')?.getAttribute('src') },
            title: { default: 'Video', parseHTML: (el) => el.querySelector('iframe')?.getAttribute('title') || 'Video' },
        };
    },
    parseHTML() {
        return [{ tag: 'div[data-video-embed]' }, { tag: 'div[data-youtube-video]' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['div', { 'data-video-embed': '' }, ['iframe', mergeAttributes({ src: HTMLAttributes.src, title: HTMLAttributes.title, allowfullscreen: 'true' })]];
    },
});

const ICON = (name) => `<i class="bi ${name}" aria-hidden="true"></i>`;

function buildTools(profile, allowSource) {
    const level = PROFILE_LEVEL[profile] ?? 2;
    const groups = [
        [
            { id: 'undo', icon: 'bi-arrow-counterclockwise', label: 'Undo', keys: 'Ctrl+Z' },
            { id: 'redo', icon: 'bi-arrow-clockwise', label: 'Redo', keys: 'Ctrl+Y' },
        ],
    ];

    if (level >= 2) groups.push([{ id: 'block', type: 'select', label: 'Text style' }]);

    groups.push([
        { id: 'bold', icon: 'bi-type-bold', label: 'Bold', keys: 'Ctrl+B' },
        { id: 'italic', icon: 'bi-type-italic', label: 'Italic', keys: 'Ctrl+I' },
        { id: 'underline', icon: 'bi-type-underline', label: 'Underline', keys: 'Ctrl+U' },
        ...(level >= 2 ? [{ id: 'strike', icon: 'bi-type-strikethrough', label: 'Strikethrough' }] : []),
        ...(level >= 2 ? [{ id: 'color', icon: 'bi-palette', label: 'Text colour', type: 'menu' }] : []),
    ]);

    if (level >= 2) {
        groups.push([
            { id: 'align-left', icon: 'bi-text-left', label: 'Align left' },
            { id: 'align-center', icon: 'bi-text-center', label: 'Centre' },
            { id: 'align-right', icon: 'bi-text-right', label: 'Align right' },
            { id: 'align-justify', icon: 'bi-justify', label: 'Justify' },
        ]);
    }

    if (level >= 1) {
        groups.push([
            { id: 'bullet', icon: 'bi-list-ul', label: 'Bulleted list' },
            { id: 'ordered', icon: 'bi-list-ol', label: 'Numbered list' },
            ...(level >= 2 ? [{ id: 'quote', icon: 'bi-quote', label: 'Quote' }, { id: 'rule', icon: 'bi-hr', label: 'Horizontal line' }] : []),
        ]);
    }

    groups.push([
        { id: 'link', icon: 'bi-link-45deg', label: 'Add or edit link', keys: 'Ctrl+K' },
        { id: 'unlink', icon: 'bi-link-45deg rt-unlink', label: 'Remove link' },
    ]);

    if (level >= 3) {
        groups.push([
            { id: 'image', icon: 'bi-image', label: 'Insert image' },
            { id: 'video', icon: 'bi-play-btn', label: 'Insert YouTube or Vimeo video' },
            { id: 'table', icon: 'bi-table', label: 'Insert table' },
        ]);
    }

    groups.push([
        { id: 'clear', icon: 'bi-eraser', label: 'Clear formatting' },
        ...(level >= 2 ? [{ id: 'fullscreen', icon: 'bi-arrows-fullscreen', label: 'Full screen' }] : []),
        ...(allowSource ? [{ id: 'source', icon: 'bi-code-slash', label: 'Edit HTML source (advanced)' }] : []),
    ]);

    return groups;
}

class RichTextEditor {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.textarea = wrapper.querySelector('textarea');
        this.profile = wrapper.dataset.profile || 'standard';
        this.level = PROFILE_LEVEL[this.profile] ?? 2;
        this.max = Number(wrapper.dataset.max || 0);
        this.allowSource = wrapper.dataset.allowSource === '1';
        this.label = wrapper.dataset.label || 'Text';
        this.syncing = false;
        this.sourceMode = false;
        this.build();
    }

    build() {
        const { wrapper, textarea } = this;
        const uid = textarea.id || `rt-${Math.random().toString(36).slice(2, 9)}`;
        textarea.id = uid;

        const shell = document.createElement('div');
        shell.className = 'rt-shell';
        shell.dataset.rtGenerated = '';
        shell.innerHTML = `
            <div class="rt-toolbar" role="toolbar" aria-label="${escapeHtml(`Formatting for ${this.label}`)}" aria-controls="${uid}-editor"></div>
            <div class="rt-panel" data-rt-panel hidden></div>
            <div class="rt-content" id="${uid}-editor"></div>
            <div class="rt-footer">
                <span data-rt-count aria-live="off"></span>
                <span class="rt-footer-hint">Press Alt+F10 to reach the formatting buttons</span>
            </div>`;
        textarea.insertAdjacentElement('beforebegin', shell);
        textarea.classList.add('rt-source');
        textarea.hidden = true;
        this.shell = shell;
        this.toolbar = shell.querySelector('.rt-toolbar');
        this.panel = shell.querySelector('[data-rt-panel]');
        this.count = shell.querySelector('[data-rt-count]');

        const extensions = [
            StarterKit.configure({
                heading: this.level >= 2 ? { levels: [2, 3, 4] } : false,
                blockquote: this.level >= 2 ? {} : false,
                horizontalRule: this.level >= 2 ? {} : false,
                strike: this.level >= 2 ? {} : false,
                bulletList: this.level >= 1 ? {} : false,
                orderedList: this.level >= 1 ? {} : false,
                listItem: this.level >= 1 ? {} : false,
                listKeymap: this.level >= 1 ? {} : false,
                code: false,
                codeBlock: false,
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    isAllowedUri: (url) => isValidLink(url),
                    HTMLAttributes: { rel: 'noopener noreferrer', target: null },
                },
            }),
            CharacterCount,
            Placeholder.configure({ placeholder: this.wrapper.dataset.placeholder || 'Start typing…' }),
        ];

        if (this.level >= 2) {
            extensions.push(TextAlign.configure({ types: ['heading', 'paragraph'], alignments: ['left', 'center', 'right', 'justify'] }), TextStyle, Color);
        }
        if (this.level >= 3) {
            extensions.push(TableKit.configure({ table: { resizable: false } }), Image.configure({ inline: false, allowBase64: false }), VideoEmbed);
        }

        this.editor = new Editor({
            element: shell.querySelector('.rt-content'),
            extensions,
            content: textarea.value,
            editorProps: {
                attributes: {
                    class: 'rt-editable',
                    role: 'textbox',
                    'aria-multiline': this.level === 0 ? 'false' : 'true',
                    'aria-label': this.label,
                    ...(textarea.getAttribute('aria-describedby') ? { 'aria-describedby': textarea.getAttribute('aria-describedby') } : {}),
                    ...(textarea.required ? { 'aria-required': 'true' } : {}),
                },
                handleKeyDown: (view, event) => this.onKey(event),
            },
            onUpdate: () => this.pushToTextarea(),
            onSelectionUpdate: () => this.refreshToolbar(),
            onTransaction: () => this.refreshToolbar(),
            onFocus: () => shell.classList.add('is-focused'),
            onBlur: () => shell.classList.remove('is-focused'),
        });

        // Code that sets the textarea's value directly (library pickers, the
        // package builder's restore) updates the editor too.
        const descriptor = Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value');
        const self = this;
        Object.defineProperty(textarea, 'value', {
            configurable: true,
            get() { return descriptor.get.call(this); },
            set(value) {
                descriptor.set.call(this, value);
                if (!self.syncing && self.editor && !self.editor.isDestroyed && !self.sourceMode) {
                    self.editor.commands.setContent(value || '', { emitUpdate: false });
                    self.refreshToolbar();
                }
            },
        });

        // A label pointing at the hidden textarea should focus the editor.
        document.querySelectorAll(`label[for="${CSS.escape(uid)}"]`).forEach((label) => {
            label.addEventListener('click', (event) => {
                event.preventDefault();
                this.editor.commands.focus();
            });
        });

        if (textarea.classList.contains('is-invalid')) shell.classList.add('is-invalid');

        this.renderToolbar();
        this.refreshToolbar();
        wrapper.richText = this;
    }

    pushToTextarea() {
        this.syncing = true;
        const html = this.editor.isEmpty ? '' : this.editor.getHTML();
        this.textarea.value = html;
        this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
        this.textarea.dispatchEvent(new Event('change', { bubbles: true }));
        this.syncing = false;
    }

    renderToolbar() {
        const groups = buildTools(this.profile, this.allowSource);
        this.toolbar.innerHTML = groups.map((group) => `<div class="rt-group" role="group">${group.map((tool) => {
            if (tool.type === 'select') {
                return `<select class="form-select form-select-sm rt-block" data-rt="block" aria-label="${tool.label}">
                    <option value="p">Paragraph</option><option value="h2">Heading</option><option value="h3">Subheading</option><option value="h4">Small heading</option>
                </select>`;
            }
            const title = tool.keys ? `${tool.label} (${tool.keys})` : tool.label;
            return `<button type="button" class="rt-btn" data-rt="${tool.id}" aria-label="${escapeHtml(tool.label)}" title="${escapeHtml(title)}" ${['bold', 'italic', 'underline', 'strike', 'bullet', 'ordered', 'quote', 'link', 'fullscreen', 'source', 'align-left', 'align-center', 'align-right', 'align-justify'].includes(tool.id) ? 'aria-pressed="false"' : ''}>${ICON(tool.icon)}</button>`;
        }).join('')}</div>`).join('');

        if (this.level >= 3) {
            this.toolbar.insertAdjacentHTML('beforeend', `<div class="rt-group rt-table-tools" role="group" aria-label="Table" hidden>
                <button type="button" class="rt-btn rt-btn-text" data-rt="row-after">+ Row</button>
                <button type="button" class="rt-btn rt-btn-text" data-rt="col-after">+ Column</button>
                <button type="button" class="rt-btn rt-btn-text" data-rt="row-delete">− Row</button>
                <button type="button" class="rt-btn rt-btn-text" data-rt="col-delete">− Column</button>
                <button type="button" class="rt-btn rt-btn-text" data-rt="header-row">Header row</button>
                <button type="button" class="rt-btn rt-btn-text text-danger" data-rt="table-delete">Delete table</button>
            </div>
            <div class="rt-group rt-image-tools" role="group" aria-label="Image" hidden>
                <button type="button" class="rt-btn rt-btn-text" data-rt="image-alt">Image description</button>
            </div>`);
        }

        // Roving tabindex: the toolbar is one Tab stop; arrow keys move inside it.
        // Buttons that cannot be used right now (Undo with nothing to undo) are
        // marked aria-disabled rather than disabled, so they stay reachable and
        // the toolbar never loses its Tab stop.
        const controls = () => this.toolbarControls();
        controls().forEach((el, i) => { el.tabIndex = i === 0 ? 0 : -1; });

        this.toolbar.addEventListener('keydown', (event) => {
            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End', 'Escape'].includes(event.key)) return;
            if (event.target.tagName === 'SELECT' && ['ArrowUp', 'ArrowDown'].includes(event.key)) return;
            const list = controls();
            const index = list.indexOf(document.activeElement);
            if (event.key === 'Escape') {
                event.preventDefault();
                this.editor.commands.focus();
                return;
            }
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? list.length - 1
                : (index + (event.key === 'ArrowRight' ? 1 : -1) + list.length) % list.length;
            list.forEach((el) => { el.tabIndex = -1; });
            list[next].tabIndex = 0;
            list[next].focus();
        });

        this.toolbar.addEventListener('mousedown', (event) => {
            // Keep the text selection when a toolbar button is clicked.
            if (event.target.closest('button')) event.preventDefault();
        });
        this.toolbar.addEventListener('click', (event) => {
            const button = event.target.closest('[data-rt]');
            if (!button || button.tagName !== 'BUTTON') return;
            if (button.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                return;
            }
            this.run(button.dataset.rt, button);
        });
        this.toolbar.querySelector('[data-rt="block"]')?.addEventListener('change', (event) => this.run('block', event.target));
    }

    toolbarControls() {
        return Array.from(this.toolbar.querySelectorAll('button:not([hidden]), select'))
            .filter((el) => !el.closest('[hidden]') && !el.disabled);
    }

    run(action, control) {
        const chain = () => this.editor.chain().focus();
        switch (action) {
            case 'undo': chain().undo().run(); break;
            case 'redo': chain().redo().run(); break;
            case 'bold': chain().toggleBold().run(); break;
            case 'italic': chain().toggleItalic().run(); break;
            case 'underline': chain().toggleUnderline().run(); break;
            case 'strike': chain().toggleStrike().run(); break;
            case 'bullet': chain().toggleBulletList().run(); break;
            case 'ordered': chain().toggleOrderedList().run(); break;
            case 'quote': chain().toggleBlockquote().run(); break;
            case 'rule': chain().setHorizontalRule().run(); break;
            case 'align-left': case 'align-center': case 'align-right': case 'align-justify':
                chain().setTextAlign(action.replace('align-', '')).run(); break;
            case 'block': {
                const value = control.value;
                if (value === 'p') chain().setParagraph().run();
                else chain().setHeading({ level: Number(value.slice(1)) }).run();
                break;
            }
            case 'clear': chain().unsetAllMarks().clearNodes().run(); announce('Formatting cleared'); break;
            case 'link': this.openLinkPanel(); break;
            case 'unlink': chain().extendMarkRange('link').unsetLink().run(); announce('Link removed'); break;
            case 'color': this.openColorPanel(); break;
            case 'image': this.insertImage(); break;
            case 'image-alt': this.openImageAltPanel(); break;
            case 'video': this.openVideoPanel(); break;
            case 'table': chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(); announce('Table inserted with 3 rows and 3 columns'); break;
            case 'row-after': chain().addRowAfter().run(); break;
            case 'col-after': chain().addColumnAfter().run(); break;
            case 'row-delete': chain().deleteRow().run(); break;
            case 'col-delete': chain().deleteColumn().run(); break;
            case 'header-row': chain().toggleHeaderRow().run(); break;
            case 'table-delete': chain().deleteTable().run(); announce('Table deleted'); break;
            case 'fullscreen': this.toggleFullscreen(); break;
            case 'source': this.toggleSource(); break;
            default: break;
        }
        this.refreshToolbar();
    }

    onKey(event) {
        if (event.altKey && event.key === 'F10') {
            event.preventDefault();
            const list = this.toolbarControls();
            (list.find((el) => el.tabIndex === 0) || list[0])?.focus();
            return true;
        }
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            this.openLinkPanel();
            return true;
        }
        if (event.key === 'Escape' && this.shell.classList.contains('is-fullscreen')) {
            this.toggleFullscreen();
            return true;
        }
        return false;
    }

    refreshToolbar() {
        const { editor } = this;
        if (!editor || editor.isDestroyed) return;
        const pressed = {
            bold: editor.isActive('bold'),
            italic: editor.isActive('italic'),
            underline: editor.isActive('underline'),
            strike: editor.isActive('strike'),
            bullet: editor.isActive('bulletList'),
            ordered: editor.isActive('orderedList'),
            quote: editor.isActive('blockquote'),
            link: editor.isActive('link'),
            'align-left': editor.isActive({ textAlign: 'left' }),
            'align-center': editor.isActive({ textAlign: 'center' }),
            'align-right': editor.isActive({ textAlign: 'right' }),
            'align-justify': editor.isActive({ textAlign: 'justify' }),
            fullscreen: this.shell.classList.contains('is-fullscreen'),
            source: this.sourceMode,
        };
        Object.entries(pressed).forEach(([id, on]) => {
            const button = this.toolbar.querySelector(`[data-rt="${id}"]`);
            if (button) {
                button.setAttribute('aria-pressed', on ? 'true' : 'false');
                button.classList.toggle('is-active', on);
            }
        });

        const block = this.toolbar.querySelector('[data-rt="block"]');
        if (block) {
            block.value = [2, 3, 4].map((l) => (editor.isActive('heading', { level: l }) ? `h${l}` : null)).find(Boolean) || 'p';
        }
        // While the source is shown only Source and Full screen do anything.
        this.toolbar.querySelectorAll('button[data-rt]').forEach((button) => {
            const id = button.dataset.rt;
            const unavailable = (this.sourceMode && id !== 'source' && id !== 'fullscreen')
                || (id === 'unlink' && !editor.isActive('link'))
                || (id === 'undo' && !editor.can().undo())
                || (id === 'redo' && !editor.can().redo());
            if (unavailable) button.setAttribute('aria-disabled', 'true');
            else button.removeAttribute('aria-disabled');
        });
        if (block) block.disabled = this.sourceMode;

        const tableTools = this.toolbar.querySelector('.rt-table-tools');
        if (tableTools) tableTools.hidden = !editor.isActive('table');
        const imageTools = this.toolbar.querySelector('.rt-image-tools');
        if (imageTools) imageTools.hidden = !editor.isActive('image');

        const words = editor.storage.characterCount.words();
        const chars = editor.storage.characterCount.characters();
        const tooLong = this.max && editor.getHTML().length > this.max;
        this.count.textContent = `${words} ${words === 1 ? 'word' : 'words'} · ${chars} ${chars === 1 ? 'character' : 'characters'}${tooLong ? ' · too long to save — shorten it or split it' : ''}`;
        this.count.classList.toggle('text-danger', !!tooLong);
    }

    closePanel() {
        this.panel.hidden = true;
        this.panel.innerHTML = '';
    }

    showPanel(html, focusSelector) {
        this.panel.innerHTML = html;
        this.panel.hidden = false;
        this.panel.querySelector(focusSelector)?.focus();
        this.panel.querySelectorAll('[data-rt-cancel]').forEach((b) => b.addEventListener('click', () => { this.closePanel(); this.editor.commands.focus(); }));
        // The editor usually sits inside a page's own form, and forms cannot be
        // nested, so panels are plain groups: Enter applies them, Escape closes.
        this.panel.onkeydown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                this.closePanel();
                this.editor.commands.focus();
            } else if (event.key === 'Enter' && event.target.matches('input:not([type="checkbox"])')) {
                event.preventDefault();
                this.panel.querySelector('[data-rt-apply]')?.click();
            }
        };
    }

    openLinkPanel() {
        const attrs = this.editor.getAttributes('link');
        const uid = `${this.textarea.id}-link`;
        this.showPanel(`
            <div class="rt-panel-form" role="group" aria-label="Link">
                <label class="form-label" for="${uid}">Link address</label>
                <input type="text" class="form-control form-control-sm" id="${uid}" value="${escapeHtml(attrs.href || '')}" placeholder="/contact or https://…" aria-describedby="${uid}-help ${uid}-error" autocomplete="off">
                <div class="form-help" id="${uid}-help">A page on this website starts with a slash, like /contact. Other websites start with https://. Email: mailto:name@example.com</div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="${uid}-tab" ${attrs.target === '_blank' ? 'checked' : ''}>
                    <label class="form-check-label" for="${uid}-tab">Open in a new tab</label>
                </div>
                <p class="small text-danger mb-0 mt-2" id="${uid}-error" role="alert"></p>
                <div class="rt-panel-actions">
                    <button type="button" class="btn btn-primary btn-sm" data-rt-apply>${attrs.href ? 'Update link' : 'Add link'}</button>
                    ${attrs.href ? '<button type="button" class="btn btn-outline-danger btn-sm" data-rt-remove-link>Remove link</button>' : ''}
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-rt-cancel>Cancel</button>
                </div>
            </div>`, `#${CSS.escape(uid)}`);

        const form = this.panel.querySelector('.rt-panel-form');
        form.querySelector('[data-rt-apply]').addEventListener('click', () => {
            const href = form.querySelector(`#${CSS.escape(uid)}`).value.trim();
            const error = form.querySelector(`#${CSS.escape(uid)}-error`);
            if (!isValidLink(href)) {
                error.textContent = href ? `“${href}” is not a link the website can open. ${linkSuggestion(href)}` : 'Type a link address.';
                return;
            }
            const target = form.querySelector(`#${CSS.escape(uid)}-tab`).checked ? '_blank' : null;
            const chain = this.editor.chain().focus().extendMarkRange('link');
            if (this.editor.state.selection.empty && !this.editor.isActive('link')) {
                chain.insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href, target } }] }).run();
            } else {
                chain.setLink({ href, target }).run();
            }
            this.closePanel();
            announce('Link saved');
        });
        form.querySelector('[data-rt-remove-link]')?.addEventListener('click', () => {
            this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
            this.closePanel();
            announce('Link removed');
        });
    }

    openColorPanel() {
        const current = this.editor.getAttributes('textStyle').color || '';
        this.showPanel(`
            <div class="rt-colors" role="group" aria-label="Text colour">
                ${COLORS.map(([value, label]) => `<button type="button" class="rt-swatch ${value === current ? 'is-active' : ''}" data-color="${value}" aria-label="${label}" aria-pressed="${value === current}" style="${value ? `--swatch:${value}` : ''}">${value ? '' : ICON('bi-slash-circle')}<span>${label}</span></button>`).join('')}
                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" data-rt-cancel>Close</button>
            </div>`, '.rt-swatch.is-active, .rt-swatch');

        this.panel.querySelectorAll('[data-color]').forEach((swatch) => swatch.addEventListener('click', () => {
            const color = swatch.dataset.color;
            if (color) this.editor.chain().focus().setColor(color).run();
            else this.editor.chain().focus().unsetColor().run();
            this.closePanel();
        }));
    }

    async insertImage() {
        const item = await openMediaPicker({ title: 'Insert an image into the text', requireAlt: true });
        if (!item) {
            this.editor.commands.focus();
            return;
        }
        this.editor.chain().focus().setImage({ src: item.url, alt: item.alt || '', title: item.caption || null }).run();
        announce('Image inserted');
    }

    openImageAltPanel() {
        const attrs = this.editor.getAttributes('image');
        const uid = `${this.textarea.id}-alt`;
        this.showPanel(`
            <div class="rt-panel-form" role="group" aria-label="Image description">
                <label class="form-label" for="${uid}">Image description (alt text)</label>
                <input type="text" class="form-control form-control-sm" id="${uid}" maxlength="255" value="${escapeHtml(attrs.alt || '')}">
                <div class="form-help">What does the photo show? Read aloud to visitors who cannot see it.</div>
                <div class="rt-panel-actions">
                    <button type="button" class="btn btn-primary btn-sm" data-rt-apply>Save description</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-rt-cancel>Cancel</button>
                </div>
            </div>`, `#${CSS.escape(uid)}`);
        this.panel.querySelector('[data-rt-apply]').addEventListener('click', () => {
            this.editor.chain().focus().updateAttributes('image', { alt: this.panel.querySelector('input').value.trim() }).run();
            this.closePanel();
        });
    }

    openVideoPanel() {
        const uid = `${this.textarea.id}-video`;
        this.showPanel(`
            <div class="rt-panel-form" role="group" aria-label="Video">
                <label class="form-label" for="${uid}">YouTube or Vimeo link</label>
                <input type="url" class="form-control form-control-sm" id="${uid}" placeholder="https://www.youtube.com/watch?v=…" aria-describedby="${uid}-error">
                <div class="form-help">Open the video, copy the address from the browser bar and paste it here.</div>
                <p class="small text-danger mb-0 mt-2" id="${uid}-error" role="alert"></p>
                <div class="rt-panel-actions">
                    <button type="button" class="btn btn-primary btn-sm" data-rt-apply>Insert video</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-rt-cancel>Cancel</button>
                </div>
            </div>`, `#${CSS.escape(uid)}`);
        this.panel.querySelector('[data-rt-apply]').addEventListener('click', () => {
            const src = videoEmbedUrl(this.panel.querySelector('input').value);
            if (!src) {
                this.panel.querySelector(`#${CSS.escape(uid)}-error`).textContent = 'This is not a YouTube or Vimeo video link. Copy the address of the video page and paste it here.';
                return;
            }
            this.editor.chain().focus().insertContent({ type: 'videoEmbed', attrs: { src, title: 'Video' } }).run();
            this.closePanel();
            announce('Video inserted');
        });
    }

    toggleFullscreen() {
        const on = !this.shell.classList.contains('is-fullscreen');
        this.shell.classList.toggle('is-fullscreen', on);
        document.body.classList.toggle('rt-fullscreen-open', on);
        const button = this.toolbar.querySelector('[data-rt="fullscreen"]');
        if (button) {
            button.innerHTML = ICON(on ? 'bi-fullscreen-exit' : 'bi-arrows-fullscreen');
            button.setAttribute('aria-label', on ? 'Exit full screen' : 'Full screen');
            button.title = on ? 'Exit full screen (Esc)' : 'Full screen';
        }
        this.editor.commands.focus();
        announce(on ? 'Full screen editing. Press Escape to leave.' : 'Left full screen');
    }

    toggleSource() {
        this.sourceMode = !this.sourceMode;
        const content = this.shell.querySelector('.rt-content');
        if (this.sourceMode) {
            this.pushToTextarea();
            content.hidden = true;
            this.textarea.hidden = false;
            this.textarea.classList.add('rt-source-visible');
            this.textarea.rows = 14;
            this.textarea.focus();
            announce('Editing the HTML source. Unsafe code is removed when the page is saved.');
        } else {
            this.editor.commands.setContent(this.textarea.value, { emitUpdate: false });
            this.textarea.hidden = true;
            this.textarea.classList.remove('rt-source-visible');
            content.hidden = false;
            this.pushToTextarea();
            this.editor.commands.focus();
        }
        this.refreshToolbar();
    }

    destroy() {
        this.editor?.destroy();
        document.body.classList.remove('rt-fullscreen-open');
    }
}

export function initRichText(root = document) {
    const wrappers = [];
    if (root.matches?.('[data-rich-text]')) wrappers.push(root);
    root.querySelectorAll?.('[data-rich-text]').forEach((el) => wrappers.push(el));

    wrappers.forEach((wrapper) => {
        // A cloned section brings a copy of the old editor's markup: remove it
        // and start again from the textarea.
        if (wrapper.dataset.rtReady && !wrapper.richText) {
            wrapper.querySelectorAll('[data-rt-generated]').forEach((el) => el.remove());
            const textarea = wrapper.querySelector('textarea');
            if (textarea) textarea.hidden = false;
            delete wrapper.dataset.rtReady;
        }
        if (wrapper.dataset.rtReady || !wrapper.querySelector('textarea') || wrapper.closest('template')) return;
        wrapper.dataset.rtReady = '1';
        try {
            new RichTextEditor(wrapper); // eslint-disable-line no-new
        } catch (error) {
            // The plain textarea still works if the editor cannot start.
            wrapper.querySelectorAll('[data-rt-generated]').forEach((el) => el.remove());
            const textarea = wrapper.querySelector('textarea');
            if (textarea) textarea.hidden = false;
            // eslint-disable-next-line no-console
            console.error('[ub-admin] rich text editor failed to start', error);
        }
    });
}

export function watchRichText() {
    initRichText(document);
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) initRichText(node);
            });
            mutation.removedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                const removed = node.matches?.('[data-rich-text]') ? [node] : Array.from(node.querySelectorAll?.('[data-rich-text]') || []);
                removed.forEach((wrapper) => {
                    if (wrapper.richText && !document.body.contains(wrapper)) {
                        wrapper.richText.destroy();
                        delete wrapper.richText;
                    }
                });
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
}
