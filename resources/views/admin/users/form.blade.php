@extends('layouts.admin')

@section('title', $user->exists ? 'Edit Admin User' : 'New Admin User')

@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="user-name" class="form-label">Name</label>
                    <input type="text" name="name" id="user-name" class="form-control" value="{{ old('name', $user->name) }}">
                </div>
                <div class="col-md-6">
                    <label for="user-email" class="form-label">Email</label>
                    <input type="email" name="email" id="user-email" class="form-control" value="{{ old('email', $user->email) }}">
                </div>
                <div class="col-md-6">
                    <label for="user-role" class="form-label">Role</label>
                    <select name="role" id="user-role" class="form-select">
                        <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="content_editor" {{ old('role', $user->role) === 'content_editor' ? 'selected' : '' }}>Content Editor</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="user-password" class="form-label">Password {{ $user->exists ? '(leave blank to keep current password)' : '' }}</label>
                    <input type="password" name="password" id="user-password" class="form-control" autocomplete="new-password">
                </div>
                @if($user->exists)
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} {{ auth()->user()->is($user) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                            @if(auth()->user()->is($user))
                                <input type="hidden" name="is_active" value="1">
                                <div class="form-text">You cannot deactivate your own account.</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
