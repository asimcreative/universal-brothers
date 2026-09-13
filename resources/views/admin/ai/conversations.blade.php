@extends('layouts.admin')

@section('title', 'AI Conversations')

@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <h1 class="admin-page-title mb-1">AI Conversations</h1>
            <p class="text-muted mb-0">
                {{ number_format($stats['conversations']) }} conversations ·
                {{ number_format($stats['messages']) }} messages ·
                {{ number_format($stats['leads']) }} leads ·
                {{ number_format($stats['failures']) }} failed replies
            </p>
        </div>
        <a href="{{ route('admin.ai.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i>Settings
        </a>
    </div>

    @if($stats['prompt_tokens'] || $stats['completion_tokens'])
        <div class="alert alert-secondary small">
            <i class="bi bi-graph-up me-1"></i>
            Token usage to date: {{ number_format($stats['prompt_tokens']) }} prompt +
            {{ number_format($stats['completion_tokens']) }} completion.
            Cost depends on your provider's rates — this is a usage count, not a billing figure.
        </div>
    @endif

    <div class="admin-card p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Started</th>
                        <th>Last activity</th>
                        <th class="text-center">Messages</th>
                        <th>Language</th>
                        <th>Lead</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conversation)
                        <tr>
                            <td class="small">{{ $conversation->created_at->format('d M Y, H:i') }}</td>
                            <td class="small">{{ $conversation->last_activity_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-center">{{ $conversation->messages_count }}</td>
                            <td class="small">{{ $conversation->locale ?: '—' }}</td>
                            <td>
                                @if($conversation->lead_captured && $conversation->inquiry)
                                    <a href="{{ route('admin.inquiries.show', $conversation->inquiry) }}" class="badge bg-success text-decoration-none">
                                        {{ $conversation->inquiry->name }}
                                    </a>
                                @elseif($conversation->lead_captured)
                                    <span class="badge bg-success">Captured</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.ai.conversation', $conversation) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No conversations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $conversations->links() }}</div>

    <p class="text-muted small mt-3">
        <i class="bi bi-shield-check me-1"></i>
        Conversations that did not produce an enquiry are deleted automatically after
        {{ config('ai.history.retention_days') }} days by the <code>ai:prune</code> command.
        Visitor IP addresses are stored only as a one-way hash.
    </p>
@endsection
