@extends('layouts.admin')

@section('title', 'Media Gallery')

@section('actions')
    <a href="{{ route('admin.media.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Media Item</a>
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
                                    <i class="bi bi-play-circle fs-4 text-muted"></i>
                                @endif
                            </td>
                            <td>{{ $item->title ?? '—' }}</td>
                            <td class="text-capitalize">{{ $item->media_type }}</td>
                            <td class="text-capitalize">{{ $item->gallery_type }}</td>
                            <td>{!! $item->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.media.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this media item?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No media items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
@endsection
