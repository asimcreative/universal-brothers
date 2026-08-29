@extends('layouts.admin')

@section('title', $article->exists ? 'Edit Article' : 'New Article')

@section('content')
    <form method="POST" action="{{ $article->exists ? route('admin.news.update', $article) : route('admin.news.store') }}" enctype="multipart/form-data">
        @csrf
        @if($article->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-8">
                    <label for="news-title" class="form-label">Title</label>
                    <input type="text" name="title" id="news-title" class="form-control" value="{{ old('title', $article->title) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="news-published-at" class="form-label">Published At</label>
                    <input type="datetime-local" name="published_at" id="news-published-at" class="form-control" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-12">
                    <label for="news-excerpt" class="form-label">Excerpt</label>
                    <textarea name="excerpt" id="news-excerpt" class="form-control" rows="2">{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>
                <div class="col-12">
                    <label for="news-body" class="form-label">Body</label>
                    <textarea name="body" id="news-body" class="form-control" rows="8">{{ old('body', $article->body) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="news-cover-image" class="form-label">Cover Image</label>
                    <input type="file" name="cover_image" id="news-cover-image" class="form-control" accept="image/*">
                    @if($article->cover_image)<img src="{{ Storage::url($article->cover_image) }}" class="mt-2" style="height:60px;">@endif
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $article->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="news-meta-title" class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" id="news-meta-title" class="form-control" value="{{ old('meta_title', $article->meta_title) }}">
                </div>
                <div class="col-md-6">
                    <label for="news-meta-description" class="form-label">Meta Description</label>
                    <input type="text" name="meta_description" id="news-meta-description" class="form-control" value="{{ old('meta_description', $article->meta_description) }}">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
