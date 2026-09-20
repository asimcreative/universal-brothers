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
        // Always one or the other. Saying nothing when a package has no
        // Aziziya leg left the reader to infer it from an absence, and it is
        // one of the two things people compare these packages on.
        $chips[] = ['bi-building', $package->has_aziziya ? 'Aziziya' : 'No Aziziya'];
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
        {{-- A real photograph of where this package actually goes. The subject
             is derived from the package's own fields — the destination named
             in a tourism package, or which holy city a Hajj/Umrah package
             arrives in first — so twelve Hajj cards do not all show the same
             Kaaba photograph, and a package added through the admin picks up
             appropriate imagery with no code change. A cover image uploaded
             through the CMS always wins over the library. --}}
        @php $ubPhoto = \App\Support\SiteImagery::forPackage($package); @endphp

        @if($package->cover_image || \App\Support\SiteImagery::has($ubPhoto))
            {{-- No extra ratio box here: `.package-card-img-wrap` above already
                 provides the 16/10 crop and the overflow, and `.package-card-img`
                 belongs on the image itself — that is what the card's existing
                 hover-scale rule targets. --}}
            <x-photo
                :image="$package->cover_image"
                :key="$ubPhoto"
                :alt="$package->name"
                class="package-card-img"
                sizes="(min-width: 992px) 30vw, (min-width: 576px) 45vw, 92vw" />

            @if($figure || $caption)
                <div class="photo-caption">
                    @if($caption)<span class="photo-caption-eyebrow">{{ $caption }}</span>@endif
                    @if($figure)
                        <p class="photo-caption-title">{{ $figure }} {{ $figureLabel }}</p>
                    @endif
                </div>
            @endif
        @else
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
        @endif

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
        {{-- Price at the top, beside the series. It sat in the footer under
             the summary, which meant the one thing most people are comparing
             was the last thing on the card and never at the same height on
             two cards side by side. --}}
        <div class="package-card-head">
            @if($seriesEyebrow)
                <span class="package-card-series">{{ $seriesEyebrow }}</span>
            @endif

            @php
                // The chosen currency, and no other. Quoting a package's own
                // `currency` column here showed rupees to a visitor reading in
                // dollars, because that column holds one number in one currency
                // and cannot answer the question being asked.
                $ubCurrency = \App\Support\Currency::current();
                $ubFrom = $package->startingPriceIn($ubCurrency);
                $ubTo = $package->endingPriceIn($ubCurrency);
            @endphp
            <span class="package-card-price">
                @if($ubFrom)
                    <small>{{ $ubTo && $ubTo > $ubFrom ? 'From – to' : 'From' }}</small>
                    {{ \App\Support\Currency::format($ubFrom) }}@if($ubTo && $ubTo > $ubFrom)<span class="package-card-price-to"> &ndash; {{ \App\Support\Currency::format($ubTo) }}</span>@endif
                @else
                    Price on request
                @endif
            </span>
        </div>

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
            <a href="{{ route('packages.show', [$package->category->slug, $package->slug]) }}" class="btn btn-sm btn-primary package-card-cta">
                View Details<i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
