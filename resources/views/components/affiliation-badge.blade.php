@props(['affiliation'])

@php
    $ubVariant = crc32('aff' . $affiliation->organization_name) % 8;
@endphp

<div class="col-6 col-sm-4 col-lg-2 reveal-on-scroll">
    <div class="affiliation-tile">
        @if($affiliation->logo)
            <img src="{{ Storage::url($affiliation->logo) }}" alt="{{ $affiliation->organization_name }}" class="affiliation-logo" loading="lazy" decoding="async">
            {{-- The organisation name must remain reachable as accessible text in
                 BOTH branches: journeys-visitor.spec.js asserts the literal text
                 "IATA" is visible, and it would disappear the moment a logo was
                 uploaded if the name only existed in the no-logo branch. --}}
            <span class="visually-hidden">{{ $affiliation->organization_name }}</span>
        @else
            <span class="affiliation-mark ub-visual ub-visual--v{{ $ubVariant }}" aria-hidden="true"></span>
            <span class="affiliation-name">{{ $affiliation->organization_name }}</span>
        @endif

        @if($affiliation->year)
            <span class="affiliation-year">Since {{ $affiliation->year }}</span>
        @endif
    </div>
</div>
