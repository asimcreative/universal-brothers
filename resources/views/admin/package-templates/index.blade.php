@extends('layouts.admin')

@section('title', 'Package Templates')
@section('subtitle', 'Starting points for new packages. A package made from a template gets its own copy, so editing a template never changes an existing package.')

@section('actions')
    <a href="{{ route('admin.package-templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New template</a>
@endsection

@section('content')
    <div class="alert alert-light border admin-alert" role="note">
        <i class="bi bi-lightbulb" aria-hidden="true"></i>
        <div class="admin-alert-body">
            The quickest way to make a template is from a finished package: open it and choose <strong>More → Save as template</strong>.
        </div>
    </div>

    <div class="card">
        <nav class="admin-tabs" aria-label="Filter by status">
            @foreach(['active' => ['Active', $counts['active']], 'archived' => ['Archived', $counts['archived']]] as $value => [$label, $count])
                <a href="{{ route('admin.package-templates.index', ['status' => $value]) }}" class="{{ $status === $value ? 'active' : '' }}" @if($status === $value) aria-current="page" @endif>{{ $label }} <span class="admin-tab-count">{{ $count }}</span></a>
            @endforeach
        </nav>

        <div class="table-responsive">
            <table class="table align-middle admin-table">
                <thead>
                    <tr>
                        <th scope="col">Template</th>
                        <th scope="col">Contents</th>
                        <th scope="col">Made from</th>
                        <th scope="col">Updated</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        @php $payload = $template->payload ?? []; @endphp
                        <tr>
                            <td style="min-width: 220px">
                                <a href="{{ route('admin.package-templates.edit', $template) }}" class="admin-table-primary">{{ $template->name }}</a>
                                @if($template->description)<span class="admin-table-secondary">{{ Str::limit($template->description, 100) }}</span>@endif
                            </td>
                            <td class="small text-muted">
                                {{ count($payload['variants'] ?? []) ? count($payload['variants']).' options · ' : '' }}{{ count($payload['room_options'] ?? []) }} room prices · {{ count($payload['accommodations'] ?? []) }} hotels · {{ count($payload['itinerary'] ?? []) }} days
                            </td>
                            <td class="small">{{ $template->sourcePackage ? trim($template->sourcePackage->code.' — '.$template->sourcePackage->name, ' —') : '—' }}</td>
                            <td class="small text-muted text-nowrap">{{ $template->updated_at->diffForHumans() }}</td>
                            <td class="text-end text-nowrap">
                                @if($template->is_active)
                                    <a href="{{ route('admin.hajj-packages.create', ['template' => $template->id]) }}" class="btn btn-sm btn-primary">Use</a>
                                @endif
                                <a href="{{ route('admin.package-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary admin-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $template->name }}">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form method="POST" action="{{ route('admin.package-templates.'.($template->is_active ? 'archive' : 'restore'), $template) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="dropdown-item"><i class="bi {{ $template->is_active ? 'bi-archive' : 'bi-arrow-counterclockwise' }} me-2" aria-hidden="true"></i>{{ $template->is_active ? 'Archive' : 'Restore' }}</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.package-templates.destroy', $template) }}" data-confirm="The template &quot;{{ $template->name }}&quot; will be deleted. Packages already made from it are not affected." data-confirm-title="Delete this template?" data-confirm-button="Delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2" aria-hidden="true"></i>Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="admin-empty-state">
                                <i class="bi bi-files" aria-hidden="true"></i>
                                <p class="mb-2">{{ $status === 'archived' ? 'No archived templates.' : 'No templates yet.' }}</p>
                                @if($status !== 'archived')
                                    <a href="{{ route('admin.hajj-packages.index') }}" class="btn btn-sm btn-outline-primary">Open a package to save it as a template</a>
                                @endif
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="admin-card-footer">{{ $templates->links() }}</div>
        @endif
    </div>
@endsection
