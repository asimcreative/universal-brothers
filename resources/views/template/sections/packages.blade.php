{{--
    Featured packages — one section, one tab per category.

    The markup and every class name below are the designer's, taken from the
    template we now serve compiled at `public/template/css/template.css`. Only
    the content is ours: the tabs are built from the categories that actually
    have published packages, and each card is a real row out of the database.
    Anything the designer's mock asserted but our data cannot back — hotel star
    ratings, room occupancy, the "Makkah 9 nights · Madinah 5 nights" line — is
    dropped rather than reproduced as decoration.

    Tabs are plain HTML: one `role="tab"` button per category pointing at a
    `role="tabpanel"` by id, and the small script at the foot of the file swaps
    the `hidden` attribute, `aria-selected`, the roving tabindex and the
    designer's own active/inactive class lists. No framework, and with one
    category the tab bar is not rendered at all.

    The template faded these in with Framer Motion, which this layout does not
    ship; the `reveal-on-scroll` hooks declared in `layouts/template` stand in
    for it. The arrow that Framer swapped in on hover is a single arrow here —
    its duplicate existed only to be animated, and rule one of this port is that
    nothing is left invisible by an inline style.
--}}

@php
    // Only a category that exists AND has published packages earns a tab —
    // an empty "Tours & Holidays" tab is worse than no tab at all.
    $ubPackageTabs = collect([
        ['slug' => 'hajj', 'label' => 'Hajj Packages', 'category' => $hajjCategory, 'packages' => $hajjPackages],
        ['slug' => 'umrah', 'label' => 'Umrah Packages', 'category' => $umrahCategory, 'packages' => $umrahPackages],
        ['slug' => 'tourism', 'label' => 'Tours & Holidays', 'category' => $tourismCategory, 'packages' => $tourismPackages],
    ])->filter(fn ($tab) => $tab['category'] && $tab['packages']->isNotEmpty())->values();

    // Written once, in PHP, and handed to both the markup and the script — so
    // the class list the script switches to can never drift from the one the
    // server rendered.
    $ubTabBase = 'relative isolate shrink-0 rounded-pill px-5 py-2.5 text-sm font-semibold transition-colors duration-300 sm:px-7 sm:py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-700 focus-visible:ring-offset-2 focus-visible:ring-offset-sand-100';
    $ubTabOn = $ubTabBase.' text-inverse';
    $ubTabOff = $ubTabBase.' text-ink-500 hover:text-inverse';
    $ubTabBgOn = 'absolute inset-0 -z-10 rounded-pill bg-accent-500';
    $ubTabBgOff = 'absolute inset-0 -z-10 rounded-pill border border-inverse/15 bg-ivory';

    $ubHajjCount = $counters['hajj_packages'] ?? null;
    $ubShowHajjCount = $ubHajjCount !== null && $ubPackageTabs->contains(fn ($tab) => $tab['slug'] === 'hajj');
@endphp

@if($ubPackageTabs->isNotEmpty())
<section id="featured-packages" class="bg-sand-100 py-16 sm:py-20 lg:py-24"><div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10"><header class="mx-auto max-w-2xl text-center"><div class="reveal-on-scroll"><p class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-800"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Our Packages</p></div><div class="reveal-on-scroll reveal-delay-1"><h2 class="font-display text-3xl font-bold uppercase leading-tight sm:text-4xl lg:text-5xl"><span class="text-inverse">One Company,</span> <span class="text-accent-700">Every Journey</span></h2></div><div class="reveal-on-scroll reveal-delay-2"><p class="mt-4 text-sm leading-relaxed text-ink-600 sm:text-base">Hajj, Umrah and tours — the same team, the same standard.@if($ubPackageTabs->count() > 1) Pick a tab to see what is running now.@endif</p></div>

@if($ubShowHajjCount)
{{-- A div, not a p: `x-stat-number` renders a div, and the parser closes a p
     before one — which would drop the counter out of the line it belongs to. --}}
<div class="reveal-on-scroll reveal-delay-3 mt-6 flex items-center justify-center gap-2.5"><x-stat-number :display="(string) $ubHajjCount" :target="$ubHajjCount" class="font-display text-3xl font-extrabold leading-none text-accent-700" /><span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-500">Hajj packages published</span></div>
@endif

</header>

