{{--
    Shared banner for every interior page.

    Each page previously rolled its own `.hero-slide` with an inline
    `linear-gradient(135deg,#101B45,#0A1230)` background and a per-file
    `min-height` of 38/42/48/55vh — a flat navy rectangle with a title in the
    top-left and a large empty right half, repeated on thirteen pages. That
    repetition is the direct reason "every dark section looks identical".
    One component now owns the treatment, and the generated geometric stage
    (see `_visuals.scss`) gives the band something to look at.

    Props
      eyebrow      small caps kicker
      title        the h1 — exactly one per page
      lead         supporting sentence
      copy         optional second paragraph (feature heroes)
      breadcrumbs  ['Label' => url|null, ...]; a null url renders as current
      centered     centred feature layout (Hajj/Umrah services)
      compact      tighter vertical rhythm

    Slots
      default   rendered under the text block (badges, price, meta)
      actions   CTA row
      aside     right-hand panel at lg+ (non-centred layouts only)
--}}
@props([
    'eyebrow' => null,
    'title',
    'lead' => null,
    'copy' => null,
    'breadcrumbs' => [],
    'centered' => false,
    'compact' => false,
    'photo' => null,
])

<section {{ $attributes->class([
    'page-hero',
    'page-hero--compact' => $compact,
    'page-hero--centered' => $centered,
    'page-hero--photo' => \App\Support\SiteImagery::has($photo),
]) }}>
    {{-- A real photograph of the place the page is about, where one exists;
         the generated stage otherwise. The banner is marked `aria-hidden`
         either way — it is the page's backdrop, and the heading beside it
         already says what the page is, so announcing the image to a screen
         reader would only repeat that. The scrim that keeps the white text
         at WCAG AA lives in `_photography.scss`. --}}
    @if(\App\Support\SiteImagery::has($photo))
        <div class="ub-photo-bg {{ $centered ? 'ub-photo-bg--centered' : '' }}" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url($photo) }}"
                 srcset="{{ \App\Support\SiteImagery::srcset($photo) }}"
                 sizes="100vw"
                 alt=""
                 loading="eager"
                 fetchpriority="high"
                 decoding="async">
        </div>
    @else
        <div class="ub-visual ub-visual--stage ub-visual--stage-sm" aria-hidden="true"></div>
    @endif

    <div class="container page-hero-content">
        <div class="row {{ $centered ? 'justify-content-center' : 'align-items-end' }} g-4">
            <div class="{{ $centered ? 'col-lg-9' : (isset($aside) ? 'col-lg-8' : 'col-12') }}">
                @if($breadcrumbs)
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb page-hero-breadcrumb">
                            @foreach($breadcrumbs as $label => $url)
                                @if($loop->last || ! $url)
                                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                                @else
                                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                @endif

                @if($eyebrow)
                    <span class="hero-eyebrow page-hero-eyebrow">{{ $eyebrow }}</span>
                @endif

                <h1 class="page-hero-title">{{ $title }}</h1>

                @if($lead)
                    <p class="page-hero-lead">{{ $lead }}</p>
                @endif

                @if($copy)
                    <p class="page-hero-copy">{{ $copy }}</p>
                @endif

                {{ $slot }}

                @isset($actions)
                    <div class="page-hero-actions">{{ $actions }}</div>
                @endisset
            </div>

            @isset($aside)
                <div class="col-lg-4">
                    <div class="page-hero-aside">{{ $aside }}</div>
                </div>
            @endisset
        </div>
    </div>
</section>
