@extends('layouts.app')

@section('title', 'Testimonials | Universal Brothers')
@section('meta_description', 'Hear from Hajis and Umrah pilgrims who travelled with Universal Brothers (Pvt) Ltd — real stories, real experiences.')

@section('content')
    <x-page-hero
        photo="haram-dusk"
        eyebrow="Pilgrim Stories"
        title="Their Journeys. Their Words."
        lead="Hear directly from our Hajis and Umrah pilgrims as they share their experiences."
        :breadcrumbs="['Home' => route('home'), 'Testimonials' => null]" />

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
            <div class="row g-4 justify-content-center">
                @foreach($textTestimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-testimonial-card :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-page-cta />
@endsection
