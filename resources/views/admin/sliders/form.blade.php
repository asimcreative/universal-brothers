@extends('layouts.admin')

@section('title', $slider->exists ? 'Edit Slider' : 'New Slider')

@section('content')
    <form method="POST" action="{{ $slider->exists ? route('admin.sliders.update', $slider) : route('admin.sliders.store') }}" enctype="multipart/form-data">
        @csrf
        @if($slider->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="slider-title" class="form-label">Title</label>
                    <input type="text" name="title" id="slider-title" class="form-control" value="{{ old('title', $slider->title) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="slider-subtitle" class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" id="slider-subtitle" class="form-control" value="{{ old('subtitle', $slider->subtitle) }}">
                </div>
                <div class="col-md-6">
                    <label for="slider-image" class="form-label">Image {{ $slider->exists ? '(leave blank to keep current)' : '' }}</label>
                    <input type="file" name="image" id="slider-image" class="form-control" accept="image/*" {{ $slider->exists ? '' : 'required' }}>
                    @if($slider->image)<img src="{{ Storage::url($slider->image) }}" class="mt-2" style="height:60px;">@endif
                </div>
                <div class="col-md-3">
                    <label for="slider-page-context" class="form-label">Page</label>
                    <select name="page_context" id="slider-page-context" class="form-select">
                        @foreach(['home', 'hajj', 'umrah', 'tourism'] as $ctx)
                            <option value="{{ $ctx }}" {{ old('page_context', $slider->page_context) === $ctx ? 'selected' : '' }}>{{ ucfirst($ctx) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="slider-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="slider-sort-order" class="form-control" value="{{ old('sort_order', $slider->sort_order) }}">
                </div>
                <div class="col-md-3">
                    <label for="slider-cta-label" class="form-label">CTA Label</label>
                    <input type="text" name="cta_label" id="slider-cta-label" class="form-control" value="{{ old('cta_label', $slider->cta_label) }}">
                </div>
                <div class="col-md-3">
                    <label for="slider-cta-url" class="form-label">CTA URL</label>
                    <input type="text" name="cta_url" id="slider-cta-url" class="form-control" value="{{ old('cta_url', $slider->cta_url) }}">
                </div>
                <div class="col-md-3">
                    <label for="slider-secondary-cta-label" class="form-label">Secondary CTA Label</label>
                    <input type="text" name="secondary_cta_label" id="slider-secondary-cta-label" class="form-control" value="{{ old('secondary_cta_label', $slider->secondary_cta_label) }}">
                </div>
                <div class="col-md-3">
                    <label for="slider-secondary-cta-url" class="form-label">Secondary CTA URL</label>
                    <input type="text" name="secondary_cta_url" id="slider-secondary-cta-url" class="form-control" value="{{ old('secondary_cta_url', $slider->secondary_cta_url) }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $slider->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.sliders.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
