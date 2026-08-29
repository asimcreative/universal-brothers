@extends('layouts.app')

@section('title', 'Contact Us | Universal Brothers')
@section('meta_description', 'Contact Universal Brothers — Karachi head office, phone, WhatsApp and email for Hajj, Umrah and Tourism inquiries.')

@section('content')
    <div class="bg-primary text-white py-5">
        <div class="container">
            <h1>Contact Us</h1>
            <p class="lead mb-0">We'd love to help plan your Hajj, Umrah, or next trip.</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-7">
                <h2 class="h4 mb-4">Our Offices</h2>
                @foreach($offices as $office)
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h3 class="h6">{{ $office->label }}</h3>
                            <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i>{{ $office->address }}</p>
                            @if($office->phone_primary)<p class="mb-1"><i class="bi bi-telephone-fill me-2"></i>{{ $office->phone_primary }} @if($office->phone_secondary) / {{ $office->phone_secondary }} @endif</p>@endif
                            @if($office->whatsapp)<p class="mb-1"><i class="bi bi-whatsapp me-2"></i>{{ $office->whatsapp }}</p>@endif
                            @if($office->email)<p class="mb-0"><i class="bi bi-envelope-fill me-2"></i>{{ $office->email }}</p>@endif
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
