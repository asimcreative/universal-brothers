@extends('layouts.admin')

@php
    $ubStatus = $page->displayStatus();
    $ubChanges = $page->hasUnpublishedChanges();
    $ubPublicUrl = url('/'.$page->slug);
    $ubWarnings = session('builder_warnings', []);
@endphp

@section('title', $document->get('title') ?: 'Untitled page')
@section('guide', 'pages-builder')
@section('own_error_summary', '1')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.pages.index') }}">Pages</a> / <span>Edit</span>
@endsection

@section('actions')
    <x-admin.status-badge :status="$ubStatus" />
    @if($ubChanges && $ubStatus === 'published')<x-admin.status-badge status="changes" />@endif
    @if($page->isLive())
        <a href="{{ $ubPublicUrl }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>View live page<span class="visually-hidden"> (opens in a new tab)</span></a>
    @endif
    <div class="dropdown">
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots me-1" aria-hidden="true"></i>More</button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><button type="submit" form="page-duplicate-form" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate page</button></li>
            @if($page->is_active)
                <li><button type="submit" form="page-unpublish-form" class="dropdown-item"><i class="bi bi-eye-slash me-2" aria-hidden="true"></i>Unpublish</button></li>
            @endif
            @if(is_array($page->draft) && ($page->is_active || $page->usesSections()))
                <li><button type="submit" form="page-discard-form" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Discard unpublished changes</button></li>
            @endif
            @if($page->status !== 'archived')
                <li><button type="submit" form="page-archive-form" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive page</button></li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li><button type="submit" form="page-delete-form" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete page</button></li>
        </ul>
    </div>
@endsection

