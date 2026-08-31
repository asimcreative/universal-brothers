@extends('layouts.app')

@section('title', 'Universal Brothers — Hajj, Umrah & Tourism')
@section('meta_description', 'Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator in Karachi, Pakistan. Real Hajj 2027 packages, ' . $stats['years'] . ' years of trusted service, ' . $stats['pilgrims'] . ' pilgrims served.')

@section('content')
    {{-- 1. Hero --}}
    @if($sliders->isNotEmpty())
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach($sliders as $i => $slide)
                    <div class="carousel-item hero-slide {{ $i === 0 ? 'active' : '' }}">
                        <div class="hero-slide-bg parallax-layer" style="background-image: url('{{ Storage::url($slide->image) }}')"></div>
                        <div class="container hero-content text-center py-5">
                            <div class="hero-anim mx-auto" style="max-width: 800px;">
                                <span class="hero-eyebrow">Hajj &middot; Umrah &middot; Tourism</span>
                                <h1 class="display-4 fw-bold">{{ $slide->title }}</h1>
                                @if($slide->subtitle)<p class="lead">{{ $slide->subtitle }}</p>@endif
                                <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                                    @if($slide->cta_label)
                                        <a href="{{ $slide->cta_url }}" class="btn btn-secondary btn-lg">{{ $slide->cta_label }}</a>
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
        <div class="hero-slide">
            <div class="hero-slide-bg parallax-layer" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
            <div class="container hero-content text-center py-5 text-white">
                <div class="hero-anim mx-auto" style="max-width: 800px;">
                    <span class="hero-eyebrow">Hajj &middot; Umrah &middot; Tourism</span>
                    <h1 class="display-4 fw-bold">A Sacred Journey. A Trusted Name.</h1>
                    <p class="lead">Serving the Guests of Allah with Experience, Care &amp; Commitment.</p>
                    <p class="mx-auto" style="max-width: 640px;">For more than {{ $stats['years'] }} years, Universal Brothers has been privileged to facilitate the sacred journeys of thousands of pilgrims — combining meticulous planning, personalized care and dependable on-ground support.</p>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                        @if($hajjCategory)
                            <a href="{{ route('hajj-services') }}" class="btn btn-secondary btn-lg">Explore Hajj Services</a>
                        @endif
                        @if($umrahCategory)
                            <a href="{{ route('umrah-services') }}" class="btn btn-outline-light btn-lg">Plan Your Umrah</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 2. News Ticker --}}
    @if($news->isNotEmpty())
        <div class="news-ticker">
            <div class="container d-flex align-items-center">
                <span class="news-ticker-label"><i class="bi bi-broadcast me-1"></i>Latest News</span>
                <div class="news-ticker-track">
                    @foreach($news as $article)
                        <a href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Trust ticker strip --}}
    <div class="trust-ticker">
        <div class="container text-center">
            <span><i class="bi bi-patch-check-fill me-2"></i>{{ $stats['years'] }} Years of Trust</span>
            <span><i class="bi bi-people-fill me-2"></i>{{ $stats['pilgrims'] }} Pilgrims Served</span>
            @if($stats['mina_camp_location'])<span><i class="bi bi-geo-alt-fill me-2"></i>{{ $stats['mina_camp_location'] }} Mina Camp</span>@endif
            @if($stats['iata_registered'])<span><i class="bi bi-award-fill me-2"></i>IATA Registered Operator</span>@endif
        </div>
    </div>

    {{-- 3. Experience section --}}
    <section class="section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-on-scroll">
                    <span class="section-eyebrow">{{ $stats['years'] }} Years of Experience</span>
                    <h2>Experience That Inspires Confidence</h2>
                    <p class="text-secondary">Universal Brothers has been serving pilgrims for {{ $stats['years'] }} years with one purpose: to make their sacred journey organized, comfortable and spiritually fulfilling.</p>
                    <p class="text-secondary">Our experience extends far beyond bookings and logistics. From pre-departure preparation to assistance in the Holy Lands and the journey home, our team understands the details that make Hajj and Umrah truly seamless.</p>
                </div>
                <div class="col-lg-6 text-center reveal-on-scroll reveal-delay-2">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="{{ $counters['years'] }}">0</div>
                        <div class="small text-uppercase fw-semibold">Years of Experience</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Pilgrims-served trust section --}}
    <section class="section bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-lg-2 reveal-on-scroll">
                    <span class="section-eyebrow">One Enduring Trust</span>
                    <h2>Thousands of Journeys. One Enduring Trust.</h2>
                    <p class="text-secondary">Behind every number is a pilgrim, a family and a sacred journey entrusted to us.</p>
                    <p class="text-secondary">Over the years, Universal Brothers has had the honour of serving <strong>{{ $stats['pilgrims'] }} pilgrims</strong>, earning relationships that often continue across generations.</p>
                </div>
                <div class="col-lg-6 order-lg-1 text-center reveal-on-scroll reveal-delay-2">
                    <div class="stat-tile">
                        <div class="stat-number" data-counter-target="{{ $counters['pilgrims'] }}">0</div>
                        <div class="small text-uppercase fw-semibold">Pilgrims Served</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Awards section --}}
    <section class="section">
        <div class="container">
            <div class="text-center mb-5 reveal-on-scroll">
                <span class="section-eyebrow d-flex justify-content-center">Recognized for Excellence</span>
                <h2>Recognized for Excellence. Remembered for Service.</h2>
                <p class="text-secondary mx-auto" style="max-width: 640px;">Our commitment to quality, service and professional excellence has earned Universal Brothers <strong>{{ $stats['awards_count'] }} awards and recognitions</strong> over the years. For us, every award represents something greater — the confidence placed in us by our pilgrims, partners and industry.</p>
                <div class="stat-tile d-inline-block mt-2">
                    <div class="stat-number" data-counter-target="{{ $counters['industry_awards'] }}">0</div>
                    <div class="small text-uppercase fw-semibold">Awards &amp; Recognitions</div>
                </div>
            </div>
            @if($awards->isNotEmpty())
                <div class="row g-4 mb-4">
                    @foreach($awards as $award)
                        <x-award-badge :award="$award" />
                    @endforeach
                </div>
            @endif
            <div class="text-center">
                <a href="{{ route('awards') }}" class="btn btn-outline-primary">Discover Our Achievements</a>
            </div>
        </div>
    </section>

    {{-- 6. Personalized Services section — visual split (image/visual one side, content the other) --}}
    <section class="section bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-on-scroll">
                    <div class="split-section-visual visual-placeholder" style="aspect-ratio: 4/3;">
                        <i class="bi bi-person-hearts"></i>
                        <span>Personalized Guidance</span>
                    </div>
                </div>
                <div class="col-lg-6 reveal-on-scroll reveal-delay-2">
                    <span class="section-eyebrow">Personalized Care</span>
                    <h2>Because No Two Sacred Journeys Are the Same</h2>
                    <p class="text-secondary">Every pilgrim has different expectations, requirements and circumstances. Our personalized approach allows us to provide carefully coordinated solutions covering travel arrangements, accommodation, transportation, guidance and on-ground assistance — so our guests can devote greater attention to the purpose of their journey.</p>
                    <p class="fw-semibold fs-5 mb-0">Personal Attention. Professional Management. Spiritual Peace of Mind.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 7. Why Universal Brothers --}}
    <section class="section">
        <div class="container">
            <div class="text-center mb-5 reveal-on-scroll">
                <span class="section-eyebrow d-flex justify-content-center">Why Universal Brothers</span>
                <h2>A Name Built on Trust. A Service Built Around You.</h2>
                <p class="text-secondary mx-auto" style="max-width: 640px;">For more than {{ $stats['years'] }} years, Universal Brothers has combined experience with personal care to deliver thoughtfully managed Hajj and Umrah journeys.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-1">
                        <div class="icon-pillar-icon"><i class="bi bi-award"></i></div>
                        <h3 class="h6">{{ $stats['years'] }} Years of Experience</h3>
                        <p class="small text-muted mb-0">Decades of specialized Hajj and Umrah expertise.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-2">
                        <div class="icon-pillar-icon"><i class="bi bi-people-fill"></i></div>
                        <h3 class="h6">{{ $stats['pilgrims'] }} Hajis Served</h3>
                        <p class="small text-muted mb-0">Thousands of pilgrims have travelled under our care.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-3">
                        <div class="icon-pillar-icon"><i class="bi bi-trophy-fill"></i></div>
                        <h3 class="h6">{{ $stats['awards_count'] }} Awards</h3>
                        <p class="small text-muted mb-0">Recognition for service and professional excellence.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-4">
                        <div class="icon-pillar-icon"><i class="bi bi-person-check-fill"></i></div>
                        <h3 class="h6">Personalized Assistance</h3>
                        <p class="small text-muted mb-0">Individual attention before, during and after the journey.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-5">
                        <div class="icon-pillar-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <h3 class="h6">Experienced Team</h3>
                        <p class="small text-muted mb-0">Professionals who understand the complexities of pilgrimage travel.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="icon-pillar reveal-on-scroll reveal-delay-6">
                        <div class="icon-pillar-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <h3 class="h6">On-Ground Support</h3>
                        <p class="small text-muted mb-0">Assistance where it matters most throughout your sacred journey.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 8. Hajj Feature section --}}
    @if($hajjCategory)
        <section class="section bg-primary text-white">
            <div class="container">
                <div class="row align-items-center g-5 mb-5">
                    <div class="col-lg-6 reveal-on-scroll">
                        <span class="section-eyebrow">Hajj 2027</span>
                        <h2>Hajj — The Journey of a Lifetime</h2>
                        <p>Hajj is more than a destination. It is a profound act of faith, devotion and submission.</p>
                        <p>At Universal Brothers, we understand the responsibility that comes with facilitating this sacred obligation. Our Hajj services are designed to manage the practical complexities of the journey so pilgrims can focus on what matters most — their Ibadah.</p>
                        <a href="{{ route('hajj-services') }}" class="btn btn-secondary btn-lg mt-2">Explore Hajj 2027</a>
                    </div>
                    <div class="col-lg-6 text-center reveal-on-scroll reveal-delay-2">
                        <div class="stat-tile">
                            <div class="stat-number" data-counter-target="{{ $counters['hajj_packages'] }}">0</div>
                            <div class="small text-uppercase fw-semibold">Hajj 2027 Packages</div>
                        </div>
                    </div>
                </div>
                @if($hajjPackages->isNotEmpty())
                    <h3 class="h4 mb-4">Featured Hajj Packages</h3>
                    <div class="row g-4">
                        @foreach($hajjPackages as $package)
                            <div class="col-md-6 col-lg-4">
                                <x-package-card :package="$package" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- 9. Umrah Feature section --}}
    @if($umrahCategory)
        <section class="section">
            <div class="container">
                <div class="row align-items-center g-5 mb-5">
                    <div class="col-lg-6 order-lg-2 reveal-on-scroll">
                        <div class="split-section-visual visual-placeholder" style="aspect-ratio: 4/3;">
                            <i class="bi bi-moon-stars-fill"></i>
                            <span>Umrah Journeys</span>
                        </div>
                    </div>
                    <div class="col-lg-6 order-lg-1 reveal-on-scroll reveal-delay-2">
                        <span class="section-eyebrow">Umrah, Anytime</span>
                        <h2>Answer the Call. Begin Your Journey.</h2>
                        <p class="text-secondary">Whether travelling individually, with family or as part of a group, Universal Brothers offers carefully planned Umrah solutions designed around your requirements.</p>
                        <p class="text-secondary">From flights and accommodation to transfers, Ziyarat and on-ground coordination, we bring every element together for a smoother and more meaningful experience.</p>
                        <a href="{{ route('packages.category', 'umrah') }}" class="btn btn-primary btn-lg mt-2">Explore Umrah Packages</a>
                    </div>
                </div>
                @if($umrahPackages->isNotEmpty())
                    <h3 class="h4 mb-4">Featured Umrah Packages</h3>
                    <div class="row g-4">
                        @foreach($umrahPackages as $package)
                            <div class="col-md-6 col-lg-4">
                                <x-package-card :package="$package" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- 10. Testimonial video section --}}
    @if($videoTestimonials->isNotEmpty() || $textTestimonials->isNotEmpty())
        <section class="section bg-light">
            <div class="container">
                <div class="text-center mb-5 reveal-on-scroll">
                    <span class="section-eyebrow d-flex justify-content-center">Pilgrim Stories</span>
                    <h2>Their Journeys. Their Words.</h2>
                    <p class="text-secondary mx-auto" style="max-width: 640px;">The most meaningful measure of our service is the experience of those who travelled with us. Hear directly from our Hajis and Umrah pilgrims as they share their experiences, memories and the service they received throughout their sacred journey.</p>
                </div>
                <div class="row g-4">
                    @foreach($videoTestimonials as $testimonial)
                        <div class="col-md-6 col-lg-4">
                            <x-video-testimonial-card :testimonial="$testimonial" />
                        </div>
                    @endforeach
                    @foreach($textTestimonials->take(6 - $videoTestimonials->count()) as $testimonial)
                        <div class="col-md-6 col-lg-4">
                            <x-testimonial-card :testimonial="$testimonial" />
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('testimonials') }}" class="btn btn-outline-primary">Watch Pilgrim Stories</a>
                </div>
            </div>
        </section>
    @endif

    {{-- 11. Affiliations section --}}
    @if($affiliations->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="text-center mb-5 reveal-on-scroll">
                    <span class="section-eyebrow d-flex justify-content-center">Trusted Institutions</span>
                    <h2>Connected with Trusted Institutions</h2>
                    <p class="text-secondary mx-auto" style="max-width: 640px;">Our professional affiliations and industry relationships reflect our commitment to responsible operations, established standards and dependable travel services.</p>
                </div>
                <div class="row g-4 justify-content-center align-items-center">
                    @foreach($affiliations as $affiliation)
                        <x-affiliation-badge :affiliation="$affiliation" />
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('affiliations') }}" class="btn btn-outline-primary">View Our Affiliations</a>
                </div>
            </div>
        </section>
    @endif

    {{-- 12. Final CTA section --}}
    <section class="section final-cta bg-primary text-white text-center">
        <div class="container">
            <span class="section-eyebrow d-flex justify-content-center">Speak to Universal Brothers</span>
            <h2>Your Sacred Journey Begins With a Conversation</h2>
            <p class="mx-auto mb-4" style="max-width: 640px;">Whether you are preparing for Hajj, planning Umrah or simply need guidance before making a decision, our experienced team is ready to assist you.</p>
            <p class="fw-semibold fs-5">Speak to Universal Brothers Today</p>
            <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                @if($hajjCategory)
                    <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg">Hajj Enquiry</a>
                @endif
                @if($umrahCategory)
                    <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Umrah Enquiry</a>
                @endif
                <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Contact Our Team</a>
            </div>
        </div>
    </section>
@endsection
