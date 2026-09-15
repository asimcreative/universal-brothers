@extends('layouts.admin')

@section('title', 'AI Conversations')
@section('subtitle', 'Chats visitors had with the assistant, newest first.')
@section('guide', 'ai-assistant')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.ai.index') }}">AI Assistant</a> / <span>Conversations</span>
@endsection

@section('actions')
    <a href="{{ route('admin.ai.index') }}" class="btn btn-outline-secondary"><i class="bi bi-gear me-1" aria-hidden="true"></i>Settings</a>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Conversations" :value="number_format($stats['conversations'])" icon="bi-chat-dots" tone="info" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Messages" :value="number_format($stats['messages'])" icon="bi-chat-text" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Enquiries collected" :value="number_format($stats['leads'])" icon="bi-person-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Replies that failed" :value="number_format($stats['failures'])" icon="bi-exclamation-triangle" :tone="$stats['failures'] ? 'danger' : null" /></div>
    </div>

    @if($stats['prompt_tokens'] || $stats['completion_tokens'])
        <div class="alert alert-light border admin-alert small" role="note">
            <i class="bi bi-graph-up" aria-hidden="true"></i>
            <div class="admin-alert-body">
                Usage so far: {{ number_format($stats['prompt_tokens']) }} tokens sent and {{ number_format($stats['completion_tokens']) }} tokens received.
                The cost depends on your provider's prices — this is a count, not a bill.
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        <th scope="col">Started</th>
                        <th scope="col">Last message</th>
                        <th scope="col" class="text-center">Messages</th>
                        <th scope="col" class="d-none d-md-table-cell">Language</th>
                        <th scope="col">Enquiry</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversations as $conversation)
                        <tr>
                            <td class="text-nowrap">{{ $conversation->created_at->format('d M Y, H:i') }}</td>
                            <td class="text-nowrap">{{ $conversation->last_activity_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-center">{{ $conversation->messages_count }}</td>
                            <td class="d-none d-md-table-cell">{{ $conversation->locale ?: '—' }}</td>
                            <td>
                                @if($conversation->lead_captured && $conversation->inquiry)
                                    <a href="{{ route('admin.inquiries.show', $conversation->inquiry) }}" class="status-pill status-pill-success text-decoration-none">
                                        <i class="bi bi-person-check" aria-hidden="true"></i>{{ $conversation->inquiry->name }}
                                    </a>
                                @elseif($conversation->lead_captured)
                                    <x-admin.status-badge status="ok" label="Collected" />
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.ai.conversation', $conversation) }}" class="btn btn-sm btn-outline-primary">View<span class="visually-hidden"> conversation from {{ $conversation->created_at->format('d M Y, H:i') }}</span></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="bi-chat-dots" title="No conversations yet.">Chats appear here once visitors use the assistant on the website.</x-admin.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($conversations->hasPages())
            <div class="admin-card-footer">{{ $conversations->links() }}</div>
        @endif
    </div>

    <p class="text-muted small mt-3">
        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
        Conversations that did not lead to an enquiry are deleted automatically after {{ config('ai.history.retention_days') }} days.
        Visitors' IP addresses are never stored in readable form.
    </p>
@endsection
