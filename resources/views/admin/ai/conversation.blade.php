@extends('layouts.admin')

@section('title', 'AI Conversation')

@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <h1 class="admin-page-title mb-1">Conversation</h1>
            <p class="text-muted mb-0 small">
                Started {{ $conversation->created_at->format('d M Y, H:i') }}
                · {{ $conversation->messages->count() }} messages
                @if($conversation->locale) · {{ $conversation->locale }} @endif
            </p>
        </div>
        <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>All conversations
        </a>
    </div>

    @if($conversation->lead_captured && $conversation->inquiry)
        <div class="alert alert-success">
            <i class="bi bi-person-check me-1"></i>
            This conversation produced an enquiry from <strong>{{ $conversation->inquiry->name }}</strong>.
            <a href="{{ route('admin.inquiries.show', $conversation->inquiry) }}">Open the enquiry</a>.
        </div>
    @endif

    <div class="admin-card p-4">
        @foreach($conversation->messages as $message)
            <div class="mb-3 pb-3 {{ $loop->last ? '' : 'border-bottom' }}">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                    <span class="badge {{ $message->role === 'user' ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst($message->role) }}</span>
                    <span class="text-muted small">{{ $message->created_at->format('H:i:s') }}</span>
                    @if($message->latency_ms)
                        <span class="text-muted small">{{ $message->latency_ms }} ms</span>
                    @endif
                    @if($message->failure_reason)
                        <span class="badge bg-danger">{{ $message->failure_reason }}</span>
                    @endif
                </div>

                {{-- Escaped: visitor text and model output are both untrusted. --}}
                <div>{!! nl2br(e($message->content)) !!}</div>

                @if($message->sources)
                    <div class="mt-2 small text-muted">
                        Links offered:
                        @foreach($message->sources as $source)
                            <a href="{{ $source['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $source['title'] ?? 'link' }}</a>@if(! $loop->last), @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
