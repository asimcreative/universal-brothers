@extends('layouts.admin')

@section('title', 'Pages')
@section('subtitle', 'Build website pages from ready-made sections. Changes stay in a draft until you publish them.')
@section('guide', 'pages-builder')

@section('actions')
    <a href="{{ route('admin.content-blocks.index') }}" class="btn btn-outline-secondary"><i class="bi bi-bookmark-star me-1" aria-hidden="true"></i>Saved Sections</a>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Page</a>
@endsection

@section('content')
    <div class="card">
        <nav class="admin-tabs" aria-label="Filter pages by status">
            @foreach(['all' => 'All'] + \App\Models\Page::STATUSES as $value => $label)
                <a href="{{ route('admin.pages.index', array_filter(['status' => $value === 'all' ? null : $value, 'q' => $search ?: null])) }}"
                   class="{{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="page" @endif>
                    {{ $label }} <span class="admin-tab-count">{{ $counts[$value] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.pages.index') }}" class="admin-toolbar" role="search">
            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="admin-toolbar-search">
                <label for="pages-q" class="visually-hidden">Search pages</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="pages-q" name="q" value="{{ $search }}" class="form-control" placeholder="Search by title or address">
            </div>
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>

        <div class="table-responsive">
            <table class="table mb-0 align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Page</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="d-none d-md-table-cell">Last edited</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        @php($ubStatus = $page->displayStatus())
                        <tr>
                            <td style="min-width: 240px">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="admin-table-primary">{{ $page->title }}</a>
                                <span class="admin-table-secondary">/{{ $page->slug }}</span>
                            </td>
                            <td class="text-nowrap">
                                <x-admin.status-badge :status="$ubStatus" />
                                @if($ubStatus === 'scheduled' && $page->published_at)
                                    <span class="admin-table-secondary">{{ $page->published_at->timezone(config('app.display_timezone'))->format('j M Y, g:i a') }}</span>
                                @endif
                                @if($page->hasUnpublishedChanges() && $ubStatus === 'published')
                                    <x-admin.status-badge status="changes" class="mt-1" />
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell text-nowrap">
                                {{ $page->updated_at?->diffForHumans() }}
                                @if($page->editor)<span class="admin-table-secondary">by {{ $page->editor->name }}</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if($page->isLive())
                                    <a href="{{ url('/'.$page->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View<span class="visually-hidden"> {{ $page->title }} (opens in a new tab)</span></a>
                                @endif
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary admin-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $page->title }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button>
                                            </form>
                                        </li>
                                        @if($page->status === 'archived')
                                            <li>
                                                <form method="POST" action="{{ route('admin.pages.restore', $page) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Restore as draft</button>
                                                </form>
                                            </li>
                                        @else
                                            @if($page->is_active)
                                                <li>
                                                    <form method="POST" action="{{ route('admin.pages.unpublish', $page) }}" data-confirm="Visitors will no longer be able to open /{{ $page->slug }}. The content is kept as a draft, so you can publish it again later." data-confirm-title="Unpublish “{{ $page->title }}”?" data-confirm-button="Unpublish" data-confirm-tone="primary">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="dropdown-item"><i class="bi bi-eye-slash me-2" aria-hidden="true"></i>Unpublish</button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li>
                                                <form method="POST" action="{{ route('admin.pages.archive', $page) }}" data-confirm="The page is removed from the website and moved to the Archived tab. You can restore it at any time." data-confirm-title="Archive “{{ $page->title }}”?" data-confirm-button="Archive" data-confirm-tone="primary">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive</button>
                                                </form>
                                            </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="{{ $page->isLive() ? 'This page is live. ' : '' }}The page and all its earlier versions will be permanently deleted. This cannot be undone. Archive it instead if you might need it again." data-confirm-title="Delete “{{ $page->title }}”?" data-confirm-button="Delete permanently">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <x-admin.empty-state icon="bi-file-earmark-text" :title="$search ? 'No pages match “'.$search.'”.' : 'No pages here yet.'">
                                <a href="{{ route('admin.pages.create') }}" class="btn btn-sm btn-primary mt-2">Create a page</a>
                            </x-admin.empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pages->hasPages())
            <div class="admin-card-footer">{{ $pages->links() }}</div>
        @endif
    </div>
@endsection
