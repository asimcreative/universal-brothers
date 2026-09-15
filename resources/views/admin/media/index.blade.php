@extends('layouts.admin')

@section('title', 'Media Gallery')
@section('subtitle', 'Photos and videos for the public Media page, and every image uploaded for pages and formatted text.')
@section('guide', 'website-content')

@section('actions')
    <a href="{{ route('admin.media.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Media Item</a>
@endsection

@section('content')
    <div class="card">
        <nav class="admin-tabs" aria-label="Which images">
            @foreach(\App\Models\MediaItem::COLLECTIONS as $value => $label)
                <a href="{{ route('admin.media.index', ['collection' => $value]) }}" class="{{ $collection === $value ? 'active' : '' }}" @if($collection === $value) aria-current="page" @endif>
                    {{ $label }} <span class="admin-tab-count">{{ $counts[$value] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        @if($collection === 'library')
            <p class="small text-muted px-3 pt-3 mb-0">
                These images were uploaded from the page builder or the text editor. They are not listed on the public Media page.
                Deleting one removes it from any page or text that shows it.
            </p>
        @endif

        <div class="table-responsive">
            <table class="table mb-0 align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Preview</th>
                        <th scope="col">Title and description</th>
                        <th scope="col">Type</th>
                        @if($collection === 'gallery')<th scope="col">Gallery</th>@endif
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                @if($item->media_type === 'image' && $item->file_path)
                                    <img src="{{ Storage::url($item->file_path) }}" class="admin-media-thumb" alt="{{ $item->alt_text ?: $item->title }}">
                                @else
                                    <i class="bi bi-play-circle fs-4 text-muted" aria-hidden="true"></i>
                                @endif
                            </td>
                            <td style="min-width: 220px">
                                <span class="admin-table-primary d-block">{{ $item->title ?? '—' }}</span>
                                @if($item->media_type === 'image')
                                    @if($item->alt_text)
                                        <span class="admin-table-secondary">{{ Str::limit($item->alt_text, 90) }}</span>
                                    @else
                                        <span class="status-pill status-pill-warning mt-1">No description</span>
                                    @endif
                                @endif
                                @if($item->width)<span class="admin-table-secondary">{{ $item->width }} × {{ $item->height }} px @if($item->sizeLabel()) · {{ $item->sizeLabel() }}@endif</span>@endif
                            </td>
                            <td class="text-capitalize">{{ $item->media_type }}</td>
                            @if($collection === 'gallery')<td class="text-capitalize">{{ $item->gallery_type }}</td>@endif
                            <td><span class="status-pill {{ $item->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.media.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="d-inline"
                                      data-confirm="{{ $collection === 'library' ? 'If this image is shown on a page or in formatted text, it will disappear from there. This cannot be undone.' : 'This cannot be undone.' }}"
                                      data-confirm-title="Delete this media item?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="admin-empty-state">
                                <i class="bi bi-collection-play" aria-hidden="true"></i>
                                {{ $collection === 'library' ? 'No images have been uploaded from pages or text yet.' : 'No media items yet.' }}
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="admin-card-footer">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
