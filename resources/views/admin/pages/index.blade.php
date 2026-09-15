@extends('layouts.admin')

@section('title', 'Pages')
@section('guide', 'website-content')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Page</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>{{ $page->title }}</td>
                            <td><code>/{{ $page->slug }}</code></td>
                            <td><span class="status-pill {{ $page->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $page->is_active ? 'Published' : 'Draft' }}</span></td>
                            <td class="text-end">
                                @if($page->is_active)
                                    <a href="{{ url('/'.$page->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                @endif
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-inline" data-confirm="This cannot be undone." data-confirm-title="Delete this page?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="admin-empty-state">
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                No pages yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $pages->links() }}</div>
@endsection
