@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Page' : 'New Page')

@section('content')
    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" enctype="multipart/form-data">
        @csrf
        @if($page->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Content</div>
            <div class="card-body row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="page-title" class="form-control" value="{{ old('title', $page->title) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="page-slug" class="form-control" value="{{ old('slug', $page->slug) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Body</label>
                    <textarea name="body" class="form-control" rows="10">{{ old('body', $page->body) }}</textarea>
                    <div class="form-text">HTML is supported (rendered as-is on the public page). Only trusted admin users can edit this.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Featured Image</label>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    @if($page->featured_image)<img src="{{ Storage::url($page->featured_image) }}" class="mt-2" style="height:60px;" alt="">@endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Template</label>
                    <select name="template" class="form-select">
                        <option value="default" {{ old('template', $page->template ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="about" {{ old('template', $page->template) === 'about' ? 'selected' : '' }}>About</option>
                        <option value="policy" {{ old('template', $page->template) === 'policy' ? 'selected' : '' }}>Policy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $page->is_active ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Published (visible on the public site)</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">SEO</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta Description</label>
                    <input type="text" name="meta_description" class="form-control" value="{{ old('meta_description', $page->meta_description) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Canonical URL (optional)</label>
                    <input type="text" name="canonical_url" class="form-control" value="{{ old('canonical_url', $page->canonical_url) }}">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ $page->exists ? 'Update Page' : 'Create Page' }}</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>

    @push('scripts')
    <script>
        (function () {
            const titleInput = document.getElementById('page-title');
            const slugInput = document.getElementById('page-slug');
            let slugManuallyEdited = slugInput.value.length > 0;
            slugInput.addEventListener('input', () => { slugManuallyEdited = true; });
            titleInput.addEventListener('input', function () {
                if (slugManuallyEdited) return;
                slugInput.value = this.value.toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            });
        })();
    </script>
    @endpush
@endsection
