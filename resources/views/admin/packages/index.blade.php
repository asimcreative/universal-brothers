@extends('layouts.admin')

@section('title', 'Umrah & Tourism Packages')
@section('subtitle', 'Hajj packages have their own dedicated section — see "Hajj Packages" in the sidebar.')

@section('actions')
    <a href="{{ route('admin.packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Package</a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Search by name..." value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100"><i class="bi bi-search me-1" aria-hidden="true"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Category</th><th>Duration</th><th>Price</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td><code>{{ $package->code ?? '—' }}</code></td>
                            <td>
                                {{ $package->name }}
                                @if($package->is_featured)<span class="status-pill status-pill-info ms-1">Featured</span>@endif
                            </td>
                            <td>{{ $package->category->name }}</td>
                            <td>{{ $package->duration_label }}</td>
                            <td>@if($package->starting_price) {{ $package->currency }} {{ number_format($package->starting_price) }} @else <span class="text-muted">—</span> @endif</td>
                            <td><span class="status-pill {{ $package->status === 'published' ? 'status-pill-success' : 'status-pill-secondary' }}">{{ ucfirst($package->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" class="d-inline" data-confirm="This cannot be undone." data-confirm-title="Delete this package?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="admin-empty-state">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                No packages found for these filters.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $packages->links() }}</div>
@endsection
