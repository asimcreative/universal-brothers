@extends('layouts.app')

@section('title', 'Affiliations | Universal Brothers')
@section('meta_description', 'Universal Brothers (Pvt) Ltd industry affiliations and professional memberships — IATA, TAAP, FPCCI and other recognized travel and tourism organizations.')

@section('content')
    <div class="hero-slide" style="min-height: 38vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">Affiliations</li>
                </ol>
            </nav>
            <span class="hero-eyebrow">Trusted Institutions</span>
            <h1>Strong Relationships. Trusted Connections.</h1>
        </div>
    </div>

    <div class="section">
        <div class="container">
            <p class="text-secondary mx-auto text-center mb-5" style="max-width: 720px;">Our affiliations with recognized travel, tourism and industry organizations strengthen our ability to provide dependable and professionally managed services.</p>

            @if($affiliations->isEmpty())
                <x-empty-state icon="bi-building">No affiliations have been published yet. Please check back soon.</x-empty-state>
            @else
                <div class="row g-4">
                    @foreach($affiliations as $affiliation)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm text-center reveal-on-scroll">
                                <div class="card-body p-4">
                                    @if($affiliation->logo)
                                        <img src="{{ Storage::url($affiliation->logo) }}" alt="{{ $affiliation->organization_name }}" class="img-fluid mb-3" style="max-height: 70px;">
                                    @else
                                        <div class="icon-pillar-icon mb-2 mx-auto"><i class="bi bi-diagram-3"></i></div>
                                    @endif
                                    <h2 class="h6 mb-1">{{ $affiliation->organization_name }}</h2>
                                    @if($affiliation->year)<p class="small text-muted mb-1">Since {{ $affiliation->year }}</p>@endif
                                    @if($affiliation->description)<p class="small text-secondary mb-2">{{ $affiliation->description }}</p>@endif
                                    @if($affiliation->link)
                                        <a href="{{ $affiliation->link }}" class="small" target="_blank" rel="noopener">Visit Website <i class="bi bi-box-arrow-up-right"></i></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
