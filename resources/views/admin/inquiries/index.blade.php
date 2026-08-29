@extends('layouts.admin')

@section('title', 'Inquiries')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['new', 'contacted', 'closed'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Name</th><th>Contact</th><th>Package</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse($inquiries as $inquiry)
                        <tr>
                            <td>{{ $inquiry->name }}</td>
                            <td>{{ $inquiry->email }}<br><span class="small text-muted">{{ $inquiry->phone }}</span></td>
                            <td>{{ $inquiry->package?->name ?? $inquiry->category?->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $inquiry->status === 'new' ? 'warning' : ($inquiry->status === 'contacted' ? 'info' : 'success') }}">{{ ucfirst($inquiry->status) }}</span></td>
                            <td>{{ $inquiry->created_at->format('d M Y H:i') }}</td>
                            <td><a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No inquiries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $inquiries->links() }}</div>
@endsection
