@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'Your packages, enquiries and content at a glance.')
@section('breadcrumb', 'Overview')

@section('actions')
    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Hajj Package</a>
@endsection

@section('content')
    @if($showOnboarding)
        @php $canResume = in_array($tourStatus, ['paused', 'in_progress'], true) && $tourStep > 0; @endphp
        <section class="onboarding-panel" aria-labelledby="onboarding-title" data-onboarding-panel>
            <div class="onboarding-icon" aria-hidden="true"><i class="bi bi-signpost-split"></i></div>
            <div class="onboarding-body">
                <h2 id="onboarding-title">Welcome to your website admin, {{ auth()->user()->name }}</h2>
                <p>A short tour shows where everything is — {{ $tourLength }} quick stops, about two minutes. You can skip it, stop half-way and continue later, or read the guide instead.</p>
                <div class="onboarding-actions">
                    <button type="button" class="btn btn-primary" data-tour-start="{{ $canResume ? $tourStep : 0 }}">
                        <i class="bi bi-play-fill me-1" aria-hidden="true"></i>{{ $canResume ? 'Resume tour (step '.($tourStep + 1).' of '.$tourLength.')' : 'Start guided tour' }}
                    </button>
                    <a href="{{ route('admin.guide.index') }}" class="btn btn-outline-primary"><i class="bi bi-book me-1" aria-hidden="true"></i>Open the guide</a>
                    <form method="POST" action="{{ route('admin.onboarding.dismiss') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link">Skip for now</button>
                    </form>
                </div>
            </div>
        </section>
    @endif

    <div data-tour="dashboard">
    <h2 class="admin-section-heading">Hajj packages</h2>
    <div class="row g-3 mb-4">
        @foreach([
            ['Total Hajj Packages', $stats['hajj_total'], 'bi-moon-stars', 'info', route('admin.hajj-packages.index'), $stats['hajj_archived'] ? $stats['hajj_archived'].' archived, not counted' : null],
            ['Published Packages', $stats['hajj_published'], 'bi-globe2', 'success', route('admin.hajj-packages.index', ['status' => 'published']), 'Live on the website'],
            ['Draft Packages', $stats['hajj_draft'], 'bi-pencil-square', 'warning', route('admin.hajj-packages.index', ['status' => 'draft']), 'Not visible on the website'],
            ['Featured Packages', $stats['hajj_featured'], 'bi-star', 'gold', route('admin.hajj-packages.index', ['featured' => 'yes']), 'Shown first on the Hajj page'],
        ] as [$label, $value, $icon, $tone, $url, $hint])
            <div class="col-6 col-xl-3">
                <a href="{{ $url }}" class="admin-stat-card tone-{{ $tone }}">
                    <span class="admin-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <div>
                        <div class="admin-stat-value">{{ number_format($value) }}</div>
                        <div class="admin-stat-label">{{ $label }}</div>
                        @if($hint)<div class="admin-stat-hint d-none d-sm-block">{{ $hint }}</div>@endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    </div>

    <h2 class="admin-section-heading">Enquiries and content</h2>
    <div class="row g-3 mb-4">
        @foreach([
            ['New Inquiries', $stats['inquiries_new'], 'bi-envelope-exclamation', $stats['inquiries_new'] ? 'danger' : 'success', route('admin.inquiries.index', ['status' => 'new']), 'Not yet answered'],
            ['Total Inquiries', $stats['inquiries_total'], 'bi-envelope', 'info', route('admin.inquiries.index'), null],
            ['FAQs', $stats['faqs'], 'bi-question-circle', 'info', route('admin.faqs.index'), null],
            ['Awards', $stats['awards'], 'bi-trophy', 'gold', route('admin.awards.index'), null],
            ['Affiliations', $stats['affiliations'], 'bi-diagram-3', 'info', route('admin.affiliations.index'), null],
            ['Umrah & Tourism', $stats['other_packages'], 'bi-box-seam', 'success', route('admin.packages.index'), 'Live on the website'],
        ] as [$label, $value, $icon, $tone, $url, $hint])
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ $url }}" class="admin-stat-card tone-{{ $tone }}">
                    <span class="admin-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <div>
                        <div class="admin-stat-value">{{ number_format($value) }}</div>
                        <div class="admin-stat-label">{{ $label }}</div>
                        @if($hint)<div class="admin-stat-hint d-none d-sm-block">{{ $hint }}</div>@endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            @if($incompletePublished->isNotEmpty() || $stats['inquiries_new'] > 0)
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-flag text-warning" aria-hidden="true"></i>Needs attention</div>
                    @foreach($incompletePublished as $item)
                        <div class="attention-item">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <div class="flex-grow-1">
                                <strong>{{ $item['package']->code }} — {{ $item['package']->name }}</strong> is live but incomplete:
                                {{ collect($item['problems'])->pluck('message')->implode(' ') }}
                            </div>
                            <a href="{{ route('admin.hajj-packages.edit', ['package' => $item['package'], 'step' => $item['problems'][0]['step']]) }}" class="btn btn-sm btn-outline-primary">Fix</a>
                        </div>
                    @endforeach
                    @if($stats['inquiries_new'] > 0)
                        <div class="attention-item">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <div class="flex-grow-1">{{ $stats['inquiries_new'] }} {{ Str::plural('inquiry', $stats['inquiries_new']) }} waiting for a reply.</div>
                            <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}" class="btn btn-sm btn-outline-primary">Review</a>
                        </div>
                    @endif
                </div>
            @endif

            @if($unfinishedDrafts->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Unfinished drafts
                        <a href="{{ route('admin.hajj-packages.index', ['status' => 'draft']) }}" class="btn btn-sm btn-outline-secondary">All drafts</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($unfinishedDrafts as $item)
                            @php $percent = $item['review']->percent(); @endphp
                            <li class="list-group-item draft-progress-item">
                                <div class="min-w-0 flex-grow-1">
                                    <a href="{{ route('admin.hajj-packages.edit', $item['package']) }}" class="admin-table-primary">{{ $item['package']->code ? $item['package']->code.' — ' : '' }}{{ $item['package']->name }}</a>
                                    <span class="admin-table-secondary">Last saved {{ $item['package']->updated_at->diffForHumans() }}</span>
                                    <div class="completion-meter" role="progressbar" aria-label="{{ $item['package']->name }} is {{ $percent }}% complete" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                                        <span style="width: {{ $percent }}%"></span>
                                    </div>
                                </div>
                                <span class="completion-number">{{ $percent }}%</span>
                                <a href="{{ route('admin.hajj-packages.edit', ['package' => $item['package'], 'step' => $item['package']->builder_step ?: $item['review']->nextStep()]) }}" class="btn btn-sm btn-primary">Continue</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Recent package updates
                    <a href="{{ route('admin.hajj-packages.index', ['sort' => 'updated']) }}" class="btn btn-sm btn-outline-secondary">All packages</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 admin-table">
                        <thead><tr><th scope="col">Package</th><th scope="col">Status</th><th scope="col">Updated</th><th scope="col"></th></tr></thead>
                        <tbody>
                            @forelse($recentlyUpdatedPackages as $package)
                                @php $editUrl = $package->category?->slug === 'hajj' ? route('admin.hajj-packages.edit', $package) : route('admin.packages.edit', $package); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $editUrl }}" class="admin-table-primary">{{ $package->name }}</a>
                                        <span class="admin-table-secondary">{{ $package->category?->name }}{{ $package->code ? ' · '.$package->code : '' }}</span>
                                    </td>
                                    <td>
                                        @if($package->isArchived())
                                            <span class="status-pill status-pill-secondary">Archived</span>
                                        @else
                                            <span class="status-pill status-pill-{{ $package->isPublished() ? 'success' : 'warning' }}">{{ $package->isPublished() ? 'Published' : 'Draft' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap small text-muted">{{ $package->updated_at->diffForHumans() }}</td>
                                    <td class="text-end"><a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-empty-state py-4"><i class="bi bi-box-seam" aria-hidden="true"></i>No packages yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Recent inquiries
                    <a href="{{ route('admin.inquiries.index') }}" class="btn btn-sm btn-outline-secondary">All inquiries</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 admin-table">
                        <thead><tr><th scope="col">Name</th><th scope="col">Package</th><th scope="col">Status</th><th scope="col">Received</th><th scope="col"></th></tr></thead>
                        <tbody>
                            @forelse($recentInquiries as $inquiry)
                                <tr>
                                    <td class="fw-semibold">{{ $inquiry->name }}</td>
                                    <td>{{ $inquiry->package?->name ?? '—' }}</td>
                                    <td><span class="status-pill status-pill-{{ $inquiry->status === 'new' ? 'warning' : ($inquiry->status === 'contacted' ? 'info' : 'success') }}">{{ ucfirst($inquiry->status) }}</span></td>
                                    <td class="text-nowrap small text-muted">{{ $inquiry->created_at->format('d M Y, H:i') }}</td>
                                    <td class="text-end"><a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="admin-empty-state py-4"><i class="bi bi-envelope" aria-hidden="true"></i>No inquiries yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Quick actions</div>
                <div class="card-body d-grid gap-2">
                    @foreach([
                        [route('admin.hajj-packages.create'), 'bi-plus-circle', 'Create Hajj package', 'Step-by-step package builder'],
                        [route('admin.hajj-packages.index'), 'bi-moon-stars', 'Manage Hajj packages', 'Edit, publish, duplicate, archive'],
                        [route('admin.library.index', 'hotels'), 'bi-building', 'Hotels & reusable content', 'Write once, use in every package'],
                        [route('admin.faqs.index'), 'bi-question-circle', 'Manage FAQs', null],
                        [route('admin.awards.index'), 'bi-trophy', 'Manage awards', null],
                        [route('admin.affiliations.index'), 'bi-diagram-3', 'Manage affiliations', null],
                        [route('admin.inquiries.index'), 'bi-envelope', 'View inquiries', null],
                        [route('admin.settings.index'), 'bi-gear', 'Website settings', null],
                    ] as [$url, $icon, $label, $hint])
                        <a href="{{ $url }}" class="quick-action">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            <span>{{ $label }}@if($hint)<small>{{ $hint }}</small>@endif</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Reusable content
                    <a href="{{ route('admin.guide.show', 'safe-editing') }}" class="small">What is this?</a>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($libraryCounts as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.library.index', $item['type']->key) }}" class="text-decoration-none"><i class="bi {{ $item['type']->icon }} me-2" aria-hidden="true"></i>{{ $item['type']->label }}</a>
                            <span class="badge text-bg-light">{{ $item['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <div class="card-header">Recent activity</div>
                @if($recentActivity->isEmpty())
                    <div class="admin-empty-state py-4"><i class="bi bi-clock-history" aria-hidden="true"></i>Publishing, archiving and duplicating will be listed here.</div>
                @else
                    <ul class="activity-list">
                        @foreach($recentActivity as $activity)
                            <li>
                                <span class="activity-dot" aria-hidden="true"></span>
                                <span>{{ $activity->description }}<small>{{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}</small></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
