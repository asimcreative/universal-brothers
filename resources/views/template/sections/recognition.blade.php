{{--
    "Awards & Affiliations" — the row of recognition cards, plus the links
    through to the full Awards and Affiliations pages.

    The markup and every class name here are the designer's own, copied from his
    template so the vendored stylesheet in `public/template/css/template.css`
    matches them exactly. The cards themselves are ours, built from the `awards`
    and `affiliations` tables, so what shows here is only ever what an admin has
    actually published.

    Two deliberate departures from the designer's file, both because the thing
    he relied on is not something we ship:

    1. He filled these cards with sample content — group companies, dated award
       titles, accreditation strap-lines. None of that is in our CMS and none of
       it is ours to assert, so it is all dropped. An empty group renders no
       cards at all rather than placeholder ones.

    2. His row was an endless marquee driven by a Framer Motion transform, with
       the whole track duplicated so the loop looked seamless. We do not ship
       Framer, and an un-animated `w-max` track inside `overflow-hidden` would
       leave every card past the fold permanently unreachable. The row is
       therefore scrollable (`overflow-x-auto`), and the duplicated copy of the
       track is gone — with no animation it was the same cards a second time,
       read out twice by a screen reader.
--}}

@php
    use App\Support\SiteImagery;

    // `resolve()` is what the rest of the site already uses for a stored CMS
    // path: it returns null when the column is empty, passes an absolute URL
    // straight through, and otherwise hands off to Storage::url(). That null
    // return is what the `@if`s below guard on.
    $ubHasCards = $awards->isNotEmpty() || $affiliations->isNotEmpty();
@endphp

