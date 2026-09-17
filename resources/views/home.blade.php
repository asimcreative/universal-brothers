@extends('layouts.app')

@section('title', 'Universal Brothers — Hajj, Umrah & Tourism')
@section('meta_description', 'Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator in Karachi, Pakistan. Real Hajj 2027 packages, ' . $stats['years'] . ' years of trusted service, ' . $stats['pilgrims'] . ' pilgrims served.')

@section('content')
    {{--
        Section order and band colours follow the reference template measured at
        1440px (ref_probe.json): photograph, teal, greige, teal, copper, teal,
        ivory, ivory, greige, teal, ivory, photograph. Every value shown still
        comes from the CMS and the packages tables — only the arrangement is
        borrowed.
    --}}

    {{-- 1. Hero — centred over a full-height photograph -------------------- --}}
    @if($sliders->isNotEmpty())
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach($sliders as $i => $slide)
                    <div class="carousel-item hero-slide hero-editorial {{ $i === 0 ? 'active' : '' }}">
                        <div class="hero-slide-bg parallax-layer" style="background-image: url('{{ Storage::url($slide->image) }}')"></div>
                        <div class="container hero-content py-5">
                            <div class="hero-anim">
                                <span class="hero-eyebrow">Hajj &middot; Umrah &middot; Tourism</span>
                                <h1 class="hero-title">{{ $slide->title }}</h1>
                                @if($slide->subtitle)<p class="hero-lead">{{ $slide->subtitle }}</p>@endif
                                <div class="hero-actions">
                                    @if($slide->cta_label)
                                        <a href="{{ $slide->cta_url }}" class="btn btn-secondary btn-lg">{{ $slide->cta_label }}<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                    @endif
                                    @if($slide->secondary_cta_label)
                                        <a href="{{ $slide->secondary_cta_url }}" class="btn btn-outline-light btn-lg">{{ $slide->secondary_cta_label }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($sliders->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            @endif
        </div>
    @else
        <section class="hero-slide hero-editorial">
            <div class="ub-photo-bg ub-photo-bg--centered parallax-layer" aria-hidden="true">
                <img src="{{ \App\Support\SiteImagery::url('kaaba-tawaf') }}"
                     srcset="{{ \App\Support\SiteImagery::srcset('kaaba-tawaf') }}"
                     sizes="100vw" alt="" loading="eager" fetchpriority="high" decoding="async">
            </div>

            <div class="container hero-content text-white">
                <div class="hero-anim">
                    <span class="hero-eyebrow">Hajj &middot; Umrah &middot; Tourism</span>
                    <h1 class="hero-title">A Sacred Journey.<br><span class="hero-title-accent">A Trusted Name.</span></h1>
                    <p class="hero-lead">Serving the Guests of Allah with Experience, Care &amp; Commitment.</p>
                    <p class="hero-copy">For more than {{ $stats['years'] }} years, we have planned, guided and supported the sacred journeys of thousands of pilgrims.</p>
                    <div class="hero-actions">
                        @if($hajjCategory)
                            <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-secondary btn-lg">Explore Hajj 2027<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endif
                        @if($umrahCategory)
                            <a href="{{ route('umrah-services') }}" class="btn btn-outline-light btn-lg">Plan Your Umrah</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- 2. Credibility facts ---------------------------------------------- --}}
    <x-trust-strip :stats="$stats" :counters="$counters" />

    {{-- 3. Services — greige band, wide dark cards ------------------------- --}}
    <section class="section pt-band-sand">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-eyebrow">What we do</span>
                <h2 class="pt-display">Our <span class="pt-accent">Premium Services</span></h2>
            </div>

            <div class="row g-4">
                @if($hajjCategory)
                    <div class="col-lg-4 col-md-6 reveal-on-scroll reveal-delay-1">
                        <a href="{{ route('hajj-services') }}" class="pt-service-card">
                            <div class="pt-service-media"><x-photo key="haram-dusk" sizes="(min-width: 992px) 12vw, 30vw" /></div>
                            <div class="pt-service-body">
                                <h3>Hajj 2027</h3>
                                <p>Real 1448 AH itineraries and our own ground team.</p>
                            </div>
                            <span class="pt-service-go"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </a>
                    </div>
                @endif
                @if($umrahCategory)
                    <div class="col-lg-4 col-md-6 reveal-on-scroll reveal-delay-2">
                        <a href="{{ route('umrah-services') }}" class="pt-service-card">
                            <div class="pt-service-media"><x-photo key="nabawi-aerial" sizes="(min-width: 992px) 12vw, 30vw" /></div>
                            <div class="pt-service-body">
                                <h3>Umrah</h3>
                                <p>All year round, arranged around your dates.</p>
                            </div>
                            <span class="pt-service-go"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </a>
                    </div>
                @endif
                @if($tourismCategory)
                    <div class="col-lg-4 col-md-6 reveal-on-scroll reveal-delay-3">
                        <a href="{{ route('packages.category', 'tourism') }}" class="pt-service-card">
                            <div class="pt-service-media"><x-photo key="hunza-attabad" sizes="(min-width: 992px) 12vw, 30vw" /></div>
                            <div class="pt-service-body">
                                <h3>Tourism</h3>
                                <p>Northern Pakistan and destinations abroad.</p>
                            </div>
                            <span class="pt-service-go"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                        </a>
                    </div>
                @endif
            </div>

            <p class="pt-services-note">Visas, flights, hotels and complete ziyarat guidance — every part of the journey handled by the same team.
                @if($hajjCategory)<a href="{{ route('hajj-services') }}">See all services</a>@endif
            </p>
        </div>
    </section>

    {{-- 4. Package finder — teal band -------------------------------------- --}}
    @if($hajjCategory || $umrahCategory)
        <section class="section pt-finder">
            <div class="container">
                <div class="text-center mb-4">
                    <span class="section-eyebrow">Packages</span>
                    <h2 class="pt-display">Find Your <span class="pt-accent">Perfect Package</span></h2>
                    <p class="pt-lead">Tell us how long you can travel and what you have budgeted. We will show you the packages that match.</p>
                </div>

                <form method="GET" action="{{ route('packages.category', 'hajj') }}" class="pt-finder-bar" id="packageFinder">
                    <div class="pt-finder-field">
                        <label for="finder-service">Journey</label>
                        <select id="finder-service" class="form-select" data-finder-service>
                            @if($hajjCategory)<option value="{{ route('packages.category', 'hajj') }}">Hajj</option>@endif
                            @if($umrahCategory)<option value="{{ route('packages.category', 'umrah') }}">Umrah</option>@endif
                            @if($tourismCategory)<option value="{{ route('packages.category', 'tourism') }}">Tourism</option>@endif
                        </select>
                    </div>

                    <div class="pt-finder-field">
                        <label for="finder-days">Duration</label>
                        <select name="days" id="finder-days" class="form-select">
                            <option value="">Any length</option>
                            @foreach($filterDurations as $days)
                                <option value="{{ $days }}">{{ $days }} days</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-finder-field">
                        <label for="finder-budget">Budget up to (US$)</label>
                        <input type="number" name="price_max" id="finder-budget" class="form-control" min="0" step="500" placeholder="e.g. 12000" inputmode="numeric">
                    </div>

                    <button type="submit" class="btn btn-secondary">Search packages<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </form>
            </div>
        </section>
    @endif

    {{-- 5. Copper marquee of the real award names -------------------------- --}}
    {{-- The list is rendered twice and the track animates by exactly -50%, so
         the loop is seamless; the second copy is hidden from assistive tech. --}}
    @if($awards->isNotEmpty())
        <div class="pt-strip" role="region" aria-label="Awards and recognitions">
            <div class="container">
                <div class="pt-strip-track">
                    @foreach($awards as $award)
                        <span class="pt-strip-item"><i class="bi bi-asterisk" aria-hidden="true"></i>{{ $award->name }}</span>
                    @endforeach
                    @foreach($awards as $award)
                        <span class="pt-strip-item" aria-hidden="true"><i class="bi bi-asterisk"></i>{{ $award->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 6. Hajj 2027 — teal band over Mina --------------------------------- --}}
    @if($hajjCategory)
        <section class="section pt-hajj-band position-relative">
            <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
                <img src="{{ \App\Support\SiteImagery::url('mina-tents') }}"
                     srcset="{{ \App\Support\SiteImagery::srcset('mina-tents') }}"
                     sizes="100vw" alt="" loading="lazy" decoding="async">
            </div>

            <div class="container position-relative">
                <div class="row align-items-end g-4 mb-5">
                    <div class="col-lg-7 reveal-on-scroll">
                        <span class="section-eyebrow">Hajj 2027 &middot; 1448 AH</span>
                        <h2 class="pt-display">Hajj — The Journey<br><span class="pt-accent">of a Lifetime</span></h2>
                        <p>Our Hajj services are designed to manage the practical complexities of the journey so pilgrims can focus on what matters most — their Ibadah.</p>
                    </div>
                    <div class="col-lg-5 text-lg-end reveal-on-scroll reveal-delay-2">
                        <div class="pt-count">
                            <x-stat-number :display="(string) $counters['hajj_packages']" :target="$counters['hajj_packages']" />
                            <span>Hajj 2027 packages with real 1448 AH itineraries, hotels and pricing</span>
                        </div>
                    </div>
                </div>

                @if($hajjPackages->isNotEmpty())
                    <h3 class="pt-band-subtitle">Featured Hajj Packages</h3>
                    <div class="row g-4">
                        @foreach($hajjPackages as $package)
                            <div class="col-md-6 col-xl-4">
                                <x-package-card :package="$package" />
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="text-center mt-5">
                    <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-secondary btn-lg">See all {{ $counters['hajj_packages'] }} Hajj packages<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    @endif

    {{-- 7. Umrah — ivory band ---------------------------------------------- --}}
    @if($umrahCategory && $umrahPackages->isNotEmpty())
        <section class="section pt-band-ivory">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-eyebrow">Umrah, any time of year</span>
                    <h2 class="pt-display">Answer the Call. <span class="pt-accent">Begin Your Journey.</span></h2>
                    <p class="pt-lead">Individual, family and group Umrah arranged around your preferred dates, duration, accommodation and travel requirements.</p>
                </div>

                <div class="row g-4">
                    @foreach($umrahPackages as $package)
                        <div class="col-md-6 col-xl-4">
                            <x-package-card :package="$package" />
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-5">
                    <a href="{{ route('umrah-services') }}" class="btn btn-outline-primary">Explore Umrah services<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    @endif

    {{-- 8. Impact — the page's one loud heading ---------------------------- --}}
    <section class="section pt-impact">
        <div class="container text-center">
            <span class="section-eyebrow">Our impact</span>
            <h2 class="pt-display-xl">Trusted by Pilgrims<br><span class="pt-accent">Around the World</span></h2>

            <div class="pt-stat-pill">
                <x-stat-number :display="$stats['pilgrims']" :target="$counters['pilgrims']" />
                <span>Hajis served</span>
            </div>

            <p class="pt-lead">Our Hajj and Umrah programmes are arranged for pilgrims travelling from Pakistan and for families joining from abroad — with the same documentation support, accommodation standards and on-ground assistance wherever the journey begins.</p>

            <div class="row g-4 mt-2 text-start">
                <div class="col-md-4 reveal-on-scroll reveal-delay-1">
                    <div class="pt-reach">
                        <i class="bi bi-globe2" aria-hidden="true"></i>
                        <h3>Departures from Pakistan &amp; overseas</h3>
                        <p>Hajj 2027 packages are offered to overseas pilgrims alongside our domestic programme.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-on-scroll reveal-delay-2">
                    <div class="pt-reach">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        <h3>Guidance in your language</h3>
                        <p>Urdu and English speaking coordinators accompany our groups throughout the journey.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-on-scroll reveal-delay-3">
                    <div class="pt-reach">
                        <i class="bi bi-headset" aria-hidden="true"></i>
                        <h3>Support before, during &amp; after</h3>
                        <p>Assistance from first enquiry through to the journey home, not only at booking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 9. Why Universal Brothers — greige band ---------------------------- --}}
    <section class="section pt-band-sand">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5 reveal-on-scroll">
                    <div class="pt-stack">
                        <div class="pt-stack-back"><x-photo key="kaaba-close" sizes="(min-width: 992px) 30vw, 60vw" /></div>
                        <div class="pt-stack-front"><x-photo key="nabawi-dome" sizes="(min-width: 992px) 26vw, 55vw" /></div>
                    </div>
                </div>

                <div class="col-lg-7 reveal-on-scroll reveal-delay-2">
                    <span class="section-eyebrow">Why Universal Brothers?</span>
                    <h2 class="pt-display">A Name Built on Trust. <span class="pt-accent">A Service Built Around You.</span></h2>
                    <p>For more than {{ $stats['years'] }} years, Universal Brothers has combined experience with personal care to deliver thoughtfully managed Hajj and Umrah journeys. Every pilgrim has different expectations and circumstances, so travel, accommodation, transport, guidance and on-ground assistance are coordinated around them.</p>

                    <div class="row g-4 mt-2">
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-clock-history" aria-hidden="true"></i><div><h3>{{ $stats['years'] }} Years of Experience</h3><p>Decades of specialized Hajj and Umrah expertise.</p></div></div></div>
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-people" aria-hidden="true"></i><div><h3>{{ $stats['pilgrims'] }} Hajis Served</h3><p>Thousands of pilgrims have travelled under our care.</p></div></div></div>
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-award" aria-hidden="true"></i><div><h3>{{ $stats['awards_count'] }} Awards</h3><p>Recognition for service and professional excellence.</p></div></div></div>
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-person-heart" aria-hidden="true"></i><div><h3>Personalized Assistance</h3><p>Individual attention before, during and after the journey.</p></div></div></div>
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-briefcase" aria-hidden="true"></i><div><h3>Experienced Team</h3><p>Professionals who understand the complexities of pilgrimage travel.</p></div></div></div>
                        <div class="col-sm-6"><div class="pt-point"><i class="bi bi-geo-alt" aria-hidden="true"></i><div><h3>On-Ground Support</h3><p>Assistance where it matters most throughout your sacred journey.</p></div></div></div>
                    </div>

                    <a href="{{ url('/about-us') }}" class="btn btn-secondary mt-4">Read more about us<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>

    {{-- 10. Pilgrim voices — teal band ------------------------------------- --}}
    @if($textTestimonials->isNotEmpty() || $videoTestimonials->isNotEmpty())
        <section class="section pt-band-dark">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-eyebrow">Pilgrim stories</span>
                    <h2 class="pt-display">Their Journeys. <span class="pt-accent">Their Words.</span></h2>
                </div>

                @if($videoTestimonials->isNotEmpty())
                    <div class="row g-4 mb-4">
                        @foreach($videoTestimonials->take(3) as $testimonial)
                            <div class="col-md-4"><x-video-testimonial-card :testimonial="$testimonial" /></div>
                        @endforeach
                    </div>
                @endif

                @if($textTestimonials->isNotEmpty())
                    <div class="row g-4">
                        @foreach($textTestimonials->take(3) as $testimonial)
                            <div class="col-md-4"><x-testimonial-card :testimonial="$testimonial" /></div>
                        @endforeach
                    </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('testimonials') }}" class="btn btn-outline-light">Read all testimonials<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    @endif

    {{-- 11. Recognition and affiliations — ivory band ---------------------- --}}
    <section class="section pt-band-ivory">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-eyebrow">Recognized for excellence</span>
                <h2 class="pt-display">Recognised, Accredited <span class="pt-accent">&amp; Well Connected</span></h2>
                <p class="pt-lead">Our commitment to quality and service has earned Universal Brothers awards and recognitions over the years.</p>

                <div class="pt-stat-pill">
                    <x-stat-number :display="$stats['awards_count']" :target="$counters['industry_awards']" />
                    <span>Awards &amp; recognitions</span>
                </div>
            </div>

            @if($awards->isNotEmpty())
                <div class="row g-4">
                    @foreach($awards->take(6) as $award)
                        <x-award-badge :award="$award" />
                    @endforeach
                </div>
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('awards') }}" class="btn btn-outline-primary">View all awards<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>

            @if($affiliations->isNotEmpty())
                <div class="pt-affiliations">
                    <p class="pt-affiliations-label">Connected with trusted institutions</p>
                    <div class="row g-3 justify-content-center">
                        @foreach($affiliations as $affiliation)
                            <x-affiliation-badge :affiliation="$affiliation" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- 12. Talk to us ----------------------------------------------------- --}}
    <section class="section final-cta text-center position-relative">
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url('haram-panorama') }}"
                 srcset="{{ \App\Support\SiteImagery::srcset('haram-panorama') }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>

        <div class="container position-relative">
            <span class="section-eyebrow">Speak to Universal Brothers</span>
            <h2 class="pt-display">Your Sacred Journey Begins<br>With a <span class="pt-accent">Conversation</span></h2>
            <p class="pt-lead">Whether you are preparing for Hajj, planning Umrah or simply need guidance before making a decision, our experienced team is ready to assist you.</p>
            <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                @if($hajjCategory)
                    <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg">Hajj enquiry<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                @endif
                @if($umrahCategory)
                    <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Umrah enquiry</a>
                @endif
                <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Contact our team</a>
            </div>
        </div>
    </section>

    {{-- News stays available to the CMS, below the fold, as a quiet line. --}}
    @if($news->isNotEmpty())
        <div class="news-ticker">
            <div class="container d-flex align-items-center">
                <span class="news-ticker-label"><i class="bi bi-broadcast me-1" aria-hidden="true"></i>Latest news</span>
                <div class="news-ticker-track">
                    @foreach($news as $article)
                        <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
