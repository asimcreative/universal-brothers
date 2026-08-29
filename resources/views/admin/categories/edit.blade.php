@extends('layouts.admin')

@section('title', $category->name . ' — Category Settings')

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Category Details</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label for="category-icon" class="form-label">Icon (Bootstrap Icon class)</label>
                            <input type="text" name="icon" id="category-icon" class="form-control" value="{{ old('icon', $category->icon) }}" placeholder="e.g. bi-moon-stars">
                        </div>
                        <div class="mb-3">
                            <label for="category-description" class="form-label">Description</label>
                            <textarea name="description" id="category-description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active (visible on the public site)</label>
                        </div>
                        <button class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Series</div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        @forelse($category->series as $series)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $series->name }}
                                <form method="POST" action="{{ route('admin.categories.series.destroy', [$category, $series]) }}" onsubmit="return confirm('Remove this series?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No series yet.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('admin.categories.series.store', $category) }}">
                        @csrf
                        <div class="input-group">
                            <label for="new-series-name" class="visually-hidden">New series name</label>
                            <input type="text" name="name" id="new-series-name" class="form-control" placeholder="New series name" required>
                            <button class="btn btn-outline-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
