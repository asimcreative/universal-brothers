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

@php($ubHasPhoto = \App\Support\SiteImagery::has($photo))

<div {{ $attributes->class(['empty-state', 'text-center', 'empty-state--photo' => $ubHasPhoto]) }}>
    @if($ubHasPhoto)
        <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
            <img src="{{ \App\Support\SiteImagery::url($photo) }}"
                 srcset="{{ \App\Support\SiteImagery::srcset($photo) }}"
                 sizes="100vw" alt="" loading="lazy" decoding="async">
        </div>
    @endif

    <div class="empty-state-content">
        <div class="empty-state-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
        <p class="mb-0">{{ $slot }}</p>
    </div>
</div>
