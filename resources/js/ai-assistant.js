/**
 * Universal Brothers AI assistant widget.
 *
 * The security rule that shapes this file: the assistant's reply is NEVER
 * treated as HTML. It is escaped first, and only then does a tiny formatter
 * re-introduce line breaks, bold and list markers. Links are not parsed out of
 * the reply text unless they point at THIS site — see linkify(). The cards
 * under a reply come from the `sources` array the backend returns, which only
 * ever contains URLs this application generated with route(). A model that is
 * talked into emitting `<img onerror=...>` or a link to somewhere else
 * therefore cannot do anything: the markup is inert text, and an off-site or
 * `javascript:` URL never becomes an anchor.
 */

const ESCAPE_MAP = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ESCAPE_MAP[char]);
}

/**
 * Minimal, allow-list formatting applied AFTER escaping, so no tag can
 * originate from the model's output. Supports **bold**, line breaks and
 * "- " bullets, which is all the shapes a short assistant reply needs.
 */
function formatReply(text) {
    const escaped = escapeHtml(text).trim();

    const lines = escaped.split(/\r?\n/);
    const html = [];
    let inList = false;

    lines.forEach((rawLine) => {
        const line = rawLine.trim();

        if (/^[-*•]\s+/.test(line)) {
            if (!inList) {
                html.push('<ul class="ai-list">');
                inList = true;
            }
            html.push(`<li>${inlineFormat(line.replace(/^[-*•]\s+/, ''))}</li>`);
            return;
        }

        if (inList) {
            html.push('</ul>');
            inList = false;
        }

        if (line === '') {
            html.push('<span class="ai-break"></span>');
            return;
        }

        html.push(`<p>${inlineFormat(line)}</p>`);
    });

    if (inList) html.push('</ul>');

    return html.join('');
}

const UNESCAPE_MAP = {
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
};

function unescapeHtml(value) {
    return String(value ?? '').replace(/&(amp|lt|gt|quot|#39);/g, (entity) => UNESCAPE_MAP[entity]);
}

/**
 * Markdown links `[text](url)` and bare `https://…` URLs in an already-escaped
 * line.
 *
 * The first version rendered none of these, on purpose — and the live site
 * showed why that was the wrong trade: the model writes "check the Hajj
 * packages page [here](https://…/hajj)", and the visitor saw the raw brackets
 * and a URL they could not click.
 *
 * The protection is kept, just moved to the one place it matters. A link is
 * only ever produced when its URL is same-origin http(s) — this site's own
 * pages. Anything else (another domain, `javascript:`, `data:`, a
 * protocol-relative `//evil.example`) keeps its words and loses the link, so a
 * model talked into emitting a hostile URL still cannot put a clickable one in
 * front of a visitor. The label arrives already escaped and the href is
 * re-escaped, so no attribute can be broken out of.
 */
const LINK_PATTERN = /\[([^\]\n]{1,160})\]\(([^)\s]{1,500})\)|(https?:\/\/[^\s<>()]+[^\s<>().,;:!?])/g;

function linkify(text) {
    return text.replace(LINK_PATTERN, (match, label, markdownUrl, bareUrl) => {
        const url = unescapeHtml(markdownUrl ?? bareUrl);

        if (!isSafeInternalUrl(url)) {
            // Keep the words the visitor was meant to read; drop the link.
            return markdownUrl !== undefined ? label : match;
        }

        const text = markdownUrl !== undefined ? label : match;

        return `<a href="${escapeHtml(url)}" class="ai-inline-link">${text}</a>`;
    });
}

function inlineFormat(text) {
    return linkify(text).replace(/\*\*([^*]{1,200})\*\*/g, '<strong>$1</strong>');
}

/**
 * A source URL is rendered as an anchor only if it is same-origin. The backend
 * already generates these with route(), so this is defence in depth against a
 * future change (or a tampered response) turning the source card list into an
 * open redirect.
 */
