@extends('layouts.admin')

@php
    use App\Support\Packages\PackageCompleteness;

    $isTemplate = $mode === 'template';
    $isNew = $isTemplate ? ! $template->exists : ! $package->exists;
    $isPublished = ! $isTemplate && $package->exists && $package->isPublished();

    // Hotel options (Package A / B / C), keyed by their row index so the
    // option groups in the Prices and Hotels steps can follow a renamed letter.
    $variantRows = $state['variants'] ?? [];
    $optionsByCode = collect($variantRows)
        ->filter(fn ($row) => filled($row['code'] ?? null))
        ->mapWithKeys(fn ($row, $i) => [strtoupper(trim($row['code'])) => ['uid' => "opt-{$i}", 'label' => $row['label'] ?? '']]);
    $optionLabels = $optionsByCode->map(fn ($o) => $o['label'])->all();
    $hasOptions = $optionsByCode->isNotEmpty();
    $codes = $optionsByCode->keys();
    $hasOptionB = $codes->count() >= 2;
    $optionALabel = $hasOptionB ? 'Option '.$codes[0] : 'Option A';
    $optionBLabel = $hasOptionB ? 'Option '.$codes[1] : 'Option B';

    $errorsByStep = collect($errors->getMessages())
        ->groupBy(fn ($messages, $key) => PackageCompleteness::stepForField($key), true)
        ->map(fn ($group) => collect($group)->flatten()->values());

    if ($errorsByStep->isNotEmpty()) {
        $initialStep = collect(array_keys($steps))->first(fn ($step) => $errorsByStep->has($step)) ?? $initialStep;
    }

    $title = $isTemplate
        ? ($isNew ? 'New Package Template' : 'Edit Template: '.$template->name)
        : ($isNew ? 'Add Hajj Package' : 'Edit: '.($package->code ? $package->code.' — ' : '').$package->name);

    $formAction = $isTemplate
        ? ($isNew ? route('admin.package-templates.store') : route('admin.package-templates.update', $template))
        : ($isNew ? route('admin.hajj-packages.store') : route('admin.hajj-packages.update', $package));

    $storageKey = 'ub-builder:'.($isTemplate ? 'template-'.($template->id ?? 'new') : 'package-'.($package->id ?? 'new'));
    $baseVersion = $isTemplate ? optional($template->updated_at)->timestamp : optional($package->updated_at)->timestamp;
@endphp

@section('title', $title)
@section('own_error_summary', '1')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    @if($isTemplate)
        <a href="{{ route('admin.package-templates.index') }}">Package Templates</a> /
    @else
        <a href="{{ route('admin.hajj-packages.index') }}">Hajj Packages</a> /
    @endif
    <span>{{ $isNew ? 'New' : 'Edit' }}</span>
@endsection

@section('subtitle')
    @if($isTemplate)
        A template is a starting point. Packages created from it get their own copy.
    @elseif($isNew)
        Work through the steps. You can save a draft at any time and come back later.
    @else
        @if($isPublished)
            <span class="status-pill status-pill-success">Live on the website</span>
        @elseif($package->isArchived())
            <span class="status-pill status-pill-secondary">Archived</span>
        @else
            <span class="status-pill status-pill-warning">Draft — not visible on the website</span>
        @endif
        <span class="ms-1">Last saved {{ $package->updated_at?->diffForHumans() }}</span>
    @endif
@endsection

@section('actions')
    @if(! $isTemplate && ! $isNew)
        <a href="{{ $previewUrl }}" class="btn btn-outline-primary preview-open" target="_blank" rel="noopener"><i class="bi bi-eye me-1" aria-hidden="true"></i>Preview</a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">More</button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                @if($isPublished)
                    <li><a class="dropdown-item" href="{{ route('packages.show', ['category' => 'hajj', 'package' => $package->slug]) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>View on website</a></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('admin.hajj-packages.duplicate', $package) }}" data-confirm="A new draft copy of this package will be created. The original is not changed." data-confirm-title="Duplicate this package?" data-confirm-button="Create copy" data-confirm-tone="primary">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button>
                    </form>
                </li>
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#saveTemplateModal"><i class="bi bi-files me-2" aria-hidden="true"></i>Save as template</button></li>
                @if(! $isPublished && count($library['packageTemplates']))
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#applyTemplateModal"><i class="bi bi-magic me-2" aria-hidden="true"></i>Apply a template…</button></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                @if($isPublished)
                    <li>
                        <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'unpublish']) }}" data-confirm="The package will disappear from the website until it is published again." data-confirm-title="Move to draft?" data-confirm-button="Move to draft">
                            @csrf @method('PATCH')
                            <button type="submit" class="dropdown-item"><i class="bi bi-eye-slash me-2" aria-hidden="true"></i>Move to draft (unpublish)</button>
                        </form>
                    </li>
                @endif
                @if(! $package->isArchived())
                    <li>
                        <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'archive']) }}" data-confirm="Archived packages are removed from the website and hidden from the package list. You can restore it later." data-confirm-title="Archive this package?" data-confirm-button="Archive">
                            @csrf @method('PATCH')
                            <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive</button>
                        </form>
                    </li>
                @endif
            </ul>
        </div>
    @endif
