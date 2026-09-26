@extends('layouts.app')

@section('title', ($package->meta_title ?: $package->name . ' | Universal Brothers'))
@section('meta_description', $package->meta_description ?: Str::limit($package->publicSummary() ?? '', 160))

@section('content')
    @php $ubHeroPhoto = $package->cover_image ? null : \App\Support\SiteImagery::forPackage($package); @endphp

    <x-page-hero
        class="package-hero"
        :photo="$ubHeroPhoto"
        :eyebrow="$package->series?->name"
        :title="$package->name"
        :lead="$package->publicSummary()"
        :breadcrumbs="['Home' => route('home'), $package->category->name => route('packages.category', $package->category->slug), $package->name => null]">
        @if($package->cover_image)
            <img src="{{ Storage::url($package->cover_image) }}" alt="{{ $package->name }}" class="package-hero-photo" decoding="async">
        @endif

        <div class="package-hero-badges">
            @if($package->code)<span class="pkg-badge pkg-badge-featured">{{ $package->code }}</span>@endif
            @if($package->season_label)<span class="pkg-badge pkg-badge-outline">{{ $package->season_label }}</span>@endif
            @if($package->duration_label)<span class="pkg-badge pkg-badge-outline">{{ $package->duration_label }}</span>@endif
            @if(!is_null($package->is_shifting))<span class="pkg-badge pkg-badge-outline">{{ $package->is_shifting ? 'Shifting' : 'Non-Shifting' }}</span>@endif
            @if(!is_null($package->has_aziziya))<span class="pkg-badge pkg-badge-outline">{{ $package->has_aziziya ? 'With Aziziya' : 'Non-Aziziya' }}</span>@endif
        </div>

        {{-- These packages are published in a single currency, unlike the
             Hajj brochures. So the price is shown in the currency it is
             actually published in, formatted by the one place that knows how
             each is written. Nothing here is converted, and nothing is
             blanked because the reader happens to be browsing in another. --}}
        @if($package->starting_price !== null)
            <p class="package-hero-price">
                <span>From</span>{{ \App\Support\Currency::format($package->starting_price, $package->currency) }}
                <small>per person</small>
            </p>
        @endif
    </x-page-hero>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                @if($package->description)
                    <div class="rich-text">{!! \App\Support\Content\RichText::render($package->description, 'standard') !!}</div>
                @endif

                {{-- A second look at the destination before the price table.
                     `forDestination` reads the destination out of the package's
                     own name, so a tour added through the admin gets relevant
                     imagery without a code change; the block simply does not
                     render for a package whose destination we hold no
                     photograph of, rather than showing a generic filler. --}}
                @php
                    // A DIFFERENT photograph of the same destination from the one
                    // in the hero. Returns null when we only hold one photograph
                    // of that place, and the block is then skipped rather than
                    // repeating the hero image or padding with generic filler.
                    $ubDestination = \App\Support\SiteImagery::secondaryForDestination($package->name.' '.$package->slug);
                    $ubDestinationAlt = \App\Support\SiteImagery::alt($ubDestination);
                @endphp
                @if($ubDestination)
                    <figure class="photo-figure photo-media photo-media--wide my-4">
                        <x-photo :key="$ubDestination" sizes="(min-width: 992px) 62vw, 92vw" />
                        @if($ubDestinationAlt)
                            <figcaption class="photo-caption">
                                <span class="photo-caption-eyebrow">Where you are going</span>
                                <p class="photo-caption-title">{{ $ubDestinationAlt }}</p>
                            </figcaption>
                        @endif
                    </figure>
                @endif

                {{-- Pricing --}}
                @if($package->priceTiers->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Room Type Pricing</h2>
                    <div class="row g-3 mb-2">
                        @foreach($package->priceTiers as $tier)
                            <div class="col-md-6">
                                <div class="pricing-card h-100">
                                    <div class="pricing-card-variant">{{ $tier->label ?: 'Price' }}</div>
                                    <ul class="list-unstyled mb-0">
                                        @foreach(['sharing' => 'Sharing', 'quad' => 'Quad', 'triple' => 'Triple', 'double' => 'Double'] as $type => $label)
                                            @php $price = $tier->roomPrices->firstWhere('room_type', $type); @endphp
                                            @if($price)
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                                                    <span class="small text-muted">{{ $label }} Per Person</span>
                                                    <span class="fw-semibold">
                                                        @if($price->price !== null)
                                                            {{ \App\Support\Currency::format($price->price, $package->currency) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="small text-muted fst-italic">Book early — prices and packages are subject to change.</p>
                @endif

                {{-- Itinerary --}}
                @if($package->itineraryDays->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Day-by-Day Itinerary</h2>
                    <div class="itinerary-timeline mb-4" id="itineraryAccordion">
                        @foreach($package->itineraryDays as $day)
                            <div class="itinerary-day" data-day="{{ $day->day_number }}">
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#day{{ $day->id }}">
                                            Day {{ $day->day_number }}
                                            @if($day->date_gregorian) — {{ $day->date_gregorian->format('d M Y') }} @endif
                                            @if($day->date_hijri_label) ({{ $day->date_hijri_label }}) @endif
                                            @if($day->city) — {{ $day->city }} @endif
                                        </button>
                                    </h3>
                                    <div id="day{{ $day->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-1"><strong>{{ $package->priceTiers->count() > 1 ? 'Package A: ' : '' }}</strong>{{ $day->accommodation_a }}</p>
                                            @if($day->accommodation_b)
                                                <p class="mb-1"><strong>Package B:</strong> {{ $day->accommodation_b }}</p>
                                            @endif
                                            @if($day->notes)
                                                <p class="small text-muted mb-0">{{ $day->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Inclusions / Exclusions --}}
                <div class="row g-4">
                    @if($package->inclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Inclusions</h2>
                            <ul class="list-unstyled inclusion-list">
                                @foreach($package->inclusions as $inclusion)
                                    <li><i class="bi bi-check2-circle text-success"></i><span>{{ $inclusion->description }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($package->exclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-x-circle-fill text-danger me-2"></i>Exclusions</h2>
                            <ul class="list-unstyled exclusion-list">
                                @foreach($package->exclusions as $exclusion)
                                    <li><i class="bi bi-x-circle text-danger"></i><span>{{ $exclusion->description }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

            </div>

            <div class="col-lg-4">
                {{-- `.ub-sticky-aside` rather than an inline `top: 100px`.
                     Bootstrap's `.sticky-top` gives this the same `z-index:
                     1020` the site header has, and on a z-index tie the later
                     element in the document wins — so this card was painting
                     straight over the navigation. The 100px offset was also
                     smaller than the header's real height on every desktop
                     width (measured: 107.75px, and 125.75px at exactly
                     1200px), so it sat under the header as well. Both are
                     fixed centrally; see `_components.scss`. --}}
                <div class="sticky-top ub-sticky-aside">
                    <x-inquiry-form :package="$package" :category="$package->category" title="Enquire About This Package" />

                    <x-related-packages :related="$related" />
                </div>
            </div>
        </div>
    </div>

    @php
        /*
         * This template serves Tourism and Umrah detail pages alike, and the
         * CTA's defaults are written for pilgrimage — "Your Sacred Journey
         * Begins With a Conversation", over a photograph of Masjid al-Haram.
         *
         * On a Kashmir or Maldives holiday page that is the same mistake the
         * package cards had: pilgrimage framing, and one of Islam's holiest
         * sites, used to close a sightseeing page. The copy is wrong there too,
         * not only the picture.
         *
         * Driven off the category rather than a slug, so a tour added through
         * the admin tomorrow gets the right ending with no code change.
         */
        $ubIsTourism = ($package->category->slug ?? null) === 'tourism';
    @endphp

    @if($ubIsTourism)
        @php
            /*
             * A scenic photograph that is deliberately NOT this package's own.
             * The hero already shows the destination; repeating it a few
             * hundred pixels lower reads as a mistake. The band is decorative
             * and its copy asks where the visitor would like to go, so it makes
             * no claim about where THIS tour goes.
             */
            $ubCtaPhoto = \App\Support\SiteImagery::pickExcluding(
                ['hunza-valley', 'skardu-deosai', 'karakoram-highway', 'fairy-meadows', 'maldives', 'turkey-cappadocia'],
                (string) ($package->slug ?? $package->id),
                \App\Support\SiteImagery::forPackage($package),
            );
        @endphp

        <x-page-cta
            eyebrow="Plan Your Trip"
            title="Where Would You Like to Go?"
            copy="Tell us the dates you have in mind and how many are travelling, and our team will put together an itinerary and a price for you."
            :photo="$ubCtaPhoto"
            action-label="Browse All Tours"
            :action-url="route('packages.category', 'tourism')"
        />
    @else
        <x-page-cta />
    @endif
@endsection
