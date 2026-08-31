@extends('layouts.admin')

@section('title', $award->exists ? 'Edit Award' : 'New Award')

@section('content')
    <form method="POST" action="{{ $award->exists ? route('admin.awards.update', $award) : route('admin.awards.store') }}" enctype="multipart/form-data">
        @csrf
        @if($award->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="award-name" class="form-label">Award Name</label>
                    <input type="text" name="name" id="award-name" class="form-control" value="{{ old('name', $award->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="award-organization" class="form-label">Awarding Organization</label>
                    <input type="text" name="awarding_organization" id="award-organization" class="form-control" value="{{ old('awarding_organization', $award->awarding_organization) }}">
                </div>
                <div class="col-md-2">
                    <label for="award-year" class="form-label">Year</label>
                    <input type="text" name="year" id="award-year" class="form-control" value="{{ old('year', $award->year) }}">
                </div>
                <div class="col-md-8">
                    <label for="award-image" class="form-label">Photo / Certificate {{ $award->exists ? '(leave blank to keep current)' : '' }}</label>
                    <input type="file" name="image" id="award-image" class="form-control" accept="image/*">
                    @if($award->image)
                        <img src="{{ Storage::url($award->image) }}" class="mt-2" style="height:60px;" alt="Current award image">
                    @endif
                </div>
                <div class="col-md-2">
                    <label for="award-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="award-sort-order" class="form-control" value="{{ old('sort_order', $award->sort_order) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $award->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="award-description" class="form-label">Description (one-line, optional)</label>
                    <textarea name="description" id="award-description" class="form-control" rows="2">{{ old('description', $award->description) }}</textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.awards.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
