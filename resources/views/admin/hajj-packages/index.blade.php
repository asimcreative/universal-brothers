@extends('layouts.admin')

@section('title', 'Hajj Packages')
@section('subtitle', 'The full Hajj 2027 package structure — variants, room pricing, Aziziya, itinerary and more.')

@section('actions')
    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Hajj Package</a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Duration</th><th>Aziziya</th><th>From (USD)</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td><code>{{ $package->code ?? '—' }}</code></td>
                            <td>
                                {{ $package->name }}
                                @if($package->is_featured)<span class="status-pill status-pill-info ms-1">Featured</span>@endif
                            </td>
                            <td>{{ $package->duration_label }}</td>
                            <td><span class="status-pill {{ $package->has_aziziya ? 'status-pill-info' : 'status-pill-secondary' }}">{{ $package->has_aziziya ? 'With Aziziya' : 'Non-Aziziya' }}</span></td>
                            <td>@if($package->starting_price) US${{ number_format($package->starting_price) }} @else <span class="text-muted">—</span> @endif</td>
                            <td><span class="status-pill {{ $package->status === 'published' ? 'status-pill-success' : 'status-pill-secondary' }}">{{ ucfirst($package->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.hajj-packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.hajj-packages.destroy', $package) }}" class="d-inline" onsubmit="return confirm('Delete this Hajj package? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="admin-empty-state">
                                <i class="bi bi-moon-stars" aria-hidden="true"></i>
                                No Hajj packages found.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
