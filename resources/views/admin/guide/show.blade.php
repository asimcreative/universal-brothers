@extends('layouts.admin')

@php $isDone = in_array($section['key'], $completed, true); @endphp

@section('title', $section['title'])
@section('subtitle', $section['summary'])

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    <a href="{{ route('admin.guide.index') }}">Guide</a> /
    <span>{{ $groupTitle }}</span>
@endsection

@section('content')
    <div class="guide-layout">
        <article class="guide-article" aria-labelledby="guide-why">
            <section class="guide-block">
                <h2 id="guide-why"><i class="bi {{ $section['icon'] }}" aria-hidden="true"></i>Why this matters</h2>
                <p>{{ $section['why'] }}</p>
            </section>

            @if($section['steps'])
                <section class="guide-block">
                    <h2><i class="bi bi-list-ol" aria-hidden="true"></i>Step by step</h2>
                    <ol class="guide-steps">
                        @foreach($section['steps'] as $step)
                            <li><span class="guide-step-number" aria-hidden="true">{{ $loop->iteration }}</span><span>{{ $step }}</span></li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if(filled($section['example'] ?? null))
                <section class="guide-block guide-example">
                    <h2><i class="bi bi-lightbulb" aria-hidden="true"></i>Example</h2>
                    <p class="mb-0">{{ $section['example'] }}</p>
                </section>
            @endif

            @if($section['tips'])
                <section class="guide-block">
                    <h2><i class="bi bi-shield-check" aria-hidden="true"></i>Tips for safe editing</h2>
                    <ul class="guide-tips">
                        @foreach($section['tips'] as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($section['faqs'])
                <section class="guide-block">
                    <h2><i class="bi bi-question-circle" aria-hidden="true"></i>Common questions</h2>
                    <div class="guide-faqs">
                        @foreach($section['faqs'] as [$question, $answer])
                            <details>
                                <summary>{{ $question }}</summary>
                                <p>{{ $answer }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($links)
                <section class="guide-block">
                    <h2><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Go to the page</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($links as $link)
                            <a href="{{ $link['url'] }}" class="btn btn-outline-primary">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            <nav class="guide-pager" aria-label="Guide sections">
                @if($previous)
                    <a href="{{ route('admin.guide.show', $previous['key']) }}" class="guide-pager-link">
                        <span><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</span>
                        <strong>{{ $previous['title'] }}</strong>
                    </a>
                @else
                    <span></span>
                @endif
                @if($next)
                    <a href="{{ route('admin.guide.show', $next['key']) }}" class="guide-pager-link text-end">
                        <span>Next <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                        <strong>{{ $next['title'] }}</strong>
                    </a>
                @endif
            </nav>
        </article>

        <aside class="guide-aside">
            <div class="guide-aside-card">
                @if($isDone)
                    <p class="guide-done-note"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>You have read this section.</p>
                    <form method="POST" action="{{ route('admin.guide.uncomplete', $section['key']) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary w-100">Mark as not read</button>
                    </form>
                @else
                    <p class="small text-muted mb-2">Finished reading? Tick it off so you can see your progress in the guide.</p>
                    <form method="POST" action="{{ route('admin.guide.complete', $section['key']) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Mark as read</button>
                    </form>
                @endif
            </div>

            @if(filled($section['video'] ?? null))
                <div class="guide-aside-card">
                    <p class="small text-muted mb-2">Prefer to watch? The video shows these steps in the real admin panel.</p>
                    @include('admin.partials.watch-guide', ['video' => $section['video']])
                </div>
            @endif

            <nav class="guide-aside-card" aria-label="{{ $groupTitle }}">
                <h2>{{ $groupTitle }}</h2>
                <ul>
                    @foreach($groupSections as $item)
                        <li>
                            <a href="{{ route('admin.guide.show', $item['key']) }}" @if($item['key'] === $section['key']) aria-current="page" class="active" @endif>
                                {{ $item['title'] }}
                                @if(in_array($item['key'], $completed, true))<i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i><span class="visually-hidden">(read)</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('admin.guide.index') }}" class="small"><i class="bi bi-grid me-1" aria-hidden="true"></i>All guide sections</a>
            </nav>
        </aside>
    </div>
@endsection
