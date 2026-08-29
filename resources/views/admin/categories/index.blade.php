@extends('layouts.admin')

@section('title', 'Categories & Series')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Category</th><th>Series</th><th>Packages</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->series_count }}</td>
                            <td>{{ $category->packages_count }}</td>
                            <td>{!! $category->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                            <td class="text-end"><a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Manage</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
