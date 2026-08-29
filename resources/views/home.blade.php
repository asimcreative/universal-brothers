@extends('layouts.app')

@section('title', 'Universal Brothers — Hajj, Umrah & Tourism')
@section('meta_description', 'Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator in Karachi, Pakistan. Real Hajj 2027 packages, ' . $stats['years'] . ' years of trusted service, ' . $stats['pilgrims'] . ' pilgrims served.')

@section('content')
    {{-- Hero --}}
    @if($sliders->isNotEmpty())
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach($sliders as $i => $slide)
                    <div class="carousel-item hero-slide {{ $i === 0 ? 'active' : '' }}" style="background-image: url('{{ Storage::url($slide->image) }}')">
                        <div class="container hero-content text-center py-5">
                            <h1 class="display-4 fw-bold">{{ $slide->title }}</h1>
                            @if($slide->subtitle)<p class="lead">{{ $slide->subtitle }}</p>@endif
                            <div class="d-flex justify-content-center gap-2 mt-4">
                                @if($slide->cta_label)
                                    <a href="{{ $slide->cta_url }}" class="btn btn-secondary btn-lg">{{ $slide->cta_label }}</a>
                                @endif
                                @if($slide->secondary_cta_label)
                                    <a href="{{ $slide->secondary_cta_url }}" class="btn btn-outline-light btn-lg">{{ $slide->secondary_cta_label }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($sliders->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            @endif
        </div>
    @else
        <div class="hero-slide" style="background-image: linear-gradient(135deg, #101B45, #0A1230)">
            <div class="container hero-content text-center py-5 text-white">
                <h1 class="display-4 fw-bold">Hajj, Umrah &amp; Tourism — Done Right</h1>
                <p class="lead">The Leader &amp; Trend Setter — trusted by over {{ $stats['pilgrims'] }} pilgrims for over {{ $stats['years'] }} years.</p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                    <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-secondary btn-lg">View Hajj 2027 Packages</a>
                    <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Get a Quote</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Trust ticker --}}
    <div class="trust-ticker">
        <div class="container text-center">
            <span>{{ $stats['years'] }} Years of Trust</span>
            <span>{{ $stats['pilgrims'] }} Pilgrims Served</span>
            <span>Category A Mina Camp</span>
            <span>IATA Registered Operator</span>
            <span>Zone 1 — Near Jamarat</span>
        </div>
    </div>

    {{-- Featured packages per category --}}
    @foreach($categories as $category)
        @if($category->packages->isNotEmpty())
            <section class="py-5 {{ $loop->even ? 'bg-light' : '' }}">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Featured {{ $category->name }} Packages</h2>
                        <a href="{{ route('packages.category', $category->slug) }}" class="btn btn-outline-primary btn-sm">View All &rarr;</a>
                    </div>
                    <div class="row g-4">
                        @foreach($category->packages as $package)
                            <div class="col-md-6 col-lg-4">
                                <x-package-card :package="$package" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    {{-- About / icon pillars --}}
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Why Choose Universal Brothers</h2>
                <p class="text-muted">A company of Maxim's Group — Umrah &amp; Hajj Organizer, Travel &amp; Tours Operator.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="icon-pillar reveal-on-scroll">
                        <div class="icon-pillar-icon"><i class="bi bi-moon-stars"></i></div>
                        <h3 class="h6">Hajj Packages</h3>
                        <p class="small text-muted">Zone 1 Mina camps, real hotel accommodation, escorted service at every step.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="icon-pillar reveal-on-scroll">
                        <div class="icon-pillar-icon"><i class="bi bi-building"></i></div>
                        <h3 class="h6">Umrah Packages</h3>
                        <p class="small text-muted">Flexible Ramadan, Eid and seasonal Umrah packages.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="icon-pillar reveal-on-scroll">
                        <div class="icon-pillar-icon"><i class="bi bi-map"></i></div>
                        <h3 class="h6">Domestic Tourism</h3>
                        <p class="small text-muted">Hunza, Skardu, Kaghan and more — across Pakistan.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="icon-pillar reveal-on-scroll">
                        <div class="icon-pillar-icon"><i class="bi bi-airplane"></i></div>
                        <h3 class="h6">International Tourism</h3>
                        <p class="small text-muted">Europe, Maldives, Turkey, Dubai and more.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stat counters --}}
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="20">0</div>
                        <div class="small">Years of Trust</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="50000">0</div>
                        <div class="small">Pilgrims Served</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="12">0</div>
                        <div class="small">Hajj 2027 Packages</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="7">0</div>
                        <div class="small">Industry Awards</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    @if($testimonials->isNotEmpty())
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5">What Our Customers Say</h2>
                <div class="row g-4">
                    @foreach($testimonials as $testimonial)
                        <div class="col-md-6 col-lg-4">
                            <x-testimonial-card :testimonial="$testimonial" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- News --}}
    @if($news->isNotEmpty())
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5">Travel News</h2>
                <div class="row g-4">
                    @foreach($news as $article)
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                @if($article->cover_image)
                                    <img src="{{ Storage::url($article->cover_image) }}" class="card-img-top package-card-img" alt="{{ $article->title }}">
                                @endif
                                <div class="card-body">
                                    <p class="small text-muted mb-1">{{ optional($article->published_at)->format('d M Y') }}</p>
                                    <h3 class="h6">{{ $article->title }}</h3>
                                    <p class="small text-secondary">{{ Str::limit($article->excerpt, 100) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Quick inquiry CTA --}}
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <x-inquiry-form title="Get a Free Quote" />
                </div>
            </div>
        </div>
    </section>
@endsection
