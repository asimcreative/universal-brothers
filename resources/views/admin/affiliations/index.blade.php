@extends('layouts.admin')

@section('title', 'Affiliations')

@section('actions')
    <a href="{{ route('admin.affiliations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Affiliation</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Logo</th><th>Organization</th><th>Year</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($affiliations as $affiliation)
                        <tr>
                            <td>
                                @if($affiliation->logo)
                                    <img src="{{ Storage::url($affiliation->logo) }}" style="height:40px;" alt="{{ $affiliation->organization_name }}">
                                @else
                                    <i class="bi bi-diagram-3 fs-4 text-muted"></i>
                                @endif
                            </td>
                            <td>{{ $affiliation->organization_name }}</td>
                            <td>{{ $affiliation->year ?? '—' }}</td>
                            <td>{!! $affiliation->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.affiliations.edit', $affiliation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.affiliations.destroy', $affiliation) }}" class="d-inline" onsubmit="return confirm('Delete this affiliation?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No affiliations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $affiliations->links() }}</div>
@endsection
