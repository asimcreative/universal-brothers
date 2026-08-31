@extends('layouts.app')

@section('title', 'Testimonials | Universal Brothers')
@section('meta_description', 'Hear from Hajis and Umrah pilgrims who travelled with Universal Brothers (Pvt) Ltd — real stories, real experiences.')

@section('content')
    <div class="hero-slide" style="min-height: 38vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">Testimonials</li>
                </ol>
            </nav>
            <span class="hero-eyebrow">Pilgrim Stories</span>
            <h1>Their Journeys. Their Words.</h1>
            <p class="lead mb-0">Hear directly from our Hajis and Umrah pilgrims as they share their experiences.</p>
        </div>
    </div>

    <div class="container section-tight">
        @if($videoTestimonials->isEmpty() && $textTestimonials->isEmpty())
            <x-empty-state icon="bi-chat-quote">No testimonials have been published yet. Please check back soon.</x-empty-state>
        @endif

        @if($videoTestimonials->isNotEmpty())
            <h2 class="h4 mb-4">Pilgrim Stories on Video</h2>
            <div class="row g-4 mb-5">
                @foreach($videoTestimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-video-testimonial-card :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif

        @if($textTestimonials->isNotEmpty())
            <h2 class="h4 mb-4">More From Our Pilgrims</h2>
            <div class="row g-4">
                @foreach($textTestimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-testimonial-card :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