@section('content')
    <x-admin.validation-summary title="These need fixing before the page can be published" :links="$sectionLinks" :warnings="$ubWarnings" />

    @if($isLegacy)
        <div class="alert alert-info admin-alert" role="note">
            <i class="bi bi-magic" aria-hidden="true"></i>
            <div class="admin-alert-body">
                <strong>This page now uses sections.</strong> We turned its current content into sections below, so you can edit it the new way.
                The live page does not change until you publish.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" id="page-builder-form" novalidate
          data-page-builder
          data-section-url="{{ route('admin.pages.section-form') }}"
          data-preview-url="{{ $previewUrl }}"
          data-open-preview="{{ session('open_preview') ? '1' : '0' }}"
          data-public-url="{{ url('/') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="intent" value="save" data-intent>
        <input type="hidden" name="block_name" data-block-field="name">
        <input type="hidden" name="block_description" data-block-field="description">
        <input type="hidden" name="block_category" data-block-field="category">
        <input type="hidden" name="publish_at" data-schedule-field>

        <nav class="admin-tabs pb-tabs card mb-3" role="tablist" aria-label="Page editor">
            <a href="#pb-panel-sections" role="tab" id="pb-tab-sections" aria-controls="pb-panel-sections" aria-selected="true" class="active" data-builder-tab>
                <i class="bi bi-grid-1x2" aria-hidden="true"></i>Sections <span class="admin-tab-count" data-section-count>{{ count($sections) }}</span>
            </a>
            <a href="#pb-panel-details" role="tab" id="pb-tab-details" aria-controls="pb-panel-details" aria-selected="false" tabindex="-1" data-builder-tab @class(['text-danger' => $errors->hasAny(['title', 'slug', 'featured_image.path'])])>
                <i class="bi bi-card-heading" aria-hidden="true"></i>Title &amp; address
            </a>
            <a href="#pb-panel-seo" role="tab" id="pb-tab-seo" aria-controls="pb-panel-seo" aria-selected="false" tabindex="-1" data-builder-tab>
                <i class="bi bi-search" aria-hidden="true"></i>Search &amp; sharing
            </a>
            <a href="#pb-panel-history" role="tab" id="pb-tab-history" aria-controls="pb-panel-history" aria-selected="false" tabindex="-1" data-builder-tab>
                <i class="bi bi-clock-history" aria-hidden="true"></i>History <span class="admin-tab-count">{{ $revisions->count() }}</span>
            </a>
        </nav>

        {{-- Sections --}}
        <section id="pb-panel-sections" role="tabpanel" aria-labelledby="pb-tab-sections" data-builder-panel>
            <div class="pb-toolbar">
                <p class="small text-muted mb-0" id="builder-move-help">
                    Drag a section by its <i class="bi bi-grip-vertical" aria-hidden="true"></i><span class="visually-hidden">handle</span> to move it, use the arrow buttons, or focus the handle and press the up and down arrow keys.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-builder-expand="open"><i class="bi bi-arrows-expand me-1" aria-hidden="true"></i>Open all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-builder-expand="close"><i class="bi bi-arrows-collapse me-1" aria-hidden="true"></i>Close all</button>
                </div>
            </div>

            <div class="pb-sections" id="builder-sections" data-sections tabindex="-1" aria-label="Page sections in order">
                @foreach($sections as $i => $section)
                    @include('admin.pages.partials.section', ['section' => $section, 'position' => $i + 1, 'open' => count($sections) === 1, 'savedBlocks' => $savedBlocks->keyBy('id')])
                @endforeach
            </div>

            <div class="pb-empty" data-sections-empty @if(count($sections)) hidden @endif>
                <x-admin.empty-state icon="bi-grid-1x2" title="This page has no sections yet.">
                    Add a banner, some text, photos or a contact form to start building the page.
                </x-admin.empty-state>
            </div>

            <button type="button" class="pb-add-section" data-open-library>
                <i class="bi bi-plus-circle" aria-hidden="true"></i><span>Add a section</span>
            </button>
            <x-admin.error name="sections" />
        </section>

        {{-- Title and address --}}
        <section id="pb-panel-details" role="tabpanel" aria-labelledby="pb-tab-details" data-builder-panel>
            <x-admin.panel title="Title and address" icon="bi-card-heading" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="page-title" class="form-label">Page title <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="page-title" name="title" value="{{ $document->get('title') }}" maxlength="255" required
                               @class(['form-control', 'is-invalid' => $errors->has('title')]) aria-describedby="page-title-help" data-page-title>
                        <div class="form-help" id="page-title-help">Shown in the page banner and the browser tab.</div>
                        <x-admin.error name="title" />
                    </div>
                    <div class="col-md-5">
                        <label for="page-slug" class="form-label">Page address <span class="required-mark" aria-hidden="true">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">/</span>
                            <input type="text" id="page-slug" name="slug" value="{{ $document->get('slug') }}" maxlength="100" required
                                   @class(['form-control', 'is-invalid' => $errors->has('slug')]) aria-describedby="page-slug-help" data-page-slug @if($page->isLive()) data-live-slug="{{ $page->slug }}" @endif>
                        </div>
                        <div class="form-help" id="page-slug-help">
                            Small letters, numbers and dashes. The page opens at <span class="text-nowrap" data-slug-preview>{{ url('/') }}/{{ $document->get('slug') }}</span>.
                            @if($page->isLive())<strong class="d-block" data-slug-warning hidden>Changing the address of a live page breaks links people have saved or shared.</strong>@endif
                        </div>
                        <x-admin.error name="slug" />
                    </div>
                    <div class="col-12">
                        <x-admin.image-picker name="featured_image" label="Page banner image" :value="['path' => $document->get('featured_image'), 'alt' => '']" :alt="false"
                            help="Shown behind the page title when the page does not start with its own banner section, and used when the page is shared. Leave empty for a standard photo of the Haram." />
                    </div>
                </div>
            </x-admin.panel>
        </section>

        {{-- Search and sharing --}}
        <section id="pb-panel-seo" role="tabpanel" aria-labelledby="pb-tab-seo" data-builder-panel>
            <x-admin.panel title="Search engines and sharing" icon="bi-search" id="page-seo" class="mb-4"
                description="How the page appears on Google and when its link is shared. Everything here is optional.">
                <x-admin.seo-fields :values="$document->toArray()" :fallback-title="($document->get('title') ?: 'Page title').' | Universal Brothers'"
                    :fallback-description="\Illuminate\Support\Str::limit(\App\Support\Content\RichText::toPlainText($document->bodyHtml()), 160) ?: 'The start of the page text is used when this is left empty.'"
                    :url="url('/').'/'.$document->get('slug')" />
            </x-admin.panel>
        </section>

        {{-- History --}}
        <section id="pb-panel-history" role="tabpanel" aria-labelledby="pb-tab-history" data-builder-panel>
            <x-admin.panel title="Earlier versions" icon="bi-clock-history" class="mb-4" :flush="true"
                description="A copy is kept each time the page is published, scheduled or unpublished. Restoring a version makes it your draft; the live page changes only when you publish.">
                @if($revisions->isEmpty())
                    <x-admin.empty-state icon="bi-clock-history">No earlier versions yet. One is kept the first time you publish.</x-admin.empty-state>
                @else
                    <ul class="activity-list">
                        @foreach($revisions as $revision)
                            <li class="align-items-center">
                                <span class="activity-dot" aria-hidden="true"></span>
                                <div class="flex-grow-1">
                                    <strong>{{ \App\Models\PageRevision::ACTIONS[$revision->action] ?? ucfirst($revision->action) }}</strong>
                                    — {{ $revision->created_at->timezone(config('app.display_timezone'))->format('j M Y, g:i a') }}
                                    @if($revision->author)<span class="text-muted">by {{ $revision->author->name }}</span>@endif
                                    <span class="d-block small text-muted">“{{ $revision->data['title'] ?? $page->title }}”, {{ count($revision->data['sections'] ?? []) }} sections</span>
                                </div>
                                <button type="submit" form="revision-restore-{{ $revision->id }}" class="btn btn-sm btn-outline-secondary">Restore as draft</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </section>

        {{-- Save and publish --}}
        <div class="admin-form-actions pb-actionbar">
            <span class="pb-actionbar-status" data-dirty-status aria-live="polite">
                @if($ubChanges)
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>Draft saved — not yet published
                @elseif($page->isLive())
                    <i class="bi bi-check-circle" aria-hidden="true"></i>Published and up to date
                @else
                    <i class="bi bi-pencil" aria-hidden="true"></i>Draft
                @endif
            </span>
            <div class="pb-actionbar-buttons">
                <button type="button" class="btn btn-outline-secondary" data-builder-undo disabled><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Undo</button>
                <button type="submit" class="btn btn-outline-secondary" data-intent-button="preview"><i class="bi bi-eye me-1" aria-hidden="true"></i>Save &amp; preview</button>
                <button type="submit" class="btn btn-outline-primary" data-intent-button="save"><i class="bi bi-save me-1" aria-hidden="true"></i>Save draft</button>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary" data-intent-button="publish"
                            data-confirm="{{ $page->isLive() ? 'Visitors will see these changes straight away.' : 'The page will appear on the website at /'.$page->slug.'.' }}"
                            data-confirm-title="{{ $page->isLive() ? 'Publish these changes?' : 'Publish this page?' }}" data-confirm-button="Publish" data-confirm-tone="primary">
                        <i class="bi bi-send-check me-1" aria-hidden="true"></i>{{ $page->isLive() ? 'Publish changes' : 'Publish' }}
                    </button>
                    <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">More publishing options</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#pbScheduleModal"><i class="bi bi-clock me-2" aria-hidden="true"></i>Schedule for later…</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </form>

    {{-- Forms that live outside the editor form (forms cannot be nested). --}}
    <form id="page-duplicate-form" method="POST" action="{{ route('admin.pages.duplicate', $page) }}" class="d-none" data-confirm="A copy of this page is created as a draft. Your unsaved changes here are not included — save first if you need them." data-confirm-title="Duplicate this page?" data-confirm-button="Duplicate" data-confirm-tone="primary">@csrf</form>
    <form id="page-unpublish-form" method="POST" action="{{ route('admin.pages.unpublish', $page) }}" class="d-none" data-confirm="Visitors will no longer be able to open /{{ $page->slug }}. The content is kept, so you can publish it again later." data-confirm-title="Unpublish this page?" data-confirm-button="Unpublish" data-confirm-tone="primary">@csrf @method('PATCH')</form>
    <form id="page-discard-form" method="POST" action="{{ route('admin.pages.discard-draft', $page) }}" class="d-none" data-confirm="Everything changed since the page was last published is thrown away. This cannot be undone." data-confirm-title="Discard unpublished changes?" data-confirm-button="Discard changes">@csrf @method('DELETE')</form>
    <form id="page-archive-form" method="POST" action="{{ route('admin.pages.archive', $page) }}" class="d-none" data-confirm="The page is removed from the website and moved to the Archived list. You can restore it at any time." data-confirm-title="Archive this page?" data-confirm-button="Archive" data-confirm-tone="primary">@csrf @method('PATCH')</form>
    <form id="page-delete-form" method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-none" data-confirm="{{ $page->isLive() ? 'This page is live. ' : '' }}The page and all its earlier versions are permanently deleted. This cannot be undone. Archive it instead if you might need it again." data-confirm-title="Delete this page?" data-confirm-button="Delete permanently">@csrf @method('DELETE')</form>
    @foreach($revisions as $revision)
        <form id="revision-restore-{{ $revision->id }}" method="POST" action="{{ route('admin.pages.revisions.restore', [$page, $revision]) }}" class="d-none"
              data-confirm="The version from {{ $revision->created_at->timezone(config('app.display_timezone'))->format('j M Y, g:i a') }} replaces your current draft. The live page does not change until you publish." data-confirm-title="Restore this version as your draft?" data-confirm-button="Restore" data-confirm-tone="primary">@csrf</form>
    @endforeach

    @include('admin.pages.partials.modals')
@endsection
