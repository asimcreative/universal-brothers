@php
    $ubRegisterUrl = 'https://hums.akhg.com.pk/HajiReg/HajiLead';
    $ubHajj = $navCategories->firstWhere('slug', 'hajj');
    $ubUmrah = $navCategories->firstWhere('slug', 'umrah');
    $ubTourism = $navCategories->firstWhere('slug', 'tourism');
    $ubWhatsapp = $primaryOffice?->whatsapp;
    $ubFacebook = \App\Models\SiteSetting::get('social_facebook');
    $ubInstagram = \App\Models\SiteSetting::get('social_instagram');
    $ubYoutube = \App\Models\SiteSetting::get('social_youtube');

    // Our ten top-level items, in the order the client asked for them. The
    // template's own bar carries five, at `gap-8` and 15px; ten at that spacing
    // overflow a 1440px screen, so the gap and size step down at xl and the
    // structure is otherwise the designer's untouched.
    $ubNav = collect([
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'About Us', 'url' => url('/about-us')],
        ['label' => 'Hajj & Umrah', 'url' => $ubHajj ? route('hajj-services') : ($ubUmrah ? route('umrah-services') : null)],
        ['label' => 'Tourism', 'url' => $ubTourism ? route('packages.category', 'tourism') : null],
        ['label' => 'Awards & Recognition', 'url' => route('awards')],
        ['label' => 'Affiliations', 'url' => route('affiliations')],
        ['label' => 'Media', 'url' => route('media')],
        ['label' => 'Testimonials', 'url' => route('testimonials')],
        ['label' => 'FAQs', 'url' => route('faqs')],
        ['label' => 'Contact', 'url' => route('contact')],
    ])->filter(fn ($item) => $item['url'] !== null)->values();
@endphp

{{--
    The designer's header, with our menu and our details.

    Every class here is the template's own — `bg-brand-800`, `text-ivory/80`,
    `max-w-[var(--container-site)]` and the rest resolve against the compiled
    stylesheet in `public/template/css/template.css`, so the markup has to match
    it rather than merely resemble it.

    Two deliberate departures, both because the client asked for them earlier:
    the bar carries our ten items rather than the template's five, and the
    announcement strip is fed by our News articles rather than the designer's
    placeholder lines. The strip renders nothing at all when no article is
    published, which is why it can look empty on a fresh database.
--}}
<div class="absolute inset-x-0 top-0 z-50">
    {{-- Utility strip: contact on the left, announcements running through the
         middle, credentials and social on the right. --}}
    <div class="relative z-50 border-b border-ivory/10 bg-brand-800">
        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10 flex h-10 items-center gap-6">
            <div class="hidden shrink-0 items-center gap-5 text-xs text-ivory/80 lg:flex">
                @if($primaryOffice?->phone_primary)
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="flex items-center gap-2 transition-colors hover:text-sand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-accent-500"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>{{ $primaryOffice->phone_primary }}
                    </a>
                @endif
                @if($primaryOffice?->phone_primary && $primaryOffice?->email)
                    <span class="h-3.5 w-px bg-ivory/20"></span>
                @endif
                @if($primaryOffice?->email)
                    <a href="mailto:{{ $primaryOffice->email }}" class="flex items-center gap-2 transition-colors hover:text-sand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail text-accent-500"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>{{ $primaryOffice->email }}
                    </a>
                @endif
            </div>

            @if(($ubAnnouncements ?? collect())->isNotEmpty())
                <span class="hidden h-3.5 w-px shrink-0 bg-ivory/20 lg:block"></span>

                {{-- The list is rendered twice and the track animates by exactly
                     -50%, so the loop has no seam. The second copy is hidden
                     from assistive technology and from the tab order. --}}
                <div class="relative min-w-0 flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-6 bg-gradient-to-r from-brand-800 to-transparent sm:w-10"></div>
                    <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-6 bg-gradient-to-l from-brand-800 to-transparent sm:w-10"></div>
                    <div class="relative overflow-hidden">
                        <div class="ub-ticker-track flex w-max flex-nowrap items-center text-xs text-ivory/80">
                            @foreach([false, true] as $ubCopy)
                                <div class="flex shrink-0 flex-nowrap items-center" @if($ubCopy) aria-hidden="true" @endif>
                                    @foreach($ubAnnouncements as $ubItem)
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star mx-5 shrink-0 fill-sand-300 text-sand-300" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg>
                                            <a class="-my-3 inline-flex items-center py-3 transition-colors hover:text-sand-300" href="{{ route('news.show', $ubItem->slug) }}" @if($ubCopy) tabindex="-1" @endif>
                                                <span class="flex items-center gap-2.5 whitespace-nowrap">{{ $ubItem->title }}</span>
                                            </a>
                                        </span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="min-w-0 flex-1"></div>
            @endif

            <div class="hidden shrink-0 items-center gap-4 text-xs text-ivory/70 lg:flex">
                <span class="whitespace-nowrap">
                    @if(\App\Models\SiteSetting::get('iata_registered', '1'))IATA Registered &middot; @endif
                    Hajj License No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}
                </span>
                @if($ubFacebook || $ubInstagram || $ubYoutube)
                    <span class="h-3.5 w-px bg-ivory/20"></span>
                    <span class="flex items-center gap-3">
                        @if($ubFacebook)<a href="{{ $ubFacebook }}" target="_blank" rel="noopener" aria-label="Universal Brothers on Facebook" class="transition-colors hover:text-sand-300"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>@endif
                        @if($ubInstagram)<a href="{{ $ubInstagram }}" target="_blank" rel="noopener" aria-label="Universal Brothers on Instagram" class="transition-colors hover:text-sand-300"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg></a>@endif
                        @if($ubYoutube)<a href="{{ $ubYoutube }}" target="_blank" rel="noopener" aria-label="Universal Brothers on YouTube" class="transition-colors hover:text-sand-300"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-youtube"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path><path d="m10 15 5-3-5-3z"></path></svg></a>@endif
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Primary bar. It sits on the photograph and, once the hero has scrolled
         past, pins itself as a solid bar (see `initTemplateHeader` in app.js). --}}
    <header class="ub-site-header z-50 transition-[colors,transform] duration-300 relative bg-transparent translate-y-0">
        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10 flex h-20 items-center justify-between gap-6 lg:h-24">
            <a class="flex shrink-0 items-center gap-3" aria-label="Universal Brothers" href="{{ route('home') }}">
                <svg viewBox="0 0 44 48" class="h-10 w-auto shrink-0" aria-hidden="true"><path d="M22 1.5 41.5 12.75v22.5L22 46.5 2.5 35.25v-22.5L22 1.5Z" class="fill-none stroke-sand-300" stroke-width="2"></path><text x="22" y="30" text-anchor="middle" class="font-display font-bold fill-ivory" style="font-size:17px;letter-spacing:-0.02em">UB</text></svg>
                <span class="flex flex-col leading-none">
                    <span class="font-display text-[19px] font-bold whitespace-nowrap uppercase tracking-[0.06em] text-ivory">Universal <span class="text-accent-500">Brothers</span></span>
                    <span class="mt-1 whitespace-nowrap text-[10px] uppercase tracking-[0.22em] text-sand-300/80">Hajj &middot; Umrah &middot; Tourism</span>
                </span>
            </a>

            <nav class="ub-primary-nav" aria-label="Primary">
                <ul class="flex items-center gap-5">
                    @foreach($ubNav as $ubItem)
                        <li class="relative">
                            <a class="group flex items-center gap-1.5 py-8 text-sm font-medium text-ivory transition-colors hover:text-accent-400" href="{{ $ubItem['url'] }}">
                                <span class="relative whitespace-nowrap">{!! $ubItem['label'] !!}<span class="absolute -bottom-1 left-0 h-px bg-accent-500 transition-all duration-300 w-0 group-hover:w-full"></span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex shrink-0 items-center gap-3">
                <a class="group relative isolate items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-10 pl-5 pr-1.5 text-xs sm:h-12 sm:pl-6 sm:pr-2 sm:text-sm hidden sm:inline-flex" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" data-ub-register href="{{ $ubRegisterUrl }}" target="_blank" rel="noopener">
                    <span class="relative whitespace-nowrap">Register Now</span>
                    <span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-7 sm:size-8">
                        <span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span>
                    </span>
                </a>

                <button type="button" aria-label="Open menu" aria-expanded="false" aria-controls="ub-mobile-nav" data-ub-menu-open class="ub-menu-toggle flex size-11 items-center justify-center rounded-full border border-ivory/25 text-ivory transition-colors hover:bg-ivory/10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"></line><line x1="4" x2="20" y1="6" y2="6"></line><line x1="4" x2="20" y1="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </header>
