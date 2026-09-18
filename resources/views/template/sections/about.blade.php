{{--
    The introduction — the greige band that says who the company is, beside the
    three-tile photograph collage and the years-of-service badge.

    The markup and the class names below are the designer's own, emitted
    verbatim so the vendored template stylesheet resolves them. The prose is
    ours, taken from the approved About Us copy and from the settings an admin
    can edit, so the figures on the page and the figures in the CMS can never
    drift apart.

    Everything the designer wrote that we could not stand behind has been
    dropped rather than reworded around — the approval status of the company,
    where it is registered as a Hajj operator, its city, the Ramadan and
    year-round claims for Umrah, the tourism destinations, and the promise about
    when rooms are confirmed. None of those are in the approved copy, and a
    design port is not the place to invent them.

    The template faded each block in with Framer Motion, which we do not ship;
    the `reveal-on-scroll` hooks the rest of the site uses stand in for it. The
    button's second, offset arrow existed only to be the hidden half of that
    fade and is gone — leaving it in would have drawn the arrow twice over
    itself and come out heavier than the reference.
--}}
<section id="introduction" class="relative overflow-hidden bg-surface py-16 text-accent-500 sm:py-20 lg:py-24">
    <svg aria-hidden="true" focusable="false" class="pointer-events-none absolute inset-0 h-full w-full" style="opacity:0.035"><defs><pattern id="ub-star-150" width="150" height="150" patternUnits="userSpaceOnUse"><polygon points="37.50,20.25 40.54,30.17 49.70,25.30 44.83,34.46 54.75,37.50 44.83,40.54 49.70,49.70 40.54,44.83 37.50,54.75 34.46,44.83 25.30,49.70 30.17,40.54 20.25,37.50 30.17,34.46 25.30,25.30 34.46,30.17" fill="none" stroke="currentColor" stroke-width="1"></polygon><polygon points="112.50,95.25 115.54,105.17 124.70,100.30 119.83,109.46 129.75,112.50 119.83,115.54 124.70,124.70 115.54,119.83 112.50,129.75 109.46,119.83 100.30,124.70 105.17,115.54 95.25,112.50 105.17,109.46 100.30,100.30 109.46,105.17" fill="none" stroke="currentColor" stroke-width="1"></polygon></pattern></defs><rect width="100%" height="100%" fill="url(#ub-star-150)"></rect></svg>
    <div aria-hidden="true" class="pointer-events-none absolute right-0 top-1/4 hidden h-[520px] w-[520px] translate-x-1/3 rounded-full bg-[radial-gradient(circle,var(--color-accent-500),transparent_68%)] opacity-[0.13] lg:block"></div>
    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10 relative">
        <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-16 xl:gap-20">
            <div>
                <div class="reveal-on-scroll"><p class="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-500"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-500 text-accent-500" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Universal Brothers</p></div>
                <div class="reveal-on-scroll reveal-delay-1"><h2 class="font-display text-3xl font-bold uppercase leading-[1.1] sm:text-4xl lg:text-[42px]"><span class="text-heading">Three Journeys.</span> <span class="text-accent-500">One Standard of Care.</span></h2></div>
                <div class="reveal-on-scroll reveal-delay-2"><span aria-hidden="true" class="mt-6 flex items-center gap-2"><span class="h-px w-16 bg-accent-500/60"></span><span class="size-1.5 rotate-45 bg-accent-500"></span><span class="h-px w-8 bg-accent-500/25"></span></span></div>
                <div class="mt-6 space-y-4">
                    <div class="reveal-on-scroll reveal-delay-2"><p class="text-sm leading-relaxed text-foreground/85 sm:text-[15px]">Universal Brothers (Pvt) Ltd is a company of {{ \App\Models\SiteSetting::get('parent_group', "Maxim's Group") }}, operating as an Umrah &amp; Hajj Organizer and Travel &amp; Tours Operator under the brand &ldquo;Crown Packages&rdquo;. In {{ $stats['years'] }} years of operation it has served {{ $stats['pilgrims'] }} Hajis, and it is registered with IATA.</p></div>
                    <div class="reveal-on-scroll reveal-delay-3"><p class="text-sm leading-relaxed text-foreground/85 sm:text-[15px]">Three kinds of journey leave with us. Hajj, arranged down to the camp itself — our Mina camp stands near Jamarat{{ $stats['mina_camp_location'] ? ', at ' . $stats['mina_camp_location'] : '' }}. Umrah, run to the same standard. And travel and tours, which the company runs alongside both.</p></div>
                    <div class="reveal-on-scroll reveal-delay-4"><p class="text-sm leading-relaxed text-foreground/85 sm:text-[15px]">What does not change between them is how the journey is run: a choice of hotels near the Haram in Makkah and in Madinah, a Mufti or Aalim travelling with the group for guidance throughout, and personalized, escorted service at every step.</p></div>
                </div>
                <div class="reveal-on-scroll reveal-delay-5"><div class="mt-8"><a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-10 pl-5 pr-1.5 text-xs sm:h-12 sm:pl-6 sm:pr-2 sm:text-sm" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ url('/about-us') }}"><span class="relative">More About Us</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-7 sm:size-8"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a></div></div>
            </div>
            <div class="reveal-on-scroll reveal-delay-2">
                <div class="relative">
                    <div class="grid grid-cols-3 gap-3 sm:gap-4 lg:h-[480px] lg:grid-cols-2 lg:grid-rows-2 lg:gap-4">
                        <figure class="group relative aspect-[3/4] lg:aspect-auto lg:col-start-2 lg:row-start-1"><div class="relative h-full w-full overflow-hidden bg-gradient-teal shadow-[0_16px_28px_-14px_rgba(6,18,25,0.45)] transition-shadow duration-300 hover:shadow-[0_28px_46px_-16px_rgba(6,18,25,0.6)] rounded-2xl ring-1 ring-accent-500/25" tabindex="0"><div role="img" aria-label="{{ \App\Support\SiteImagery::alt('mina-tents') }}" class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ \App\Support\SiteImagery::url('mina-tents') }}')"></div><div aria-hidden="true" class="absolute inset-0 bg-brand-950" style="opacity:0.4"></div></div><figcaption class="pointer-events-none absolute inset-x-0 bottom-0 rounded-b-2xl bg-gradient-to-t from-brand-950/90 to-transparent px-3 pb-3 pt-10"><span class="text-[11px] font-bold uppercase tracking-[0.18em] text-ivory sm:text-xs">Hajj</span></figcaption></figure>
                        <figure class="group relative aspect-[3/4] lg:aspect-auto lg:col-start-1 lg:row-span-2 lg:row-start-1"><div class="relative h-full w-full overflow-hidden bg-gradient-teal shadow-[0_16px_28px_-14px_rgba(6,18,25,0.45)] transition-shadow duration-300 hover:shadow-[0_28px_46px_-16px_rgba(6,18,25,0.6)] rounded-2xl ring-1 ring-accent-500/25" tabindex="0"><div role="img" aria-label="{{ \App\Support\SiteImagery::alt('haram-courtyard') }}" class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ \App\Support\SiteImagery::url('haram-courtyard') }}')"></div><div aria-hidden="true" class="absolute inset-0 bg-brand-950" style="opacity:0.4"></div></div><figcaption class="pointer-events-none absolute inset-x-0 bottom-0 rounded-b-2xl bg-gradient-to-t from-brand-950/90 to-transparent px-3 pb-3 pt-10"><span class="text-[11px] font-bold uppercase tracking-[0.18em] text-ivory sm:text-xs">Umrah</span></figcaption></figure>
                        <figure class="group relative aspect-[3/4] lg:aspect-auto lg:col-start-2 lg:row-start-2"><div class="relative h-full w-full overflow-hidden bg-gradient-teal shadow-[0_16px_28px_-14px_rgba(6,18,25,0.45)] transition-shadow duration-300 hover:shadow-[0_28px_46px_-16px_rgba(6,18,25,0.6)] rounded-2xl ring-1 ring-accent-500/25" tabindex="0"><div role="img" aria-label="{{ \App\Support\SiteImagery::alt('hunza-attabad') }}" class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ \App\Support\SiteImagery::url('hunza-attabad') }}')"></div><div aria-hidden="true" class="absolute inset-0 bg-brand-950" style="opacity:0.4"></div></div><figcaption class="pointer-events-none absolute inset-x-0 bottom-0 rounded-b-2xl bg-gradient-to-t from-brand-950/90 to-transparent px-3 pb-3 pt-10"><span class="text-[11px] font-bold uppercase tracking-[0.18em] text-ivory sm:text-xs">Tourism</span></figcaption></figure>
                    </div>
                    <div class="mt-4 flex justify-end sm:absolute sm:-top-4 sm:right-3 sm:z-10 sm:mt-0 lg:-top-5 lg:right-4"><div class="flex items-center gap-3 rounded-2xl bg-accent-500 px-4 py-2.5 shadow-[0_18px_40px_-18px_rgba(6,18,25,0.85)] sm:px-5 sm:py-3"><span class="font-display text-2xl font-bold leading-none text-inverse sm:text-3xl">{{ $stats['years'] }}</span><span class="whitespace-pre-line text-[10px] font-bold uppercase leading-tight tracking-[0.16em] text-inverse/75">Years of
Service</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>
