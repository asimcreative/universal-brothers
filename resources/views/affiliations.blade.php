@extends('layouts.app')

@section('title', 'Affiliations | Universal Brothers')
@section('meta_description', 'Universal Brothers (Pvt) Ltd industry affiliations and professional memberships — IATA, TAAP, FPCCI and other recognized travel and tourism organizations.')

@section('content')
    <x-page-hero
        photo="jeddah-airport"
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
                                {{-- No initials on this card. Eight of the ten affiliations
                                     are acronyms, so the seal would have repeated the
                                     organisation name sitting immediately beside it —
                                     "IATA" printed twice, side by side. A purely geometric
                                     seal reads better here, and is legible now that
                                     `ub-seal-visual` scales the motif to the disc. The
                                     homepage badge keeps its mark, because there the disc
                                     is the whole logo chip. --}}
                                {{-- A real organisation logo is artwork drawn for a
                                     LIGHT ground — putting one inside the dark
                                     geometric seal made every mark an unreadable
                                     smudge. Logos now get their own light tile;
                                     the seal is kept only for the affiliations we
                                     hold no logo for, so the row stays even. --}}
                                @if($affiliation->logo)
                                    <div class="affiliation-card-mark">
                                        <img src="{{ \App\Support\SiteImagery::resolve($affiliation->logo) }}" alt=""
                                             {{-- Decorative: the organisation name is printed as visible text
                                                  immediately beside this mark, so an alt would have a screen
                                                  reader announce it twice. --}} loading="lazy" decoding="async">
                                    </div>
                                @else
                                    {{-- No logo held for this organisation (DTS and
                                         SECP are not in the client's brochure). A
                                         light tile keeps the row visually even
                                         instead of a dark disc that reads as a
                                         missing image. Deliberately NOT the
                                         initials: most of these names are
                                         four-letter acronyms, so that would print
                                         the name twice in a row. --}}
                                    <div class="affiliation-card-mark affiliation-card-mark--blank">
                                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                                    </div>
                                @endif
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
