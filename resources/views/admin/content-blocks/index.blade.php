@extends('layouts.admin')

@section('title', 'Saved Sections')
@section('subtitle', 'Sections kept for reuse — a call to action, trust figures, booking instructions — ready to insert into any page.')
@section('guide', 'reusable-sections')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.pages.index') }}">Pages</a> / <span>Saved sections</span>
@endsection

@section('actions')
    <a href="{{ route('admin.content-blocks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Saved Section</a>
@endsection

@section('content')
    <div class="alert alert-light border admin-alert" role="note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div class="admin-alert-body small">
            <strong>How saved sections work.</strong> When you insert one into a page, the page gets its own <strong>copy</strong>; changing the copy never changes this saved section or other pages.
            A super admin can also insert a section <strong>linked</strong>: those pages always show what is saved here, so editing it here changes them all at once.
        </div>
    </div>

    <div class="card">
        <nav class="admin-tabs" aria-label="Filter saved sections">
            @foreach(['active' => 'In use', 'archived' => 'Archived'] as $value => $label)
                <a href="{{ route('admin.content-blocks.index', ['status' => $value]) }}" class="{{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="page" @endif>{{ $label }} <span class="admin-tab-count">{{ $counts[$value] }}</span></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.content-blocks.index') }}" class="admin-toolbar" role="search" data-autosubmit>
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="admin-toolbar-search">
                <label for="blocks-q" class="visually-hidden">Search saved sections</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="blocks-q" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name or description">
            </div>
            <div class="admin-toolbar-field">
                <label for="blocks-category">Group</label>
                <select id="blocks-category" name="category" class="form-select">
                    <option value="">All groups</option>
                    @foreach(\App\Models\ContentBlock::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>

        <div class="table-responsive">
            <table class="table mb-0 align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Saved section</th>
                        <th scope="col">Kind</th>
                        <th scope="col" class="d-none d-md-table-cell">Linked pages</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blocks as $block)
                        @php($ubLinked = $linkedCounts[$block->id] ?? 0)
                        @php($ubLocked = $ubLinked && auth()->user()->cannot('link-saved-sections'))
                        <tr>
                            <td style="min-width: 240px">
                                <a href="{{ route('admin.content-blocks.edit', $block) }}" class="admin-table-primary">{{ $block->name }}</a>
                                <span class="admin-table-secondary">{{ \App\Models\ContentBlock::CATEGORIES[$block->category] ?? $block->category }}@if($block->description) · {{ \Illuminate\Support\Str::limit($block->description, 90) }}@endif</span>
                            </td>
                            <td class="text-nowrap"><i class="bi {{ \App\Support\PageBuilder\BlockRegistry::find($block->type)['icon'] ?? 'bi-bookmark' }} me-1" aria-hidden="true"></i>{{ $block->typeName() }}</td>
                            <td class="d-none d-md-table-cell">
                                @if($ubLinked)
                                    <x-admin.status-badge status="info" :label="$ubLinked.' '.\Illuminate\Support\Str::plural('page', $ubLinked)" />
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.content-blocks.edit', $block) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="{{ route('admin.content-blocks.preview', $block) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Preview<span class="visually-hidden"> {{ $block->name }} (opens in a new tab)</span></a>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary admin-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $block->name }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form method="POST" action="{{ route('admin.content-blocks.duplicate', $block) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button>
                                            </form>
                                        </li>
                                        @if($ubLocked)
                                            <li><span class="dropdown-item-text small text-muted">Linked on live pages — only a super admin can archive or restore it</span></li>
                                        @elseif($block->is_archived)
                                            <li>
                                                <form method="POST" action="{{ route('admin.content-blocks.restore', $block) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Restore</button>
                                                </form>
                                            </li>
                                        @else
                                            <li>
                                                <form method="POST" action="{{ route('admin.content-blocks.archive', $block) }}" data-confirm="{{ $ubLinked ? 'The '.$ubLinked.' '.\Illuminate\Support\Str::plural('page', $ubLinked).' linked to it will stop showing this section. ' : '' }}It is no longer offered when adding sections. You can restore it later." data-confirm-title="Archive “{{ $block->name }}”?" data-confirm-button="Archive" data-confirm-tone="primary">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive</button>
                                                </form>
                                            </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        @if($ubLinked)
                                            <li><span class="dropdown-item-text small text-muted">Linked on {{ $ubLinked }} {{ \Illuminate\Support\Str::plural('page', $ubLinked) }} — archive instead of deleting</span></li>
                                        @else
                                            <li>
                                                <form method="POST" action="{{ route('admin.content-blocks.destroy', $block) }}" data-confirm="The saved section is permanently deleted. Copies already inserted into pages are not affected." data-confirm-title="Delete “{{ $block->name }}”?" data-confirm-button="Delete">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <x-admin.empty-state icon="bi-bookmark-star" :title="$status === 'archived' ? 'Nothing is archived.' : 'No saved sections yet.'">
                                In the page builder, open a section's <strong>⋮</strong> menu and choose “Save for reuse on other pages”, or create one here.
                            </x-admin.empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($blocks->hasPages())
            <div class="admin-card-footer">{{ $blocks->links() }}</div>
        @endif
    </div>
@endsection
