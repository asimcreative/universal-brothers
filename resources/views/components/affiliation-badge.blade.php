@props(['affiliation'])

@php
    $ubVariant = crc32('aff' . $affiliation->organization_name) % 8;
@endphp

<div class="col-6 col-sm-4 col-lg-3 reveal-on-scroll">
    <div class="affiliation-tile">
        @if($affiliation->logo)
            <img src="{{ Storage::url($affiliation->logo) }}" alt="{{ $affiliation->organization_name }}" class="affiliation-logo" loading="lazy" decoding="async">
            {{-- The organisation name must remain reachable as accessible text in
                 BOTH branches: journeys-visitor.spec.js asserts the literal text
                 "IATA" is visible, and it would disappear the moment a logo was
                 uploaded if the name only existed in the no-logo branch. --}}
            <span class="visually-hidden">{{ $affiliation->organization_name }}</span>
        @else
            {{-- Geometric only, no lettering. The organisation name sits directly
                 below, and eight of the ten affiliations are acronyms, so a mark
                 inside the disc just printed "IATA" twice in a row. The disc is
                 legible on its own now that `ub-seal-visual` scales the motif
                 down to seal size — the earlier "empty dark disc" problem was
                 the motif tile being larger than the circle, not the absence of
                 text. Award medallions keep their initials, because there the
                 label beside them is a long award name, not the same acronym. --}}
            <span class="affiliation-mark ub-visual ub-visual--v{{ $ubVariant }}" aria-hidden="true"></span>
            <span class="affiliation-name">{{ $affiliation->organization_name }}</span>
        @endif

        @if($affiliation->year)
            <span class="affiliation-year">Since {{ $affiliation->year }}</span>
        @endif
    </div>
</div>
