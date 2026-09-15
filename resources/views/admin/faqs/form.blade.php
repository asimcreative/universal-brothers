@extends('layouts.admin')

@section('title', $faq->exists ? 'Edit FAQ' : 'New FAQ')
@section('guide', 'faqs')

@section('content')
    <form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}">
        @csrf
        @if($faq->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label for="faq-category" class="form-label">Category</label>
                    <select name="category" id="faq-category" class="form-select">
                        @foreach(['general', 'hajj', 'umrah', 'tourism'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', $faq->category) === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="faq-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="faq-sort-order" class="form-control" value="{{ old('sort_order', $faq->sort_order) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="faq-question" class="form-label">Question</label>
                    <input type="text" name="question" id="faq-question" class="form-control" value="{{ old('question', $faq->question) }}" required>
                </div>
                <div class="col-12">
                    <x-admin.rich-text name="answer" id="faq-answer" label="Answer" profile="standard" :value="old('answer', $faq->answer)" :max="20000" :required="true"
                        help="Keep answers short and clear. Use a list for steps, and links to point to the right page." />
                </div>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