@if($ubPackageTabs->count() > 1)
<div class="reveal-on-scroll reveal-delay-4"><div role="tablist" aria-label="Package category" data-ub-package-tabs class="mx-auto mt-10 flex max-w-3xl flex-wrap items-center justify-center gap-2 lg:mt-12">@foreach($ubPackageTabs as $ubIndex => $ubTab)<button type="button" role="tab" id="ub-package-tab-{{ $ubTab['slug'] }}" aria-controls="ub-package-panel-{{ $ubTab['slug'] }}" aria-selected="{{ $ubIndex === 0 ? 'true' : 'false' }}" tabindex="{{ $ubIndex === 0 ? '0' : '-1' }}" class="{{ $ubIndex === 0 ? $ubTabOn : $ubTabOff }}"><span data-ub-tab-bg class="{{ $ubIndex === 0 ? $ubTabBgOn : $ubTabBgOff }}"></span><span class="relative">{{ $ubTab['label'] }}</span></button>@endforeach</div></div>
@endif

<div class="mt-10 lg:mt-12">
@foreach($ubPackageTabs as $ubIndex => $ubTab)
<div @if($ubPackageTabs->count() > 1) id="ub-package-panel-{{ $ubTab['slug'] }}" role="tabpanel" aria-labelledby="ub-package-tab-{{ $ubTab['slug'] }}" tabindex="0" @unless($ubIndex === 0) hidden @endunless @endif>

<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
@foreach($ubTab['packages'] as $ubCardIndex => $package)
@php
    // The big figure on the media area is the stored day count, and the small
    // line beside it labels what that number is — the arrival city for a Hajj
    // package, otherwise the package's own duration label, otherwise just
    // "Days". Every one of them is a stored value; when `duration_days` is
    // empty the whole figure is left off rather than guessed at.
    $ubFigure = $package->duration_days ?: null;
    $ubFigureLabel = null;
    if ($package->isHajj() && ! is_null($package->medinah_first)) {
        $ubFigureLabel = $package->medinah_first ? 'Madinah First' : 'Makkah First';
    } elseif ($package->duration_label) {
        $ubFigureLabel = $package->duration_label;
    } elseif ($ubFigure) {
        $ubFigureLabel = $ubFigure == 1 ? 'Day' : 'Days';
    }

    // The designer's three chips were a hotel star rating, an occupancy and a
    // shifting/Aziziya flag. We hold the last two; the first two are not in
    // our data and are not invented here.
    $ubChips = [];
    if ($package->isHajj()) {
        if (! is_null($package->is_shifting)) {
            $ubChips[] = ['shifting', $package->is_shifting ? 'Shifting' : 'Non-Shifting'];
        }
        if ($package->has_aziziya) {
            $ubChips[] = ['aziziya', 'Aziziya'];
        }
    }

    // Series names carry a trailing parenthetical scope note — the stored value
    // is "Platinum — Non-Aziziya (Makkah & Medinah Series)". On a single
    // clamped line that truncates mid-word, so the parenthetical is dropped
    // here only; the detail page still shows the full name.
    $ubSeries = $package->series
        ? trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $package->series->name))
        : null;

    $ubHref = route('packages.show', [$package->category->slug, $package->slug]);
    $ubPhoto = \App\Support\SiteImagery::forPackage($package);
