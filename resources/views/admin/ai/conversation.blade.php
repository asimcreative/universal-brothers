@extends('layouts.admin')

@section('title', 'AI Conversation')
@section('subtitle', 'Started '.$conversation->created_at->format('d M Y, H:i').' · '.$conversation->messages->count().' messages'.($conversation->locale ? ' · '.$conversation->locale : ''))
@section('guide', 'ai-assistant')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.ai.conversations') }}">AI Conversations</a> / <span>Conversation</span>
@endsection

@section('actions')
    <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>All conversations</a>
@endsection

@section('content')
    @if($conversation->lead_captured && $conversation->inquiry)
        <div class="alert alert-success admin-alert" role="status">
            <i class="bi bi-person-check" aria-hidden="true"></i>
            <div class="admin-alert-body">
                This conversation produced an enquiry from <strong>{{ $conversation->inquiry->name }}</strong>.
                <a href="{{ route('admin.inquiries.show', $conversation->inquiry) }}">Open the enquiry</a>.
            </div>
        </div>
    @endif

    <x-admin.panel title="Messages" icon="bi-chat-text" :flush="true">
        <ol class="ai-transcript list-unstyled mb-0">
            @foreach($conversation->messages as $message)
                <li class="ai-transcript-message ai-transcript-message--{{ $message->role === 'user' ? 'visitor' : 'assistant' }}">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                        <x-admin.status-badge :status="$message->role === 'user' ? 'info' : 'draft'" :label="$message->role === 'user' ? 'Visitor' : 'Assistant'" />
                        <span class="text-muted small">{{ $message->created_at->format('H:i:s') }}</span>
                        @if($message->latency_ms)<span class="text-muted small">{{ $message->latency_ms }} ms</span>@endif
                        @if($message->failure_reason)<x-admin.status-badge status="failed" :label="$message->failure_reason" />@endif
                    </div>

                    {{-- Escaped: visitor text and model output are both untrusted. --}}
                    <div class="ai-transcript-text">{!! nl2br(e($message->content)) !!}</div>

                    @if($message->sources)
                        <div class="mt-2 small text-muted">
                            Links offered:
                            @foreach($message->sources as $source)
                                <a href="{{ $source['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $source['title'] ?? 'link' }}</a>@if(! $loop->last), @endif
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </x-admin.panel>
@endsection
