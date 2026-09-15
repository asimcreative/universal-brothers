{{-- Eyebrow, heading and introduction shared by most sections. Needs $data; optional $center. --}}
@php($center = $center ?? true)
@if(filled($data['eyebrow'] ?? null) || filled($data['heading'] ?? null) || filled($data['intro'] ?? null))
    <div class="pb-heading {{ $center ? 'pb-heading--center' : '' }} reveal-on-scroll">
        @if(filled($data['eyebrow'] ?? null))
            <span class="section-eyebrow {{ $center ? 'd-flex justify-content-center' : '' }}">{{ $data['eyebrow'] }}</span>
        @endif
        @if(filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif
        @if(filled($data['intro'] ?? null))
            <p class="pb-intro">{{ $data['intro'] }}</p>
        @endif
    </div>
@endif
