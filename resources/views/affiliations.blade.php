@extends('layouts.app')

@section('title', 'Affiliations | Universal Brothers')
@section('meta_description', 'Universal Brothers (Pvt) Ltd industry affiliations and professional memberships — IATA, TAAP, FPCCI and other recognized travel and tourism organizations.')

@section('content')
    <x-page-hero
        eyebrow="Trusted Institutions"
        title="Strong Relationships. Trusted Connections."
        lead="Recognised memberships and industry relationships that underpin how we operate."
        :breadcrumbs="['Home' => route('home'), 'Affiliations' => null]" />

    <div class="section">
        <div class="container">
            <p class="text-secondary mx-auto text-center mb-5" style="max-width: 720px;">Our affiliations with recognized travel, tourism and industry organizations strengthen our ability to provide dependable and professionally managed services.</p>

            @if($affiliations->isEmpty())
                <x-empty-state icon="bi-building">No affiliations have been published yet. Please check back soon.</x-empty-state>
            @else
                {{--
                    Previously ten identical cards, each with the same generic
                    `bi-diagram-3` glyph floating above a mostly-empty body.
                    Now an institutional roster: a geometric seal derived from
                    the organisation's own name, its acronym set as a wordmark,
                    and only the fields that actually hold data.
                --}}
                <div class="row g-4 justify-content-center">
                    @foreach($affiliations as $affiliation)
                        @php $ubVariant = crc32('aff' . $affiliation->organization_name) % 8; @endphp
                        <div class="col-sm-6 col-lg-4">
                            <div class="affiliation-card reveal-on-scroll">
                                <div class="affiliation-card-seal ub-visual ub-visual--v{{ $ubVariant }}">
                                    @if($affiliation->logo)
                                        <img src="{{ Storage::url($affiliation->logo) }}" alt="{{ $affiliation->organization_name }}" class="affiliation-card-logo" loading="lazy" decoding="async">
                                    @else
                                        <span class="affiliation-card-initials" aria-hidden="true">{{ Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $affiliation->organization_name), 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div class="affiliation-card-body">
                                    <h2 class="affiliation-card-name">{{ $affiliation->organization_name }}</h2>
                                    @if($affiliation->year)<p class="affiliation-card-since">Affiliated since {{ $affiliation->year }}</p>@endif
                                    @if($affiliation->description)<p class="affiliation-card-description">{{ $affiliation->description }}</p>@endif
                                    @if($affiliation->link)
                                        <a href="{{ $affiliation->link }}" class="affiliation-card-link" target="_blank" rel="noopener">Visit Website<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-page-cta />
@endsection
