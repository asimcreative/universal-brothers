@extends('layouts.admin')

@section('title', 'Media Gallery')

@section('actions')
    <a href="{{ route('admin.media.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Media Item</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Preview</th><th>Title</th><th>Type</th><th>Gallery</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                @if($item->media_type === 'image' && $item->file_path)
                                    <img src="{{ Storage::url($item->file_path) }}" style="height:40px;" alt="{{ $item->title }}">
                                @else
                                    <i class="bi bi-play-circle fs-4 text-muted" aria-hidden="true"></i>
                                @endif
                            </td>
                            <td>{{ $item->title ?? '—' }}</td>
                            <td class="text-capitalize">{{ $item->media_type }}</td>
                            <td class="text-capitalize">{{ $item->gallery_type }}</td>
                            <td><span class="status-pill {{ $item->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.media.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this media item? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="admin-empty-state">
                                <i class="bi bi-collection-play" aria-hidden="true"></i>
                                No media items yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
@endsection
