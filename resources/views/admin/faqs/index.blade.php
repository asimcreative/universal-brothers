@extends('layouts.admin')

@section('title', 'FAQs')

@section('actions')
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New FAQ</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Category</th><th>Question</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($faqs as $faq)
                        <tr>
                            <td class="text-capitalize">{{ $faq->category }}</td>
                            <td>{{ Str::limit($faq->question, 70) }}</td>
                            <td><span class="status-pill {{ $faq->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $faq->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="d-inline" data-confirm="This cannot be undone." data-confirm-title="Delete this FAQ?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="admin-empty-state">
                                <i class="bi bi-question-circle" aria-hidden="true"></i>
                                No FAQs yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $faqs->links() }}</div>
@endsection
