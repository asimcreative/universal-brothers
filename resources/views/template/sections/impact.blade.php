{{--
    Impact — the full-bleed world map, under the pilgrims-served figure.

    The markup, the class names and the nesting below are the designer's own,
    taken from the reference template so that the compiled stylesheet in
    `public/template/css/template.css` matches it rule for rule. Only the
    content is ours: the headline figure is the admin-editable pilgrims-served
    setting rather than the template's hardcoded number, so it can never drift
    from the figure the rest of the site quotes.

    The flag row is gone on purpose. The reference ran a marquee of ten country
    flags under the map, with the strapline that captioned it, and together they
    claimed a list of countries our pilgrims travel from. We have no data behind
    that list and will not imply markets we cannot verify, so the marquee and
    its caption were both dropped and the approved supporting sentence stands in
    the space they held. The flag artwork is a separate asset set we have not
    vendored either, so nothing here reaches for a file that does not exist.

    Two mechanical changes. The template animates with Framer Motion, which
    leaves inline `opacity`/`transform` styles on the wrappers — those are
    stripped and replaced with the site's own `reveal-on-scroll` hooks, so
    nothing is left invisible if a bundle fails to load. The map is the one
    place that needed an extra wrapper for it: the reveal hook sets `transform`
    itself, and the map's own `-translate-x-*` classes are what pull it
    full-bleed, so the hook goes on a wrapper rather than overwriting them.

    The pilgrims figure is `<x-stat-number>`, which renders a div, so the pill
    around it is a div too — the reference used a `<p>`, which may not contain
    one.
--}}

<section id="impact" class="relative overflow-hidden bg-sand-100 py-20 sm:py-24 lg:py-28">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_5%,var(--color-sand-50),transparent_45%)]"></div>
    <div class="relative z-10 mx-auto w-full max-w-[var(--container-site)]">
        <div class="reveal-on-scroll">
            <div class="flex items-center justify-center gap-4 px-5 md:gap-5"><div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg><span class="text-[11px] font-bold uppercase tracking-[0.42em] text-accent-700 md:text-[13px]">Our Impact</span></div></div>
        </div>

        <div class="reveal-on-scroll reveal-delay-1">
            <div class="mt-10 px-2 text-center sm:mt-12 md:px-5"><h2 class="font-display font-extrabold uppercase leading-[0.87] text-[46px] sm:text-[68px] md:text-[86px] lg:text-[96px] xl:text-[112px] 2xl:text-[116px]"><span class="block text-inverse">Trusted by Travellers</span><span class="mt-2 block text-accent-600 md:mt-3">Around the World</span></h2></div>
        </div>

        <div class="reveal-on-scroll reveal-delay-2">
            <div class="mt-10 flex items-center justify-center gap-4 px-4 sm:mt-12 md:gap-7"><div class="flex items-center justify-center gap-2 md:gap-6 whitespace-nowrap rounded-pill border-[1.4px] border-accent-600 bg-sand-50/80 px-6 py-2.5 backdrop-blur-sm sm:gap-2.5 sm:px-9 md:gap-3 md:px-11 md:py-3"><x-stat-number :display="$stats['pilgrims']" :target="$counters['pilgrims']" class="font-display text-[28px] font-extrabold leading-none tracking-[-0.03em] text-inverse md:text-[82px]" /><span class="text-[14px] font-medium leading-none text-ink-600 sm:text-[16px] md:text-[80px]">Haji Served</span></div></div>
        </div>

        <div class="mt-12 sm:mt-14 lg:mt-16">
            <div class="reveal-on-scroll reveal-delay-3">
                <div class="relative w-[165%] -translate-x-[19.7%] sm:w-[145%] sm:-translate-x-[15.5%] md:w-[132%] md:-translate-x-[12.2%] lg:w-[122%] lg:-translate-x-[9%] xl:w-[124%] xl:-translate-x-[9.7%] 2xl:w-[126%] 2xl:-translate-x-[10.3%]">
                    @include('template.sections.world-map')
                </div>
            </div>
        </div>

        <div class="reveal-on-scroll reveal-delay-4">
            <p class="mx-auto mt-10 max-w-2xl px-5 text-center text-sm leading-relaxed text-ink-600 sm:mt-12 sm:text-base">Our Hajj and Umrah programmes are arranged for pilgrims travelling from Pakistan and for families joining from abroad — with the same documentation support, accommodation standards and on-ground assistance wherever the journey begins.</p>
        </div>
    </div>
</section>
