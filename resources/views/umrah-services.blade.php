@extends('layouts.app')

@section('title', 'Umrah Services | Universal Brothers')
@section('meta_description', "Universal Brothers' Umrah services — flexible, personalized Umrah packages from Pakistan to the Holy Lands.")

@section('content')
    {{-- Hero --}}
    <x-page-hero
        centered
        eyebrow="Umrah, Anytime"
        title="Your Umrah. Your Time. Your Journey."
        lead="Thoughtfully Planned From Pakistan to the Holy Lands"
        copy="From a short spiritual retreat to an extended family journey, our Umrah services can be tailored around your preferred dates, duration, accommodation and travel requirements."
        :breadcrumbs="['Home' => route('home'), 'Umrah Services' => null]">
        <x-slot:actions>
            <a href="{{ route('packages.category', 'umrah') }}" class="btn btn-secondary btn-lg">Explore Umrah Packages</a>
            <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Request a Customized Umrah</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- Services included --}}
    <section class="section">
        <div class="container text-center">
            <div class="mx-auto reveal-on-scroll" style="max-width: 720px;">
                <span class="section-eyebrow d-flex justify-content-center">Fully Customizable</span>
                <h2>One Journey. Designed Around You.</h2>
                <p class="text-secondary">Services can include:</p>
                <p class="fw-semibold">Visa Assistance • Flights • Makkah Hotels • Madinah Hotels • Airport Transfers • Intercity Transportation • Ziyarat • Group Arrangements • Family Packages • Customized Itineraries</p>
            </div>
        </div>
    </section>

    {{-- How to Apply / Umrah Process --}}
    <section id="how-to-apply" class="section bg-light text-center">
        <div class="container">
            <h2 id="umrah-process">Ready to Plan Your Umrah?</h2>
            <p class="text-secondary mx-auto mb-4" style="max-width: 640px;">Share your preferred dates and requirements with our team and we'll guide you through the available options and next steps.</p>
            <a href="{{ route('contact') }}" class="btn btn-primary">Start Your Umrah Enquiry</a>
        </div>
    </section>

    {{-- Umrah Guidance / Accommodation & Transport --}}
    <section id="umrah-guidance" class="section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-on-scroll" id="accommodation-transport">
                    <span class="section-eyebrow">Where You'll Stay</span>
                    <h2>Accommodation &amp; Transport</h2>
                    <p class="text-secondary">Makkah and Madinah hotel choices, airport transfers and intercity transportation are arranged as part of every Umrah package — exact hotels and arrangements vary by package. See each package's detail page for full accommodation and transport information.</p>
                </div>
                <div class="col-lg-6 reveal-on-scroll reveal-delay-2">
                    <x-visual
                        surface="panel"
                        seed="umrah-accommodation-transport"
                        caption="Accommodation & Transport"
                        mark=""
                        class="split-section-visual" />
                </div>
            </div>
        </div>
    </section>

    {{-- Next Flight Date --}}
    <section id="next-flight-date" class="section bg-light text-center">
        <div class="container">
            <h2>Next Flight Date</h2>
            <p class="text-secondary">To be announced — <a href="{{ route('contact') }}">contact us</a> for the latest Umrah flight schedule.</p>
        </div>
    </section>

    {{-- Umrah Packages --}}
    <section class="section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-eyebrow d-flex justify-content-center">Umrah Packages</span>
                <h2>Explore Umrah Packages</h2>
            </div>
            @if($packages->isNotEmpty())
                <div class="row g-4">
                    @foreach($packages as $package)
                        <div class="col-md-6 col-lg-4">
                            <x-package-card :package="$package" />
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('packages.category', 'umrah') }}" class="btn btn-primary btn-lg">View All Umrah Packages</a>
                </div>
            @else
                <x-empty-state icon="bi-bag">No Umrah packages are published yet. Please check back soon or <a href="{{ route('contact') }}">contact us</a>.</x-empty-state>
            @endif
        </div>
    </section>

    {{-- FAQs --}}
    <section id="faqs" class="section bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Umrah FAQs</h2>
            @if($faqs->isEmpty())
                <x-empty-state icon="bi-question-circle">No Umrah FAQs have been published yet. Please <a href="{{ route('contact') }}">contact us</a> with any questions.</x-empty-state>
            @else
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="accordion faq-accordion" id="umrahFaqAccordion">
                            @foreach($faqs as $i => $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#umrah-faq-{{ $faq->id }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="umrah-faq-{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="umrah-faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#umrahFaqAccordion">
                                        <div class="accordion-body text-secondary">{{ $faq->answer }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
