@extends('layouts.admin')

@section('title', 'Packages')

@section('actions')
    <a href="{{ route('admin.packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Package</a>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search by name..." value="{{ request('q') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td>{{ $package->code ?? '—' }}</td>
                            <td>{{ $package->name }} @if($package->is_featured)<span class="badge bg-secondary ms-1">Featured</span>@endif</td>
                            <td>{{ $package->category->name }}</td>
                            <td>{{ $package->duration_label }}</td>
                            <td>@if($package->starting_price) {{ $package->currency }} {{ number_format($package->starting_price) }} @else — @endif</td>
                            <td><span class="badge bg-{{ $package->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($package->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" class="d-inline" onsubmit="return confirm('Delete this package?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No packages found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $packages->links() }}</div>
@endsection
