@extends('layouts.admin')

@section('title', 'Where it is used: '.$record->libraryTitle())
@section('subtitle', 'Packages that picked this '.$library->singular.'. Each keeps its own copy of the details.')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    <a href="{{ route('admin.library.index', $library->key) }}">{{ $library->label }}</a> /
    <span>Where it is used</span>
@endsection

@section('actions')
    <a href="{{ route('admin.library.edit', [$library->key, $record->getKey()]) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit {{ $library->singular }}</a>
@endsection

@section('content')
    @php $published = $packages->filter->isPublished()->count(); @endphp

    @if($packages->isNotEmpty() && $library->canPushToPackages())
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div class="min-w-0" style="max-width: 720px">
                    <h2 class="h6 fw-bold mb-1">Update these packages with the latest details</h2>
                    <p class="mb-0 small text-muted">
                        Copies the current details of "{{ $record->libraryTitle() }}" into every package below, replacing any change made to it inside a package.
                        @if($published){{ $published }} of them {{ $published === 1 ? 'is' : 'are' }} live, so the website changes straight away.@endif
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.library.push', [$library->key, $record->getKey()]) }}"
                      data-confirm="{{ $packages->count() }} {{ Str::plural('package', $packages->count()) }} will be updated with the current details of &quot;{{ $record->libraryTitle() }}&quot;.{{ $published ? ' '.$published.' live '.Str::plural('package', $published).' will change on the website immediately.' : '' }} Changes made to it inside those packages will be replaced."
                      data-confirm-title="Update {{ $packages->count() }} {{ Str::plural('package', $packages->count()) }}?" data-confirm-button="Update packages" data-confirm-tone="primary">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Update {{ $packages->count() }} {{ Str::plural('package', $packages->count()) }}</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle admin-table mb-0">
                <thead><tr><th scope="col">Package</th><th scope="col">Code</th><th scope="col">Status</th><th scope="col" class="text-end"></th></tr></thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td><a href="{{ route('admin.hajj-packages.edit', $package) }}" class="admin-table-primary">{{ $package->name }}</a></td>
                            <td><span class="admin-code">{{ $package->code ?? '—' }}</span></td>
                            <td>
                                @if($package->isArchived())
                                    <span class="status-pill status-pill-secondary">Archived</span>
                                @else
                                    <span class="status-pill status-pill-{{ $package->isPublished() ? 'success' : 'warning' }}">{{ $package->isPublished() ? 'Published' : 'Draft' }}</span>
                                @endif
                            </td>
                            <td class="text-end"><a href="{{ route('admin.hajj-packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Open package</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="admin-empty-state"><i class="bi bi-diagram-2" aria-hidden="true"></i>No package uses this {{ $library->singular }}. It can be deleted safely.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
