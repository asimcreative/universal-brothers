@extends('layouts.app')

@section('title', 'Hajj Services | Universal Brothers')
@section('meta_description', "Universal Brothers' Hajj services — real 2027 packages, application guidance, accommodation and transport, and on-ground support for the sacred journey.")

@section('content')
    {{-- Hero --}}
    <x-page-hero
        photo="kaaba-tawaf"
        centered
        eyebrow="Hajj 2027"
        title="Your Hajj. Our Responsibility."
        lead="A sacred obligation deserves extraordinary preparation."
        copy="Universal Brothers brings decades of experience, detailed planning and dedicated assistance together to help make your Hajj journey organized, informed and spiritually focused."
        :breadcrumbs="['Home' => route('home'), 'Hajj Services' => null]">
        <x-slot:actions>
            <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-secondary btn-lg">View Hajj Packages</a>
            <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Start Your Hajj Enquiry</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- Introduction --}}
    <section class="section">
        <div class="container">
            <div class="mx-auto text-center reveal-on-scroll" style="max-width: 720px;">
                <span class="section-eyebrow d-flex justify-content-center">Understanding Hajj</span>
                <h2>A Journey Unlike Any Other</h2>
                <p class="text-secondary">Hajj is one of the five pillars of Islam and one of life's most profound spiritual journeys. Millions answer the call each year, yet every pilgrim's Hajj is deeply personal.</p>
                <p class="text-secondary">Understanding the rituals, preparing physically and spiritually, and making appropriate travel arrangements are all important parts of that journey. Universal Brothers helps pilgrims prepare for each stage with information, coordination and experienced support.</p>
            </div>

            {{-- The three places the journey actually happens. Captions name the
                 place, nothing more — no claim is made about which of them a
                 given package includes; that is on each package's own page. --}}
            <div class="row g-4 mt-4">
                @foreach([
                    ['kaaba-tawaf', 'Makkah', 'Tawaf around the Kaaba'],
                    ['arafat', 'Arafat', 'The standing at Jabal al-Rahmah'],
                    ['nabawi-aerial', 'Madinah', 'Al-Masjid an-Nabawi'],
                ] as [$ubKey, $ubPlace, $ubCaption])
                    <div class="col-md-4 reveal-on-scroll">
                        <div class="photo-figure photo-media">
                            <x-photo :key="$ubKey" sizes="(min-width: 768px) 31vw, 92vw" />
                            <div class="photo-caption">
                                <span class="photo-caption-eyebrow">{{ $ubPlace }}</span>
                                <p class="photo-caption-title">{{ $ubCaption }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How to Apply --}}
    <section id="how-to-apply" class="section bg-light">
        <div class="container">
            <div class="text-center mb-5 reveal-on-scroll">
                <h2>From Intention to Departure — We Make the Process Clear</h2>
                <p class="text-secondary mx-auto" style="max-width: 640px;">The Hajj application process can involve multiple requirements and stages. Our team assists pilgrims in understanding the applicable procedures and completing the necessary arrangements.</p>
            </div>
            <div class="row g-4">
                @foreach([
                    ['01', 'Choose Your Hajj Program', 'Explore the available programs and select an option suited to your requirements.'],
                    ['02', 'Submit Your Details', 'Provide the required information and documentation.'],
                    ['03', 'Complete Formalities', 'Our team guides you through applicable booking and administrative requirements.'],
                    ['04', 'Prepare for Hajj', 'Receive important information, schedules and pre-departure guidance.'],
                    ['05', 'Begin Your Sacred Journey', 'Travel with the confidence of experienced coordination and support.'],
                ] as [$num, $title, $desc])
                    <div class="col-md-4 col-lg">
                        <div class="text-center reveal-on-scroll">
                            <div class="fw-bold fs-3 text-secondary">{{ $num }}</div>
                            <h3 class="h6">{{ $title }}</h3>
                            <p class="small text-muted">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Hajj Process timeline --}}
    <section id="hajj-process" class="section">
        <div class="container">
            <div class="text-center mb-5 reveal-on-scroll">
                <h2>Every Stage Planned. Every Detail Considered.</h2>
                <p class="text-secondary mx-auto" style="max-width: 640px;">From the day you register until the day you return home, our team remains committed to helping you navigate each stage of your Hajj journey.</p>
            </div>
            <x-hajj-process-timeline />
        </div>
    </section>

    {{-- Hajj Guidance --}}
    <section id="hajj-guidance" class="section bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-on-scroll">
                    <span class="section-eyebrow">Ritual Guidance</span>
                    <h2>Guidance You Can Rely On</h2>
                    <ul class="text-secondary">
                        <li class="mb-2">Mufti/Aalim available for ritual guidance throughout the journey.</li>
                        <li class="mb-2">Personalized, escorted service at every step of Hajj.</li>
                    </ul>
                </div>
                <div class="col-lg-6 reveal-on-scroll reveal-delay-2">
                    <div class="photo-figure photo-media photo-media--panel split-section-visual">
                        <x-photo key="kaaba-close" sizes="(min-width: 992px) 46vw, 92vw" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Accommodation & Transport --}}
    <section id="accommodation-transport" class="section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-lg-2 reveal-on-scroll">
                    <span class="section-eyebrow">Where You'll Stay</span>
                    <h2>Accommodation &amp; Transport</h2>
                    <ul class="text-secondary">
                        <li class="mb-2">Choice of hotels near Haram in Makkah and Madinah.</li>
                        <li class="mb-2">Best location in Mina, near Jamarat @if($minaCampLocation)— {{ $minaCampLocation }}@endif.</li>
                        <li class="mb-2">Specially designed air-conditioned tents with private bathrooms.</li>
                    </ul>
                    <p class="small text-muted">Exact hotel names, distances and transport arrangements vary by package — see each package's Accommodation &amp; Transportation sections for full detail.</p>
                </div>
                <div class="col-lg-6 order-lg-1 reveal-on-scroll reveal-delay-2">
                    <div class="photo-figure photo-media photo-media--panel split-section-visual">
                        <x-photo key="mina-tents" sizes="(min-width: 992px) 46vw, 92vw" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Next Flight Date --}}
    <section id="next-flight-date" class="section bg-light text-center">
        <div class="container">
            <h2>Next Flight Date</h2>
            @if($nextFlightDate)
                <p class="fs-4 fw-semibold text-primary">{{ $nextFlightDate }}</p>
            @else
                <p class="text-secondary">To be announced — <a href="{{ route('contact') }}">contact us</a> for the latest Hajj 2027 flight schedule.</p>
            @endif
        </div>
    </section>

    {{-- Hajj Packages --}}
    <section class="section">
        <div class="container">
            <div class="text-center mb-5 reveal-on-scroll">
                <h2>Choose the Hajj Experience That Suits You</h2>
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
                    <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-primary btn-lg">View All Hajj Packages</a>
                </div>
            @else
                <x-empty-state icon="bi-bag" photo="kaaba-tawaf">No Hajj packages are published yet. Please check back soon or <a href="{{ route('contact') }}">contact us</a>.</x-empty-state>
            @endif
        </div>
    </section>

    {{-- Why Hajj With Universal Brothers --}}
    <section class="section bg-primary text-white position-relative">
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url('haram-panorama') }}"
                 srcset="{{ \App\Support\SiteImagery::srcset('haram-panorama') }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>
        <div class="container text-center">
            <div class="mx-auto reveal-on-scroll" style="max-width: 720px;">
                <h2>When the Journey Matters This Much, Experience Matters.</h2>
                <p>The success of a Hajj operation depends on hundreds of details working together — from documentation and accommodation to transport, movement schedules and on-ground coordination.</p>
                <p>With decades of experience and thousands of pilgrims served, Universal Brothers brings institutional knowledge, professional planning and personal care to every Hajj operation.</p>
                <p class="fw-semibold fs-5 mb-0">You Focus on Your Ibadah. We Focus on the Journey.</p>
            </div>
        </div>
    </section>

    {{-- FAQs --}}
    <section id="faqs" class="section">
        <div class="container">
            <h2 class="text-center mb-4">Hajj FAQs</h2>
            @if($faqs->isEmpty())
                <x-empty-state icon="bi-question-circle">No Hajj FAQs have been published yet. Please <a href="{{ route('contact') }}">contact us</a> with any questions.</x-empty-state>
            @else
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="accordion faq-accordion" id="hajjFaqAccordion">
                            @foreach($faqs as $i => $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#hajj-faq-{{ $faq->id }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="hajj-faq-{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="hajj-faq-{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#hajjFaqAccordion">
                                        <div class="accordion-body text-secondary rich-text">{!! \App\Support\Content\RichText::render($faq->answer, 'standard') !!}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Register Now --}}
    <section class="section bg-light text-center">
        <div class="container">
            <h2>Ready to Register?</h2>
            <p class="text-secondary mx-auto mb-4" style="max-width: 560px;">Begin your Hajj 2027 registration through our official registration portal.</p>
            <a href="https://hums.akhg.com.pk/HajiReg/HajiLead" target="_blank" rel="noopener" class="btn btn-secondary btn-lg">Register Now</a>
        </div>
    </section>
@endsection