<section id="affiliations" class="bg-sand-100 py-16 sm:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
        <header class="mx-auto max-w-2xl text-center">
            <div class="reveal-on-scroll">
                <p class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-800"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Awards &amp; Affiliations</p>
            </div>
            <div class="reveal-on-scroll reveal-delay-1">
                <h2 class="font-display text-3xl font-bold uppercase leading-tight sm:text-4xl lg:text-5xl"><span class="text-inverse">Recognised, Accredited</span> <span class="text-accent-700">&amp; Well Connected</span></h2>
            </div>
            <div class="reveal-on-scroll reveal-delay-2">
                <p class="mt-4 text-sm leading-relaxed text-ink-600 sm:text-base">The awards our work has earned and the organisations we are affiliated with.</p>
            </div>

            {{-- The count is a Setting, not a COUNT(*) of the table — an admin
                 may publish only a selection of the awards we hold. It is a DIV
                 (the counter component renders one), so it sits beside the
                 paragraph above rather than inside it. --}}
            <div class="reveal-on-scroll reveal-delay-3">
                <div class="mt-6 flex items-center justify-center gap-3">
                    <x-stat-number :display="$stats['awards_count']" :target="$counters['industry_awards'] ?? null" class="font-display text-3xl font-bold text-accent-700" />
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-accent-800">Awards &amp; Recognitions</span>
                </div>
            </div>
        </header>
    </div>

    @if($ubHasCards)
        <div class="reveal-on-scroll reveal-delay-4">
            <div class="relative overflow-x-auto mt-11 py-3 [mask-image:linear-gradient(to_right,transparent,#000_7%,#000_93%,transparent)] lg:mt-14">
                <div class="flex w-max flex-nowrap items-stretch">

                    @if($awards->isNotEmpty())
                        @foreach($awards as $award)
                            @php $ubAwardImage = SiteImagery::resolve($award->image); @endphp
                            <div class="mr-4 shrink-0 sm:mr-5">
                                <div class="group flex h-[172px] w-[224px] flex-col items-center gap-2 rounded-card border border-inverse/12 bg-white px-4 py-4 text-center transition-all duration-300 sm:h-[190px] sm:w-[268px] sm:px-5">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.18em] text-accent-800/70">Award</span>
                                    <span class="flex max-h-[72px] w-full min-h-0 flex-1 items-center justify-center sm:max-h-[92px]">
                                        @if($ubAwardImage)
                                            <img src="{{ $ubAwardImage }}" alt="{{ $award->name }}" class="max-h-full max-w-full object-contain" loading="lazy" decoding="async">
                                        @else
                                            {{-- No photograph on file: the award's own name is
                                                 the card, rather than an empty plate. --}}
                                            <span class="font-display text-[15px] font-bold leading-snug text-inverse">{{ $award->name }}</span>
                                        @endif
                                    </span>
                                    @if($ubAwardImage)
                                        <span class="text-[11px] leading-snug text-ink-400">{{ $award->name }}@if($award->year) &middot; {{ $award->year }}@endif</span>
                                    @elseif($award->year)
                                        <span class="text-[11px] leading-snug text-ink-400">{{ $award->year }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @if($affiliations->isNotEmpty())
                        @foreach($affiliations as $affiliation)
                            @php $ubAffiliationLogo = SiteImagery::resolve($affiliation->logo); @endphp
                            <div class="mr-4 shrink-0 sm:mr-5">
                                {{-- The designer drew this card twice: a plain one, and a
                                     linked one with the hover lift and focus ring. Which we
                                     emit depends on whether the organisation has a link on
                                     file — the same two cards, chosen by the data. --}}
                                @if($affiliation->link)
                                    <a href="{{ $affiliation->link }}" target="_blank" rel="noopener noreferrer" class="group flex h-[172px] w-[224px] flex-col items-center gap-2 rounded-card border border-inverse/12 bg-white px-4 py-4 text-center transition-all duration-300 sm:h-[190px] sm:w-[268px] sm:px-5 hover:-translate-y-1 hover:border-accent-700/45 hover:shadow-[0_18px_34px_-20px_rgba(6,18,25,0.5)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-700 focus-visible:ring-offset-2 focus-visible:ring-offset-sand-100">
                                        <span class="text-[9px] font-bold uppercase tracking-[0.18em] text-accent-800/70">Affiliation</span>
                                        <span class="flex max-h-[72px] w-full min-h-0 flex-1 items-center justify-center sm:max-h-[92px]">
                                            @if($ubAffiliationLogo)
                                                <img src="{{ $ubAffiliationLogo }}" alt="{{ $affiliation->organization_name }}" class="max-h-full max-w-full object-contain" loading="lazy" decoding="async">
                                            @else
                                                <span class="font-display text-[15px] font-bold leading-snug text-inverse">{{ $affiliation->organization_name }}</span>
                                            @endif
                                        </span>
                                        @if($affiliation->description)
                                            <span class="text-[11px] leading-snug text-ink-400">{{ $affiliation->description }}</span>
                                        @elseif($ubAffiliationLogo)
                                            {{-- An unfamiliar mark on its own identifies nothing,
                                                 so the name is printed under it. --}}
                                            <span class="text-[11px] leading-snug text-ink-400">{{ $affiliation->organization_name }}</span>
                                        @endif
                                    </a>
                                @else
                                    <div class="group flex h-[172px] w-[224px] flex-col items-center gap-2 rounded-card border border-inverse/12 bg-white px-4 py-4 text-center transition-all duration-300 sm:h-[190px] sm:w-[268px] sm:px-5">
                                        <span class="text-[9px] font-bold uppercase tracking-[0.18em] text-accent-800/70">Affiliation</span>
                                        <span class="flex max-h-[72px] w-full min-h-0 flex-1 items-center justify-center sm:max-h-[92px]">
                                            @if($ubAffiliationLogo)
                                                <img src="{{ $ubAffiliationLogo }}" alt="{{ $affiliation->organization_name }}" class="max-h-full max-w-full object-contain" loading="lazy" decoding="async">
                                            @else
                                                <span class="font-display text-[15px] font-bold leading-snug text-inverse">{{ $affiliation->organization_name }}</span>
                                            @endif
                                        </span>
                                        @if($affiliation->description)
                                            <span class="text-[11px] leading-snug text-ink-400">{{ $affiliation->description }}</span>
                                        @elseif($ubAffiliationLogo)
                                            <span class="text-[11px] leading-snug text-ink-400">{{ $affiliation->organization_name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
    @endif

    @if($ubHasCards)
        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
            <div class="reveal-on-scroll reveal-delay-5">
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    @if($awards->isNotEmpty())
                        <a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-11 pl-4 pr-1.5 text-xs sm:h-14 sm:pl-8 sm:pr-2.5 sm:text-[15px]" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ route('awards') }}"><span class="relative">All Awards</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-8 sm:size-9"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a>
                    @endif
                    @if($affiliations->isNotEmpty())
                        <a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-11 pl-4 pr-1.5 text-xs sm:h-14 sm:pl-8 sm:pr-2.5 sm:text-[15px]" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ route('affiliations') }}"><span class="relative">All Affiliations</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-8 sm:size-9"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>
