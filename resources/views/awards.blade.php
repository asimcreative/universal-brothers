@extends('layouts.app')

@section('title', 'Awards & Recognition | Universal Brothers')
@section('meta_description', 'Awards and industry recognition earned by Universal Brothers (Pvt) Ltd over ' . \App\Models\SiteSetting::get('years_in_operation', '20+') . ' years of Hajj, Umrah and Tourism service.')

@section('content')
    <div class="hero-slide" style="min-height: 42vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">Awards &amp; Recognition</li>
                </ol>
            </nav>
            <span class="hero-eyebrow">Recognized for Excellence</span>
            <h1>Excellence Recognized. Trust Earned.</h1>
            <p class="lead mb-0">Over {{ \App\Models\SiteSetting::get('industry_awards_count', '20+') }} Recognitions. One Consistent Commitment.</p>
        </div>
    </div>

    <div class="section">
        <div class="container">
            <p class="text-secondary mx-auto text-center mb-5" style="max-width: 720px;">Awards tell part of our story. The greater achievement is maintaining the trust of our pilgrims and travellers year after year.</p>

            @if($awards->isEmpty())
                <x-empty-state icon="bi-trophy">No awards have been published yet. Please check back soon.</x-empty-state>
            @else
                <div class="row g-4">
                    @foreach($awards as $award)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm reveal-on-scroll package-card">
                                <div class="package-card-img-wrap" style="aspect-ratio: 4/3;">
                                    @if($award->image)
                                        <img src="{{ Storage::url($award->image) }}" class="package-card-img" alt="{{ $award->name }}" loading="lazy">
                                    @else
                                        <div class="package-card-img visual-placeholder">
                                            <i class="bi bi-trophy"></i>
                                            <span>Award Photo Coming Soon</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h2 class="h5">{{ $award->name }}</h2>
                                    @if($award->awarding_organization)
                                        <p class="small text-muted mb-1"><i class="bi bi-building me-1"></i>{{ $award->awarding_organization }}</p>
                                    @endif
                                    @if($award->year)
                                        <p class="small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>{{ $award->year }}</p>
                                    @endif
                                    @if($award->description)
                                        <p class="small text-secondary mb-0">{{ $award->description }}</p>
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
