@extends('layouts.app')

@section('title', 'Contact Us | Universal Brothers')
@section('meta_description', 'Contact Universal Brothers — Karachi head office, phone, WhatsApp and email for Hajj, Umrah and Tourism inquiries.')

@section('content')
    <x-page-hero
        photo="karachi"
        eyebrow="We're Here to Help"
        title="Contact Us"
        lead="We'd love to help plan your Hajj, Umrah, or next trip."
        :breadcrumbs="['Home' => route('home'), 'Contact' => null]" />

    <div class="container section-tight">
        @if($primaryOffice?->phone_primary || $primaryOffice?->whatsapp || $primaryOffice?->email)
            <div class="row g-3 mb-5">
                @if($primaryOffice->phone_primary)
                    <div class="col-md-4">
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="contact-method-card">
                            <span class="contact-method-icon"><i class="bi bi-telephone-fill"></i></span>
                            <span>
                                <span class="d-block small text-uppercase fw-semibold text-muted" style="letter-spacing:0.04em;">Call Us</span>
                                <span class="fw-semibold">{{ $primaryOffice->phone_primary }}</span>
                            </span>
                        </a>
                    </div>
                @endif
                @if($primaryOffice->whatsapp)
                    <div class="col-md-4">
                        <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $primaryOffice->whatsapp) }}" class="contact-method-card" target="_blank" rel="noopener">
                            <span class="contact-method-icon"><i class="bi bi-whatsapp"></i></span>
                            <span>
                                <span class="d-block small text-uppercase fw-semibold text-muted" style="letter-spacing:0.04em;">WhatsApp</span>
                                <span class="fw-semibold">{{ $primaryOffice->whatsapp }}</span>
                            </span>
                        </a>
                    </div>
                @endif
                @if($primaryOffice->email)
                    <div class="col-md-4">
                        <a href="mailto:{{ $primaryOffice->email }}" class="contact-method-card">
                            <span class="contact-method-icon"><i class="bi bi-envelope-fill"></i></span>
                            <span>
                                <span class="d-block small text-uppercase fw-semibold text-muted" style="letter-spacing:0.04em;">Email</span>
                                <span class="fw-semibold">{{ $primaryOffice->email }}</span>
                            </span>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-7">
                <x-section-header eyebrow="Our Offices" title="Visit or Reach Us" align="start" class="mb-3" />
                @foreach($offices as $office)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h3 class="h6">{{ $office->label }}</h3>
                            <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i>{{ $office->address }}</p>
                            @if($office->phone_primary)<p class="mb-1"><i class="bi bi-telephone-fill me-2"></i>{{ $office->phone_primary }} @if($office->phone_secondary) / {{ $office->phone_secondary }} @endif</p>@endif
                            @if($office->whatsapp)<p class="mb-1"><i class="bi bi-whatsapp me-2"></i>{{ $office->whatsapp }}</p>@endif
                            @if($office->email)<p class="mb-0"><i class="bi bi-envelope-fill me-2"></i>{{ $office->email }}</p>@endif
                            @if($office->mapEmbedUrl())
                                {{--
                                    Click-to-load, not an always-on iframe.

                                    The embed is the only third-party resource on the
                                    site, and the browser's `load` event waits for it.
                                    When Google was slow the Contact page simply never
                                    finished loading — it produced 7 failures in a single
                                    Playwright run, every one a `page.goto('/contact')`
                                    timeout, and a real visitor sees the tab spinner keep
                                    spinning. Holding the iframe markup inside a
                                    <template> keeps it completely inert (browsers do not
                                    fetch resources inside one), so the page now owns its
                                    own load event.

                                    The facade is a real link to Google Maps, so it works
                                    with JavaScript disabled; app.js upgrades it to load
                                    the map inline instead. Nothing is requested from
                                    Google until the visitor asks for it.
                                --}}
                                <div class="map-embed mt-3" data-map-embed>
                                    <a class="map-embed-facade ub-visual ub-visual--v3"
                                       href="{{ $office->mapsUrl() ?: 'https://www.google.com/maps' }}"
                                       target="_blank" rel="noopener"
                                       data-map-load>
                                        <span class="map-embed-body">
                                            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                            <span class="map-embed-title">Show map</span>
                                            <span class="map-embed-note">{{ $office->label ?: 'Office location' }}</span>
                                        </span>
                                    </a>
                                    <template data-map-source><iframe src="{{ $office->mapEmbedUrl() }}" title="Map of {{ $office->label ?: 'our office' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></template>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Send Us a Message</h2>
                        <form method="POST" action="{{ route('contact.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="contact-name" class="form-label">Full Name</label>
                                <input type="text" name="name" id="contact-name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact-email" class="form-label">Email</label>
                                <input type="email" name="email" id="contact-email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact-phone" class="form-label">Phone</label>
                                <input type="text" name="phone" id="contact-phone" class="form-control" value="{{ old('phone') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact-message" class="form-label">Message</label>
                                <textarea name="message" id="contact-message" rows="4" class="form-control" required>{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
