@extends('layouts.admin')

@section('title', 'Hajj Packages')

@section('actions')
    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Hajj Package</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Duration</th><th>Aziziya</th><th>From (USD)</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td>{{ $package->code ?? '—' }}</td>
                            <td>{{ $package->name }} @if($package->is_featured)<span class="badge bg-secondary ms-1">Featured</span>@endif</td>
                            <td>{{ $package->duration_label }}</td>
                            <td>{!! $package->has_aziziya ? '<span class="badge bg-info text-dark">With Aziziya</span>' : '<span class="badge bg-light text-dark">Non-Aziziya</span>' !!}</td>
                            <td>@if($package->starting_price) US${{ number_format($package->starting_price) }} @else — @endif</td>
                            <td><span class="badge bg-{{ $package->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($package->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.hajj-packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.hajj-packages.destroy', $package) }}" class="d-inline" onsubmit="return confirm('Delete this Hajj package?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No Hajj packages found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
