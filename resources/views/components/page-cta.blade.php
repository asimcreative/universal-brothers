{{--
    Closing call-to-action band for interior pages.

    Awards, Affiliations, Media, Testimonials and FAQs all ended abruptly —
    content, then several hundred pixels of empty white, then the footer. On
    the pages with little data (Media has no news/gallery/video rows yet) that
    left the page reading as broken rather than simply young. This gives every
    page a deliberate ending and somewhere to go next.

    Props
      eyebrow, title, copy — all optional overrides of the defaults below.
--}}
@props([
    'eyebrow' => 'Speak to Universal Brothers',
    'title' => 'Your Sacred Journey Begins With a Conversation',
    'copy' => 'Whether you are preparing for Hajj, planning Umrah or simply need guidance before making a decision, our experienced team is ready to assist you.',
    'photo' => 'haram-panorama',
    // The primary action used to be hardcoded to the Hajj catalogue, which
    // meant a Kashmir or Maldives holiday page closed by sending the visitor
    // to Hajj 2027. The picture being wrong was the visible half of that; the
    // link being wrong was the half they would actually have clicked.
    'actionLabel' => 'View Hajj 2027 Packages',
    'actionUrl' => null,
])

<section class="page-cta">
    @if(\App\Support\SiteImagery::has($photo))
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url($photo) }}"
                 srcset="{{ \App\Support\SiteImagery::srcset($photo) }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>
    @else
        <div class="ub-visual ub-visual--stage ub-visual--stage-sm" aria-hidden="true"></div>
    @endif
    <div class="container page-cta-content">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8 reveal-on-scroll">
                <span class="hero-eyebrow page-cta-eyebrow">{{ $eyebrow }}</span>
                <h2 class="page-cta-title">{{ $title }}</h2>
                <p class="page-cta-copy">{{ $copy }}</p>
                <div class="page-cta-actions">
                    <a href="{{ $actionUrl ?? route('packages.category', 'hajj') }}" class="btn btn-secondary btn-lg">{{ $actionLabel }}</a>
                    <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Contact Our Team</a>
                </div>
            </div>
        </div>
    </div>
</section>
