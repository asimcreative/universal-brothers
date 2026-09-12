@props(['affiliation'])

@php
    $ubVariant = crc32('aff' . $affiliation->organization_name) % 8;
@endphp

<div class="col-6 col-sm-4 col-lg-3 reveal-on-scroll">
    <div class="affiliation-tile">
        @if($affiliation->logo)
            <span class="affiliation-plate">
                <img src="{{ \App\Support\SiteImagery::resolve($affiliation->logo) }}" alt=""
                                             {{-- Decorative: the organisation name is printed as visible text
                                                  immediately beside this mark, so an alt would have a screen
                                                  reader announce it twice. --}} loading="lazy" decoding="async">
            </span>
            <span class="affiliation-name">{{ $affiliation->organization_name }}</span>
            {{-- The organisation name is now shown in BOTH branches rather than
                 only in the no-logo one. journeys-visitor.spec.js asserts the
                 literal text "IATA" is visible, and with real logos in place the
                 name is also what makes an unfamiliar mark identifiable. --}}
        @else
            {{-- Two of the ten organisations (DTS, SECP) are not in the client's
                 brochure, so we hold no logo for them. They get the same plate
                 with a neutral mark — NOT their initials. Eight of the ten names
                 are four-letter acronyms, so "first four characters" is the whole
                 name, and printing it here put "IATA" directly above "IATA". The
                 original seal carried no lettering for exactly this reason. --}}
            <span class="affiliation-plate affiliation-plate--blank" aria-hidden="true"><i class="bi bi-patch-check"></i></span>
            <span class="affiliation-name">{{ $affiliation->organization_name }}</span>
        @endif

        @if($affiliation->year)
            <span class="affiliation-year">Since {{ $affiliation->year }}</span>
        @endif
    </div>
</div>
