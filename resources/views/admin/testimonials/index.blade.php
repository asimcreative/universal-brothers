@extends('layouts.admin')

@section('title', 'Testimonials')

@section('actions')
    <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Testimonial</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Name</th><th>Quote</th><th>Service</th><th>Video</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($testimonials as $testimonial)
                        <tr>
                            <td>{{ $testimonial->name }}</td>
                            <td>{{ Str::limit($testimonial->quote, 60) }}</td>
                            <td class="text-capitalize">{{ $testimonial->service_tag }}</td>
                            <td>{!! $testimonial->video_url ? '<span class="status-pill status-pill-info">Video</span>' : '<span class="text-muted">—</span>' !!}</td>
                            <td><span class="status-pill {{ $testimonial->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $testimonial->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" class="d-inline" onsubmit="return confirm('Delete this testimonial? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="admin-empty-state">
                                <i class="bi bi-chat-quote" aria-hidden="true"></i>
                                No testimonials yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $testimonials->links() }}</div>
@endsection
