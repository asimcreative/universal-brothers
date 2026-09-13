@extends('layouts.admin')

@section('title', 'AI Assistant')

@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <h1 class="admin-page-title mb-1">AI Assistant</h1>
            <p class="text-muted mb-0">Configure the website chat assistant, its provider and its safety limits.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.ai.test') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-play-circle me-1"></i>Test panel
            </a>
            <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chat-dots me-1"></i>Conversations
            </a>
        </div>
    </div>

    @include('layouts.partials.flash')

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Conversations', 'value' => number_format($stats['conversations']), 'icon' => 'bi-chat-dots'],
            ['label' => 'Messages', 'value' => number_format($stats['messages']), 'icon' => 'bi-chat-text'],
            ['label' => 'Failed replies', 'value' => number_format($stats['failures']), 'icon' => 'bi-exclamation-triangle'],
            ['label' => 'Leads captured', 'value' => number_format($stats['leads']), 'icon' => 'bi-person-check'],
            ['label' => 'Indexed records', 'value' => number_format($stats['indexed']), 'icon' => 'bi-database'],
        ] as $card)
            <div class="col-6 col-lg">
                <div class="admin-card h-100 p-3">
                    <div class="text-muted small"><i class="bi {{ $card['icon'] }} me-1"></i>{{ $card['label'] }}</div>
                    <div class="fs-4 fw-semibold">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.ai.update') }}">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 mb-3">General</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))>
                        <label class="form-check-label" for="is_enabled">Assistant enabled</label>
                    </div>
                    <div class="form-text">Master switch. Off means the assistant is unavailable everywhere.</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="public_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="public_enabled" name="public_enabled" value="1" @checked(old('public_enabled', $settings->public_enabled))>
                        <label class="form-check-label" for="public_enabled">Show on the public website</label>
                    </div>
                    <div class="form-text">Off keeps the assistant available in the admin test panel only.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="assistant_name">Assistant name</label>
                    <input class="form-control" id="assistant_name" name="assistant_name" value="{{ old('assistant_name', $settings->assistant_name) }}" maxlength="80" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="assistant_icon">Icon class</label>
                    <input class="form-control" id="assistant_icon" name="assistant_icon" value="{{ old('assistant_icon', $settings->assistant_icon) }}" maxlength="60" placeholder="bi-stars">
                    <div class="form-text">Any Bootstrap Icons class, e.g. <code>bi-stars</code>, <code>bi-chat-heart</code>.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="welcome_message">Welcome message</label>
                    <textarea class="form-control" id="welcome_message" name="welcome_message" rows="3" maxlength="2000" placeholder="{{ $defaults['welcome'] }}">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                    <div class="form-text">Leave blank to use the default.</div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="fallback_message">Fallback message</label>
                    <textarea class="form-control" id="fallback_message" name="fallback_message" rows="2" maxlength="2000" placeholder="{{ $defaults['fallback'] }}">{{ old('fallback_message', $settings->fallback_message) }}</textarea>
                    <div class="form-text">Shown to the visitor whenever the provider cannot be reached.</div>
                </div>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 mb-1">Provider</h2>
            <p class="text-muted small mb-3">Any OpenAI-compatible provider works — change the base URL and model.</p>

            <div class="alert {{ $keySource === 'missing' ? 'alert-warning' : 'alert-secondary' }} d-flex align-items-start gap-2">
                <i class="bi {{ $keySource === 'missing' ? 'bi-exclamation-triangle' : 'bi-key' }} mt-1"></i>
                <div>
                    @if($keySource === 'environment')
                        <strong>API key is set in the server environment.</strong>
                        <div class="small mb-0">The <code>OPENAI_API_KEY</code> variable is in use and takes priority over any key saved here. To manage the key from this screen instead, remove that variable from the server's <code>.env</code>.</div>
                    @elseif($keySource === 'database')
                        <strong>API key is stored here, encrypted.</strong>
                        <div class="small mb-0">Saved key: <code>{{ $maskedKey }}</code> — the full value is never displayed or sent to the browser.</div>
                    @else
                        <strong>No API key configured.</strong>
                        <div class="small mb-0">The assistant cannot answer anything until a key is set, either in the server's <code>OPENAI_API_KEY</code> variable or in the field below.</div>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="provider">Provider</label>
                    <input class="form-control" id="provider" name="provider" value="{{ old('provider', $settings->provider) }}" maxlength="40" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="base_url">Base URL</label>
                    <input class="form-control" id="base_url" name="base_url" value="{{ old('base_url', $settings->base_url) }}" maxlength="190" placeholder="{{ config('ai.base_url') }}">
                    <div class="form-text">Must be HTTPS (or localhost) — the API key travels on this request.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="model">Model</label>
                    <input class="form-control" id="model" name="model" value="{{ old('model', $settings->model) }}" maxlength="80" placeholder="{{ config('ai.model') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="api_key">API key</label>
                    <input class="form-control" id="api_key" name="api_key" type="password" autocomplete="new-password" maxlength="300" placeholder="{{ $maskedKey ? 'Leave blank to keep the saved key' : 'Paste a key to store it encrypted' }}">
                    <div class="form-text">
                        Leaving this blank never clears the saved key.
                        @if($maskedKey)
                            <button type="submit" form="ai-clear-key" class="btn btn-link btn-sm p-0 align-baseline text-danger">Remove the stored key</button>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="temperature">Temperature</label>
                    <input class="form-control" id="temperature" name="temperature" type="number" step="0.05" min="0" max="2" value="{{ old('temperature', $settings->temperature) }}" required>
                    <div class="form-text">Lower is more factual. 0.3 or below is recommended here.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="max_tokens">Max response tokens</label>
                    <input class="form-control" id="max_tokens" name="max_tokens" type="number" min="64" max="8000" value="{{ old('max_tokens', $settings->max_tokens) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="timeout_seconds">Timeout (seconds)</label>
                    <input class="form-control" id="timeout_seconds" name="timeout_seconds" type="number" min="5" max="120" value="{{ old('timeout_seconds', $settings->timeout_seconds) }}" required>
                </div>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 mb-1">Prompt &amp; tone</h2>
            <p class="text-muted small mb-3">The anti-hallucination, currency, package-variant, link and escalation rules are built in and always applied. These fields add to them.</p>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="brand_tone">Brand tone</label>
                    <textarea class="form-control" id="brand_tone" name="brand_tone" rows="2" maxlength="2000" placeholder="{{ $defaults['tone'] }}">{{ old('brand_tone', $settings->brand_tone) }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label" for="supported_languages">Supported languages</label>
                    <input class="form-control" id="supported_languages" name="supported_languages" value="{{ old('supported_languages', $settings->supported_languages) }}" maxlength="300" placeholder="{{ $defaults['languages'] }}">
                </div>

                <div class="col-12">
                    <label class="form-label" for="system_prompt">Additional instructions</label>
                    <textarea class="form-control" id="system_prompt" name="system_prompt" rows="5" maxlength="6000">{{ old('system_prompt', $settings->system_prompt) }}</textarea>
                    <div class="form-text">Appended to the built-in rules — for example seasonal notes, or a phrase you want used consistently.</div>
                </div>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 mb-1">Knowledge</h2>
            <p class="text-muted small mb-3">
                Last rebuilt:
                <strong>{{ $settings->indexed_at?->diffForHumans() ?? 'never' }}</strong>
                — {{ number_format($settings->indexed_records) }} records.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="use_database_retrieval" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="use_database_retrieval" name="use_database_retrieval" value="1" @checked(old('use_database_retrieval', $settings->use_database_retrieval))>
                        <label class="form-check-label" for="use_database_retrieval">Answer from the project database</label>
                    </div>
                    <div class="form-text text-danger-emphasis">Turning this off removes every factual grounding. Not recommended.</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="use_page_retrieval" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="use_page_retrieval" name="use_page_retrieval" value="1" @checked(old('use_page_retrieval', $settings->use_page_retrieval))>
                        <label class="form-check-label" for="use_page_retrieval">Include website page content</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="show_source_links" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_source_links" name="show_source_links" value="1" @checked(old('show_source_links', $settings->show_source_links))>
                        <label class="form-check-label" for="show_source_links">Show source links in replies</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="lead_capture_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="lead_capture_enabled" name="lead_capture_enabled" value="1" @checked(old('lead_capture_enabled', $settings->lead_capture_enabled))>
                        <label class="form-check-label" for="lead_capture_enabled">Offer the enquiry form</label>
                    </div>
                    <div class="form-text">Shown only when the visitor signals booking or contact intent.</div>
                </div>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 mb-3">Safety &amp; limits</h2>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="rate_limit_per_minute">Messages per minute</label>
                    <input class="form-control" id="rate_limit_per_minute" name="rate_limit_per_minute" type="number" min="1" max="120" value="{{ old('rate_limit_per_minute', $settings->rate_limit_per_minute) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="max_message_length">Max message length</label>
                    <input class="form-control" id="max_message_length" name="max_message_length" type="number" min="50" max="4000" value="{{ old('max_message_length', $settings->max_message_length) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="max_conversation_messages">Max messages per chat</label>
                    <input class="form-control" id="max_conversation_messages" name="max_conversation_messages" type="number" min="4" max="500" value="{{ old('max_conversation_messages', $settings->max_conversation_messages) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="daily_message_limit">Daily limit per visitor</label>
                    <input class="form-control" id="daily_message_limit" name="daily_message_limit" type="number" min="0" max="100000" value="{{ old('daily_message_limit', $settings->daily_message_limit) }}" required>
                    <div class="form-text">0 means no daily cap.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="log_level">Logging</label>
                    <select class="form-select" id="log_level" name="log_level" required>
                        @foreach(['none' => 'Nothing', 'errors' => 'Errors only', 'all' => 'All activity'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('log_level', $settings->log_level) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">API keys are never written to the log at any level.</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Save settings</button>
            <button type="submit" form="ai-reindex" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-repeat me-1"></i>Rebuild knowledge index
            </button>
        </div>
    </form>

    {{-- Kept outside the settings form: nesting forms is invalid HTML and the
         browser silently drops the inner one. --}}
    <form id="ai-reindex" method="POST" action="{{ route('admin.ai.reindex') }}" class="d-none">@csrf</form>

    @if($maskedKey)
        <form id="ai-clear-key" method="POST" action="{{ route('admin.ai.key.clear') }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif

    @if($stats['top_questions']->isNotEmpty() || $stats['top_packages']->isNotEmpty())
        <div class="row g-3 mt-1">
            @if($stats['top_questions']->isNotEmpty())
                <div class="col-lg-7">
                    <div class="admin-card p-4 h-100">
                        <h2 class="h5 mb-3">Most asked questions <small class="text-muted fw-normal">(last 200)</small></h2>
                        <ol class="mb-0 small">
                            @foreach($stats['top_questions'] as $question => $count)
                                <li class="mb-1">{{ Str::limit($question, 110) }} <span class="badge bg-secondary">{{ $count }}</span></li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif

            @if($stats['top_packages']->isNotEmpty())
                <div class="col-lg-5">
                    <div class="admin-card p-4 h-100">
                        <h2 class="h5 mb-1">Most requested packages</h2>
                        <p class="text-muted small mb-3">Codes visitors named themselves, in the last 500 messages — demand, not what the assistant happened to retrieve.</p>
                        <ul class="list-unstyled mb-0 small">
                            @foreach($stats['top_packages'] as $code => $count)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <strong>{{ $code }}</strong>
                                    <span class="badge bg-primary">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection
