{{--
    "Why Universal Brothers?" — the two-photograph collage beside the six
    reasons to travel with us, and the link through to the About page.

    The markup and every class name here are the designer's own, copied from his
    template so the vendored stylesheet in `public/template/css/template.css`
    matches them exactly. Only the content is ours: the figures come from the
    admin-editable Settings via `$stats`, the photographs from our own shipped
    library, and the six points are the approved list.

    The template animated with Framer Motion, which we do not ship, so each
    element it faded in carries `reveal-on-scroll` instead. The designer's
    inline `opacity:0` / `transform` pairs are deliberately NOT copied — left in
    place with no motion library to clear them, the whole section would render
    permanently invisible. The two inline styles that ARE kept are real design,
    not motion: the photographic `background-image`, and the 0.35 scrim that
    darkens each photograph so the card's rounded edge stays readable.
--}}

@php
    use App\Support\SiteImagery;

    // Makkah on the large plate, Madinah on the small ringed one — the same
    // pairing the designer composed, drawn from our own photograph registry so
    // the aria-label describes the image we actually serve.
    $ubPlateOne = 'haram-courtyard';
    $ubPlateTwo = 'nabawi-dome';
@endphp

<section id="why-us" class="overflow-hidden bg-ivory py-16 sm:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

            {{-- The collage: one large plate behind, one smaller ringed plate
                 overlapping it at the top right. --}}
            <div class="relative aspect-[66/69] w-full">
                <div class="absolute left-[3%] top-[20%] h-[75%] w-[63%] hover:z-20 reveal-on-scroll">
                    <div class="relative h-full w-full overflow-hidden bg-gradient-teal shadow-[0_16px_28px_-14px_rgba(6,18,25,0.45)] transition-shadow duration-300 hover:shadow-[0_28px_46px_-16px_rgba(6,18,25,0.6)] rounded-[20px] sm:rounded-[28px]" tabindex="0">
                        <div role="img" aria-label="{{ SiteImagery::alt($ubPlateOne) }}" class="absolute inset-0 bg-cover bg-center"
                             @if(SiteImagery::url($ubPlateOne)) style="background-image:url({{ SiteImagery::url($ubPlateOne) }})" @endif></div>
                        <div aria-hidden="true" class="absolute inset-0 bg-brand-950" style="opacity:0.35"></div>
                    </div>
                </div>
                <div class="absolute right-0 top-[4%] z-10 h-[62%] w-[55%] hover:z-20 reveal-on-scroll reveal-delay-2">
                    <div class="relative h-full w-full overflow-hidden bg-gradient-teal shadow-[0_16px_28px_-14px_rgba(6,18,25,0.45)] transition-shadow duration-300 hover:shadow-[0_28px_46px_-16px_rgba(6,18,25,0.6)] rounded-[20px] sm:rounded-[28px] ring-[6px] ring-sand-100" tabindex="0">
                        <div role="img" aria-label="{{ SiteImagery::alt($ubPlateTwo) }}" class="absolute inset-0 bg-cover bg-center"
                             @if(SiteImagery::url($ubPlateTwo)) style="background-image:url({{ SiteImagery::url($ubPlateTwo) }})" @endif></div>
                        <div aria-hidden="true" class="absolute inset-0 bg-brand-950" style="opacity:0.35"></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="reveal-on-scroll">
                    <p class="mb-5 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-800"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Why Universal Brothers?</p>
                </div>
                <div class="reveal-on-scroll reveal-delay-1">
                    <h2 class="font-display text-3xl font-bold leading-tight sm:text-4xl lg:text-[42px]"><span class="text-inverse">A Name Built on Trust.</span> <span class="text-accent-700">A Service Built Around You.</span></h2>
                </div>
                <div class="reveal-on-scroll reveal-delay-2">
                    <p class="mt-6 text-sm leading-relaxed text-ink-600 sm:text-base">For more than {{ $stats['years'] }} years, Universal Brothers has combined experience with personal care to deliver thoughtfully managed Hajj and Umrah journeys. Every pilgrim has different expectations and circumstances, so travel, accommodation, transport, guidance and on-ground assistance are coordinated around them.</p>
                </div>

                <ul class="mt-10 grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                    <li>
                        <div class="reveal-on-scroll reveal-delay-1">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-history" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path><path d="M12 7v5l4 2"></path></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">{{ $stats['years'] }} Years of Experience</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Decades of specialized Hajj and Umrah expertise.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="reveal-on-scroll reveal-delay-2">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">{{ $stats['pilgrims'] }} Hajis Served</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Thousands of pilgrims have travelled under our care.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="reveal-on-scroll reveal-delay-3">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award" aria-hidden="true"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">{{ $stats['awards_count'] }} Awards</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Recognition for service and professional excellence.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="reveal-on-scroll reveal-delay-4">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart-handshake" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path><path d="M12 5 9.04 7.96a2.17 2.17 0 0 0 0 3.08c.82.82 2.13.85 3 .07l2.07-1.9a2.82 2.82 0 0 1 3.79 0l2.96 2.66"></path><path d="m18 15-2-2"></path><path d="m15 18-2-2"></path></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">Personalized Assistance</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Individual attention before, during and after the journey.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="reveal-on-scroll reveal-delay-5">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-briefcase" aria-hidden="true"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><rect width="20" height="14" x="2" y="6" rx="2"></rect></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">Experienced Team</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Professionals who understand the complexities of pilgrimage travel.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="reveal-on-scroll reveal-delay-6">
                            <div class="group flex gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full border border-2 border-inverse/70 bg-sand-100 text-accent-700 transition-colors duration-300 group-hover:border-accent-700/50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-life-buoy" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle></svg></span>
                                <div class="min-w-0">
                                    <h3 class="font-display text-[15px] font-bold leading-snug text-inverse sm:text-base">On-Ground Support</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-500">Assistance where it matters most throughout your sacred journey.</p>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                {{-- The gradient on this button is painted by an inline
                     `background-image` in the designer's own markup, not by a
                     class, so it is kept verbatim — drop it and the pill loses
                     its brand-to-copper wipe entirely.

                     The designer stacked TWO arrow glyphs in the same grid cell
                     and crossfaded them on hover with Framer. Only one is
                     emitted here: the second was held off-screen by
                     `opacity:0`, and with no motion library to animate it the
                     pair would simply sit on top of each other. --}}
                <div class="reveal-on-scroll">
                    <div class="mt-10">
                        <a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-11 pl-4 pr-1.5 text-xs sm:h-14 sm:pl-8 sm:pr-2.5 sm:text-[15px]" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ url('/about-us') }}"><span class="relative">Read More About Us</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-8 sm:size-9"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
