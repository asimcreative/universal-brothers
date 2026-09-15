@extends('layouts.admin')

@section('title', 'Hajj Packages')
@section('subtitle', 'Create, update and publish Hajj packages. Drafts are never shown on the website.')
@section('guide', 'hajj-packages')

@section('actions')
    @if($templates->isNotEmpty())
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-files me-1" aria-hidden="true"></i>From a template</button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                @foreach($templates as $template)
                    <li><a class="dropdown-item" href="{{ route('admin.hajj-packages.create', ['template' => $template->id]) }}">{{ $template->name }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Hajj Package</a>
@endsection

@section('content')
    @php
        $status = request('status', '');
        $tabUrl = fn ($value) => route('admin.hajj-packages.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => $value])));
    @endphp

    <div class="card">
        <nav class="admin-tabs" aria-label="Filter by status">
            @foreach(['' => ['All', $counts['all']], 'published' => ['Published', $counts['published']], 'draft' => ['Drafts', $counts['draft']], 'archived' => ['Archived', $counts['archived']]] as $value => [$label, $count])
                <a href="{{ $tabUrl($value) }}" class="{{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="page" @endif>{{ $label }} <span class="admin-tab-count">{{ $count }}</span></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.hajj-packages.index') }}" class="admin-toolbar" data-autosubmit role="search">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="admin-toolbar-search">
                <label for="filter-q" class="visually-hidden">Search by title or code</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="filter-q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by title or code">
            </div>
            <div class="admin-toolbar-field">
                <label for="filter-series">Series</label>
                <select id="filter-series" name="series" class="form-select">
                    <option value="">All series</option>
                    @foreach($series as $item)
                        <option value="{{ $item->id }}" @selected((string) request('series') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-toolbar-field">
                <label for="filter-arrival">Arrival</label>
                <select id="filter-arrival" name="arrival" class="form-select">
                    <option value="">Any</option>
                    <option value="madinah" @selected(request('arrival') === 'madinah')>Madinah first</option>
                    <option value="makkah" @selected(request('arrival') === 'makkah')>Makkah first</option>
                </select>
            </div>
            <div class="admin-toolbar-field">
                <label for="filter-aziziya">Aziziya</label>
                <select id="filter-aziziya" name="aziziya" class="form-select">
                    <option value="">Any</option>
                    @foreach(['included' => 'Included', 'optional' => 'Optional upgrade', 'not_included' => 'Not included', 'not_applicable' => 'Not applicable'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('aziziya') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-toolbar-field" style="flex-basis: 110px; min-width: 110px">
                <label for="filter-days">Days</label>
                <select id="filter-days" name="days" class="form-select">
                    <option value="">Any</option>
                    @foreach($durations as $days)
                        <option value="{{ $days }}" @selected((string) request('days') === (string) $days)>{{ $days }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-toolbar-field" style="flex-basis: 120px; min-width: 120px">
                <label for="filter-featured">Featured</label>
                <select id="filter-featured" name="featured" class="form-select">
                    <option value="">Any</option>
                    <option value="yes" @selected(request('featured') === 'yes')>Featured</option>
                    <option value="no" @selected(request('featured') === 'no')>Not featured</option>
                </select>
            </div>
            <div class="admin-toolbar-field">
                <label for="filter-sort">Sort by</label>
                <select id="filter-sort" name="sort" class="form-select">
                    <option value="order" @selected(request('sort', 'order') === 'order')>Display order</option>
                    <option value="updated" @selected(request('sort') === 'updated')>Recently updated</option>
                    <option value="title" @selected(request('sort') === 'title')>Title A–Z</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                @if($filtersActive)
                    <a href="{{ route('admin.hajj-packages.index', array_filter(['status' => $status])) }}" class="btn btn-link">Clear filters</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Package</th>
                        <th scope="col">Code</th>
                        <th scope="col">Days</th>
                        <th scope="col">Arrival</th>
                        <th scope="col">From</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Featured</th>
                        <th scope="col">Last updated</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td style="min-width: 240px">
                                <a href="{{ route('admin.hajj-packages.edit', $package) }}" class="admin-table-primary">{{ $package->name }}</a>
                                <span class="admin-table-secondary">{{ $package->series?->name ?? 'No series' }}{{ $package->has_aziziya ? ' · With Aziziya' : '' }}</span>
                            </td>
                            <td><span class="admin-code">{{ $package->code ?? '—' }}</span></td>
                            <td>{{ $package->duration_days ?? '—' }}</td>
                            <td class="text-nowrap">{{ $package->medinah_first ? 'Madinah' : 'Makkah' }}</td>
                            <td class="text-nowrap">@if($package->starting_price) US${{ number_format($package->starting_price) }} @else <span class="text-muted">—</span> @endif</td>
                            <td>
                                @if($package->isArchived())
                                    <span class="status-pill status-pill-secondary">Archived</span>
                                @elseif($package->isPublished())
                                    <span class="status-pill status-pill-success">Published</span>
                                @else
                                    <span class="status-pill status-pill-warning">Draft</span>
                                @endif
                                @php $percent = $progress[$package->id]->percent(); @endphp
                                <div class="completion-inline" title="Checklist: {{ $percent }}% complete">
                                    <div class="completion-meter" role="progressbar" aria-label="{{ $package->name }} is {{ $percent }}% complete" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"><span style="width: {{ $percent }}%"></span></div>
                                    <small>{{ $percent }}% complete</small>
                                </div>
                            </td>

                            <td class="text-center">
                                @unless($package->isArchived())
                                    <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, $package->is_featured ? 'unfeature' : 'feature']) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="star-toggle {{ $package->is_featured ? 'is-on' : '' }}" aria-pressed="{{ $package->is_featured ? 'true' : 'false' }}" aria-label="{{ $package->is_featured ? 'Stop featuring' : 'Feature' }} {{ $package->name }}" title="{{ $package->is_featured ? 'Featured — click to stop featuring' : 'Not featured — click to feature' }}">
                                            <i class="bi {{ $package->is_featured ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endunless
                            </td>
                            <td class="text-nowrap small text-muted" title="{{ $package->updated_at }}">{{ $package->updated_at?->diffForHumans() }}</td>
                            <td class="text-end text-nowrap">
                                @if(! $package->isPublished() && ! $package->isArchived() && $percent < 100)
                                    <a href="{{ route('admin.hajj-packages.edit', ['package' => $package, 'step' => $package->builder_step ?: $progress[$package->id]->nextStep()]) }}" class="btn btn-sm btn-primary" aria-label="Continue {{ $package->name }}">Continue</a>
                                @else
                                    <a href="{{ route('admin.hajj-packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endif
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary admin-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $package->name }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item" href="{{ \App\Support\Packages\PackagePreview::url($package) }}" target="_blank" rel="noopener"><i class="bi bi-eye me-2" aria-hidden="true"></i>Preview</a></li>
                                        @if($package->isPublished())
                                            <li><a class="dropdown-item" href="{{ route('packages.show', ['category' => 'hajj', 'package' => $package->slug]) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>View on website</a></li>
                                        @endif
                                        <li>
                                            <form method="POST" action="{{ route('admin.hajj-packages.duplicate', $package) }}" data-confirm="A new draft copy of {{ $package->name }} will be created. The original is not changed." data-confirm-title="Duplicate this package?" data-confirm-button="Create copy" data-confirm-tone="primary">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($package->isArchived())
                                            <li>
                                                <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'restore']) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Restore as draft</button>
                                                </form>
                                            </li>
                                        @elseif($package->isPublished())
                                            <li>
                                                <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'unpublish']) }}" data-confirm="{{ $package->name }} will disappear from the website until it is published again." data-confirm-title="Move to draft?" data-confirm-button="Move to draft">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-eye-slash me-2" aria-hidden="true"></i>Unpublish</button>
                                                </form>
                                            </li>
                                        @else
                                            <li>
                                                <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'publish']) }}" data-confirm="{{ $package->name }} will appear on the website straight away." data-confirm-title="Publish this package?" data-confirm-button="Publish" data-confirm-tone="primary">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-globe2 me-2" aria-hidden="true"></i>Publish</button>
                                                </form>
                                            </li>
                                        @endif
                                        @unless($package->isArchived())
                                            <li>
                                                <form method="POST" action="{{ route('admin.hajj-packages.quick', [$package, 'archive']) }}" data-confirm="{{ $package->name }} will be removed from the website and moved to the Archived tab. You can restore it later." data-confirm-title="Archive this package?" data-confirm-button="Archive">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive</button>
                                                </form>
                                            </li>
                                        @endunless
                                        @unless($package->isPublished())
                                            <li>
                                                <form method="POST" action="{{ route('admin.hajj-packages.destroy', $package) }}" data-confirm="{{ $package->name }} will be deleted. This cannot be undone from the admin. Archive it instead if you might need it again." data-confirm-title="Delete this package?" data-confirm-button="Delete">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                                </form>
                                            </li>
                                        @endunless
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">
                            <div class="admin-empty-state">
                                <i class="bi bi-moon-stars" aria-hidden="true"></i>
                                @if($filtersActive || $status)
                                    <p class="mb-2">No packages match these filters.</p>
                                    <a href="{{ route('admin.hajj-packages.index') }}" class="btn btn-sm btn-outline-primary">Show all packages</a>
                                @else
                                    <p class="mb-2">No Hajj packages yet.</p>
                                    <a href="{{ route('admin.hajj-packages.create') }}" class="btn btn-sm btn-primary">Add the first package</a>
                                @endif
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($packages->hasPages())
            <div class="admin-card-footer">{{ $packages->links() }}</div>
        @endif
    </div>
@endsection
