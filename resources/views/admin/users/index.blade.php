@extends('layouts.admin')

@section('title', 'Users & Roles')
@section('subtitle', 'Manage who can access the admin panel and what they can do.')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Admin User</a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="status-pill {{ $user->isSuperAdmin() ? 'status-pill-info' : 'status-pill-secondary' }}">{{ str_replace('_', ' ', $user->role) }}</span>
                            </td>
                            <td>
                                <span class="status-pill {{ $user->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if(! auth()->user()->is($user))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Remove this admin account? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="admin-empty-state">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                No admin users yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
