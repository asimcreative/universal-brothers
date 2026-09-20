@php
    // One tree for both headers — see App\Support\SiteNavigation. The menu
    // used to exist twice, and the two copies had already drifted apart.
    $ubNav = \App\Support\SiteNavigation::tree($navCategories);
    $ubRegisterUrl = \App\Support\SiteNavigation::REGISTER_URL;
    $ubWhatsapp = $primaryOffice?->whatsapp;

    // The homepage lays the bar over its photograph; every other page has no
    // hero behind it, so there the bar is a solid band in the normal flow.
    $ubOverlay = $ubOverlay ?? true;

    $ubChevron = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="ub-nav-chevron" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
@endphp

{{--
    The designer's header, with our menu and our details.

    Every class here is the template's own — `bg-brand-800`, `text-on-dark-muted`,
    `max-w-[var(--container-site)]` and the rest resolve against the compiled
    stylesheet in `resources/css/site.css`, so the markup has to match
    it rather than merely resemble it.

    Two deliberate departures, both because the client asked for them earlier:
    the bar carries our ten items rather than the template's five, and the
    announcement strip is fed by our News articles rather than the designer's
    placeholder lines. The strip renders nothing at all when no article is
    published, which is why it can look empty on a fresh database.
--}}
<div class="ub-chrome {{ $ubOverlay ? 'absolute inset-x-0 top-0' : 'ub-chrome--flow' }} z-50">
    {{-- Utility strip: contact on the left, announcements running through the
         middle, credentials and social on the right. --}}
    <div class="relative z-50 border-b border-ivory/10 bg-brand-800">
        <div class="mx-[auto] w-full max-w-[var(--container-site)] px-[1.25rem] sm:px-8 lg:px-10 flex h-10 items-center gap-6">
            <div class="hidden shrink-0 items-center gap-[1.25rem] text-xs text-on-dark-muted lg:flex">
                @if($primaryOffice?->phone_primary)
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="flex items-center gap-[0.5rem] transition-colors hover:text-sand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-accent-500"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>{{ $primaryOffice->phone_primary }}
                    </a>
                @endif
                @if($primaryOffice?->phone_primary && $primaryOffice?->email)
                    <span class="h-3.5 w-px bg-ivory/20"></span>
                @endif
                @if($primaryOffice?->email)
                    <a href="mailto:{{ $primaryOffice->email }}" class="flex items-center gap-[0.5rem] transition-colors hover:text-sand-300">
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
                        <div class="ub-ticker-track flex w-max flex-nowrap items-center text-xs text-on-dark-muted">
                            @foreach([false, true] as $ubCopy)
                                <div class="flex shrink-0 flex-nowrap items-center" @if($ubCopy) aria-hidden="true" @endif>
                                    @foreach($ubAnnouncements as $ubItem)
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star mx-[1.25rem] shrink-0 fill-sand-300 text-sand-300" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg>
                                            <a class="-my-3 inline-flex items-center py-[0.75rem] transition-colors hover:text-sand-300" href="{{ route('news.show', $ubItem->slug) }}" @if($ubCopy) tabindex="-1" @endif>
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

            <div class="hidden shrink-0 items-center gap-[1rem] text-xs text-on-dark-muted lg:flex">
                {{-- Changing the currency changes which packages are listed, not
                     just how the numbers are written, so it is a POST. Three
                     buttons rather than a select: it has to work with no
                     JavaScript, and a select without one needs its own submit. --}}
                <form method="POST" action="{{ route('currency.store') }}" class="ub-currency-switch">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                    <span class="ub-currency-switch__label">Prices in</span>
                    @foreach(\App\Support\Currency::SUPPORTED as $ubCode)
                        <button type="submit" name="currency" value="{{ $ubCode }}"
                                class="ub-currency-switch__option{{ \App\Support\Currency::current() === $ubCode ? ' is-active' : '' }}"
                                @if(\App\Support\Currency::current() === $ubCode) aria-current="true" @endif
                                aria-label="Show prices in {{ \App\Support\Currency::label($ubCode) }}">{{ $ubCode }}</button>
                    @endforeach
                </form>

                <span class="h-3.5 w-px bg-ivory/20"></span>

                <span class="whitespace-nowrap">
                    @if(\App\Models\SiteSetting::get('iata_registered', '1'))IATA Registered &middot; @endif
                    Hajj License No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}
                </span>
                {{-- A divider only when there is something to divide from: the
                     component renders nothing at all when no link is saved. --}}
                @if(\App\Models\SiteSetting::where('key', 'like', 'social\_%')->where('value', '!=', '')->exists())
                    <span class="h-3.5 w-px bg-ivory/20"></span>
                @endif

                <x-social-links
                    :size="14"
                    class="flex items-center gap-[0.75rem]"
                    link-class="transition-colors hover:text-sand-300" />
            </div>
        </div>
    </div>

    {{-- Primary bar. It sits on the photograph and, once the hero has scrolled
         past, pins itself as a solid bar (see `initTemplateHeader` in app.js). --}}
    <header class="ub-site-header z-50 transition-[colors,transform] duration-300 relative {{ $ubOverlay ? 'bg-[transparent]' : 'bg-brand-800' }} translate-y-0">
        <div class="mx-[auto] w-full max-w-[var(--container-site)] px-[1.25rem] sm:px-8 lg:px-10 flex h-20 items-center justify-between gap-6 lg:h-24">
            <a class="flex shrink-0 items-center gap-[0.75rem]" aria-label="Universal Brothers" href="{{ route('home') }}">
                <svg viewBox="0 0 44 48" class="h-10 w-auto shrink-0" aria-hidden="true"><path d="M22 1.5 41.5 12.75v22.5L22 46.5 2.5 35.25v-22.5L22 1.5Z" class="fill-none stroke-sand-300" stroke-width="2"></path><text x="22" y="30" text-anchor="middle" class="font-display font-bold fill-ivory" style="font-size:17px;letter-spacing:-0.02em">UB</text></svg>
                <span class="flex flex-col leading-none">
                    <span class="font-display text-[19px] font-bold whitespace-nowrap uppercase tracking-[0.06em] text-ivory">Universal <span class="text-accent-500">Brothers</span></span>
                    <span class="mt-[0.25rem] whitespace-nowrap text-[11px] sm:text-[10px] uppercase tracking-[0.22em] text-on-dark-muted">Hajj &middot; Umrah &middot; Tourism</span>
                </span>
            </a>

            <nav class="ub-primary-nav" aria-label="Primary">
                <ul class="flex items-center gap-[1.25rem]">
                    @foreach($ubNav as $ubItem)
                        @php
                            $ubColumns = $ubItem['columns'] ?? [];
                            $ubLinks = $ubItem['links'] ?? [];
                            $ubHasPanel = $ubColumns || $ubLinks;
                        @endphp
                        <li class="relative {{ $ubHasPanel ? 'ub-nav-item' : '' }}">
                            <a class="group flex items-center gap-1.5 py-8 text-sm font-medium text-ivory transition-colors hover:text-accent-400" href="{{ $ubItem['url'] }}" @if($ubHasPanel) aria-haspopup="true" @endif>
                                <span class="relative whitespace-nowrap">{!! $ubItem['label'] !!}<span class="absolute -bottom-1 left-0 h-px bg-accent-500 transition-all duration-300 w-0 group-hover:w-full"></span></span>
                                @if($ubHasPanel){!! $ubChevron !!}@endif
                            </a>

                            @if($ubColumns)
                                <div class="ub-nav-panel ub-nav-panel--mega">
                                    @foreach($ubColumns as $ubCol)
                                        <div>
                                            <a class="ub-nav-col-title" href="{{ $ubCol['url'] }}">{{ $ubCol['title'] }}</a>
                                            <ul class="ub-nav-links">
                                                @foreach($ubCol['links'] as $ubLink)
                                                    <li><a href="{{ $ubLink['url'] }}" @if($ubLink['external'] ?? false) target="_blank" rel="noopener" @endif>{{ $ubLink['label'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach

                                    @if($ubItem['feature'] ?? null)
                                        <div class="ub-nav-feature">
                                            <span class="ub-nav-feature-eyebrow">{!! $ubItem['feature']['eyebrow'] !!}</span>
                                            <p class="ub-nav-feature-title">{!! $ubItem['feature']['title'] !!}</p>
                                            <a class="ub-nav-feature-cta" href="{{ $ubItem['feature']['cta']['url'] }}">{{ $ubItem['feature']['cta']['label'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            @elseif($ubLinks)
                                <div class="ub-nav-panel ub-nav-panel--list">
                                    <ul class="ub-nav-links">
                                        @foreach($ubLinks as $ubLink)
                                            <li><a href="{{ $ubLink['url'] }}">{{ $ubLink['label'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex shrink-0 items-center gap-[0.75rem]">
                <a class="group relative isolate items-center gap-[0.75rem] overflow-hidden rounded-[var(--radius-pill)] font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent text-inverse shadow-[0_10px_15px_-3px_var(--tw-shadow-color),0_4px_6px_-4px_var(--tw-shadow-color)] shadow-accent-900/25 hover:text-ivory h-10 pl-5 pr-1.5 text-xs sm:h-12 sm:pl-6 sm:pr-2 sm:text-sm hidden sm:inline-flex" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" data-ub-register href="{{ $ubRegisterUrl }}" target="_blank" rel="noopener">
                    <span class="relative whitespace-nowrap">Register Now</span>
                    <span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-7 sm:size-8">
                        <span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span>
                    </span>
                </a>

                <button type="button" aria-label="Open menu" aria-expanded="false" aria-controls="ub-mobile-nav" data-ub-menu-open class="ub-menu-toggle flex size-11 items-center justify-center rounded-full border-solid border-[1px] border-ivory/25 text-ivory transition-colors hover:bg-ivory/10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"></line><line x1="4" x2="20" y1="6" y2="6"></line><line x1="4" x2="20" y1="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </header>
</div>

{{-- Mobile drawer. The template's own is rendered by React; this is the same
     surface built as a plain dialog so it works without a framework. --}}
<div id="ub-mobile-nav" hidden class="ub-chrome fixed inset-0 z-[60]">
    <div class="absolute inset-0 bg-brand-900/70" data-ub-menu-close></div>
    <div role="dialog" aria-modal="true" aria-label="Menu" class="absolute inset-y-0 right-0 flex w-[min(20rem,88vw)] flex-col bg-brand-800 shadow-2xl">
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-ivory/10 px-6">
            <span class="font-display text-[17px] font-bold uppercase tracking-[0.06em] text-ivory">Universal <span class="text-accent-500">Brothers</span></span>
            <button type="button" aria-label="Close menu" data-ub-menu-close class="flex size-10 items-center justify-center rounded-full border-solid border-[1px] border-ivory/25 text-ivory transition-colors hover:bg-ivory/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-6 py-6" aria-label="Menu">
            <ul class="flex flex-col gap-[0.25rem]">
                @foreach($ubNav as $ubItem)
                    @php
                        // A mega panel's columns flatten into one list here: a
                        // drawer has no room for three columns, but every
                        // destination must still be reachable from it.
                        $ubChildren = collect($ubItem['columns'] ?? [])
                            ->flatMap(fn ($col) => array_merge([['label' => $col['title'], 'url' => $col['url']]], $col['links']))
                            ->merge($ubItem['links'] ?? [])
                            ->all();
                    @endphp
                    @if($ubChildren)
                        <li>
                            <details class="ub-drawer-group">
                                <summary>{!! $ubItem['label'] !!}{!! $ubChevron !!}</summary>
                                <ul class="ub-nav-links">
                                    @foreach($ubChildren as $ubChild)
                                        <li><a href="{{ $ubChild['url'] }}" @if($ubChild['external'] ?? false) target="_blank" rel="noopener" @endif>{{ $ubChild['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            </details>
                        </li>
                    @else
                        <li><a class="block rounded-lg px-[0.75rem] py-[0.75rem] text-[15px] font-medium text-ivory transition-colors hover:bg-ivory/10 hover:text-accent-400" href="{{ $ubItem['url'] }}">{!! $ubItem['label'] !!}</a></li>
                    @endif
                @endforeach
            </ul>
        </nav>
        <div class="shrink-0 space-y-3 border-t border-ivory/10 px-6 py-6">
            {{-- The same control as the top bar's, because the top bar's is
                 hidden below 1024px and the gate only ever asks once. Without
                 this, a visitor on a phone who answered the question in May
                 could never change their mind. --}}
            <form method="POST" action="{{ route('currency.store') }}" class="ub-currency-switch ub-currency-switch--drawer">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                <span class="ub-currency-switch__label">Prices in</span>
                @foreach(\App\Support\Currency::SUPPORTED as $ubCode)
                    <button type="submit" name="currency" value="{{ $ubCode }}"
                            class="ub-currency-switch__option{{ \App\Support\Currency::current() === $ubCode ? ' is-active' : '' }}"
                            @if(\App\Support\Currency::current() === $ubCode) aria-current="true" @endif
                            aria-label="Show prices in {{ \App\Support\Currency::label($ubCode) }}">{{ $ubCode }}</button>
                @endforeach
            </form>

            <a data-ub-register href="{{ $ubRegisterUrl }}" target="_blank" rel="noopener" class="flex h-12 w-full items-center justify-center rounded-[var(--radius-pill)] bg-accent-500 text-sm font-semibold text-inverse">Register Now</a>
            @if($ubWhatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $ubWhatsapp) }}" target="_blank" rel="noopener" class="flex h-12 w-full items-center justify-center rounded-[var(--radius-pill)] border-solid border-[1px] border-ivory/25 text-sm font-semibold text-ivory">WhatsApp Us</a>
            @endif
            @if($primaryOffice?->phone_primary)
                <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="block text-center text-sm text-on-dark-muted">{{ $primaryOffice->phone_primary }}</a>
            @endif
        </div>
    </div>
</div>
