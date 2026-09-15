@extends('layouts.admin')

@section('title', 'AI Assistant')
@section('subtitle', 'The chat assistant on the website: whether it is on, how it answers, and the limits that protect your account.')
@section('guide', 'ai-assistant')

@section('actions')
    <x-admin.status-badge :status="$settings->is_enabled ? ($settings->public_enabled ? 'live' : 'draft') : 'disabled'"
        :label="$settings->is_enabled ? ($settings->public_enabled ? 'On the website' : 'Admin testing only') : 'Switched off'" />
    <a href="{{ route('admin.ai.test') }}" class="btn btn-outline-primary"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Test panel</a>
    <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-secondary"><i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Conversations</a>
@endsection

@php
    $icons = [
        'bi-stars' => 'Sparkles',
        'bi-chat-heart' => 'Speech bubble with heart',
        'bi-chat-dots' => 'Speech bubble',
        'bi-headset' => 'Headset',
        'bi-moon-stars' => 'Moon and stars',
        'bi-person-circle' => 'Person',
        'bi-question-circle' => 'Question mark',
    ];
    $currentIcon = old('assistant_icon', $settings->assistant_icon) ?: 'bi-stars';
    $providers = ['openai' => 'OpenAI', 'openai-compatible' => 'Another provider that works like OpenAI'];
    $currentProvider = old('provider', $settings->provider) ?: 'openai';
@endphp

