@extends('layouts.admin')

@section('title', 'News')

@section('actions')
    <a href="{{ route('admin.news.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Article</a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Title</th><th>Published</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>{{ $article->title }}</td>
                            <td>{{ optional($article->published_at)->format('d M Y') }}</td>
                            <td><span class="status-pill {{ $article->is_active ? 'status-pill-success' : 'status-pill-secondary' }}">{{ $article->is_active ? 'Published' : 'Draft' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.news.edit', $article) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.news.destroy', $article) }}" class="d-inline" onsubmit="return confirm('Delete this article? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="admin-empty-state">
                                <i class="bi bi-newspaper" aria-hidden="true"></i>
                                No news articles yet.
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $articles->links() }}</div>
@endsection