@endphp
<div class="h-full reveal-on-scroll reveal-delay-{{ min($ubCardIndex + 1, 6) }}"><article class="group flex h-full flex-col overflow-hidden rounded-card border border-border bg-surface shadow-[0_10px_30px_-24px_rgba(6,20,24,0.5)] transition-[border-color,box-shadow] duration-300 hover:border-accent-500/45 hover:shadow-[0_24px_50px_-30px_rgba(6,20,24,0.65)]"><div class="relative aspect-[16/11] shrink-0 overflow-hidden bg-gradient-teal"><x-photo :image="$package->cover_image" :key="$ubPhoto" :alt="$package->name" class="absolute inset-0 h-full w-full object-cover" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 92vw" /><div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/55 to-brand-950/0"></div>@if($package->code)<span class="absolute left-3.5 top-3.5 rounded-pill bg-brand-950/70 px-2.5 py-1 text-[11px] font-bold tracking-[0.08em] text-ivory/90 backdrop-blur">{{ $package->code }}</span>@endif
@if($package->is_featured)<span class="absolute right-3.5 top-3.5 rounded-pill bg-accent-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-inverse">Featured</span>@endif
@if($ubFigure)<div class="absolute inset-x-4 bottom-3 flex items-end gap-2.5"><span class="font-display text-[40px] font-extrabold leading-[0.85] tracking-[-0.04em] text-ivory">{{ $ubFigure }}</span>@if($ubFigureLabel)<span class="pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-ivory/75">{{ $ubFigureLabel }}</span>@endif</div>@endif</div><div class="flex flex-1 flex-col p-5"><h3 class="line-clamp-2 min-h-[3.1rem] font-display text-lg font-bold leading-snug text-heading"><a class="rounded-sm transition-colors duration-300 hover:text-accent-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-surface" href="{{ $ubHref }}">{{ $package->name }}</a></h3><ul class="mt-3 flex h-[26px] flex-wrap gap-1.5 overflow-hidden">@foreach($ubChips as [$ubChipIcon, $ubChipLabel])<li class="inline-flex h-[26px] shrink-0 items-center gap-1.5 rounded-pill bg-surface-2 px-2.5 text-[11px] font-medium text-ivory/80">@if($ubChipIcon === 'shifting')<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-horizontal shrink-0 text-accent-400" aria-hidden="true"><path d="m18 8 4 4-4 4"></path><path d="M2 12h20"></path><path d="m6 8-4 4 4 4"></path></svg>@else<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 shrink-0 text-accent-400" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>@endif{{ $ubChipLabel }}</li>@endforeach</ul><p class="mt-3 line-clamp-2 min-h-[2.85rem] text-sm leading-relaxed text-muted-foreground">{{ $package->publicSummary() }}</p>@if($ubSeries)<p class="mt-1.5 line-clamp-1 text-xs text-muted-foreground/80"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon-star mr-1.5 inline-block align-[-1px] text-accent-500/70" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path></svg>{{ $ubSeries }}</p>@endif<div class="mt-auto flex items-center justify-between gap-3 pt-4"><p class="min-w-0">@if($package->starting_price)<span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">From</span><span class="font-display text-xl font-bold text-accent-400"><span class="mr-0.5 text-xs font-semibold text-muted-foreground">{{ $package->currency === 'USD' ? 'US$' : 'PKR' }}</span>{{ number_format($package->starting_price) }}</span>@else<span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">&nbsp;</span><span class="font-display text-sm font-bold text-accent-400">Price on request</span>@endif</p><a aria-label="View details for {{ $package->name }}" class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-9 pl-4 pr-1.5 text-[11px] sm:h-10 sm:pl-5 sm:text-xs min-h-11 shrink-0" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)" href="{{ $ubHref }}"><span class="relative">View Details</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-6 sm:size-7"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a></div></div></article></div>
@endforeach
</div>

<div class="reveal-on-scroll"><div class="mt-11 flex justify-center lg:mt-14"><a class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-ivory shadow-lg shadow-brand-950/20 hover:text-inverse h-10 pl-5 pr-1.5 text-xs sm:h-12 sm:pl-6 sm:pr-2 sm:text-sm" style="background-image:linear-gradient(to right, var(--color-accent-500) 0 50%, var(--color-brand-700) 50% 100%)" href="{{ route('packages.category', $ubTab['slug']) }}"><span class="relative">View All {{ $ubTab['label'] }}</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-inverse group-hover:text-accent-600 size-7 sm:size-8"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></a></div></div>

</div>
@endforeach
</div>

</div></section>

@if($ubPackageTabs->count() > 1)
<script>
(function () {
    var root = document.querySelector('[data-ub-package-tabs]');
    if (!root || root.dataset.ubTabsReady) return;
    root.dataset.ubTabsReady = '1';

    var TAB_ON = @json($ubTabOn);
    var TAB_OFF = @json($ubTabOff);
    var BG_ON = @json($ubTabBgOn);
    var BG_OFF = @json($ubTabBgOff);

    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));

    function select(tab, moveFocus) {
        tabs.forEach(function (candidate) {
            var on = candidate === tab;

            candidate.className = on ? TAB_ON : TAB_OFF;
            candidate.setAttribute('aria-selected', on ? 'true' : 'false');
            candidate.tabIndex = on ? 0 : -1;

            var bg = candidate.querySelector('[data-ub-tab-bg]');
            if (bg) bg.className = on ? BG_ON : BG_OFF;

            var panel = document.getElementById(candidate.getAttribute('aria-controls'));
            if (!panel) return;
            panel.hidden = !on;

            // A panel that loads hidden never intersects the viewport, so its
            // reveal hooks would still be at opacity 0 when the reader opens
            // it. Settle them the moment the panel is shown.
            if (on) {
                Array.prototype.forEach.call(panel.querySelectorAll('.reveal-on-scroll'), function (el) {
                    el.classList.add('is-visible');
                });
            }
        });

        if (moveFocus) tab.focus();
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { select(tab, false); });
        tab.addEventListener('keydown', function (event) {
            var step = event.key === 'ArrowRight' ? 1 : (event.key === 'ArrowLeft' ? -1 : 0);
            if (!step) return;
            event.preventDefault();
            select(tabs[(index + step + tabs.length) % tabs.length], true);
        });
    });
})();
</script>
@endif
@endif