@endsection

@section('content')
    {{-- Unsaved work kept in this browser, offered back after a crash or a closed tab. --}}
    <div class="alert alert-info admin-alert restore-banner" role="status" data-restore-banner hidden>
        <i class="bi bi-clock-history" aria-hidden="true"></i>
        <div class="admin-alert-body">You have changes from <strong data-restore-time></strong> that were not saved.</div>
        <button type="button" class="btn btn-sm btn-primary" data-restore-accept>Restore them</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-restore-discard>Discard</button>
    </div>

    @if($resumed ?? false)
        <div class="alert alert-info admin-alert" role="status">
            <i class="bi bi-bookmark-star" aria-hidden="true"></i>
            <div class="admin-alert-body">Opened on <strong>{{ $steps[$initialStep] }}</strong>, where you saved last time.</div>
            <a href="{{ route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'basics']) }}" class="btn btn-sm btn-outline-secondary">Start from step 1</a>
        </div>
    @endif

    @if($fromTemplate)
        <div class="alert alert-info admin-alert" role="status">
            <i class="bi bi-files" aria-hidden="true"></i>
            <div class="admin-alert-body">Started from the template <strong>{{ $fromTemplate->name }}</strong>. Add the title, code and dates for this package.</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger admin-alert builder-error-summary" role="alert" tabindex="-1" data-error-summary>
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div class="admin-alert-body">
                <strong>{{ $errors->has('publish.*') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'publish.')) ? 'The package cannot be published yet.' : 'Some details need attention before saving.' }}</strong>
                <span class="d-block small">Nothing was saved. Fix the items below — each link opens the right step.</span>
                <ul>
                    @foreach($errorsByStep as $step => $messages)
                        <li>
                            <a href="#step-{{ $step }}" data-step-link="{{ $step }}">{{ $steps[$step] ?? PackageCompleteness::STEPS[$step] ?? ucfirst($step) }}</a>:
                            {{ $messages->unique()->implode(' ') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="package-builder" method="POST" action="{{ $formAction }}" enctype="multipart/form-data" novalidate
          data-mode="{{ $mode }}"
          data-storage-key="{{ $storageKey }}"
          data-base-version="{{ $baseVersion ?? 'new' }}"
          data-has-errors="{{ $errors->any() ? '1' : '0' }}"
          data-initial-step="{{ $initialStep }}"
          data-hotel-store-url="{{ route('admin.library.store', 'hotels') }}"
          data-journey-store-url="{{ route('admin.library.store', 'journey-templates') }}"
          @unless($isTemplate)
              data-assess-url="{{ $isNew ? route('admin.hajj-packages.assess') : route('admin.hajj-packages.assess-existing', $package) }}"
              data-reviewed-saved="{{ collect($review->checklist())->firstWhere('key', 'review')['done'] ? '1' : '0' }}"
          @endunless>
        @csrf
        @if(! $isNew) @method('PUT') @endif
        <input type="hidden" name="_step" value="{{ $initialStep }}" data-current-step>
        @unless($isTemplate)<input type="hidden" name="_reviewed" value="0" data-reviewed>@endunless

        <div class="builder">
            <aside class="builder-steps" aria-label="Package steps">
                {{-- Phones: one line saying where you are; the full list opens from it. --}}
                <button type="button" class="builder-steps-toggle" data-steps-toggle aria-expanded="false" aria-controls="builder-step-list">
                    <span class="step-number" data-steps-toggle-number>{{ array_search($initialStep, array_keys($steps)) + 1 }}</span>
                    <span class="builder-steps-toggle-text">
                        <small>Step <span data-steps-toggle-index>{{ array_search($initialStep, array_keys($steps)) + 1 }}</span> of {{ count($steps) }}</small>
                        <strong data-steps-toggle-label>{{ $steps[$initialStep] }}</strong>
                    </span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>

                <div class="builder-progress">
                    @if($review)
                        @php $percent = $review->percent(); @endphp
                        <span data-progress-text>{{ $percent }}% complete</span>
                        <div class="progress" role="progressbar" aria-label="Package completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" data-progress-meter>
                            <div class="progress-bar" data-progress-bar style="width: {{ $percent }}%"></div>
                        </div>
                    @else
                        @php $filledSteps = collect($stepStatus)->only(array_keys($steps))->filter()->count(); @endphp
                        <span data-progress-text>{{ $filledSteps }} of {{ count($steps) }} steps filled in</span>
                        <div class="progress" role="progressbar" aria-label="Steps filled in" aria-valuemin="0" aria-valuemax="{{ count($steps) }}" aria-valuenow="{{ $filledSteps }}" data-progress-meter>
                            <div class="progress-bar" data-progress-bar style="width: {{ round($filledSteps / count($steps) * 100) }}%"></div>
                        </div>
                    @endif
                </div>
                <ol id="builder-step-list">
                    @foreach($steps as $key => $label)
                        @php $hasError = $errorsByStep->has($key); @endphp
                        <li>
                            <button type="button" data-step-button="{{ $key }}" aria-controls="step-{{ $key }}"
                                    class="{{ $key === $initialStep ? 'active' : '' }} {{ ($stepStatus[$key] ?? false) ? 'is-done' : '' }} {{ $hasError ? 'has-error' : '' }}"
                                    @if($key === $initialStep) aria-current="step" @endif>
                                <span class="step-number">{{ $loop->iteration }}</span>
                                <span class="step-label">{{ $label }}</span>
                                <span class="step-state" aria-hidden="true">
                                    @if($hasError)<i class="bi bi-exclamation-circle-fill text-danger"></i>@elseif($stepStatus[$key] ?? false)<i class="bi bi-check-circle-fill text-success"></i>@endif
                                </span>
                                @if($hasError)<span class="visually-hidden">(needs attention)</span>@endif
                            </button>
                        </li>
                    @endforeach
                </ol>

                @if($review)
                    @php $checklist = $review->checklist(); @endphp
                    <details class="builder-checklist" data-checklist-panel>
                        <summary>Checklist <span class="badge text-bg-light" data-checklist-count>{{ collect($checklist)->where('done', true)->count() }} of {{ count($checklist) }}</span></summary>
                        <ul data-checklist>
                            @foreach($checklist as $item)
                                <li class="{{ $item['done'] ? 'is-done' : '' }}">
                                    <a href="#step-{{ $item['step'] }}" data-step-link="{{ $item['step'] }}">
                                        <i class="bi {{ $item['done'] ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>{{ $item['label'] }}<span class="visually-hidden">: {{ $item['done'] ? 'done' : 'not done' }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </aside>

            <div class="builder-main">
                @foreach(array_keys($steps) as $key)
                    <section class="builder-panel" id="step-{{ $key }}" data-step="{{ $key }}" @if($key !== $initialStep) hidden @endif aria-labelledby="step-title-{{ $key }}" tabindex="-1">
                        @include('admin.hajj-packages.steps.'.$key)

                        <div class="d-flex justify-content-between gap-2 mt-2">
                            @if(! $loop->first)
                                <button type="button" class="btn btn-outline-secondary" data-step-go="prev"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Previous</button>
                            @else
                                <span></span>
                            @endif
                            @if(! $loop->last)
                                <button type="button" class="btn btn-outline-primary" data-step-go="next">Next: {{ array_values($steps)[$loop->index + 1] }}<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <div class="builder-actionbar">
            <div class="builder-actionbar-state" aria-live="polite">
                @if($isTemplate)
                    <span class="status-pill status-pill-info">Template</span>
                @elseif($isPublished)
                    <span class="status-pill status-pill-success">Live</span>
                @else
                    <span class="status-pill status-pill-warning">Draft</span>
                @endif
                <span class="dirty-flag" data-dirty-flag hidden><i class="bi bi-dot" aria-hidden="true"></i>Unsaved changes</span>
                <span data-saved-flag>{{ $isNew ? 'Not saved yet' : 'All changes saved' }}</span>
            </div>

            @if($isTemplate)
                <a href="{{ route('admin.package-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-outline-primary" name="_intent" value="continue" aria-label="Save & continue"><span class="d-sm-none">Continue</span><span class="d-none d-sm-inline">Save &amp; continue</span></button>
                <button type="submit" class="btn btn-primary" name="_intent" value="save"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save template</button>
            @else
                <a href="{{ route('admin.hajj-packages.index') }}" class="btn btn-outline-secondary d-none d-md-inline-block">Cancel</a>
                @if($isPublished)
                    <button type="submit" class="btn btn-outline-primary" name="_intent" value="preview" aria-label="Save & preview"><i class="bi bi-eye me-1" aria-hidden="true"></i><span class="d-sm-none">Preview</span><span class="d-none d-sm-inline">Save &amp; preview</span></button>
                    <button type="submit" class="btn btn-outline-primary" name="_intent" value="continue" aria-label="Save & continue"><span class="d-sm-none">Continue</span><span class="d-none d-sm-inline">Save &amp; continue</span></button>
                    <button type="submit" class="btn btn-primary" name="_intent" value="save" aria-label="Save changes"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save<span class="d-none d-sm-inline"> changes</span></button>
                @else
                    <button type="submit" class="btn btn-outline-secondary" name="_intent" value="draft" aria-label="Save draft"><i class="bi bi-save me-1" aria-hidden="true"></i><span class="d-sm-none">Draft</span><span class="d-none d-sm-inline">Save draft</span></button>
                    <button type="submit" class="btn btn-outline-primary" name="_intent" value="preview" aria-label="Save & preview"><i class="bi bi-eye me-1" aria-hidden="true"></i><span class="d-sm-none">Preview</span><span class="d-none d-sm-inline">Save &amp; preview</span></button>
                    <button type="submit" class="btn btn-outline-primary" name="_intent" value="continue" aria-label="Save & continue"><span class="d-sm-none">Continue</span><span class="d-none d-sm-inline">Save &amp; continue</span></button>
                    <button type="submit" class="btn btn-primary" name="_intent" value="publish"
                            data-confirm-publish="Once published, this package appears on the website straight away."><i class="bi bi-globe2 me-1" aria-hidden="true"></i>Publish</button>
                @endif
            @endif
        </div>

        <datalist id="dl-journey-places">
            @foreach(['Makkah', 'Madinah', 'Mina', 'Arafat', 'Muzdalifah', 'Aziziya', 'Jeddah', 'To Makkah', 'To Madinah', 'To Mina', 'To Arafat', 'Departure'] as $place)
                <option value="{{ $place }}">
            @endforeach
        </datalist>
        <datalist id="dl-journey-stays" data-stays-list></datalist>
    </form>

    {{-- Row templates. Rendered with the real partials, so a new row is
         identical to a saved one. --}}
    <template id="tpl-variants">@include('admin.hajj-packages.rows.option', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-room_options">@include('admin.hajj-packages.rows.room', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-accommodations">@include('admin.hajj-packages.rows.hotel', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-aziziya_room_options">@include('admin.hajj-packages.rows.aziziya-room', ['i' => '__INDEX__', 'row' => [], 'options' => $optionLabels])</template>
    <template id="tpl-aziziya_services">@include('admin.hajj-packages.rows.aziziya-service', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-itinerary">@include('admin.hajj-packages.rows.day', ['i' => '__INDEX__', 'row' => [], 'hasOptionB' => $hasOptionB, 'optionALabel' => $optionALabel, 'optionBLabel' => $optionBLabel])</template>
    <template id="tpl-transportation">@include('admin.hajj-packages.rows.transport', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-inclusions">@include('admin.hajj-packages.rows.service', ['i' => '__INDEX__', 'row' => [], 'list' => 'inclusions'])</template>
    <template id="tpl-exclusions">@include('admin.hajj-packages.rows.service', ['i' => '__INDEX__', 'row' => [], 'list' => 'exclusions'])</template>
    <template id="tpl-upgrades">@include('admin.hajj-packages.rows.upgrade', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-notes">@include('admin.hajj-packages.rows.note', ['i' => '__INDEX__', 'row' => []])</template>
    <template id="tpl-media">@include('admin.hajj-packages.rows.media', ['i' => '__INDEX__', 'row' => []])</template>
    @foreach(['room_options' => 'Room prices', 'accommodations' => 'Hotels'] as $groupedRows => $groupNoun)
        <template id="tpl-group-{{ $groupedRows }}">
            @include('admin.hajj-packages.partials.option-group', ['rowsName' => $groupedRows, 'code' => '__CODE__', 'uid' => '__UID__', 'label' => '__LABEL__', 'rows' => [], 'noun' => $groupNoun])
        </template>
    @endforeach

    <script type="application/json" id="builder-library">@json($library)</script>

    @include('admin.hajj-packages.partials.modals')
@endsection