</div>

{{-- Mobile drawer. The template's own is rendered by React; this is the same
     surface built as a plain dialog so it works without a framework. --}}
<div id="ub-mobile-nav" hidden class="fixed inset-0 z-[60]">
    <div class="absolute inset-0 bg-brand-900/70" data-ub-menu-close></div>
    <div role="dialog" aria-modal="true" aria-label="Menu" class="absolute inset-y-0 right-0 flex w-[min(20rem,88vw)] flex-col bg-brand-800 shadow-2xl">
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-ivory/10 px-6">
            <span class="font-display text-[17px] font-bold uppercase tracking-[0.06em] text-ivory">Universal <span class="text-accent-500">Brothers</span></span>
            <button type="button" aria-label="Close menu" data-ub-menu-close class="flex size-10 items-center justify-center rounded-full border border-ivory/25 text-ivory transition-colors hover:bg-ivory/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-6 py-6" aria-label="Menu">
            <ul class="flex flex-col gap-1">
                @foreach($ubNav as $ubItem)
                    <li><a class="block rounded-lg px-3 py-3 text-[15px] font-medium text-ivory transition-colors hover:bg-ivory/10 hover:text-accent-400" href="{{ $ubItem['url'] }}">{!! $ubItem['label'] !!}</a></li>
                @endforeach
            </ul>
        </nav>
        <div class="shrink-0 space-y-3 border-t border-ivory/10 px-6 py-6">
            <a data-ub-register href="{{ $ubRegisterUrl }}" target="_blank" rel="noopener" class="flex h-12 w-full items-center justify-center rounded-pill bg-accent-500 text-sm font-semibold text-inverse">Register Now</a>
            @if($ubWhatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $ubWhatsapp) }}" target="_blank" rel="noopener" class="flex h-12 w-full items-center justify-center rounded-pill border border-ivory/25 text-sm font-semibold text-ivory">WhatsApp Us</a>
            @endif
            @if($primaryOffice?->phone_primary)
                <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="block text-center text-sm text-ivory/80">{{ $primaryOffice->phone_primary }}</a>
            @endif
        </div>
    </div>
</div>

<style>
    .ub-ticker-track { animation: ub-ticker 55s linear infinite; }
    @keyframes ub-ticker { from { transform: translateX(0) } to { transform: translateX(-50%) } }
    @media (prefers-reduced-motion: reduce) { .ub-ticker-track { animation: none } }

    /* Pinned state, added by initTemplateHeader once the hero has scrolled by.
       The template drops its header entirely at that point; keeping it means the
       menu is still reachable nine thousand pixels down. */
    .ub-site-header.is-stuck {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: var(--color-brand-800);
        box-shadow: 0 6px 24px rgb(6 11 30 / 0.28);
    }
</style>
