@extends('layouts.admin')

@section('title', 'Inquiries')
@section('guide', 'enquiries')
@section('subtitle', 'Enquiries submitted from the public site\'s contact and package forms.')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach(['new', 'contacted', 'closed'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Contact</th><th>Package / Category</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($inquiries as $inquiry)
                        <tr>
                            <td class="fw-semibold">{{ $inquiry->name }}</td>
                            <td>{{ $inquiry->email }}<br><span class="small text-muted">{{ $inquiry->phone }}</span></td>
                            <td>{{ $inquiry->package?->name ?? $inquiry->category?->name ?? '—' }}</td>
                            <td><span class="status-pill status-pill-{{ $inquiry->status === 'new' ? 'warning' : ($inquiry->status === 'contacted' ? 'info' : 'success') }}">{{ ucfirst($inquiry->status) }}</span></td>
                            <td class="text-nowrap">{{ $inquiry->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end"><a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="admin-empty-state">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                No inquiries found for this filter.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $inquiries->links() }}</div>
@endsection
