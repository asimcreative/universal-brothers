@extends('layouts.admin')

@section('title', $library->label)
@section('subtitle', $library->intro)
@section('guide', ['hotels' => 'hotels', 'meal-plans' => 'meals', 'transport' => 'transport', 'inclusions' => 'inclusions', 'exclusions' => 'exclusions', 'upgrades' => 'upgrades', 'mashaer' => 'mashaer', 'notes' => 'notes', 'journey-templates' => 'itinerary'][$library->key] ?? 'safe-editing')
@section('guide_video', 'reusable-information')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <span>Reusable content</span> / <span>{{ $library->label }}</span>
@endsection

@section('actions')
    <a href="{{ route('admin.library.create', $library->key) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add {{ $library->singular }}</a>
@endsection

@section('content')
    @include('admin.library.partials.switcher')

    <div class="card">
        <nav class="admin-tabs" aria-label="Filter by status">
            @foreach(['active' => ['Active', $counts['active']], 'archived' => ['Archived', $counts['archived']]] as $value => [$label, $count])
                <a href="{{ route('admin.library.index', array_merge(['type' => $library->key], request()->except(['status', 'page']), ['status' => $value])) }}" class="{{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="page" @endif>{{ $label }} <span class="admin-tab-count">{{ $count }}</span></a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.library.index', $library->key) }}" class="admin-toolbar" data-autosubmit role="search">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="admin-toolbar-search">
                <label for="library-q" class="visually-hidden">Search</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="library-q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search {{ strtolower($library->label) }}">
            </div>
            @foreach($library->filters as $column => $filter)
                <div class="admin-toolbar-field">
                    <label for="library-filter-{{ $column }}">{{ $filter['label'] }}</label>
                    <select id="library-filter-{{ $column }}" name="{{ $column }}" class="form-select">
                        <option value="">All</option>
                        @foreach($filter['options'] as $value => $label)
                            <option value="{{ $value }}" @selected(request($column) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>

        <div class="table-responsive">
            <table class="table align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        @foreach($library->columns as $column)
                            <th scope="col">{{ $column['label'] }}</th>
                        @endforeach
                        @if($library->tracksUsage())<th scope="col">Used in</th>@endif
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php $usage = (int) ($record->usage_count ?? 0); @endphp
                        <tr>
                            <td style="min-width: 240px">
                                <a href="{{ route('admin.library.edit', [$library->key, $record->getKey()]) }}" class="admin-table-primary">{{ $record->libraryTitle() }}</a>
                                @if($library->subtitleField && $record->{$library->subtitleField} !== $record->libraryTitle())
                                    <span class="admin-table-secondary">{{ Str::limit($record->{$library->subtitleField}, 110) }}</span>
                                @endif
                                @unless($record->is_active)<span class="status-pill status-pill-secondary mt-1">Archived</span>@endunless
                            </td>
                            @foreach($library->columns as $column)
                                <td class="text-nowrap">{{ $library->formatColumn($record, $column) }}</td>
                            @endforeach
                            @if($library->tracksUsage())
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.library.usage', [$library->key, $record->getKey()]) }}" class="usage-pill {{ $usage ? 'is-used' : '' }}">
                                        {{ $usage }} {{ Str::plural('package', $usage) }}
                                    </a>
                                </td>
                            @endif
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.library.edit', [$library->key, $record->getKey()]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary admin-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $record->libraryTitle() }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form method="POST" action="{{ route('admin.library.duplicate', [$library->key, $record->getKey()]) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i class="bi bi-copy me-2" aria-hidden="true"></i>Duplicate</button>
                                            </form>
                                        </li>
                                        @if($library->tracksUsage())
                                            <li><a class="dropdown-item" href="{{ route('admin.library.usage', [$library->key, $record->getKey()]) }}"><i class="bi bi-diagram-2 me-2" aria-hidden="true"></i>Where it is used</a></li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        @if($record->is_active)
                                            <li>
                                                <form method="POST" action="{{ route('admin.library.archive', [$library->key, $record->getKey()]) }}" data-confirm="&quot;{{ $record->libraryTitle() }}&quot; will no longer be offered when building packages.{{ $usage ? ' The '.$usage.' '.Str::plural('package', $usage).' already using it keep their details.' : '' }}" data-confirm-title="Archive this {{ $library->singular }}?" data-confirm-button="Archive" data-confirm-tone="primary">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2" aria-hidden="true"></i>Archive</button>
                                                </form>
                                            </li>
                                        @else
                                            <li>
                                                <form method="POST" action="{{ route('admin.library.restore', [$library->key, $record->getKey()]) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Restore</button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($usage === 0)
                                            <li>
                                                <form method="POST" action="{{ route('admin.library.destroy', [$library->key, $record->getKey()]) }}" data-confirm="&quot;{{ $record->libraryTitle() }}&quot; will be deleted. No package uses it." data-confirm-title="Delete this {{ $library->singular }}?" data-confirm-button="Delete">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                                </form>
                                            </li>
                                        @else
                                            <li><span class="dropdown-item-text small text-muted">Used in {{ $usage }} {{ Str::plural('package', $usage) }} — archive instead of deleting</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($library->columns) + ($library->tracksUsage() ? 3 : 2) }}">
                            <div class="admin-empty-state">
                                <i class="bi {{ $library->icon }}" aria-hidden="true"></i>
                                @if(request('q') || collect($library->filters)->keys()->contains(fn ($c) => request()->filled($c)))
                                    <p class="mb-2">Nothing matches your search.</p>
                                    <a href="{{ route('admin.library.index', ['type' => $library->key, 'status' => $status]) }}" class="btn btn-sm btn-outline-primary">Clear search</a>
                                @elseif($status === 'archived')
                                    <p class="mb-0">Nothing archived.</p>
                                @else
                                    <p class="mb-2">No {{ strtolower($library->label) }} saved yet.</p>
                                    <a href="{{ route('admin.library.create', $library->key) }}" class="btn btn-sm btn-primary">Add the first one</a>
                                @endif
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="admin-card-footer">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
