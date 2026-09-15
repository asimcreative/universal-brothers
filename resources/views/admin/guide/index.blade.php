@extends('layouts.admin')

@section('title', 'Admin Guide')
@section('subtitle', 'Step-by-step help for every part of the admin, in plain language.')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <span>Guide</span>
@endsection

@section('content')
    @php $done = count($completed); @endphp

    <div class="guide-hero">
        <div class="guide-hero-text">
            <h2>What would you like to do?</h2>
            <p>Each section explains what a part of the admin is for, what to do step by step, and a real example. Tick a section when you have read it.</p>
            <form method="GET" action="{{ route('admin.guide.index') }}" class="guide-search" role="search">
                <label for="guide-search" class="visually-hidden">Search the guide</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="guide-search" name="q" value="{{ $search }}" class="form-control" placeholder="Search, for example “room prices” or “publish”">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="guide-hero-side">
            <div class="guide-progress">
                <span class="guide-progress-label">Your progress</span>
                <strong>{{ $done }} of {{ $total }} sections read</strong>
                <div class="progress" role="progressbar" aria-label="Guide sections read" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}">
                    <div class="progress-bar" style="width: {{ $total ? round($done / $total * 100) : 0 }}%"></div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.onboarding.restart') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-signpost-split me-1" aria-hidden="true"></i>{{ $tourStatus === 'completed' ? 'Take the tour again' : 'Take the guided tour' }}
                </button>
            </form>
            <a href="{{ route('admin.guide.show', 'hajj-packages') }}" class="btn btn-primary w-100">
                <i class="bi bi-moon-stars me-1" aria-hidden="true"></i>How to create a Hajj package
            </a>
        </div>
    </div>

    @if($search !== '')
        <p class="text-muted" role="status">
            @php $found = collect($grouped)->flatten(1)->count(); @endphp
            {{ $found }} {{ \Illuminate\Support\Str::plural('section', $found) }} match “{{ $search }}”.
            <a href="{{ route('admin.guide.index') }}">Show all sections</a>
        </p>
    @endif

    @forelse($grouped as $group => $sections)
        <section class="guide-group" aria-labelledby="guide-group-{{ $group }}">
            <h2 class="admin-section-heading" id="guide-group-{{ $group }}">{{ $groups[$group] }}</h2>
            <div class="guide-grid">
                @foreach($sections as $section)
                    @php $isDone = in_array($section['key'], $completed, true); @endphp
                    <a href="{{ route('admin.guide.show', $section['key']) }}" class="guide-card {{ $isDone ? 'is-done' : '' }}">
                        <span class="guide-card-icon" aria-hidden="true"><i class="bi {{ $section['icon'] }}"></i></span>
                        <span class="guide-card-body">
                            <strong>{{ $section['title'] }}</strong>
                            <span>{{ $section['summary'] }}</span>
                        </span>
                        @if($isDone)
                            <span class="guide-card-done"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Read</span></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card"><div class="card-body text-center py-5">
            <p class="mb-2">Nothing in the guide matches “{{ $search }}”.</p>
            <a href="{{ route('admin.guide.index') }}" class="btn btn-outline-primary">Show all sections</a>
        </div></div>
    @endforelse
@endsection
