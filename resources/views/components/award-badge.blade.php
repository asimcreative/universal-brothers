@props(['award'])

@php
    // Every award previously rendered the identical `bi-trophy-fill` glyph, so
    // the homepage showed six visually identical trophies in a row — the exact
    // opposite of "prestigious". The medallion is now differentiated per award
    // by a deterministic variant, and carries the award's real initials.
    $ubVariant = crc32((string) $award->id . $award->name) % 8;

    // Initials from the significant words of the award name (a parenthetical
    // qualifier like "(Gold Medal)" is not part of the identity).
    $ubBase = trim(preg_replace('/\s*\([^)]*\)/', '', (string) $award->name));
    $ubWords = array_values(array_filter(
        preg_split('/\s+/', $ubBase),
        fn ($w) => ! in_array(Str::lower($w), ['award', 'awards', 'of', 'the', 'in', 'for', 'and', '&'], true)
    ));
    $ubInitials = Str::upper(implode('', array_map(fn ($w) => Str::substr($w, 0, 1), array_slice($ubWords, 0, 3))));
@endphp

{{-- Six across was right for a row of struck-medal seals and wrong for real
     photographs: at that width each badge is ~120px, and cropping a photograph
     of an award ceremony into a 120px circle leaves an unreadable dark smudge.
     Awards with a real photograph get a wider column and a landscape panel;
     the circular seal is kept only where there is no photograph. --}}
<div class="col-6 col-md-4 {{ $award->image ? 'col-lg-4' : 'col-lg-2' }} reveal-on-scroll">
    @if($award->image)
        <figure class="award-photo-card">
            <div class="photo-media award-photo-card-media">
                <img src="{{ \App\Support\SiteImagery::resolve($award->image) }}"
                     alt="{{ $award->name }}" class="ub-photo" loading="lazy" decoding="async">
            </div>
            <figcaption class="award-photo-card-body">
                <p class="award-photo-card-name">{{ $award->name }}</p>
                @if($award->year)
                    <p class="award-photo-card-meta">{{ $award->year }}</p>
                @endif
            </figcaption>
        </figure>
    @else
        <div class="award-medallion">
            <div class="award-medallion-disc ub-visual ub-visual--v{{ $ubVariant }}">
                <span class="award-medallion-initials" aria-hidden="true">{{ $ubInitials !== '' ? $ubInitials : 'UB' }}</span>
            </div>
            <p class="award-medallion-name">{{ $award->name }}</p>
            @if($award->year)
                <p class="award-medallion-meta">{{ $award->year }}</p>
            @endif
        </div>
    @endif
</div>
