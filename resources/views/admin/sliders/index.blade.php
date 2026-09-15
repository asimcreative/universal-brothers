@extends('layouts.admin')

@section('title', 'Sliders')
@section('guide', 'website-content')

@section('actions')
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Slider</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Image</th><th>Title</th><th>Page</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($sliders as $slider)
                        <tr>
                            <td><img src="{{ Storage::url($slider->image) }}" style="height:40px;border-radius:4px;" alt="{{ $slider->title }}"></td>
                            <td>{{ $slider->title }}</td>
                            <td class="text-capitalize">{{ $slider->page_context }}</td>
                            <td><span class="status-pill {{ $slider->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $slider->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.sliders.destroy', $slider) }}" class="d-inline" data-confirm="This cannot be undone." data-confirm-title="Delete this slider?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="admin-empty-state">
                                <i class="bi bi-images" aria-hidden="true"></i>
                                No sliders yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $sliders->links() }}</div>
@endsection
