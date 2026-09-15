@extends('layouts.admin')

@section('title', $affiliation->exists ? 'Edit Affiliation' : 'New Affiliation')
@section('guide', 'affiliations')

@section('content')
    <form method="POST" action="{{ $affiliation->exists ? route('admin.affiliations.update', $affiliation) : route('admin.affiliations.store') }}" enctype="multipart/form-data">
        @csrf
        @if($affiliation->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="affiliation-organization-name" class="form-label">Organization Name</label>
                    <input type="text" name="organization_name" id="affiliation-organization-name" class="form-control" value="{{ old('organization_name', $affiliation->organization_name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="affiliation-link" class="form-label">Link (optional)</label>
                    <input type="text" name="link" id="affiliation-link" class="form-control" value="{{ old('link', $affiliation->link) }}">
                </div>
                <div class="col-md-2">
                    <label for="affiliation-year" class="form-label">Year</label>
                    <input type="text" name="year" id="affiliation-year" class="form-control" value="{{ old('year', $affiliation->year) }}">
                </div>
                <div class="col-md-8">
                    <label for="affiliation-logo" class="form-label">Logo {{ $affiliation->exists ? '(leave blank to keep current)' : '' }}</label>
                    <input type="file" name="logo" id="affiliation-logo" class="form-control" accept="image/*">
                    @if($affiliation->logo)
                        <img src="{{ Storage::url($affiliation->logo) }}" class="mt-2" style="height:60px;" alt="Current affiliation logo">
                    @endif
                </div>
                <div class="col-md-2">
                    <label for="affiliation-sort-order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="affiliation-sort-order" class="form-control" value="{{ old('sort_order', $affiliation->sort_order) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $affiliation->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="affiliation-description" class="form-label">Description (one-line, optional)</label>
                    <textarea name="description" id="affiliation-description" class="form-control" rows="2">{{ old('description', $affiliation->description) }}</textarea>
                </div>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.affiliations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
