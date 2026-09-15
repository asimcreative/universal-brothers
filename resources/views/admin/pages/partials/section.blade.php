{{--
    One section card in the page builder.

    $section      ['id', 'type', 'visible', 'data']
    $position     1-based position (0 when added by script; the builder renumbers)
    $open         show the fields (otherwise collapsed)
    $savedBlocks  saved sections keyed by id, for linked sections
--}}
@php
    use App\Support\PageBuilder\BlockRegistry;

    $definition = BlockRegistry::find($section['type']);
    $sid = $section['id'];
    $visible = (bool) ($section['visible'] ?? true);
    $hasErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "sections.{$sid}."));
    $isOpen = ($open ?? false) || $hasErrors;
    $linked = $section['type'] === 'saved_block' ? ($savedBlocks[$section['data']['block_id'] ?? 0] ?? null) : null;
    $summary = $linked ? $linked->name : BlockRegistry::summary($section['type'], $section['data'] ?? []);
@endphp

@if($definition)
<article @class(['pb-section-card', 'is-hidden' => ! $visible, 'has-errors' => $hasErrors, 'is-open' => $isOpen])
         id="section-{{ $sid }}" data-section data-section-id="{{ $sid }}" data-section-type="{{ $section['type'] }}"
         data-section-name="{{ $definition['name'] }}" aria-labelledby="section-{{ $sid }}-name" tabindex="-1">
    <input type="hidden" name="sections[{{ $sid }}][id]" value="{{ $sid }}">
    <input type="hidden" name="sections[{{ $sid }}][type]" value="{{ $section['type'] }}">
    <input type="hidden" name="sections[{{ $sid }}][visible]" value="{{ $visible ? '1' : '0' }}" data-section-visible>

    <header class="pb-section-head">
        <button type="button" class="pb-drag" data-drag-handle
                aria-label="Move {{ $definition['name'] }}" aria-describedby="builder-move-help">
            <i class="bi bi-grip-vertical" aria-hidden="true"></i>
        </button>
        <span class="pb-section-icon" aria-hidden="true"><i class="bi {{ $definition['icon'] }}"></i></span>

        <button type="button" class="pb-section-toggle" data-section-toggle aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="section-{{ $sid }}-body">
            <span class="pb-section-position" data-section-position>{{ $position ? 'Section '.$position : 'New section' }}</span>
            <span class="pb-section-name" id="section-{{ $sid }}-name">{{ $definition['name'] }}</span>
            <span class="pb-section-summary" data-section-summary>{{ $summary }}</span>
        </button>

        <span class="pb-section-badges">
            <span class="status-pill status-pill-secondary" data-hidden-badge @if($visible) hidden @endif><i class="bi bi-eye-slash" aria-hidden="true"></i>Hidden</span>
            @if($hasErrors)<span class="status-pill status-pill-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Needs attention</span>@endif
            @if($section['type'] === 'saved_block')<span class="status-pill status-pill-info"><i class="bi bi-link-45deg" aria-hidden="true"></i>Linked</span>@endif
        </span>

        <div class="pb-section-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-section-action="up" aria-label="Move {{ $definition['name'] }} up" title="Move up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-section-action="down" aria-label="Move {{ $definition['name'] }} down" title="Move down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-section-action="visibility"
                    aria-label="{{ $visible ? 'Hide' : 'Show' }} {{ $definition['name'] }}" aria-pressed="{{ $visible ? 'false' : 'true' }}" title="{{ $visible ? 'Hide from visitors' : 'Show to visitors' }}">
                <i class="bi {{ $visible ? 'bi-eye' : 'bi-eye-slash' }}" aria-hidden="true"></i>
            </button>
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $definition['name'] }}">
                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><button type="button" class="dropdown-item" data-section-action="duplicate"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button></li>
                    <li><button type="button" class="dropdown-item" data-section-action="add-below"><i class="bi bi-plus-square me-2" aria-hidden="true"></i>Add a section below</button></li>
                    @if($section['type'] !== 'saved_block')
                        <li><button type="button" class="dropdown-item" data-section-action="save-block"><i class="bi bi-bookmark-plus me-2" aria-hidden="true"></i>Save for reuse on other pages</button></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item text-danger" data-section-action="delete"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete section</button></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="pb-section-body" id="section-{{ $sid }}-body" @unless($isOpen) hidden @endunless>
        <p class="pb-section-description">{{ $definition['description'] }}</p>

        @if($section['type'] === 'saved_block')
            <input type="hidden" name="sections[{{ $sid }}][data][block_id]" value="{{ $section['data']['block_id'] ?? '' }}">
            @if($linked)
                <div class="alert alert-info admin-alert mb-0">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    <div class="admin-alert-body">
                        This section shows the saved section <strong>“{{ $linked->name }}”</strong> ({{ $linked->typeName() }}).
                        Its content is edited in one place, and every page that links to it changes together.
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.content-blocks.edit', $linked) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Edit saved section<span class="visually-hidden"> (opens in a new tab)</span></a>
                            <a href="{{ route('admin.content-blocks.preview', $linked) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Preview<span class="visually-hidden"> (opens in a new tab)</span></a>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning admin-alert mb-0">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <div class="admin-alert-body">The saved section this links to was deleted or archived, so nothing is shown here. Delete this section or insert another saved section.</div>
                </div>
            @endif
            <x-admin.error :name="'sections.'.$sid.'.data.block_id'" />
        @else
            @include('admin.pages.partials.fields', [
                'fields' => $definition['fields'],
                'values' => $section['data'] ?? [],
                'name' => 'sections['.$sid.'][data]',
                'errorKey' => 'sections.'.$sid.'.data',
                'idPrefix' => 'f-'.$sid,
            ])
        @endif
    </div>
</article>
@endif
