@extends('layouts.admin')

@php
    use App\Support\Training\TrainingCatalog;
    $video = $chapter['video'];
    $done = $record?->isCompleted();
@endphp

@section('title', 'Chapter '.$chapter['number'].': '.$chapter['title'])
@section('subtitle', $chapter['description'])

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    <a href="{{ route('admin.guide.index') }}">Guide</a> /
    <a href="{{ route('admin.training.index') }}">Video Training</a> /
    <span>{{ $category['title'] }}</span>
@endsection

@section('content')
    <div class="training-layout">
        <div class="training-main">
            <section class="training-player-card" aria-labelledby="training-watch">
                <h2 id="training-watch" class="visually-hidden">Watch video</h2>

                @if($video)
                    <div class="training-player"
                         data-training-player
                         data-progress-url="{{ route('admin.training.progress', $chapter['key']) }}"
                         data-complete-url="{{ route('admin.training.complete', $chapter['key']) }}"
                         data-resume="{{ $resumeAt }}"
                         data-completed="{{ $done ? '1' : '0' }}"
                         data-next-url="{{ $next ? route('admin.training.show', $next['key']) : '' }}"
                         data-next-title="{{ $next['title'] ?? '' }}">
                        <video controls preload="metadata" playsinline
                               @if($video['poster']) poster="{{ route('admin.training.poster', $chapter['key']) }}" @endif
                               aria-label="Training video: {{ $chapter['title'] }}"
                               data-training-video>
                            <source src="{{ route('admin.training.video', $chapter['key']) }}" type="video/webm">
                            @if($video['captions'])
                                <track kind="captions" srclang="en" label="English step captions" src="{{ route('admin.training.captions', $chapter['key']) }}">
                            @endif
                            <p>This browser cannot play the training video. The same steps are written out below.</p>
                        </video>

                        <div class="training-player-error alert alert-warning admin-alert" role="alert" data-training-error hidden>
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <div class="admin-alert-body">This video could not be played in this browser. Try Chrome, Edge or Firefox, or follow the written steps below — they show exactly the same thing.</div>
                        </div>

                        <div class="training-resume" role="status" data-training-resume hidden>
                            <span>Resumed from <strong data-training-resume-time></strong>.</span>
                            <button type="button" class="btn btn-link btn-sm p-0" data-training-restart>Start from the beginning</button>
                        </div>

                        <div class="training-toolbar" role="toolbar" aria-label="Video controls">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-training-seek="-10" aria-label="Back 10 seconds"><i class="bi bi-skip-backward-fill" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">10s</span></button>
                            <button type="button" class="btn btn-sm btn-primary" data-training-toggle aria-label="Play"><i class="bi bi-play-fill" aria-hidden="true"></i><span class="ms-1" data-training-toggle-label>Play</span></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-training-seek="10" aria-label="Forward 10 seconds"><span class="d-none d-sm-inline me-1">10s</span><i class="bi bi-skip-forward-fill" aria-hidden="true"></i></button>
                            <label class="visually-hidden" for="training-speed">Playback speed</label>
                            <select id="training-speed" class="form-select form-select-sm w-auto" data-training-speed>
                                @foreach(['0.75' => '0.75×', '1' => 'Normal speed', '1.25' => '1.25×', '1.5' => '1.5×', '2' => '2×'] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $value === '1')>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($video['captions'])
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-training-captions aria-pressed="false"><i class="bi bi-badge-cc" aria-hidden="true"></i><span class="ms-1">Captions</span></button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-training-replay><i class="bi bi-arrow-repeat" aria-hidden="true"></i><span class="ms-1">Replay</span></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-training-fullscreen aria-label="Full screen"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i><span class="ms-1 d-none d-sm-inline">Full screen</span></button>
                            <span class="training-time ms-auto" aria-hidden="true" data-training-time>0:00 / {{ TrainingCatalog::formatDuration($video['duration']) }}</span>
                        </div>

                        <div class="training-next alert alert-success admin-alert" role="status" data-training-next hidden>
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <div class="admin-alert-body">Chapter completed.@if($next) Next: <strong>{{ $next['title'] }}</strong>@endif</div>
                            @if($next)<a href="{{ route('admin.training.show', $next['key']) }}" class="btn btn-sm btn-primary">Next chapter</a>@endif
                        </div>
                    </div>
                @else
                    <div class="training-missing">
                        <i class="bi bi-camera-video-off" aria-hidden="true"></i>
                        <div>
                            <strong>The video for this chapter is not on this server yet.</strong>
                            <p class="mb-0">Follow the written steps below — they are the same steps the video shows.</p>
                        </div>
                    </div>
                @endif

                <div class="training-status-row">
                    <span class="training-status {{ $done ? 'is-done' : '' }}" data-training-status>
                        @if($done)<i class="bi bi-check-circle-fill" aria-hidden="true"></i>Completed @else<i class="bi bi-circle" aria-hidden="true"></i>Not completed @endif
                    </span>
                    <span class="text-muted small"><i class="bi bi-bar-chart me-1" aria-hidden="true"></i>{{ $chapter['difficulty'] }} · <i class="bi bi-clock mx-1" aria-hidden="true"></i>About {{ $chapter['task_minutes'] }} min to do @if($video) · <i class="bi bi-camera-video mx-1" aria-hidden="true"></i>{{ TrainingCatalog::formatDuration($video['duration']) }} video @endif</span>
                    @if($done)
                        <form method="POST" action="{{ route('admin.training.uncomplete', $chapter['key']) }}" class="ms-auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as not completed</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.training.complete', $chapter['key']) }}" class="ms-auto" data-training-complete-form>
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Mark as Completed</button>
                        </form>
                    @endif
                </div>
            </section>

            @if($moments)
                <section class="guide-block" aria-labelledby="training-moments">
                    <h2 id="training-moments"><i class="bi bi-list-ol" aria-hidden="true"></i>In this video</h2>
                    <ol class="training-moments">
                        @foreach($moments as [$seconds, $text])
                            <li>
                                <button type="button" class="training-moment" data-training-jump="{{ $seconds }}">
                                    <span class="training-moment-time">{{ intdiv($seconds, 60) }}:{{ str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $text }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <section class="guide-block" aria-labelledby="training-learn">
                <h2 id="training-learn"><i class="bi bi-mortarboard" aria-hidden="true"></i>What you will learn</h2>
                <ul class="guide-tips">
                    @foreach($chapter['learn'] as $item)<li>{{ $item }}</li>@endforeach
                </ul>
                <p class="mb-0 mt-2"><strong>Why it matters:</strong> {{ $chapter['why'] }}</p>
            </section>

            <section class="guide-block" aria-labelledby="training-steps">
                <h2 id="training-steps"><i class="bi bi-list-check" aria-hidden="true"></i>Step-by-step instructions</h2>
                <ol class="guide-steps">
                    @foreach($chapter['steps'] as $step)
                        <li><span class="guide-step-number" aria-hidden="true">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                    @endforeach
                </ol>
                @if($chapter['fields'])
                    <h3 class="h6 mt-3">Values used in the video</h3>
                    <div class="table-responsive">
                        <table class="table table-sm review-table mb-0">
                            <thead><tr><th scope="col">Field</th><th scope="col">Example value</th></tr></thead>
                            <tbody>
                                @foreach($chapter['fields'] as [$field, $value])
                                    <tr><td>{{ $field }}</td><td>{{ $value }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <p class="guide-example mt-3 mb-0 p-2 rounded"><strong>When you are done:</strong> {{ $chapter['expected'] }}</p>
            </section>

            <div class="row g-3">
                @if($chapter['notes'])
                    <div class="col-lg-6">
                        <section class="guide-block h-100 mb-0" aria-labelledby="training-notes">
                            <h2 id="training-notes"><i class="bi bi-info-circle" aria-hidden="true"></i>Important notes</h2>
                            <ul class="guide-tips">@foreach($chapter['notes'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                        </section>
                    </div>
                @endif
                @if($chapter['mistakes'])
                    <div class="col-lg-6">
                        <section class="guide-block h-100 mb-0" aria-labelledby="training-mistakes">
                            <h2 id="training-mistakes"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Common mistakes</h2>
                            <ul class="guide-tips">@foreach($chapter['mistakes'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                        </section>
                    </div>
                @endif
            </div>

            @if($chapter['check'] || $related)
                <section class="guide-block mt-3" aria-labelledby="training-check">
                    <h2 id="training-check"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Check before moving on</h2>
                    @if($chapter['check'])
                        <ul class="guide-tips">@foreach($chapter['check'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if($related)
                            <a href="{{ $related['url'] }}" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Related admin section: {{ $related['label'] }}</a>
                        @endif
                        @if(\App\Support\Guide\GuideContent::section($chapter['guide']))
                            <a href="{{ route('admin.guide.show', $chapter['guide']) }}" class="btn btn-outline-secondary"><i class="bi bi-book me-1" aria-hidden="true"></i>Written guide: {{ \App\Support\Guide\GuideContent::section($chapter['guide'])['title'] }}</a>
                        @endif
                    </div>
                </section>
            @endif

            <nav class="guide-pager" aria-label="Training chapters">
                @if($previous)
                    <a href="{{ route('admin.training.show', $previous['key']) }}" class="guide-pager-link">
                        <span><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous chapter</span>
                        <strong>{{ $previous['number'] }}. {{ $previous['title'] }}</strong>
                    </a>
                @else
                    <span></span>
                @endif
                @if($next)
                    <a href="{{ route('admin.training.show', $next['key']) }}" class="guide-pager-link text-end">
                        <span>Next chapter <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                        <strong>{{ $next['number'] }}. {{ $next['title'] }}</strong>
                    </a>
                @endif
            </nav>
        </div>

        <aside class="training-aside">
            <div class="guide-aside-card">
                <div class="guide-progress mb-2">
                    <span class="guide-progress-label">Your training progress</span>
                    <strong data-training-overall>{{ $summary['completed'] }} of {{ $summary['total'] }} completed</strong>
                    <div class="progress" role="progressbar" aria-label="Training chapters completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $summary['percent'] }}">
                        <div class="progress-bar" style="width: {{ $summary['percent'] }}%"></div>
                    </div>
                </div>
                <a href="{{ route('admin.training.index') }}" class="small"><i class="bi bi-grid me-1" aria-hidden="true"></i>All chapters</a>
                ·
                <a href="{{ route('admin.training.checklist') }}" class="small">Package checklist</a>
            </div>

            <nav class="guide-aside-card training-chapter-nav" aria-label="Chapters">
                <h2>Chapters</h2>
                <ol>
                    @foreach($chapters as $item)
                        @php $itemDone = $progress->get($item['key'])?->isCompleted(); @endphp
                        <li>
                            <a href="{{ route('admin.training.show', $item['key']) }}" @if($item['key'] === $chapter['key']) aria-current="page" class="active" @endif>
                                <span class="training-nav-number">{{ $item['number'] }}</span>
                                <span class="training-nav-title">{{ $item['title'] }}</span>
                                @if($itemDone)<i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i><span class="visually-hidden">(completed)</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>
    </div>
@endsection
