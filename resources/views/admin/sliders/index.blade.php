@extends('layouts.admin')

@section('title', 'Sliders')

@section('actions')
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Slider</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Image</th><th>Title</th><th>Page</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($sliders as $slider)
                        <tr>
                            <td><img src="{{ Storage::url($slider->image) }}" style="height:40px;" alt=""></td>
                            <td>{{ $slider->title }}</td>
                            <td class="text-capitalize">{{ $slider->page_context }}</td>
                            <td>{!! $slider->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.sliders.destroy', $slider) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No sliders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $sliders->links() }}</div>
@endsection
