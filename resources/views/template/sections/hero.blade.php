@php
    // The template runs a three-slide carousel. Ours shows whatever the CMS
    // holds: every active home Slider row, or — when none has been added, which
    // is the case today — one editorial slide built from approved copy. The
    // dots and the auto-advance only appear when there is more than one, so
    // nothing here ever pretends to be a carousel it is not.
    $ubSlides = $sliders->map(fn ($slide) => [
        'image' => \Illuminate\Support\Facades\Storage::url($slide->image),
        'eyebrow' => ['Hajj', 'Umrah', 'Tourism'],
        'title' => $slide->title,
        'accent' => null,
        'lead' => $slide->subtitle,
        'primary' => $slide->cta_label ? ['label' => $slide->cta_label, 'url' => $slide->cta_url] : null,
        'secondary' => $slide->secondary_cta_label ? ['label' => $slide->secondary_cta_label, 'url' => $slide->secondary_cta_url] : null,
    ]);

    if ($ubSlides->isEmpty()) {
        $ubSlides = collect([[
            'image' => \App\Support\SiteImagery::url('kaaba-tawaf'),
            'eyebrow' => ['Hajj', 'Umrah', 'Tourism'],
            'title' => 'A Sacred Journey.',
            'accent' => 'A Trusted Name.',
            'lead' => 'For more than ' . $stats['years'] . ' years, we have planned, guided and supported the sacred journeys of thousands of pilgrims.',
            'primary' => $hajjCategory ? ['label' => 'Explore Hajj 2027', 'url' => route('packages.category', 'hajj')] : null,
            'secondary' => $umrahCategory ? ['label' => 'Plan Your Umrah', 'url' => route('umrah-services')] : null,
        ]]);
    }
@endphp

{{--
    The designer's hero, with our photography and our words.

    The class names are the template's own and resolve against
    `public/template/css/template.css`; the structure has to match it rather
    than resemble it. What is ours: the photograph, the headline, the two
    actions, and the handwritten closing line — which is the company's own
    tagline, already carried in the footer as plain text.
--}}
<section class="relative isolate flex min-h-[100svh] flex-col overflow-hidden" data-ub-hero>
    @foreach($ubSlides as $ubIndex => $ubSlide)
        <div class="absolute inset-0 transition-opacity duration-700" data-ub-slide="{{ $ubIndex }}" @if($ubIndex > 0) style="opacity:0" @endif aria-hidden="{{ $ubIndex === 0 ? 'false' : 'true' }}">
            <div class="absolute inset-0 bg-gradient-teal"></div>
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $ubSlide['image'] }}')"></div>
            <div class="absolute inset-0 bg-brand-950/50"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-950/25 to-brand-950/70"></div>
        </div>
    @endforeach

    @php $ubFirst = $ubSlides->first(); @endphp

    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10 relative z-10 flex flex-1 flex-col items-center justify-center pb-28 pt-40 text-center lg:pt-48">
        <div class="w-full">
            <p class="mb-5 flex flex-wrap items-center justify-center gap-x-2.5 gap-y-1 text-xs font-bold uppercase tracking-[0.16em] text-accent-400 sm:mb-6 sm:gap-x-3 sm:text-sm sm:tracking-[0.18em]">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-500 text-accent-500" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>
                @foreach($ubFirst['eyebrow'] as $ubWord)
                    <span class="flex items-center gap-x-2.5 sm:gap-x-3">@if(! $loop->first)<span aria-hidden="true" class="text-accent-500/50">&middot;</span>@endif{{ $ubWord }}</span>
                @endforeach
            </p>

            <h1 class="mx-auto max-w-5xl text-balance font-display text-[38px] font-bold uppercase leading-[1.1] tracking-tight text-ivory sm:text-5xl sm:leading-[1.06] lg:text-6xl xl:text-[68px]">
                <span class="block" data-ub-slide-title>{{ $ubFirst['title'] }}</span>
                @if($ubFirst['accent'])<span class="block text-accent-300" data-ub-slide-accent>{{ $ubFirst['accent'] }}</span>@endif
            </h1>

            @if($ubFirst['lead'])
                <p class="mx-auto mt-5 max-w-xl text-pretty text-sm leading-relaxed text-ivory/80 sm:mt-7 sm:text-lg" data-ub-slide-lead>{{ $ubFirst['lead'] }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center justify-center gap-2.5 sm:mt-10 sm:gap-4">
                @if($ubFirst['primary'])
                    <a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-11 pl-4 pr-1.5 text-xs sm:h-14 sm:pl-8 sm:pr-2.5 sm:text-[15px]" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ $ubFirst['primary']['url'] }}">
                        <span class="relative">{{ $ubFirst['primary']['label'] }}</span>
                        <span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-8 sm:size-9">
                            <span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span>
                        </span>
                    </a>
                @endif

                @if($ubFirst['secondary'])
                    <a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent text-inverse shadow-lg shadow-brand-950/20 hover:text-inverse h-11 pl-4 pr-1.5 text-xs sm:h-14 sm:pl-8 sm:pr-2.5 sm:text-[15px]" style="background-image:linear-gradient(to right, var(--color-accent-500) 0 50%, var(--color-ivory) 50% 100%)" href="{{ $ubFirst['secondary']['url'] }}">
                        <span class="relative">{{ $ubFirst['secondary']['label'] }}</span>
                        <span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-accent-500 text-inverse group-hover:bg-ivory group-hover:text-accent-700 size-8 sm:size-9">
                            <span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span>
                        </span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <p class="pointer-events-none absolute right-10 top-[58%] z-10 hidden max-w-[240px] text-right font-hand text-[28px] leading-snug text-accent-300 xl:block 2xl:right-16 2xl:text-[34px]" style="transform: rotate(-5deg)" aria-hidden="true">&ldquo;Serving journeys. Building trust.&rdquo;<span aria-hidden="true" class="mt-2 ml-auto block h-px w-24 bg-gradient-to-l from-accent-400 to-transparent"></span></p>

    @if($ubSlides->count() > 1)
        <div class="relative z-10 flex justify-center pb-8">
            <div class="flex items-center justify-center gap-2.5" role="tablist" aria-label="Hero slides">
                @foreach($ubSlides as $ubIndex => $ubSlide)
                    <button type="button" role="tab" aria-selected="{{ $ubIndex === 0 ? 'true' : 'false' }}" aria-label="Go to slide {{ $ubIndex + 1 }}" data-ub-dot="{{ $ubIndex }}" class="group relative h-2.5 rounded-full after:absolute after:-inset-x-2.5 after:-inset-y-4 after:content-['']">
                        <span class="block h-2.5 rounded-full transition-all duration-300 {{ $ubIndex === 0 ? 'w-7 bg-transparent' : 'w-2.5 bg-ivory/45 group-hover:bg-ivory/70' }}"></span>
                        @if($ubIndex === 0)<span class="absolute inset-0 rounded-full bg-accent-500"></span>@endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="relative z-10 flex justify-center pb-10">
        <a href="#ub-after-hero" aria-label="Scroll to next section" class="flex size-11 items-center justify-center rounded-pill border border-ivory/35 text-ivory transition-colors hover:border-accent-500 hover:bg-accent-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-down animate-bounce-slow"><path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path></svg>
        </a>
    </div>
</section>
