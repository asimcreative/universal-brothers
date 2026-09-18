{{--
    Services — the three-card row that sits under the hero.

    The markup, the class names and the nesting below are the designer's own,
    taken from the reference template so that the compiled stylesheet in
    `public/template/css/template.css` matches it rule for rule. Only the
    content is ours: the cards are driven by the package categories and the
    photograph library, and the copy is the approved service wording rather
    than the placeholder lines the template shipped with.

    Two things were changed on purpose. The template animates with Framer
    Motion, which leaves inline `opacity`/`transform` styles on the wrappers —
    those are stripped and replaced with the site's own `reveal-on-scroll`
    hooks, so nothing is left invisible if a bundle fails to load. And the
    placeholder copy made claims we cannot stand behind (a Mina zone, a hotel
    distance), so each card carries our approved sentence instead; the Mina
    camp is stated only when the client has actually set it.
--}}

@php
    // One card per category that exists and is active — a category the client
    // has switched off simply loses its card rather than linking to an empty
    // listing. Building the list here keeps the three cards identical in
    // markup, which is the whole point of serving the designer's classes.
    $ubServices = array_values(array_filter([
        $hajjCategory ? [
            'name' => $hajjCategory->name,
            'href' => route('hajj-services'),
            'photo' => \App\Support\SiteImagery::url('haram-dusk'),
            'alt' => \App\Support\SiteImagery::alt('haram-dusk', 'Hajj'),
            'copy' => 'Real 1448 AH itineraries and our own ground team.'
                . (filled($stats['mina_camp_location'] ?? null)
                    ? ' Mina camp at ' . $stats['mina_camp_location'] . '.'
                    : ''),
        ] : null,
        $umrahCategory ? [
            'name' => $umrahCategory->name,
            'href' => route('umrah-services'),
            'photo' => \App\Support\SiteImagery::url('nabawi-aerial'),
            'alt' => \App\Support\SiteImagery::alt('nabawi-aerial', 'Umrah'),
            'copy' => 'All year round, arranged around your dates.',
        ] : null,
        $tourismCategory ? [
            'name' => $tourismCategory->name,
            'href' => route('packages.category', 'tourism'),
            'photo' => \App\Support\SiteImagery::url('hunza-attabad'),
            'alt' => \App\Support\SiteImagery::alt('hunza-attabad', 'Tourism'),
            'copy' => 'Northern Pakistan and destinations abroad.',
        ] : null,
    ]));
@endphp

@if($ubServices)
<section id="services" class="bg-ivory py-16 sm:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
        <header class="text-center">
            <div class="reveal-on-scroll">
                <p class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-800"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Services</p>
            </div>
            <div class="reveal-on-scroll reveal-delay-1">
                <h2 class="font-display text-3xl font-bold uppercase leading-tight sm:text-4xl lg:text-5xl"><span class="text-accent-700">Our Premium</span> <span class="text-inverse">Services</span></h2>
            </div>
        </header>

        <div class="mt-11 grid gap-4 sm:gap-5 lg:mt-14 lg:grid-cols-3">
            @foreach($ubServices as $ubService)
                <div class="h-full reveal-on-scroll reveal-delay-{{ $loop->iteration }}">
                    <a class="group flex h-full min-h-[178px] gap-4 rounded-card border border-border bg-surface p-4 shadow-[0_10px_30px_-24px_rgba(6,20,24,0.5)] transition-all duration-300 hover:-translate-y-1 hover:border-accent-500/45 hover:shadow-[0_24px_50px_-30px_rgba(6,20,24,0.65)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-ivory sm:min-h-[202px] sm:gap-5 sm:p-[18px]" href="{{ $ubService['href'] }}">
                        <div class="w-[136px] shrink-0 self-stretch overflow-hidden rounded-[14px] bg-gradient-teal sm:w-[168px]">
                            <div role="img" aria-label="{{ $ubService['alt'] }}" class="h-full w-full bg-cover bg-center transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-110"@if($ubService['photo']) style="background-image:url('{{ $ubService['photo'] }}')"@endif></div>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div>
                                <h3 class="font-display text-[26px] font-bold leading-tight text-heading transition-colors duration-300 group-hover:text-accent-300 sm:text-[30px]">{{ $ubService['name'] }}</h3>
                                <p class="mt-2 text-[13px] leading-snug text-foreground/85 sm:text-sm">{{ $ubService['copy'] }}</p>
                            </div>
                            <span aria-hidden="true" class="mt-auto flex size-9 shrink-0 items-center justify-center self-end rounded-full border border-accent-500 bg-accent-500 text-inverse transition-all duration-300 group-hover:border-ivory group-hover:bg-ivory sm:size-10"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="reveal-on-scroll">
            <p class="mx-auto mt-12 max-w-2xl text-center text-sm leading-relaxed text-ink-600 sm:text-base">Visas, flights, hotels and complete ziyarat guidance — every part of the journey handled by the same team. @if($hajjCategory)<a class="font-semibold text-accent-800 underline decoration-accent-800/40 underline-offset-4 transition-colors hover:text-inverse hover:decoration-inverse" href="{{ route('hajj-services') }}">See all services</a>@endif</p>
        </div>
    </div>
</section>
@endif
