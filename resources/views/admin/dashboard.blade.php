@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'An overview of packages, content and enquiries.')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['packages_published'] }}</div>
                    <div class="admin-stat-label">Published Packages</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-file-earmark" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['packages_draft'] }}</div>
                    <div class="admin-stat-label">Draft Packages</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['inquiries_new'] }}</div>
                    <div class="admin-stat-label">New Inquiries</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-chat-quote" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['testimonials_active'] }}</div>
                    <div class="admin-stat-label">Active Testimonials</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-moon-stars" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['hajj_packages'] }}</div>
                    <div class="admin-stat-label">Hajj Packages</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['umrah_packages'] }}</div>
                    <div class="admin-stat-label">Umrah Packages</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-compass" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['tourism_packages'] }}</div>
                    <div class="admin-stat-label">Tourism Packages</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat-card">
                <span class="admin-stat-icon"><i class="bi bi-collection-play" aria-hidden="true"></i></span>
                <div>
                    <div class="admin-stat-value">{{ $stats['media_items'] }}</div>
                    <div class="admin-stat-label">Media Items</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Recent Inquiries
                    <a href="{{ route('admin.inquiries.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
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
                                    <td>
                                        <span class="status-pill status-pill-{{ $inquiry->status === 'new' ? 'warning' : ($inquiry->status === 'contacted' ? 'info' : 'success') }}">{{ ucfirst($inquiry->status) }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $inquiry->created_at->format('d M Y H:i') }}</td>
                                    <td><a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5">
                                    <div class="admin-empty-state py-4">
                                        <i class="bi bi-envelope" aria-hidden="true"></i>
                                        No inquiries yet.
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Recent News</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Title</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                        <tbody>
                            @forelse($recentNews as $article)
                                <tr>
                                    <td>{{ $article->title }}</td>
                                    <td><span class="status-pill status-pill-{{ $article->is_active ? 'success' : 'secondary' }}">{{ $article->is_active ? 'Published' : 'Draft' }}</span></td>
                                    <td class="text-nowrap">{{ $article->updated_at->diffForHumans() }}</td>
                                    <td><a href="{{ route('admin.news.edit', $article) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">
                                    <div class="admin-empty-state py-4">
                                        <i class="bi bi-newspaper" aria-hidden="true"></i>
                                        No news articles yet.
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">Recently Updated Packages</div>
                <ul class="list-group list-group-flush">
                    @forelse($recentlyUpdatedPackages as $package)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $package->name }}</div>
                                <div class="small text-muted">{{ $package->category?->name }} &middot; {{ $package->updated_at->diffForHumans() }}</div>
                            </div>
                            <span class="status-pill status-pill-{{ $package->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($package->status) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item">
                            <div class="admin-empty-state py-4">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                                No packages yet.
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>New Hajj Package</a>
                    <a href="{{ route('admin.packages.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>New Umrah/Tourism Package</a>
                    <a href="{{ route('admin.news.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>New News Article</a>
                    <a href="{{ route('admin.inquiries.index') }}" class="btn btn-outline-primary text-start"><i class="bi bi-envelope me-2" aria-hidden="true"></i>Review Inquiries</a>
                </div>
            </div>
        </div>
    </div>
@endsection
