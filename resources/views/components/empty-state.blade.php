{{--
    Honest empty state.

    Several pages legitimately have nothing to show yet — no Umrah packages are
    published, the media library has no rows. The site has always said so
    plainly rather than inventing content, and that stays. What changed is that
    the message used to sit alone in several hundred pixels of empty white,
    which reads as a broken page rather than a young one.

    Passing `photo` puts a real photograph behind the message, so the block
    still looks deliberate. It is optional on purpose: an empty state inside an
    already-busy page (an FAQ list, an awards grid) is better left plain, and
    a photograph there would be the decoration the brief rules out.

    Props
      icon   Bootstrap icon class
      photo  optional SiteImagery key for a photographic backdrop
--}}
@props([
    'icon' => 'bi-info-circle',
    'photo' => null,
])

@php
    /*
     * An empty state usually sits directly beneath the page hero, and several
     * templates named the same photograph for both — so /umrah and /media each
     * showed one picture twice, one above the other, which reads as a bug
     * rather than a motif. Asking for a sibling that is not already on the page
     * fixes it wherever this component is used, including pages not written yet.
     */
    $ubPhoto = \App\Support\SiteImagery::unusedSibling($photo);
    $ubHasPhoto = \App\Support\SiteImagery::has($ubPhoto);
@endphp

<div {{ $attributes->class(['empty-state', 'text-center', 'empty-state--photo' => $ubHasPhoto]) }}>
    @if($ubHasPhoto)
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url($ubPhoto) }}"
                 srcset="{{ \App\Support\SiteImagery::srcset($ubPhoto) }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>
    @endif

    <div class="empty-state-content">
        <div class="empty-state-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
        <p class="mb-0">{{ $slot }}</p>
    </div>
</div>
