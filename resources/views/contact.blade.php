@extends('layouts.app')

@section('title', 'Contact Us | Universal Brothers')
@section('meta_description', 'Contact Universal Brothers — Karachi head office, phone, WhatsApp and email for Hajj, Umrah and Tourism inquiries.')

@section('content')
    <div class="hero-slide" style="min-height: 38vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <span class="hero-eyebrow">We're Here to Help</span>
            <h1>Contact Us</h1>
            <p class="lead mb-0">We'd love to help plan your Hajj, Umrah, or next trip.</p>
        </div>
    </div>

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
                <span class="section-eyebrow">Our Offices</span>
                <h2 class="h4 mb-4">Visit or Reach Us</h2>
                @foreach($offices as $office)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h3 class="h6">{{ $office->label }}</h3>
                            <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i>{{ $office->address }}</p>
                            @if($office->phone_primary)<p class="mb-1"><i class="bi bi-telephone-fill me-2"></i>{{ $office->phone_primary }} @if($office->phone_secondary) / {{ $office->phone_secondary }} @endif</p>@endif
                            @if($office->whatsapp)<p class="mb-1"><i class="bi bi-whatsapp me-2"></i>{{ $office->whatsapp }}</p>@endif
                            @if($office->email)<p class="mb-0"><i class="bi bi-envelope-fill me-2"></i>{{ $office->email }}</p>@endif
                            @if($office->google_maps_embed)
                                <div class="ratio ratio-16x9 rounded overflow-hidden mt-3">{!! $office->google_maps_embed !!}</div>
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
