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
        {{--
            No slider rows exist yet, so this is what every visitor actually
            sees. It previously rendered as a flat navy rectangle with centred
            text and roughly 900px of empty colour — the single weakest thing
            on the site. It is now a composed editorial hero: a real photograph
            of tawaf at Hajj, a left-aligned type hierarchy, and a credentials
            panel filling what used to be dead space on the right. Every
            credential shown is real, admin-editable site data — nothing here
            is invented. Uploading slider rows through the admin still takes
            over completely, which is the branch above.
        --}}
        <section class="hero-slide hero-editorial">
            {{-- The real thing the whole site is about: pilgrims performing
                 tawaf during Hajj. Loaded eagerly with a high fetch priority
                 because it IS the largest contentful paint — lazy-loading a
                 hero is the classic way to make a page feel slower while
                 looking like an optimisation. The scrim in `_photography.scss`
                 is what keeps the white type at WCAG AA over it. --}}
            <div class="ub-photo-bg parallax-layer" aria-hidden="true">
                <img src="{{ \App\Support\SiteImagery::url('kaaba-tawaf') }}"
                     srcset="{{ \App\Support\SiteImagery::srcset('kaaba-tawaf') }}"
                     sizes="100vw" alt="" loading="eager" fetchpriority="high" decoding="async">
            </div>

            <div class="container hero-content text-white">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7 hero-anim">
                        <span class="hero-eyebrow">Hajj &middot; Umrah &middot; Tourism</span>
                        <h1 class="hero-title">A Sacred Journey.<br><span class="hero-title-accent">A Trusted Name.</span></h1>
                        <p class="hero-lead">Serving the Guests of Allah with Experience, Care &amp; Commitment.</p>
                        <p class="hero-copy">For more than {{ $stats['years'] }} years, Universal Brothers has been privileged to facilitate the sacred journeys of thousands of pilgrims — combining meticulous planning, personalized care and dependable on-ground support.</p>
                        <div class="hero-actions">
                            @if($hajjCategory)
                                <a href="{{ route('hajj-services') }}" class="btn btn-secondary btn-lg">Explore Hajj Services</a>
                            @endif
                            @if($umrahCategory)
                                <a href="{{ route('umrah-services') }}" class="btn btn-outline-light btn-lg">Plan Your Umrah</a>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-5 hero-anim">
                        <div class="hero-credentials">
                            <span class="hero-credentials-eyebrow">Why pilgrims trust us</span>
                            <ul class="hero-credentials-list">
                                <li>
                                    <span class="hero-credential-figure">{{ $stats['years'] }}</span>
                                    <span class="hero-credential-label">Years serving pilgrims</span>
                                </li>
                                <li>
                                    <span class="hero-credential-figure">{{ $stats['pilgrims'] }}</span>
                                    <span class="hero-credential-label">Hajis served</span>
                                </li>
                                <li>
                                    <span class="hero-credential-figure">{{ $stats['awards_count'] }}</span>
                                    <span class="hero-credential-label">Awards &amp; recognitions</span>
                                </li>
                            </ul>
                            <div class="hero-credentials-foot">
                                @if($stats['iata_registered'])
                                    <span><i class="bi bi-patch-check-fill"></i>IATA Registered</span>
                                @endif
                                <span><i class="bi bi-shield-check"></i>Hajj Licence No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}</span>
                                @if($stats['mina_camp_location'])
                                    <span><i class="bi bi-geo-alt-fill"></i>Mina Camp &mdash; {{ $stats['mina_camp_location'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
                <div class="col-lg-6 reveal-on-scroll reveal-delay-2">
                    {{-- Matches the pilgrims-served figure below: the same
                         approved statistic and the same count-up behaviour, now
                         on a real photograph instead of a generated motif panel,
                         so the two trust sections read as one design. --}}
                    <div class="photo-figure photo-media photo-media--panel stat-photo">
                        <x-photo key="jamarat" sizes="(min-width: 992px) 46vw, 92vw" />
                        <div class="photo-caption stat-photo-caption">
                            <span class="photo-caption-eyebrow">Years of Experience</span>
                            <x-stat-number
                                :display="$stats['years']"
                                :target="$counters['years']"
                                class="stat-photo-figure" />
                            <span class="photo-caption-meta">Specialised Hajj and Umrah operations, not general travel.</span>
                        </div>
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
                <div class="col-lg-6 order-lg-1 reveal-on-scroll reveal-delay-2">
                    {{-- The statistic now sits ON a real photograph of pilgrims
                         rather than on a generated motif panel. The figure and
                         its label are unchanged approved site data; only the
                         surface behind them is different. --}}
                    <div class="photo-figure photo-media photo-media--panel stat-photo">
                        <x-photo key="mina-tents" sizes="(min-width: 992px) 46vw, 92vw" />
                        <div class="photo-caption stat-photo-caption">
                            <span class="photo-caption-eyebrow">Pilgrims Served</span>
                            {{-- Still `x-stat-number`, not a plain string. Replacing
                                 the generated stat panel with a photograph must not
                                 quietly drop the count-up animation, nor the
                                 server-rendered approved figure that keeps the
                                 number correct for crawlers and for anyone whose
                                 JavaScript never runs. --}}
                            <x-stat-number
                                :display="$stats['pilgrims']"
                                :target="$counters['pilgrims']"
                                class="stat-photo-figure" />
                            <span class="photo-caption-meta">Relationships that often continue across generations.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{--
        4b. Trusted by Pilgrims Around the World.

        An approved homepage block (source flow item d, with its two approved
        sub-lines "Your Sacred Journey, Wherever You Are" and "Serving Pilgrims
        Across the Globe") that had never been implemented. No country counts or
        office locations are claimed — the only concrete facts stated here are
        ones the project already holds: the Hajj 2027 programme is sold to
        overseas pilgrims as well as from Pakistan, and the operator is IATA
        registered under the licence number recorded in settings.
    --}}
    <section class="section global-reach">
        {{-- Pilgrims arriving from around the world is what this section is
             about, so it shows the Haram full of them rather than a gradient. --}}
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url('haram-dusk') }}"
                 srcset="{{ \App\Support\SiteImagery::srcset('haram-dusk') }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>
        <div class="container global-reach-content">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 reveal-on-scroll">
                    <span class="section-eyebrow d-inline-flex">Trusted by Pilgrims Around the World</span>
                    <h2 class="global-reach-title">Your Sacred Journey, Wherever You Are</h2>
                    <p class="global-reach-lead">Serving Pilgrims Across the Globe</p>
                    <p class="global-reach-copy">Our Hajj and Umrah programmes are arranged for pilgrims travelling from Pakistan and for families joining from abroad — with the same documentation support, accommodation standards and on-ground assistance wherever the journey begins.</p>
                </div>
            </div>

            <div class="row g-4 mt-2 justify-content-center">
                <div class="col-md-4 reveal-on-scroll reveal-delay-1">
                    <div class="reach-pillar">
                        <i class="bi bi-globe2"></i>
                        <h3>Departures From Pakistan &amp; Overseas</h3>
                        <p>Hajj 2027 packages are offered to overseas pilgrims alongside our domestic programme.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-on-scroll reveal-delay-2">
                    <div class="reach-pillar">
                        <i class="bi bi-translate"></i>
                        <h3>Guidance In Your Language</h3>
                        <p>Urdu and English speaking coordinators accompany our groups throughout the journey.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-on-scroll reveal-delay-3">
                    <div class="reach-pillar">
                        <i class="bi bi-headset"></i>
                        <h3>Support Before, During &amp; After</h3>
                        <p>Assistance from first enquiry through to the journey home, not only at booking.</p>
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
                    <x-stat-number :display="$stats['awards_count']" :target="$counters['industry_awards']" />
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

    {{--
        5b. Servicing — Hajj 2027 / Umrah / Tourism.

        Approved homepage item (f). Only the Hajj and Umrah thirds existed, as
        separate long-form sections much further down the page; there was no
        three-way entry point at all, and Tourism was absent from the homepage
        entirely despite being one of the company's three services.
    --}}
    <section class="section bg-light">
        <div class="container">
            <div class="section-heading-block mx-auto text-center mb-5 reveal-on-scroll">
                <span class="section-eyebrow d-inline-flex">What We Do</span>
                <h2>Three Services. One Standard of Care.</h2>
            </div>

            <div class="row g-4">
                @if($hajjCategory)
                    <div class="col-md-4 reveal-on-scroll reveal-delay-1">
                        <a href="{{ route('hajj-services') }}" class="service-panel">
                            {{-- Each service now shows the thing it actually is: the Kaaba
                                 at Hajj, Masjid an-Nabawi for Umrah, and a real Pakistani
                                 destination for tourism. Three different photographs, so the
                                 row reads as three services rather than three motifs. --}}
                            <div class="photo-media photo-media--16x9">
                                <x-photo key="haram-dusk" sizes="(min-width: 768px) 31vw, 92vw" />
                            </div>
                            <div class="service-panel-body">
                                <span class="service-panel-eyebrow">Hajj 2027 &middot; 1448 AH</span>
                                <h3 class="service-panel-title">Hajj</h3>
                                <p class="service-panel-copy">Complete Hajj programmes with real itineraries, Makkah and Madinah accommodation, Mina and Arafat arrangements and full on-ground support.</p>
                                <span class="service-panel-cta">Explore Hajj Services<i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                            </div>
                        </a>
                    </div>
                @endif

                @if($umrahCategory)
                    <div class="col-md-4 reveal-on-scroll reveal-delay-2">
                        <a href="{{ route('umrah-services') }}" class="service-panel">
                            <div class="photo-media photo-media--16x9">
                                <x-photo key="nabawi-aerial" sizes="(min-width: 768px) 31vw, 92vw" />
                            </div>
                            <div class="service-panel-body">
                                <span class="service-panel-eyebrow">Any Time of Year</span>
                                <h3 class="service-panel-title">Umrah</h3>
                                <p class="service-panel-copy">Individual, family and group Umrah arranged around your preferred dates, duration, accommodation and travel requirements.</p>
                                <span class="service-panel-cta">Explore Umrah Services<i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                            </div>
                        </a>
                    </div>
                @endif

                @if($tourismCategory)
                    <div class="col-md-4 reveal-on-scroll reveal-delay-3">
                        <a href="{{ route('packages.category', 'tourism') }}" class="service-panel">
                            <div class="photo-media photo-media--16x9">
                                <x-photo key="hunza-attabad" sizes="(min-width: 768px) 31vw, 92vw" />
                            </div>
                            <div class="service-panel-body">
                                <span class="service-panel-eyebrow">Domestic &amp; International</span>
                                <h3 class="service-panel-title">Tourism</h3>
                                <p class="service-panel-copy">Leisure travel across Pakistan and abroad, planned by the same team that manages our pilgrimage operations.</p>
                                <span class="service-panel-cta">Browse Tour Packages<i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{--
        5c. Filter My Packages.

        Approved homepage item (g) — never built. This is a real, working
        entry point, not a decorative form: it GET-submits straight to the
        existing category listing using the query parameters that listing
        already understands (`days`, `price_max`), so there is no second
        filtering implementation to drift out of sync. Duration options come
        from the published Hajj packages themselves, so the widget can never
        offer a length that returns nothing.
    --}}
    @if($hajjCategory || $umrahCategory)
        <section class="section package-finder-section position-relative">
        {{-- A real photograph behind the band, with the centred scrim that keeps
             the white type at WCAG AA over it. --}}
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url('nabawi-aerial') }}"
                 srcset="{{ \App\Support\SiteImagery::srcset('nabawi-aerial') }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>

            <div class="container">
                <div class="package-finder reveal-on-scroll">
                    <div class="package-finder-intro">
                        <span class="section-eyebrow d-inline-flex">Filter My Packages</span>
                        <h2 class="package-finder-title">Find the Package That Fits Your Journey</h2>
                        <p class="package-finder-copy">Tell us how long you can travel and what you have budgeted. We will show you the packages that match.</p>
                    </div>

                    <form method="GET" action="{{ route('packages.category', 'hajj') }}" class="package-finder-form" id="packageFinder">
                        <div class="package-finder-field">
                            <label for="finder-service">Service</label>
                            {{-- The form action is rewritten to the chosen category's
                                 listing URL on submit (see app.js), so this control
                                 does not need to post a value of its own. --}}
                            <select id="finder-service" class="form-select" data-finder-service>
                                @if($hajjCategory)<option value="{{ route('packages.category', 'hajj') }}">Hajj</option>@endif
                                @if($umrahCategory)<option value="{{ route('packages.category', 'umrah') }}">Umrah</option>@endif
                                @if($tourismCategory)<option value="{{ route('packages.category', 'tourism') }}">Tourism</option>@endif
                            </select>
                        </div>

                        <div class="package-finder-field">
                            <label for="finder-days">Duration</label>
                            <select name="days" id="finder-days" class="form-select">
                                <option value="">Any length</option>
                                @foreach($filterDurations as $days)
                                    <option value="{{ $days }}">{{ $days }} days</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="package-finder-field">
                            <label for="finder-budget">Budget up to (US$)</label>
                            <input type="number" name="price_max" id="finder-budget" class="form-control" min="0" step="500" placeholder="e.g. 12000" inputmode="numeric">
                        </div>

                        <div class="package-finder-action">
                            <button type="submit" class="btn btn-secondary btn-lg">
                                <i class="bi bi-search" aria-hidden="true"></i>Find Packages
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    @endif

    {{-- 6. Personalized Services section — visual split (image/visual one side, content the other) --}}
    <section class="section bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-on-scroll">
                    {{-- An image-and-text composition rather than another
                         background: a main photograph with a second, smaller
                         one overlapping it. The inset is hidden below `md`,
                         where two stacked crops only eat vertical space. --}}
                    <div class="photo-figure-stack">
                        <div class="photo-figure photo-figure-main photo-media photo-media--panel">
                            <x-photo key="haram-courtyard" sizes="(min-width: 992px) 46vw, 92vw" />
                        </div>
                        <div class="photo-figure-inset photo-media photo-media--square">
                            {{-- `sizes` must never be 0px: the inset is hidden below `md` but is
                                 visible from 768px up, and a zero descriptor left the
                                 browser with no candidate to choose, so the image failed
                                 to load at exactly that width. --}}
                            <x-photo key="nabawi-dome" sizes="(min-width: 992px) 21vw, 35vw" />
                        </div>
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
                    <div class="col-lg-6 reveal-on-scroll reveal-delay-2">
                        <x-stat-panel
                            :display="(string) $counters['hajj_packages']"
                            :target="$counters['hajj_packages']"
                            label="Hajj 2027 Packages"
                            note="Real 1448 AH itineraries, hotels and pricing."
                            :variant="4"
                            class="stat-panel-on-dark" />
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
                        <div class="photo-figure photo-media photo-media--panel">
                            <x-photo key="kaaba-close" sizes="(min-width: 992px) 46vw, 92vw" />
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
                {{-- Centred: the row holds up to 6 cards in threes, so a 4th
                     testimonial otherwise sat alone hard-left with half the row
                     empty beside it. --}}
                <div class="row g-4 justify-content-center">
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
                {{-- `align-items-start`, not `-center`: "Ministry of Religious
                     Affairs (Pakistan)" wraps to two lines, and centring made its
                     seal sit visibly higher than every other seal in the row. --}}
                <div class="row g-4 justify-content-center align-items-start">
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
    <section class="section final-cta bg-primary text-white text-center position-relative">
        {{-- A real photograph behind the band, with the centred scrim that keeps
             the white type at WCAG AA over it. --}}
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url('haram-panorama') }}"
                 srcset="{{ \App\Support\SiteImagery::srcset('haram-panorama') }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>

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
