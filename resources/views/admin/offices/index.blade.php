@extends('layouts.admin')

@section('title', 'Offices')

@section('actions')
    <a href="{{ route('admin.offices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Office</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Label</th><th>Address</th><th>Phone</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($offices as $office)
                        <tr>
                            <td>{{ $office->label }}</td>
                            <td>{{ Str::limit($office->address, 50) }}</td>
                            <td>{{ $office->phone_primary }}</td>
                            <td>{!! $office->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.offices.edit', $office) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.offices.destroy', $office) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No offices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
