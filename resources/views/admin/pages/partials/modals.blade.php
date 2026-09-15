@push('modals')
    {{-- Section library --}}
    <div class="modal fade" id="pbLibraryModal" tabindex="-1" aria-labelledby="pbLibraryTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="pbLibraryTitle">Add a section</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <nav class="admin-tabs" role="tablist" aria-label="Kinds of section">
                    <a href="#pb-lib-new" role="tab" id="pb-lib-tab-new" aria-controls="pb-lib-new" aria-selected="true" class="active" data-library-tab><i class="bi bi-grid" aria-hidden="true"></i>New section</a>
                    <a href="#pb-lib-saved" role="tab" id="pb-lib-tab-saved" aria-controls="pb-lib-saved" aria-selected="false" tabindex="-1" data-library-tab><i class="bi bi-bookmark-star" aria-hidden="true"></i>Saved sections <span class="admin-tab-count">{{ $savedBlocks->count() }}</span></a>
                </nav>
                <div class="modal-body">
                    <section id="pb-lib-new" role="tabpanel" aria-labelledby="pb-lib-tab-new" data-library-panel>
                        <div class="admin-toolbar-search mb-3">
                            <label for="pb-lib-search" class="visually-hidden">Search sections</label>
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="pb-lib-search" class="form-control" placeholder="Search, e.g. photo, video, form, packages" data-library-search>
                        </div>
                        @foreach($groups as $groupKey => $group)
                            <div class="pb-library-group" data-library-group>
                                <h3 class="pb-library-heading">{{ $group['label'] }}</h3>
                                <div class="pb-library-grid">
                                    @foreach($group['blocks'] as $type => $block)
                                        <button type="button" class="pb-library-card" data-add-block="{{ $type }}" data-search="{{ strtolower($block['name'].' '.$block['description'].' '.$group['label']) }}">
                                            <span class="pb-library-icon" aria-hidden="true"><i class="bi {{ $block['icon'] }}"></i></span>
                                            <span class="pb-library-text">
                                                <strong>{{ $block['name'] }}</strong>
                                                <small>{{ $block['description'] }}</small>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <p class="text-muted small" data-library-none hidden>No section matches your search.</p>
                    </section>

                    <section id="pb-lib-saved" role="tabpanel" aria-labelledby="pb-lib-tab-saved" data-library-panel hidden>
                        <div class="alert alert-light border admin-alert small">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <div class="admin-alert-body">
                                <strong>Insert a copy</strong> (recommended): the section is copied into this page, and you can change it here without affecting any other page.
                                @if($canLink)
                                    <br><strong>Insert linked</strong>: the page always shows the saved section's current content. Editing the saved section changes every page that links to it — including live pages.
                                @endif
                            </div>
                        </div>
                        @forelse($savedBlocks as $block)
                            <div class="pb-saved-row">
                                <span class="pb-library-icon" aria-hidden="true"><i class="bi {{ \App\Support\PageBuilder\BlockRegistry::find($block->type)['icon'] ?? 'bi-bookmark' }}"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <strong class="d-block">{{ $block->name }}</strong>
                                    <small class="text-muted">{{ $block->typeName() }} · {{ \App\Models\ContentBlock::CATEGORIES[$block->category] ?? $block->category }}@if($block->description) · {{ $block->description }}@endif</small>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.content-blocks.preview', $block) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Preview<span class="visually-hidden"> {{ $block->name }} (opens in a new tab)</span></a>
                                    <button type="button" class="btn btn-sm btn-primary" data-insert-block="{{ $block->id }}" data-mode="copy">Insert a copy<span class="visually-hidden"> of {{ $block->name }}</span></button>
                                    @if($canLink)
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-insert-block="{{ $block->id }}" data-mode="linked">Insert linked<span class="visually-hidden"> {{ $block->name }}</span></button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <x-admin.empty-state icon="bi-bookmark-star" title="No saved sections yet.">
                                Open any section's <strong>⋮</strong> menu and choose “Save for reuse on other pages”.
                            </x-admin.empty-state>
                        @endforelse
                    </section>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview --}}
    <div class="modal fade pb-preview-modal" id="pbPreviewModal" tabindex="-1" aria-labelledby="pbPreviewTitle" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header flex-wrap gap-2">
                    <h2 class="modal-title fs-5 me-auto" id="pbPreviewTitle">Preview of your saved draft</h2>
                    <div class="btn-group" role="group" aria-label="Screen size">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" data-preview-width="100%" aria-pressed="true"><i class="bi bi-display me-1" aria-hidden="true"></i>Desktop</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-preview-width="820px" aria-pressed="false"><i class="bi bi-tablet me-1" aria-hidden="true"></i>Tablet</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-preview-width="390px" aria-pressed="false"><i class="bi bi-phone me-1" aria-hidden="true"></i>Phone</button>
                    </div>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Open in a new tab</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close preview"></button>
                </div>
                <div class="modal-body pb-preview-body">
                    <p class="alert alert-warning admin-alert small mb-2" data-preview-unsaved hidden>
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        <span class="admin-alert-body">You have changes that are not saved yet, so they are not in this preview. Use “Save &amp; preview” to include them.</span>
                    </p>
                    <div class="pb-preview-frame-wrap">
                        <iframe title="Page preview" class="pb-preview-frame" data-preview-frame></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Save a section for reuse --}}
    <div class="modal fade" id="pbSaveBlockModal" tabindex="-1" aria-labelledby="pbSaveBlockTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" data-save-block-form novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="pbSaveBlockTitle">Save this section for reuse</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">The section is kept under <strong>Saved Sections</strong>, ready to insert into any page. Your page draft is saved at the same time.</p>
                    <div class="mb-3">
                        <label for="pb-block-name" class="form-label">Name <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="pb-block-name" class="form-control" maxlength="120" required placeholder="e.g. Hajj call to action" aria-describedby="pb-block-name-error">
                        <p class="small text-danger mb-0 mt-1" id="pb-block-name-error" role="alert" hidden>Give the saved section a name so you can find it later.</p>
                    </div>
                    <div class="mb-3">
                        <label for="pb-block-description" class="form-label">Description (optional)</label>
                        <input type="text" id="pb-block-description" class="form-control" maxlength="500" placeholder="Where it is meant to be used">
                    </div>
                    <div>
                        <label for="pb-block-category" class="form-label">Group</label>
                        <select id="pb-block-category" class="form-select">
                            @foreach(\App\Models\ContentBlock::CATEGORIES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Save section</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Schedule --}}
    <div class="modal fade" id="pbScheduleModal" tabindex="-1" aria-labelledby="pbScheduleTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" data-schedule-form novalidate>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="pbScheduleTitle">Schedule this page</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">The page is checked now and appears on the website automatically at the time you choose. {{ $page->isLive() ? 'Until then, the page is taken off the website.' : '' }}</p>
                    <label for="pb-schedule-at" class="form-label">Date and time <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="datetime-local" id="pb-schedule-at" class="form-control" required aria-describedby="pb-schedule-help pb-schedule-error">
                    <div class="form-help" id="pb-schedule-help">Use the time on your own computer's clock.</div>
                    <p class="small text-danger mb-0 mt-1" id="pb-schedule-error" role="alert" hidden>Choose a date and time in the future.</p>
                    <x-admin.error name="publish_at" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-clock me-1" aria-hidden="true"></i>Schedule</button>
                </div>
            </form>
        </div>
    </div>
@endpush
