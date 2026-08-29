@extends('layouts.admin')

@section('title', $testimonial->exists ? 'Edit Testimonial' : 'New Testimonial')

@section('content')
    <form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}">
        @csrf
        @if($testimonial->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="testimonial-name" class="form-label">Name</label>
                    <input type="text" name="name" id="testimonial-name" class="form-control" value="{{ old('name', $testimonial->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="testimonial-service-tag" class="form-label">Service</label>
                    <select name="service_tag" id="testimonial-service-tag" class="form-select">
                        @foreach(['hajj', 'umrah', 'tourism', 'general'] as $tag)
                            <option value="{{ $tag }}" {{ old('service_tag', $testimonial->service_tag) === $tag ? 'selected' : '' }}>{{ ucfirst($tag) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="testimonial-rating" class="form-label">Rating (1-5)</label>
                    <input type="number" min="1" max="5" name="rating" id="testimonial-rating" class="form-control" value="{{ old('rating', $testimonial->rating ?? 5) }}">
                </div>
                <div class="col-12">
                    <label for="testimonial-quote" class="form-label">Quote</label>
                    <textarea name="quote" id="testimonial-quote" class="form-control" rows="4" required>{{ old('quote', $testimonial->quote) }}</textarea>
                </div>
                <div class="col-md-8">
                    <label for="testimonial-source" class="form-label">Source (provenance note)</label>
                    <input type="text" name="source" id="testimonial-source" class="form-control" value="{{ old('source', $testimonial->source) }}">
                </div>
                <div class="col-md-2">
                    <label for="testimonial-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="testimonial-sort-order" class="form-control" value="{{ old('sort_order', $testimonial->sort_order) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $testimonial->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
