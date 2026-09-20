{{--
    The awards band — the copper strip that scrolls the company's awards across
    the page between the hero and the introduction.

    The markup and the class names below are the designer's own, emitted
    verbatim so the vendored template stylesheet resolves them. Only the content
    is ours: the designer's band carried their own placeholder wording, which is
    replaced here by the real Award records, so the strip is hidden outright
    when nothing has been published rather than scrolling invented text.

    The band scrolled by a JavaScript-driven inline transform in the template,
    which we do not ship. It scrolls by CSS here instead: the award list is
    rendered twice inside one track and the track is translated by exactly
    -50%, so at the moment the first copy has left the frame the second sits
    precisely where the first began and the loop has no seam. The second copy
    is `aria-hidden` — a screen reader should read each award once, not twice —
    and the animation stops for anyone who has asked for reduced motion, and
    while a pointer or keyboard focus is resting on the band, so moving text is
    never something a reader has to chase.
--}}
<style>
    .ub-marquee-track {
        animation: ub-marquee-scroll var(--ub-marquee-duration, 40s) linear infinite;
        will-change: transform;
    }
    @keyframes ub-marquee-scroll {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }
    .ub-marquee:hover .ub-marquee-track,
    .ub-marquee:focus-within .ub-marquee-track { animation-play-state: paused; }
    @media (prefers-reduced-motion: reduce) {
        .ub-marquee-track { animation: none; }
    }
</style>

@if($awards->isNotEmpty())
    @php
        // With only one or two awards published the track would be narrower
        // than the viewport, and the -50% translate would then drag a visible
        // gap across the band. So the list repeats until one half carries at
        // least six items — the count the designer's spacing was drawn for.
        // Both halves stay identical, which is what keeps the loop seamless.
        $ubHalf = collect();
        for ($ubPass = 0; $ubPass < (int) ceil(6 / $awards->count()); $ubPass++) {
            $ubHalf = $ubHalf->concat($awards);
        }

        // One item takes roughly six seconds to cross, so the band reads at the
        // same pace whether two awards are published or six.
        $ubDuration = max(24, $ubHalf->count() * 6);
    @endphp

    <section id="highlights"><h2 class="sr-only">Awards and recognitions</h2><div class="reveal-on-scroll"><div class="ub-marquee relative overflow-hidden bg-accent-500 py-4 sm:py-6"><div class="ub-marquee-track flex w-max flex-nowrap items-center text-inverse" style="--ub-marquee-duration: {{ $ubDuration }}s">@foreach([false, true] as $ubIsDuplicate)<div @if($ubIsDuplicate) aria-hidden="true" @endif class="flex shrink-0 flex-nowrap items-center">@foreach($ubHalf as $award)<span class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle mx-6 shrink-0 text-on-dark-muted sm:mx-9" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg><span class="whitespace-nowrap font-display text-base font-bold tracking-wide text-inverse sm:text-xl lg:text-[32px]">{{ $award->name }}</span></span>@endforeach</div>@endforeach</div></div></div></section>
@endif
