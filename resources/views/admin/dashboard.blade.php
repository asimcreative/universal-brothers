@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Published Packages</div>
                    <div class="fs-3 fw-bold">{{ $stats['packages_published'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Draft Packages</div>
                    <div class="fs-3 fw-bold">{{ $stats['packages_draft'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">New Inquiries</div>
                    <div class="fs-3 fw-bold">{{ $stats['inquiries_new'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Active Testimonials</div>
                    <div class="fs-3 fw-bold">{{ $stats['testimonials_active'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">Recent Inquiries</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Name</th><th>Package</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($recentInquiries as $inquiry)
                        <tr>
                            <td>{{ $inquiry->name }}</td>
                            <td>{{ $inquiry->package?->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $inquiry->status === 'new' ? 'warning' : ($inquiry->status === 'contacted' ? 'info' : 'success') }}">{{ ucfirst($inquiry->status) }}</span></td>
                            <td>{{ $inquiry->created_at->format('d M Y H:i') }}</td>
                            <td><a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No inquiries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
