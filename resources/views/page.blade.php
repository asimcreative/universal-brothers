@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title . ' | Universal Brothers')
@section('meta_description', $page->meta_description ?: Str::limit(strip_tags($page->body ?? ''), 160))

@push('head')
    @if($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @endif
@endpush

@section('content')
    <div class="hero-slide" style="min-height: 38vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $page->title }}</li>
                </ol>
            </nav>
            @if($page->template === 'about')<span class="hero-eyebrow">Our Story</span>@endif
            <h1>{{ $page->title }}</h1>
        </div>
    </div>

    @if($page->template === 'about')
        {{-- Beginning --}}
        <section class="section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        @if($page->featured_image)
                            <img src="{{ Storage::url($page->featured_image) }}" alt="{{ $page->title }}" class="img-fluid rounded mb-4" loading="lazy">
                        @endif
                        <span class="section-eyebrow">The Beginning</span>
                        <div class="page-body">
                            {!! $page->body !!}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Experiences --}}
        <section class="section bg-light">
            <div class="container">
                <div class="text-center mb-5 reveal-on-scroll">
                    <span class="section-eyebrow d-flex justify-content-center">By the Numbers</span>
                    <h2>Our Experience</h2>
                </div>
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <div class="stat-number" data-counter-target="{{ (int) preg_replace('/\D/', '', $stats['years']) ?: 0 }}">0</div>
                            <div class="small text-uppercase fw-semibold">Years of Experience</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <div class="stat-number" data-counter-target="{{ (int) preg_replace('/\D/', '', $stats['pilgrims']) ?: 0 }}">0</div>
                            <div class="small text-uppercase fw-semibold">Pilgrims Served</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <div class="stat-number" data-counter-target="{{ (int) preg_replace('/\D/', '', (string) $stats['awards_count']) ?: 0 }}">0</div>
                            <div class="small text-uppercase fw-semibold">Awards &amp; Recognitions</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Awards --}}
        @if($awards->isNotEmpty())
            <section class="section">
                <div class="container">
                    <div class="text-center mb-5 reveal-on-scroll">
                        <span class="section-eyebrow d-flex justify-content-center">Recognized for Excellence</span>
                        <h2>Awards &amp; Recognition</h2>
                    </div>
                    <div class="row g-4 mb-4">
                        @foreach($awards as $award)
                            <x-award-badge :award="$award" />
                        @endforeach
                    </div>
                    <div class="text-center">
                        <a href="{{ route('awards') }}" class="btn btn-outline-primary">View All Awards</a>
                    </div>
                </div>
            </section>
        @endif

        {{-- Affiliations --}}
        @if($affiliations->isNotEmpty())
            <section class="section bg-light">
                <div class="container">
                    <div class="text-center mb-5 reveal-on-scroll">
                        <span class="section-eyebrow d-flex justify-content-center">Trusted Institutions</span>
                        <h2>Affiliations</h2>
                    </div>
                    <div class="row g-4 justify-content-center align-items-center mb-4">
                        @foreach($affiliations as $affiliation)
                            <x-affiliation-badge :affiliation="$affiliation" />
                        @endforeach
                    </div>
                    <div class="text-center">
                        <a href="{{ route('affiliations') }}" class="btn btn-outline-primary">View All Affiliations</a>
                    </div>
                </div>
            </section>
        @endif
    @else
        <div class="container section-tight">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @if($page->featured_image)
                        <img src="{{ Storage::url($page->featured_image) }}" alt="{{ $page->title }}" class="img-fluid rounded mb-4" loading="lazy">
                    @endif
                    <div class="page-body">
                        {!! $page->body !!}
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
