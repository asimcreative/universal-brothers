@extends('layouts.admin')

@section('title', 'Categories & Series')
@section('subtitle', 'Manage Hajj, Umrah and Tourism categories and their series groupings.')

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Category</th><th>Series</th><th>Packages</th><th>Active</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td>{{ $category->series_count }}</td>
                            <td>{{ $category->packages_count }}</td>
                            <td><span class="status-pill {{ $category->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Manage</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
