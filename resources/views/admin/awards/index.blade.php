@extends('layouts.admin')

@section('title', 'Awards')

@section('actions')
    <a href="{{ route('admin.awards.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Award</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Image</th><th>Name</th><th>Organization</th><th>Year</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($awards as $award)
                        <tr>
                            <td>
                                @if($award->image)
                                    <img src="{{ Storage::url($award->image) }}" style="height:40px;" alt="{{ $award->name }}">
                                @else
                                    <i class="bi bi-trophy fs-4 text-muted" aria-hidden="true"></i>
                                @endif
                            </td>
                            <td>{{ $award->name }}</td>
                            <td>{{ $award->awarding_organization ?? '—' }}</td>
                            <td>{{ $award->year ?? '—' }}</td>
                            <td><span class="status-pill {{ $award->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $award->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.awards.edit', $award) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.awards.destroy', $award) }}" class="d-inline" onsubmit="return confirm('Delete this award? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="admin-empty-state">
                                <i class="bi bi-trophy" aria-hidden="true"></i>
                                No awards yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $awards->links() }}</div>
@endsection
