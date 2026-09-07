@props(['package'])

@php
    // The media area now carries the package's day count as a large editorial
    // figure. It is real stored data (`duration_days`), never invented — when
    // the column is empty the figure is simply omitted and the generated
    // composition stands on its own.
    $figure = $package->duration_days ?: null;
    $figureLabel = $figure ? ($figure == 1 ? 'Day' : 'Days') : null;

    // One short, high-signal caption instead of the five cramped metadata
    // rows the card used to stack under its title.
    $caption = null;
    if ($package->isHajj() && ! is_null($package->medinah_first)) {
        $caption = $package->medinah_first ? 'Madinah First' : 'Makkah First';
    } elseif ($package->season_label) {
        $caption = $package->season_label;
    }

    // Chips are capped at three. Duration is deliberately excluded — it is
    // already the figure on the media area, and repeating it was a large part
    // of why these cards read as admin-panel rows rather than travel cards.
    $chips = [];
    if ($package->isHajj()) {
        if (! is_null($package->is_shifting)) {
            $chips[] = ['bi-arrow-left-right', $package->is_shifting ? 'Shifting' : 'Non-Shifting'];
        }
        if ($package->has_aziziya) {
            $chips[] = ['bi-building', 'Aziziya'];
        }
    }
    if (! $package->isHajj() && $package->duration_label) {
        $chips[] = ['bi-calendar-event', $package->duration_label];
    }
    $chips = array_slice($chips, 0, 3);

    // Series names carry a trailing parenthetical scope note — the real value
    // is "Platinum — Non-Aziziya (Makkah & Medinah Series)". As a one-line
    // eyebrow that truncated mid-word to "PLATINUM — NON-AZIZIYA (MAKKAH …",
    // so the parenthetical is dropped for the eyebrow only. The full,
    // unmodified series name is still shown on the package detail page and in
    // the listing's own filter controls.
    $seriesEyebrow = $package->series
        ? trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $package->series->name))
        : null;
@endphp

<div class="card package-card reveal-on-scroll">
    <div class="package-card-img-wrap">
        <x-visual
            :image="$package->cover_image"
            :alt="$package->name"
            :seed="$package->code ?: $package->slug"
            surface="card"
            :figure="$figure"
            :figure-label="$figureLabel"
            :caption="$caption"
            mark=""
            class="package-card-img" />

        <div class="package-card-badges">
            @if($package->code)
                <span class="pkg-badge pkg-badge-code">{{ $package->code }}</span>
            @endif
            @if($package->is_featured)
                <span class="pkg-badge pkg-badge-featured">Featured</span>
            @endif
        </div>
    </div>

    <div class="card-body d-flex flex-column">
        @if($seriesEyebrow)
            <span class="package-card-series">{{ $seriesEyebrow }}</span>
        @endif

        <h3 class="package-card-title">{{ $package->name }}</h3>

        @if($chips)
            <div class="package-card-meta">
                @foreach($chips as [$icon, $label])
                    <span><i class="bi {{ $icon }}"></i>{{ $label }}</span>
                @endforeach
            </div>
        @endif

        @if($package->publicSummary())
            <p class="small text-secondary">{{ Str::limit($package->publicSummary(), 110) }}</p>
        @endif

        <div class="package-card-foot mt-auto">
            <span class="package-card-price">
                @if($package->starting_price)
                    <small>From</small>{{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($package->starting_price) }}
                @else
                    <small>&nbsp;</small>Price on request
                @endif
            </span>
            <a href="{{ route('packages.show', [$package->category->slug, $package->slug]) }}" class="btn btn-sm btn-primary package-card-cta">
                View Details<i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
