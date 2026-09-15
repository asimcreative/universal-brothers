@extends('layouts.admin')

@section('title', $item->exists ? 'Edit Media Item' : 'New Media Item')

@section('content')
    <form method="POST" action="{{ $item->exists ? route('admin.media.update', $item) : route('admin.media.store') }}" enctype="multipart/form-data">
        @csrf
        @if($item->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label for="media-type" class="form-label">Type</label>
                    <select name="media_type" id="media-type" class="form-select">
                        <option value="image" {{ old('media_type', $item->media_type) === 'image' ? 'selected' : '' }}>Image</option>
                        <option value="video" {{ old('media_type', $item->media_type) === 'video' ? 'selected' : '' }}>Video</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="media-gallery-type" class="form-label">Gallery</label>
                    <select name="gallery_type" id="media-gallery-type" class="form-select">
                        <option value="gallery" {{ old('gallery_type', $item->gallery_type) === 'gallery' ? 'selected' : '' }}>General Gallery</option>
                        <option value="event" {{ old('gallery_type', $item->gallery_type) === 'event' ? 'selected' : '' }}>Event</option>
                        <option value="promo" {{ old('gallery_type', $item->gallery_type) === 'promo' ? 'selected' : '' }}>Promotional</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="media-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="media-sort-order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}">
                </div>
                <div class="col-12">
                    <label for="media-title" class="form-label">Title (optional)</label>
                    <input type="text" name="title" id="media-title" class="form-control" value="{{ old('title', $item->title) }}">
                </div>
                <div class="col-md-6">
                    <label for="media-alt" class="form-label">Image description (alt text)</label>
                    <input type="text" name="alt_text" id="media-alt" class="form-control @error('alt_text') is-invalid @enderror" maxlength="255" value="{{ old('alt_text', $item->alt_text) }}" aria-describedby="media-alt-help">
                    @error('alt_text')<div class="invalid-feedback" id="media-alt-error">{{ $message }}</div>@enderror
                    <div class="form-help" id="media-alt-help">Describe what the photo shows, for visitors who use a screen reader and for search engines. For example “Pilgrims performing tawaf around the Kaaba”.</div>
                </div>
                <div class="col-md-6">
                    <label for="media-caption" class="form-label">Caption (optional)</label>
                    <input type="text" name="caption" id="media-caption" class="form-control" maxlength="500" value="{{ old('caption', $item->caption) }}" aria-describedby="media-caption-help">
                    <div class="form-help" id="media-caption-help">Shown under the photo when it is opened larger.</div>
                </div>
                <div class="col-md-6">
                    <label for="media-file" class="form-label">Image File {{ $item->exists ? '(leave blank to keep current)' : '(required for image type)' }}</label>
                    <input type="file" name="file_path" id="media-file" class="form-control @error('file_path') is-invalid @enderror" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="media-file-help">
                    @error('file_path')<div class="invalid-feedback" id="media-file-error">{{ $message }}</div>@enderror
                    <div class="form-help" id="media-file-help">JPG, PNG, WebP or GIF, up to 8 MB.</div>
                    @if($item->media_type === 'image' && $item->file_path)
                        <img src="{{ Storage::url($item->file_path) }}" class="mt-2" style="height:60px;" alt="Current media image">
                    @endif
                </div>
                <div class="col-md-6">
                    <label for="media-video-url" class="form-label">Video URL (for video type — e.g. YouTube embed link)</label>
                    <input type="text" name="video_url" id="media-video-url" class="form-control" value="{{ old('video_url', $item->video_url) }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
