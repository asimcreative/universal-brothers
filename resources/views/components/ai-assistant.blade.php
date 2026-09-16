@php
    use App\Support\Ai\AiConfig;

    // Rendered on every public page, so this must stay cheap: AiConfig caches
    // the settings row for an hour and nothing else here touches the database.
    $available = AiConfig::publiclyAvailable();
@endphp

@if($available)
    @php
        $assistantName = AiConfig::assistantName();
        $welcome = AiConfig::welcomeMessage();
        $icon = AiConfig::settings()->assistant_icon ?: 'bi-stars';
        $leadEnabled = AiConfig::leadCaptureEnabled();
        $maxLength = AiConfig::maxMessageLength();

        // Predefined questions behind the quick-action chips. Written the way a
        // visitor would actually ask, so the retrieval layer scores them the
        // same way it scores a real question.
        $quickActions = [
            ['label' => __('Hajj Packages'), 'icon' => 'bi-moon-stars', 'question' => 'What Hajj 2027 packages does Universal Brothers offer?'],
            ['label' => __('Hajj 2027 Prices'), 'icon' => 'bi-tag', 'question' => 'What are the Hajj 2027 package prices in USD, PKR and SAR?'],
            ['label' => __('Compare Packages'), 'icon' => 'bi-sliders', 'question' => 'Can you compare the Hajj packages and help me choose one?'],
            ['label' => __('Umrah Services'), 'icon' => 'bi-brightness-high', 'question' => 'What Umrah services does Universal Brothers provide?'],
            ['label' => __('Tourism'), 'icon' => 'bi-airplane', 'question' => 'What tourism packages are available?'],
            ['label' => __('How to Register'), 'icon' => 'bi-journal-check', 'question' => 'How do I register for Hajj with Universal Brothers?'],
            ['label' => __('Contact Us'), 'icon' => 'bi-telephone', 'question' => 'How can I contact Universal Brothers?'],
        ];
    @endphp

    <div
        class="ai-assistant"
        id="ai-assistant"
        data-ai-assistant
        data-chat-url="{{ route('ai.chat') }}"
        data-token-url="{{ route('ai.token') }}"
        data-lead-url="{{ route('ai.lead') }}"
        data-reset-url="{{ route('ai.reset') }}"
        data-max-length="{{ $maxLength }}"
        data-lead-enabled="{{ $leadEnabled ? '1' : '0' }}"
    >
        <button
            type="button"
            class="ai-launcher"
            data-ai-toggle
            aria-expanded="false"
            aria-controls="ai-assistant-panel"
            aria-label="{{ __('Open the :name', ['name' => $assistantName]) }}"
        >
            <i class="bi {{ $icon }} ai-launcher-icon" aria-hidden="true"></i>
            <i class="bi bi-x-lg ai-launcher-close" aria-hidden="true"></i>
            <span class="ai-launcher-label">{{ __('Ask us') }}</span>
        </button>

        <div
            class="ai-panel"
            id="ai-assistant-panel"
            role="dialog"
            aria-modal="false"
            aria-labelledby="ai-panel-title"
            hidden
        >
            <header class="ai-panel-header">
                <span class="ai-avatar" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                <span class="ai-panel-heading">
                    <span class="ai-panel-title" id="ai-panel-title">{{ $assistantName }}</span>
                    {{-- Short enough not to wrap at the 380px panel width, and
                         it still leads with "AI assistant", which is the part
                         the visitor is entitled to know. --}}
                    <span class="ai-panel-subtitle">{{ __('AI assistant — any language') }}</span>
                </span>
                <button type="button" class="ai-icon-button" data-ai-clear title="{{ __('Start a new conversation') }}" aria-label="{{ __('Start a new conversation') }}">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </button>
                <button type="button" class="ai-icon-button" data-ai-close aria-label="{{ __('Close the chat') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            {{-- aria-live so a screen reader announces replies as they arrive;
                 "polite" rather than "assertive" so it waits for a pause
                 instead of interrupting whatever the user is doing. --}}
            <div class="ai-messages" data-ai-messages role="log" aria-live="polite" aria-relevant="additions" tabindex="0">
                <div class="ai-message ai-message--assistant">
                    <span class="ai-avatar ai-avatar--sm" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                    <div class="ai-bubble">{!! nl2br(e($welcome)) !!}</div>
                </div>
            </div>

            <div class="ai-quick-actions" data-ai-quick-actions>
                @foreach($quickActions as $action)
                    <button type="button" class="ai-chip" data-ai-quick="{{ $action['question'] }}">
                        <i class="bi {{ $action['icon'] }}" aria-hidden="true"></i>{{ $action['label'] }}
                    </button>
                @endforeach
            </div>

            @if($leadEnabled)
                {{--
                    Inside a <template> rather than rendered hidden.

                    A hidden form is still real DOM: its fields stay in the
                    document, so "Email" on this form and "Email" on the page's
                    own contact form become two matches for the same accessible
                    name on every single page. That broke 17 existing browser
                    tests, and what it actually means for a visitor is that a
                    form they were never offered is sitting in the page
                    competing with the one they are using.

                    <template> content is inert — not in the document tree, not
                    in the accessibility tree, not matched by a query — until
                    the assistant clones it in.
                --}}
                <template data-ai-lead-template>
                <form class="ai-lead-form" data-ai-lead-form novalidate>
                    <p class="ai-lead-intro">{{ __('Leave your details and our team will contact you.') }}</p>

                    <div class="ai-lead-grid">
                        <label class="ai-field">
                            <span>{{ __('Name') }}</span>
                            <input type="text" name="name" required maxlength="120" autocomplete="name">
                        </label>
                        <label class="ai-field">
                            <span>{{ __('Email') }}</span>
                            <input type="email" name="email" required maxlength="190" autocomplete="email">
                        </label>
                        <label class="ai-field">
                            <span>{{ __('Phone / WhatsApp') }}</span>
                            <input type="tel" name="phone" required maxlength="40" autocomplete="tel">
                        </label>
                        <label class="ai-field">
                            <span>{{ __('Interested in') }}</span>
                            <select name="service">
                                <option value="Hajj">{{ __('Hajj') }}</option>
                                <option value="Umrah">{{ __('Umrah') }}</option>
                                <option value="Tourism">{{ __('Tourism') }}</option>
                                <option value="Other">{{ __('Other') }}</option>
                            </select>
                        </label>
                        <label class="ai-field">
                            <span>{{ __('Preferred package') }} <small>{{ __('optional') }}</small></span>
                            <input type="text" name="package_code" maxlength="20" placeholder="UB001">
                        </label>
                        <label class="ai-field">
                            <span>{{ __('Currency') }}</span>
                            <select name="currency">
                                <option value="">{{ __('No preference') }}</option>
                                <option value="PKR">PKR</option>
                                <option value="SAR">SAR</option>
                                <option value="USD">USD</option>
                            </select>
                        </label>
                    </div>

                    <label class="ai-field ai-field--full">
                        <span>{{ __('Message') }} <small>{{ __('optional') }}</small></span>
                        <textarea name="message" rows="2" maxlength="2000"></textarea>
                    </label>

                    <label class="ai-consent">
                        <input type="checkbox" name="consent" value="1" required>
                        <span>{{ __('I agree to be contacted by Universal Brothers about this enquiry.') }}</span>
                    </label>

                    <div class="ai-lead-actions">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Send enquiry') }}</button>
                        <button type="button" class="btn btn-link btn-sm" data-ai-lead-cancel>{{ __('Not now') }}</button>
                    </div>

                    <p class="ai-lead-status" data-ai-lead-status role="status" hidden></p>
                </form>
                </template>
            @endif

            <form class="ai-composer" data-ai-form>
                <label class="visually-hidden" for="ai-input">{{ __('Type your question') }}</label>
                <textarea
                    id="ai-input"
                    class="ai-input"
                    data-ai-input
                    rows="1"
                    maxlength="{{ $maxLength }}"
                    {{-- Short enough to sit on ONE line inside the composer at
                         390px. The longer version wrapped to two lines in a
                         one-row textarea and had its second line clipped. --}}
                    placeholder="{{ __('Ask about packages or prices…') }}"
                    autocomplete="off"
                ></textarea>
                {{--
                    "Send to the assistant", not "Send message".

                    The widget is on every page, so any accessible name it
                    contributes competes with the page's own forms — and
                    matching is substring-based, so "Send message" is a match
                    for both "Message" (the contact and enquiry textareas) and
                    "Send Message" (their submit buttons). That is not only a
                    test problem: a screen-reader user tabbing a contact page
                    would meet two controls whose names collide.

                    Rule for anything added here: its accessible name must not
                    contain any label the public forms use — Full Name, Email,
                    Phone, Phone / WhatsApp, Message, Service, Duration,
                    Sharing, Arrival, Aziziya, Package Type. Guarded by the
                    "never competes with the page" test.
                --}}
                <button type="submit" class="ai-send" data-ai-send aria-label="{{ __('Send to the assistant') }}">
                    <i class="bi bi-send-fill" aria-hidden="true"></i>
                </button>
            </form>

            <p class="ai-disclaimer">{{ __('AI assistant — please confirm prices and availability with our team.') }}</p>
        </div>
    </div>
@endif