function isSafeInternalUrl(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.origin === window.location.origin && /^https?:$/.test(parsed.protocol);
    } catch {
        return false;
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

class AiAssistant {
    constructor(root) {
        this.root = root;
        this.chatUrl = root.dataset.chatUrl;
        this.leadUrl = root.dataset.leadUrl;
        this.resetUrl = root.dataset.resetUrl;
        this.tokenUrl = root.dataset.tokenUrl;
        this.maxLength = parseInt(root.dataset.maxLength, 10) || 1000;
        this.leadEnabled = root.dataset.leadEnabled === '1';

        this.panel = root.querySelector('.ai-panel');
        this.toggle = root.querySelector('[data-ai-toggle]');
        this.messages = root.querySelector('[data-ai-messages]');
        this.form = root.querySelector('[data-ai-form]');
        this.input = root.querySelector('[data-ai-input]');
        this.sendButton = root.querySelector('[data-ai-send]');
        this.quickActions = root.querySelector('[data-ai-quick-actions]');
        // The lead form lives in a <template> and is cloned in only when the
        // backend offers it — see the Blade comment. Until then there is no
        // form in the document at all.
        this.leadTemplate = root.querySelector('[data-ai-lead-template]');
        this.leadForm = null;
        this.leadStatus = null;

        this.busy = false;
        this.open = false;

        this.bind();
    }

    bind() {
        this.toggle?.addEventListener('click', () => this.setOpen(!this.open));
        this.root.querySelector('[data-ai-close]')?.addEventListener('click', () => this.setOpen(false));
        this.root.querySelector('[data-ai-clear]')?.addEventListener('click', () => this.clear());

        this.form?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.send(this.input.value);
        });

        this.input?.addEventListener('keydown', (event) => {
            // Enter sends, Shift+Enter makes a new line — the convention every
            // chat interface uses, so anything else feels broken.
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.send(this.input.value);
            }
        });

        this.input?.addEventListener('input', () => this.autoGrow());

        this.quickActions?.querySelectorAll('[data-ai-quick]').forEach((button) => {
            button.addEventListener('click', () => this.send(button.dataset.aiQuick));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.open) {
                this.setOpen(false);
                this.toggle?.focus();
            }
        });
    }

    /**
     * Clone the lead form out of its template and attach it, once. Handlers are
     * bound here rather than in bind(), because before this runs the form does
     * not exist.
     */
    showLeadForm() {
        if (!this.leadTemplate) return;

        if (!this.leadForm) {
            const fragment = this.leadTemplate.content.cloneNode(true);
            this.leadForm = fragment.querySelector('[data-ai-lead-form]');
            if (!this.leadForm) return;

            this.leadForm.addEventListener('submit', (event) => {
                event.preventDefault();
                this.submitLead();
            });

            this.leadForm.querySelector('[data-ai-lead-cancel]')?.addEventListener('click', () => {
                this.removeLeadForm();
            });

            // Before the composer, so the visitor's next action is either the
            // form or carrying on typing — not a form below the send button.
            this.form.parentNode.insertBefore(this.leadForm, this.form);
            this.leadStatus = this.leadForm.querySelector('[data-ai-lead-status]');
        }

        this.leadForm.hidden = false;
        this.scrollToLatest();
    }

    removeLeadForm() {
        this.leadForm?.remove();
        this.leadForm = null;
        this.leadStatus = null;
    }

    setOpen(open) {
        this.open = open;
        this.panel.hidden = !open;
        this.root.classList.toggle('is-open', open);
        this.toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            this.scrollToLatest();
            // Only steal focus on a pointer-width screen. On a phone, focusing
            // the textarea opens the keyboard immediately and covers the
            // conversation the visitor just opened.
            if (window.matchMedia('(min-width: 576px)').matches) {
                window.setTimeout(() => this.input?.focus(), 120);
            }
        }
    }

    autoGrow() {
        if (!this.input) return;
        this.input.style.height = 'auto';
        this.input.style.height = `${Math.min(this.input.scrollHeight, 120)}px`;
    }

    appendMessage(role, html, options = {}) {
        const wrapper = document.createElement('div');
        wrapper.className = `ai-message ai-message--${role}`;
        if (options.error) wrapper.classList.add('ai-message--error');

        if (role === 'assistant') {
            const avatar = document.createElement('span');
            avatar.className = 'ai-avatar ai-avatar--sm';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.innerHTML = '<i class="bi bi-stars"></i>';
            wrapper.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble';
        bubble.innerHTML = html;
        wrapper.appendChild(bubble);

        this.messages.appendChild(wrapper);
        this.scrollToLatest();

        return wrapper;
    }

    appendSources(sources) {
        if (!Array.isArray(sources) || sources.length === 0) return;

        const safe = sources.filter((source) => source && isSafeInternalUrl(source.url));
        if (safe.length === 0) return;

        const list = document.createElement('div');
        list.className = 'ai-sources';

        const heading = document.createElement('p');
        heading.className = 'ai-sources-title';
        heading.textContent = 'Helpful pages';
        list.appendChild(heading);

        safe.forEach((source) => {
            const link = document.createElement('a');
            link.className = 'ai-source-card';
            link.href = source.url;
            // textContent, not innerHTML: the title comes from the database
            // and must never be able to introduce markup here.
            link.innerHTML = '<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>';

            const body = document.createElement('span');
            const title = document.createElement('strong');
            title.textContent = source.title || 'Open page';
            body.appendChild(title);

            if (source.reason) {
                const reason = document.createElement('small');
                reason.textContent = source.reason;
                body.appendChild(reason);
            }

            link.appendChild(body);
            list.appendChild(link);
        });

        this.messages.appendChild(list);
        this.scrollToLatest();
    }

    showTyping() {
        const wrapper = document.createElement('div');
        wrapper.className = 'ai-message ai-message--assistant ai-typing';
        wrapper.setAttribute('aria-label', 'Assistant is typing');
        wrapper.innerHTML =
            '<span class="ai-avatar ai-avatar--sm" aria-hidden="true"><i class="bi bi-stars"></i></span>' +
            '<div class="ai-bubble"><span class="ai-dot"></span><span class="ai-dot"></span><span class="ai-dot"></span></div>';
        this.messages.appendChild(wrapper);
        this.scrollToLatest();
        return wrapper;
    }

    setBusy(busy) {
        this.busy = busy;
        if (this.sendButton) this.sendButton.disabled = busy;
        if (this.input) this.input.disabled = busy;
        this.root.classList.toggle('is-busy', busy);
    }

    scrollToLatest() {
        window.requestAnimationFrame(() => {
            this.messages.scrollTop = this.messages.scrollHeight;
        });
    }

    async send(rawMessage) {
        const message = String(rawMessage ?? '').trim();

        if (!message || this.busy) return;

        if (message.length > this.maxLength) {
            this.appendMessage('assistant', formatReply(`Your message is too long. Please keep it under ${this.maxLength} characters.`), { error: true });
            return;
        }

        this.quickActions?.setAttribute('hidden', 'hidden');
        this.appendMessage('user', formatReply(message));

        if (this.input) {
            this.input.value = '';
            this.autoGrow();
        }

        this.setBusy(true);
        const typing = this.showTyping();

        try {
            const response = await this.post(this.chatUrl, JSON.stringify({ message }));

            const data = await response.json().catch(() => null);
            typing.remove();

            if (response.status === 419) {
                // A fresh token was fetched and the message still refused, so
                // the page itself is too old to trust. Say so plainly instead
                // of offering a "Try again" that cannot work.
                this.appendMessage('assistant', formatReply('Your session has timed out. Please refresh the page and ask again.'), { error: true });
                return;
            }

            if (!data) {
                this.appendRetry(message, 'Sorry — something went wrong. Please try again.');
                return;
            }

            const reply = data.reply || 'Sorry — I could not answer that. Please contact our team.';

            if (!data.ok) {
                // A limit is not a failure a retry can fix. Offering "Try again"
                // after "you have reached today's message limit" invites the
                // visitor to press a button that cannot work.
                if (data.error === 'daily_limit' || data.error === 'conversation_too_long') {
                    this.appendMessage('assistant', formatReply(reply), { error: true });
                    return;
                }

                this.appendRetry(message, reply);
                return;
            }

            this.appendMessage('assistant', formatReply(reply));
            this.appendSources(data.sources);

            if (data.offer_lead && this.leadEnabled) {
                this.showLeadForm();
            }
        } catch {
            typing.remove();
            this.appendRetry(message, 'Sorry — I could not reach the server. Please check your connection and try again.');
        } finally {
            this.setBusy(false);
            if (window.matchMedia('(min-width: 576px)').matches) this.input?.focus();
        }
    }

    appendRetry(message, text) {
        const wrapper = this.appendMessage('assistant', formatReply(text), { error: true });

        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'ai-retry';
        retry.textContent = 'Try again';
        retry.addEventListener('click', () => {
            wrapper.remove();
            this.send(message);
        });

        wrapper.querySelector('.ai-bubble')?.appendChild(retry);
    }

    /**
     * Send a request with the page's form token, and if the session has
     * expired while the visitor had the page open, fetch a fresh token and
     * send it once more. Without this the visitor is simply told the
     * assistant cannot answer, and pressing "Try again" fails the same way
     * because the page still holds the token of a session that is gone.
     */
    async post(url, body = null) {
        const send = () => fetch(url, {
            method: 'POST',
            headers: {
                ...(body === null ? {} : { 'Content-Type': 'application/json' }),
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            ...(body === null ? {} : { body }),
        });

        const response = await send();

        if (response.status !== 419 || !(await this.refreshToken())) {
            return response;
        }

        return send();
    }

    async refreshToken() {
        if (!this.tokenUrl) return false;

        try {
            const response = await fetch(this.tokenUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) return false;

            const data = await response.json().catch(() => null);

            if (!data?.token) return false;

            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.content = data.token;

            return true;
        } catch {
            return false;
        }
    }

    async submitLead() {
        if (!this.leadForm) return;

        const formData = new FormData(this.leadForm);
        const payload = Object.fromEntries(formData.entries());
        payload.consent = formData.get('consent') ? 1 : 0;

        this.setLeadStatus('Sending…', false);

        try {
            const response = await this.post(this.leadUrl, JSON.stringify(payload));

            const data = await response.json().catch(() => null);

            if (response.status === 419) {
                // Their name and number are still in the form, so refreshing
                // the page is the one instruction that keeps their details.
                this.setLeadStatus('Your session has timed out. Please refresh the page and send your details again.', true);
                return;
            }

            if (response.ok && data?.ok) {
                // Removed rather than hidden: the enquiry is sent, so the form
                // has no further purpose and should not linger in the page.
                this.removeLeadForm();
                this.appendMessage('assistant', formatReply(data.message || 'Thank you. Our team will contact you shortly.'));
                return;
            }

            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
            this.setLeadStatus(firstError || 'Please check the form and try again.', true);
        } catch {
            this.setLeadStatus('Could not send right now. Please try again.', true);
        }
    }

    setLeadStatus(text, isError) {
        if (!this.leadStatus) return;
        this.leadStatus.hidden = false;
        this.leadStatus.textContent = text;
        this.leadStatus.classList.toggle('is-error', Boolean(isError));
    }

    async clear() {
        try {
            await this.post(this.resetUrl);
        } catch {
            // A failed reset is not worth an error message: the visitor's
            // intent is a clean panel, and they get that either way.
        }

        // Keep the seeded welcome message, drop everything after it.
        const welcome = this.messages.firstElementChild;
        this.messages.innerHTML = '';
        if (welcome) this.messages.appendChild(welcome);

        this.quickActions?.removeAttribute('hidden');
        this.removeLeadForm();
    }
}

export function initAiAssistant() {
    document.querySelectorAll('[data-ai-assistant]').forEach((root) => new AiAssistant(root));
}

export { escapeHtml, formatReply, isSafeInternalUrl };
