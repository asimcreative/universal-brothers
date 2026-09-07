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

<div class="col-6 col-md-4 col-lg-2 reveal-on-scroll">
    <div class="award-medallion">
        <div class="award-medallion-disc ub-visual ub-visual--v{{ $ubVariant }}">
            @if($award->image)
                <img src="{{ Storage::url($award->image) }}" alt="{{ $award->name }}" class="award-medallion-img" loading="lazy" decoding="async">
            @else
                <span class="award-medallion-initials" aria-hidden="true">{{ $ubInitials !== '' ? $ubInitials : 'UB' }}</span>
            @endif
        </div>
        <p class="award-medallion-name">{{ $award->name }}</p>
        @if($award->year)
            <p class="award-medallion-meta">{{ $award->year }}</p>
        @endif
    </div>
</div>
