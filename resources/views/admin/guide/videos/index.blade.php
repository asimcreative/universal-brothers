@extends('layouts.admin')

@php use App\Support\Training\TrainingCatalog; @endphp

@section('title', 'Video Training')
@section('subtitle', 'Watch the real admin panel build a complete Hajj package, chapter by chapter, then do it yourself.')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.guide.index') }}">Guide</a> / <span>Video Training</span>
@endsection

@section('content')
    @php
        $recorded = collect(TrainingCatalog::chapters())->filter(fn ($c) => $c['video'])->count();
        $totalLength = TrainingCatalog::totalDuration();
    @endphp

    <div class="guide-hero training-hero">
        <div class="training-hero-text">
            <h2>Learn by watching the real admin panel</h2>
            <p>{{ TrainingCatalog::count() }} short chapters follow one complete package — based on the approved brochure package UB015 — from signing in to the published page. Every chapter has the video and the same steps written out underneath.@if($totalLength) Total length {{ intdiv($totalLength, 60) }} minutes.@endif</p>
            <form method="GET" action="{{ route('admin.training.index') }}" class="guide-search" role="search">
                <label for="training-search" class="visually-hidden">Search the video training</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="training-search" name="q" value="{{ $search }}" class="form-control" placeholder="Search, for example “room pricing”, “itinerary” or “publish”">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="guide-hero-side">
            <div class="guide-progress">
                <span class="guide-progress-label">Your training progress</span>
                <strong>{{ $summary['completed'] }} of {{ $summary['total'] }} chapters completed</strong>
                <div class="progress" role="progressbar" aria-label="Training chapters completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $summary['percent'] }}">
                    <div class="progress-bar" style="width: {{ $summary['percent'] }}%"></div>
                </div>
                <small class="d-block mt-1 text-muted">{{ $summary['percent'] }}% complete{{ $lastWatched ? ' · last watched: '.(TrainingCatalog::chapter($lastWatched->chapter_key)['title'] ?? '') : '' }}</small>
            </div>
            <a href="{{ route('admin.training.continue') }}" class="btn btn-primary w-100"><i class="bi bi-play-fill me-1" aria-hidden="true"></i>{{ $summary['watched'] ? 'Continue Training' : 'Start Training' }}</a>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.training.restart') }}" class="btn btn-outline-primary flex-fill"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Restart Training</a>
                <a href="{{ route('admin.training.checklist') }}" class="btn btn-outline-primary flex-fill"><i class="bi bi-list-check me-1" aria-hidden="true"></i>Package checklist</a>
            </div>
            @if($summary['watched'] || $summary['completed'])
                <form method="POST" action="{{ route('admin.training.reset') }}" data-confirm="Your watched positions and completed ticks for every chapter will be cleared. Other admins are not affected." data-confirm-title="Reset your training progress?" data-confirm-button="Reset progress">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm w-100">Reset Progress</button>
                </form>
            @endif
        </div>
    </div>

    @if($recorded < TrainingCatalog::count())
        <div class="alert alert-warning admin-alert" role="status">
            <i class="bi bi-camera-video-off" aria-hidden="true"></i>
            <div class="admin-alert-body">{{ TrainingCatalog::count() - $recorded }} {{ Str::plural('chapter', TrainingCatalog::count() - $recorded) }} have no video file on this server yet. Their written guides still work.</div>
        </div>
    @endif

    @if($search !== '')
        @php $found = collect($grouped)->flatten(1)->count(); @endphp
        <p class="text-muted" role="status">{{ $found }} {{ Str::plural('chapter', $found) }} match “{{ $search }}”. <a href="{{ route('admin.training.index') }}">Show all chapters</a></p>
    @endif

    @forelse($grouped as $categoryKey => $chapters)
        <section class="guide-group" aria-labelledby="training-cat-{{ $categoryKey }}">
            <h2 class="admin-section-heading" id="training-cat-{{ $categoryKey }}"><i class="bi {{ $categories[$categoryKey]['icon'] }} me-1" aria-hidden="true"></i>{{ $categories[$categoryKey]['title'] }}</h2>
            <p class="text-muted small mt-n2">{{ $categories[$categoryKey]['summary'] }}</p>
            <div class="training-grid">
                @foreach($chapters as $chapter)
                    @php
                        $record = $progress->get($chapter['key']);
                        $done = $record?->isCompleted();
                        $started = ! $done && $record && $record->position_seconds > 0;
                    @endphp
                    <article class="training-card {{ $done ? 'is-done' : '' }}" aria-labelledby="training-title-{{ $chapter['key'] }}">
                        <a href="{{ route('admin.training.show', $chapter['key']) }}" class="training-card-thumb" tabindex="-1" aria-hidden="true">
                            @if($chapter['video']['poster'] ?? null)
                                <img src="{{ route('admin.training.poster', $chapter['key']) }}" alt="" loading="lazy" width="320" height="180">
                            @else
                                <span class="training-card-placeholder"><i class="bi bi-camera-video"></i></span>
                            @endif
                            <span class="training-card-duration">{{ TrainingCatalog::formatDuration($chapter['video']['duration'] ?? null) }}</span>
                        </a>
                        <div class="training-card-body">
                            <span class="training-card-number">Chapter {{ $chapter['number'] }}</span>
                            <h3 id="training-title-{{ $chapter['key'] }}"><a href="{{ route('admin.training.show', $chapter['key']) }}">{{ $chapter['title'] }}</a></h3>
                            <p>{{ $chapter['description'] }}</p>
                            <ul class="training-card-meta">
                                <li><i class="bi bi-camera-video" aria-hidden="true"></i><span class="visually-hidden">Video length:</span>{{ $chapter['video'] ? TrainingCatalog::formatDuration($chapter['video']['duration']) : 'No video yet' }}</li>
                                <li><i class="bi bi-bar-chart" aria-hidden="true"></i><span class="visually-hidden">Difficulty:</span>{{ $chapter['difficulty'] }}</li>
                                <li><i class="bi bi-clock" aria-hidden="true"></i><span class="visually-hidden">Time to do it:</span>About {{ $chapter['task_minutes'] }} min to do</li>
                            </ul>
                        </div>
                        <div class="training-card-foot">
                            <span class="training-status {{ $done ? 'is-done' : ($started ? 'is-started' : '') }}">
                                @if($done)<i class="bi bi-check-circle-fill" aria-hidden="true"></i>Completed
                                @elseif($started)<i class="bi bi-pause-circle" aria-hidden="true"></i>Stopped at {{ TrainingCatalog::formatDuration($record->position_seconds) }}
                                @else<i class="bi bi-circle" aria-hidden="true"></i>Not completed
                                @endif
                            </span>
                            <a href="{{ route('admin.training.show', $chapter['key']) }}" class="btn btn-sm btn-primary" aria-label="{{ $started ? 'Resume' : 'Watch' }} chapter {{ $chapter['number'] }}: {{ $chapter['title'] }}">
                                <i class="bi bi-play-fill" aria-hidden="true"></i>{{ $started ? 'Resume' : 'Watch' }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card"><div class="card-body text-center py-5">
            <p class="mb-2">No chapter matches “{{ $search }}”.</p>
            <a href="{{ route('admin.training.index') }}" class="btn btn-outline-primary">Show all chapters</a>
        </div></div>
    @endforelse
@endsection