@section('content')
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Conversations', 'value' => number_format($stats['conversations']), 'icon' => 'bi-chat-dots', 'tone' => 'info', 'href' => route('admin.ai.conversations')],
            ['label' => 'Messages', 'value' => number_format($stats['messages']), 'icon' => 'bi-chat-text', 'tone' => null, 'href' => null],
            ['label' => 'Replies that failed', 'value' => number_format($stats['failures']), 'icon' => 'bi-exclamation-triangle', 'tone' => $stats['failures'] ? 'danger' : null, 'href' => null],
            ['label' => 'Enquiries collected', 'value' => number_format($stats['leads']), 'icon' => 'bi-person-check', 'tone' => 'success', 'href' => null],
            ['label' => 'Records the assistant knows', 'value' => number_format($stats['indexed']), 'icon' => 'bi-database', 'tone' => 'gold', 'href' => null],
        ] as $card)
            <div class="col-6 col-lg">
                <x-admin.stat-card :label="$card['label']" :value="$card['value']" :icon="$card['icon']" :tone="$card['tone']" :href="$card['href']" />
            </div>
        @endforeach
    </div>

    <nav class="admin-tabs card mb-3 pb-tabs" aria-label="AI assistant settings sections">
        <a href="#ai-general"><i class="bi bi-toggle-on" aria-hidden="true"></i>On or off</a>
        <a href="#ai-provider"><i class="bi bi-key" aria-hidden="true"></i>Provider and key</a>
        <a href="#ai-answers"><i class="bi bi-chat-square-text" aria-hidden="true"></i>How it answers</a>
        <a href="#ai-knowledge"><i class="bi bi-database" aria-hidden="true"></i>Knowledge and enquiries</a>
        <a href="#ai-limits"><i class="bi bi-shield-check" aria-hidden="true"></i>Limits and logging</a>
        @if($stats['top_questions']->isNotEmpty() || $stats['top_packages']->isNotEmpty())
            <a href="#ai-insights"><i class="bi bi-graph-up" aria-hidden="true"></i>What visitors ask</a>
        @endif
    </nav>

    <form method="POST" action="{{ route('admin.ai.update') }}" novalidate>
        @csrf
        @method('PUT')

        <x-admin.panel id="ai-general" title="On or off" icon="bi-toggle-on" class="mb-4" description="Switch the assistant on for everyone, or keep it for testing in the admin only.">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled)) aria-describedby="is_enabled_help">
                        <label class="form-check-label fw-semibold" for="is_enabled">Assistant enabled</label>
                    </div>
                    <div class="form-help" id="is_enabled_help">The main switch. When off, the assistant does not answer anywhere, including the test panel.</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="public_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="public_enabled" name="public_enabled" value="1" @checked(old('public_enabled', $settings->public_enabled)) aria-describedby="public_enabled_help">
                        <label class="form-check-label fw-semibold" for="public_enabled">Show on the public website</label>
                    </div>
                    <div class="form-help" id="public_enabled_help">When off, visitors do not see the chat button; you can still try it in the test panel.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="assistant_name">Name shown to visitors <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('assistant_name')]) id="assistant_name" name="assistant_name" value="{{ old('assistant_name', $settings->assistant_name) }}" maxlength="80" required>
                    <x-admin.error name="assistant_name" />
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="assistant_icon">Chat button icon</label>
                    <div class="pb-icon-select">
                        <span class="pb-icon-preview" aria-hidden="true"><i class="bi {{ $currentIcon }}" data-ai-icon-preview></i></span>
                        <select @class(['form-select', 'is-invalid' => $errors->has('assistant_icon')]) id="assistant_icon" name="assistant_icon" data-ai-icon-select>
                            @foreach($icons as $value => $label)
                                <option value="{{ $value }}" @selected($currentIcon === $value)>{{ $label }}</option>
                            @endforeach
                            @unless(array_key_exists($currentIcon, $icons))
                                <option value="{{ $currentIcon }}" selected>Current icon</option>
                            @endunless
                        </select>
                    </div>
                    <x-admin.error name="assistant_icon" />
                </div>

                <div class="col-12">
                    <label class="form-label" for="welcome_message">Welcome message</label>
                    <textarea class="form-control" id="welcome_message" name="welcome_message" rows="3" maxlength="2000" placeholder="{{ $defaults['welcome'] }}" aria-describedby="welcome_help">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                    <div class="form-help" id="welcome_help">The first message visitors see when they open the chat. Leave empty to use the message shown in grey.</div>
                    <x-admin.error name="welcome_message" />
                </div>

                <div class="col-12">
                    <label class="form-label" for="fallback_message">Message when the assistant cannot answer</label>
                    <textarea class="form-control" id="fallback_message" name="fallback_message" rows="2" maxlength="2000" placeholder="{{ $defaults['fallback'] }}" aria-describedby="fallback_help">{{ old('fallback_message', $settings->fallback_message) }}</textarea>
                    <div class="form-help" id="fallback_help">Shown when the AI service cannot be reached or has run out of credit, so visitors are pointed to your team instead.</div>
                    <x-admin.error name="fallback_message" />
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel id="ai-provider" title="Provider and API key" icon="bi-key" class="mb-4" description="The AI service that writes the answers, and the secret key for your account with it.">
            <div @class(['alert', 'admin-alert', 'alert-warning' => $keySource === 'missing', 'alert-light border' => $keySource !== 'missing']) role="status">
                <i class="bi {{ $keySource === 'missing' ? 'bi-exclamation-triangle-fill' : 'bi-shield-lock' }}" aria-hidden="true"></i>
                <div class="admin-alert-body">
                    @if($keySource === 'environment')
                        <strong>The API key is set on the server.</strong>
                        It is kept in the server's configuration and takes priority over a key saved here. It is never shown on this screen.
                    @elseif($keySource === 'database')
                        <strong>An API key is saved, encrypted.</strong>
                        Saved key: <code class="admin-code">{{ $maskedKey }}</code> — only the first and last characters are shown, and the full key is never sent to your browser.
                    @else
                        <strong>No API key yet.</strong>
                        The assistant cannot answer until a key is added below (or set on the server by your developer).
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="api_key">{{ $maskedKey ? 'Replace the API key' : 'API key' }}</label>
                    <div class="password-toggle-wrap">
                        <input @class(['form-control', 'is-invalid' => $errors->has('api_key')]) id="api_key" name="api_key" type="password" autocomplete="new-password" maxlength="300"
                               placeholder="{{ $maskedKey ? 'Leave empty to keep the saved key' : 'Paste the key from your provider account' }}" aria-describedby="api_key_help">
                    </div>
                    <div class="form-help" id="api_key_help">
                        Paste a new key only when you want to change it. Leaving this empty never removes the saved key. The key is encrypted when saved and is never written to logs.
                    </div>
                    <x-admin.error name="api_key" />
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if($keySource !== 'missing')
                            <button type="submit" form="ai-check-key" class="btn btn-sm btn-outline-primary"><i class="bi bi-plug me-1" aria-hidden="true"></i>Check the key works</button>
                        @endif
                        @if($maskedKey)
                            <button type="submit" form="ai-clear-key" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>Remove the saved key</button>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="provider">Provider <span class="required-mark" aria-hidden="true">*</span></label>
                    <select @class(['form-select', 'is-invalid' => $errors->has('provider')]) id="provider" name="provider" required>
                        @foreach($providers as $value => $label)
                            <option value="{{ $value }}" @selected($currentProvider === $value)>{{ $label }}</option>
                        @endforeach
                        @unless(array_key_exists($currentProvider, $providers))
                            <option value="{{ $currentProvider }}" selected>{{ $currentProvider }}</option>
                        @endunless
                    </select>
                    <x-admin.error name="provider" />
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="model">AI model <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('model')]) id="model" name="model" value="{{ old('model', $settings->model) }}" maxlength="80" placeholder="{{ config('ai.model') }}" required list="ai-model-suggestions" aria-describedby="model_help">
                    <datalist id="ai-model-suggestions">
                        <option value="gpt-4o-mini">
                        <option value="gpt-4.1-mini">
                        <option value="gpt-4o">
                    </datalist>
                    <div class="form-help" id="model_help">Which of the provider's models answers. Change only if your provider asks you to.</div>
                    <x-admin.error name="model" />
                </div>

                <div class="col-12">
                    <details class="seo-advanced" @if($errors->hasAny(['base_url', 'timeout_seconds'])) open @endif>
                        <summary>Advanced connection settings</summary>
                        <div class="row g-3 pt-3">
                            <div class="col-md-8">
                                <label class="form-label" for="base_url">Provider address (base URL)</label>
                                <input @class(['form-control', 'is-invalid' => $errors->has('base_url')]) id="base_url" name="base_url" value="{{ old('base_url', $settings->base_url) }}" maxlength="190" placeholder="{{ config('ai.base_url') }}" aria-describedby="base_url_help">
                                <div class="form-help" id="base_url_help">Leave empty for the standard address. It must start with https:// because the key is sent to it.</div>
                                <x-admin.error name="base_url" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="timeout_seconds">Give up waiting after (seconds) <span class="required-mark" aria-hidden="true">*</span></label>
                                <input @class(['form-control', 'is-invalid' => $errors->has('timeout_seconds')]) id="timeout_seconds" name="timeout_seconds" type="number" min="5" max="120" value="{{ old('timeout_seconds', $settings->timeout_seconds) }}" required>
                                <x-admin.error name="timeout_seconds" />
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel id="ai-answers" title="How it answers" icon="bi-chat-square-text" class="mb-4"
            description="The rules against making things up, and for prices, package options, links and handing over to your team, are always applied. These settings add to them.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="temperature">Style of answers</label>
                    <input type="range" class="form-range" id="temperature" name="temperature" min="0" max="1" step="0.05" value="{{ min(1, (float) old('temperature', $settings->temperature)) }}" aria-describedby="temperature_help temperature_value" data-ai-temperature>
                    <div class="d-flex justify-content-between small text-muted"><span>Precise and factual</span><strong id="temperature_value" data-ai-temperature-value>{{ number_format(min(1, (float) old('temperature', $settings->temperature)), 2) }}</strong><span>More varied</span></div>
                    <div class="form-help" id="temperature_help">Keep it towards "Precise" (0.3 or lower) so answers about prices and packages stay exact.</div>
                    <x-admin.error name="temperature" />
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="max_tokens">Longest answer <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-group">
                        <input @class(['form-control', 'is-invalid' => $errors->has('max_tokens')]) id="max_tokens" name="max_tokens" type="number" min="64" max="8000" value="{{ old('max_tokens', $settings->max_tokens) }}" required aria-describedby="max_tokens_help" data-ai-tokens>
                        <span class="input-group-text">tokens</span>
                    </div>
                    <div class="form-help" id="max_tokens_help">A token is about three quarters of a word, so this allows roughly <strong data-ai-words>{{ number_format((int) round(old('max_tokens', $settings->max_tokens) * 0.75)) }}</strong> words. Longer answers use more credit.</div>
                    <x-admin.error name="max_tokens" />
                </div>

                <div class="col-12">
                    <label class="form-label" for="brand_tone">Tone of voice</label>
                    <textarea class="form-control" id="brand_tone" name="brand_tone" rows="2" maxlength="2000" placeholder="{{ $defaults['tone'] }}">{{ old('brand_tone', $settings->brand_tone) }}</textarea>
                    <x-admin.error name="brand_tone" />
                </div>

                <div class="col-12">
                    <label class="form-label" for="supported_languages">Languages it replies in</label>
                    <input class="form-control" id="supported_languages" name="supported_languages" value="{{ old('supported_languages', $settings->supported_languages) }}" maxlength="300" placeholder="{{ $defaults['languages'] }}" aria-describedby="languages_help">
                    <div class="form-help" id="languages_help">The assistant replies in the language the visitor writes in, from this list.</div>
                    <x-admin.error name="supported_languages" />
                </div>

                <div class="col-12">
                    <label class="form-label" for="system_prompt">Extra instructions</label>
                    <textarea class="form-control" id="system_prompt" name="system_prompt" rows="5" maxlength="6000" aria-describedby="system_prompt_help">{{ old('system_prompt', $settings->system_prompt) }}</textarea>
                    <div class="form-help" id="system_prompt_help">For example a seasonal note ("Hajj 2027 bookings close on 30 November") or a phrase to use. Never put passwords or keys here.</div>
                    <x-admin.error name="system_prompt" />
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel id="ai-knowledge" title="Knowledge and enquiries" icon="bi-database" class="mb-4">
            <x-slot:actions>
                <button type="submit" form="ai-reindex" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Refresh what it knows</button>
            </x-slot:actions>
            <p class="small text-muted">
                Last refreshed <strong>{{ $settings->indexed_at?->diffForHumans() ?? 'never' }}</strong> — {{ number_format($settings->indexed_records) }} records from your packages, pages, FAQs and company details.
                Refresh after large changes to pages or FAQs; package prices are always read live.
            </p>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="use_database_retrieval" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="use_database_retrieval" name="use_database_retrieval" value="1" @checked(old('use_database_retrieval', $settings->use_database_retrieval)) aria-describedby="db_help">
                        <label class="form-check-label fw-semibold" for="use_database_retrieval">Answer from your packages and company details</label>
                    </div>
                    <div class="form-help text-danger-emphasis" id="db_help">Keep this on. Turning it off leaves the assistant with nothing reliable to answer from.</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="use_page_retrieval" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="use_page_retrieval" name="use_page_retrieval" value="1" @checked(old('use_page_retrieval', $settings->use_page_retrieval))>
                        <label class="form-check-label fw-semibold" for="use_page_retrieval">Also use the website's pages, FAQs and news</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="show_source_links" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_source_links" name="show_source_links" value="1" @checked(old('show_source_links', $settings->show_source_links))>
                        <label class="form-check-label fw-semibold" for="show_source_links">Add links to the matching pages in answers</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="lead_capture_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="lead_capture_enabled" name="lead_capture_enabled" value="1" @checked(old('lead_capture_enabled', $settings->lead_capture_enabled)) aria-describedby="lead_help">
                        <label class="form-check-label fw-semibold" for="lead_capture_enabled">Offer the enquiry form in the chat</label>
                    </div>
                    <div class="form-help" id="lead_help">Shown only when a visitor wants to book or be contacted. Enquiries arrive under Inquiries.</div>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel id="ai-limits" title="Limits and logging" icon="bi-shield-check" class="mb-4" description="Protect your AI credit from misuse without getting in the way of real visitors.">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="rate_limit_per_minute">Messages per minute <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('rate_limit_per_minute')]) id="rate_limit_per_minute" name="rate_limit_per_minute" type="number" min="1" max="120" value="{{ old('rate_limit_per_minute', $settings->rate_limit_per_minute) }}" required>
                    <div class="form-help">Per visitor.</div>
                    <x-admin.error name="rate_limit_per_minute" />
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="max_message_length">Longest visitor message <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('max_message_length')]) id="max_message_length" name="max_message_length" type="number" min="50" max="4000" value="{{ old('max_message_length', $settings->max_message_length) }}" required>
                    <div class="form-help">In characters.</div>
                    <x-admin.error name="max_message_length" />
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="max_conversation_messages">Messages in one chat <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('max_conversation_messages')]) id="max_conversation_messages" name="max_conversation_messages" type="number" min="4" max="500" value="{{ old('max_conversation_messages', $settings->max_conversation_messages) }}" required>
                    <x-admin.error name="max_conversation_messages" />
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="daily_message_limit">Daily limit per visitor <span class="required-mark" aria-hidden="true">*</span></label>
                    <input @class(['form-control', 'is-invalid' => $errors->has('daily_message_limit')]) id="daily_message_limit" name="daily_message_limit" type="number" min="0" max="100000" value="{{ old('daily_message_limit', $settings->daily_message_limit) }}" required aria-describedby="daily_help">
                    <x-admin.error name="daily_message_limit" />
                </div>

                <div class="col-12">
                    <div class="form-help" id="daily_help">
                        Messages one visitor can send in 24 hours. <strong>0 = no limit.</strong>
                        Raise it if genuine visitors reach it; lower it to protect your OpenAI credit.
                    </div>
                    <div class="alert admin-alert small mt-2 mb-0 {{ $addressReachesApp ? 'alert-light border' : 'alert-warning' }}" role="note">
                        <i class="bi {{ $addressReachesApp ? 'bi-shield-check' : 'bi-exclamation-triangle-fill' }}" aria-hidden="true"></i>
                        <div class="admin-alert-body">
                            @if($settings->daily_message_limit > 0)
                                Each visitor gets <strong>{{ number_format($settings->daily_message_limit) }}</strong> messages a day.
                                One network address is allowed <strong>{{ number_format($settings->daily_message_limit * \App\Support\Ai\AiConfig::SHARED_ADDRESS_MULTIPLIER) }}</strong>
                                ({{ \App\Support\Ai\AiConfig::SHARED_ADDRESS_MULTIPLIER }}×), because mobile networks put many people behind one address — so a busy network does not block everyone on it, but a bot still cannot drain the account.
                            @else
                                There is currently <strong>no daily limit</strong>. Only the per-minute limit protects your OpenAI credit.
                            @endif
                            @unless($addressReachesApp)
                                <div class="mt-1"><strong>Visitor IP addresses are not reaching the site</strong> (your own request arrived from an internal address), so the per-address limit is switched off and only the per-visitor limit applies. Ask your hosting provider to check that the proxy passes the visitor's IP.</div>
                            @endunless
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="log_level">What to record in the server log <span class="required-mark" aria-hidden="true">*</span></label>
                    <select @class(['form-select', 'is-invalid' => $errors->has('log_level')]) id="log_level" name="log_level" required aria-describedby="log_help">
                        @foreach(['none' => 'Nothing', 'errors' => 'Only problems (recommended)', 'all' => 'Everything (for troubleshooting)'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('log_level', $settings->log_level) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-help" id="log_help">API keys are never written to the log at any level.</div>
                    <x-admin.error name="log_level" />
                </div>
            </div>
        </x-admin.panel>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1" aria-hidden="true"></i>Save settings</button>
            <a href="{{ route('admin.ai.test') }}" class="btn btn-outline-secondary">Try it in the test panel</a>
        </div>
    </form>

    {{-- Outside the settings form: forms cannot be nested. --}}
    <form id="ai-reindex" method="POST" action="{{ route('admin.ai.reindex') }}" class="d-none">@csrf</form>
    <form id="ai-check-key" method="POST" action="{{ route('admin.ai.key.check') }}" class="d-none">@csrf</form>
    @if($maskedKey)
        <form id="ai-clear-key" method="POST" action="{{ route('admin.ai.key.clear') }}" class="d-none"
              data-confirm="{{ $keySource === 'environment' ? 'The key set on the server keeps working.' : 'The assistant stops answering until a new key is added.' }}"
              data-confirm-title="Remove the saved API key?" data-confirm-button="Remove key">
            @csrf
            @method('DELETE')
        </form>
    @endif

    @if($stats['top_questions']->isNotEmpty() || $stats['top_packages']->isNotEmpty())
        <div class="row g-3 mt-2" id="ai-insights">
            @if($stats['top_questions']->isNotEmpty())
                <div class="col-lg-7">
                    <x-admin.panel title="Most asked questions" icon="bi-question-circle" description="From the last 200 visitor messages." class="h-100">
                        <ol class="mb-0 small ps-3">
                            @foreach($stats['top_questions'] as $question => $count)
                                <li class="mb-1">{{ Str::limit($question, 110) }} <span class="status-pill status-pill-secondary">{{ $count }}</span></li>
                            @endforeach
                        </ol>
                    </x-admin.panel>
                </div>
            @endif

            @if($stats['top_packages']->isNotEmpty())
                <div class="col-lg-5">
                    <x-admin.panel title="Most requested packages" icon="bi-box-seam" description="Codes visitors named themselves in the last 500 messages — demand, not what the assistant happened to find." class="h-100">
                        <ul class="list-unstyled mb-0 small">
                            @foreach($stats['top_packages'] as $code => $count)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <strong>{{ $code }}</strong>
                                    <span class="status-pill status-pill-info">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-admin.panel>
                </div>
            @endif
        </div>
    @endif

    @push('scripts')
        <script>
            (function () {
                var icon = document.querySelector('[data-ai-icon-select]');
                icon && icon.addEventListener('change', function () {
                    document.querySelector('[data-ai-icon-preview]').className = 'bi ' + icon.value;
                });
                var range = document.querySelector('[data-ai-temperature]');
                range && range.addEventListener('input', function () {
                    document.querySelector('[data-ai-temperature-value]').textContent = Number(range.value).toFixed(2);
                });
                var tokens = document.querySelector('[data-ai-tokens]');
                tokens && tokens.addEventListener('input', function () {
                    document.querySelector('[data-ai-words]').textContent = Math.round((Number(tokens.value) || 0) * 0.75).toLocaleString();
                });
            })();
        </script>
    @endpush
@endsection
