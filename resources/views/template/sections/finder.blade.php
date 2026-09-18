{{--
    Package finder — the band that sends a visitor straight into a filtered
    package listing.

    The markup, the class names and the inline SVGs are the designer's own, so
    the vendored template stylesheet dresses them with no rebuild in between.
    The data and the form behaviour are ours: the reference's three fields are
    decorative `<button aria-haspopup="listbox">` stand-ins for a scripted
    dropdown, and here they are real controls that GET into the package
    listing's existing filters. Picking a journey only retargets the form, so
    there is no second copy of the filtering logic anywhere.

    Two departures, both forced by making it work. A native control has no
    inner span to arrange, so `flex items-center justify-between gap-2` comes
    off the field and the chevron is lifted out as an overlay in the same
    place — every class that carries the look stays put. And the submit
    button's second, offset arrow was one half of a hover swap we do not
    ship, so only the arrow that was ever visible is kept.
--}}
@if($hajjCategory || $umrahCategory)
    <section id="packages" class="bg-surface py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
            <header class="mx-auto max-w-2xl text-center">
                <div>
                    <p class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-500 reveal-on-scroll"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-500 text-accent-500" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Packages</p>
                </div>
                <div>
                    <h2 class="font-display text-3xl font-bold uppercase leading-tight sm:text-4xl lg:text-5xl reveal-on-scroll reveal-delay-1"><span class="text-heading">Find Your Perfect</span> <span class="text-accent-500">Package</span></h2>
                </div>
                <div>
                    <p class="mt-4 text-sm leading-relaxed text-foreground sm:text-base reveal-on-scroll reveal-delay-2">Tell us how long you can travel and what you have budgeted. We will show you the packages that match.</p>
                </div>
            </header>

            <div class="mx-auto mt-12 max-w-5xl lg:mt-14">
                <div class="rounded-card border border-border bg-surface-2 p-5 shadow-[0_16px_40px_-32px_rgba(6,20,24,0.6)] sm:p-6 reveal-on-scroll reveal-delay-3">
                    <form method="GET" action="{{ route('packages.category', 'hajj') }}" id="packageFinder" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end lg:gap-5">
                        <div class="relative">
                            <label for="finder-service" class="block font-semibold uppercase tracking-[0.14em] mb-1.5 text-[11px] text-muted-foreground">Journey</label>
                            <select id="finder-service" data-finder-service class="w-full rounded-pill border text-left transition-colors duration-200 h-12 px-4 pr-6 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-accent-500 bg-accent-500/15 font-semibold text-accent-300 appearance-none cursor-pointer">
                                @if($hajjCategory)<option value="{{ route('packages.category', 'hajj') }}">Hajj</option>@endif
                                @if($umrahCategory)<option value="{{ route('packages.category', 'umrah') }}">Umrah</option>@endif
                                @if($tourismCategory)<option value="{{ route('packages.category', 'tourism') }}">Tourism</option>@endif
                            </select>
                            <span class="pointer-events-none absolute bottom-0 right-4 flex h-12 items-center"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down shrink-0 transition-transform duration-300 text-accent-400"><path d="m6 9 6 6 6-6"></path></svg></span>
                        </div>

                        <div class="relative">
                            <label for="finder-days" class="block font-semibold uppercase tracking-[0.14em] mb-1.5 text-[11px] text-muted-foreground">Duration</label>
                            <select name="days" id="finder-days" class="w-full rounded-pill border text-left transition-colors duration-200 h-12 px-4 pr-6 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-border bg-surface text-foreground hover:border-brand-400 appearance-none cursor-pointer">
                                <option value="">Any length</option>
                                @foreach($filterDurations as $days)
                                    <option value="{{ $days }}">{{ $days }} days</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute bottom-0 right-4 flex h-12 items-center"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down shrink-0 transition-transform duration-300 text-muted-foreground"><path d="m6 9 6 6 6-6"></path></svg></span>
                        </div>

                        <div class="relative">
                            <label for="finder-budget" class="block font-semibold uppercase tracking-[0.14em] mb-1.5 text-[11px] text-muted-foreground">Budget up to (US$)</label>
                            <input type="number" name="price_max" id="finder-budget" min="0" step="500" placeholder="e.g. 12000" inputmode="numeric" class="w-full rounded-pill border text-left transition-colors duration-200 h-12 px-4 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-border bg-surface text-foreground hover:border-brand-400">
                        </div>

                        <button class="group relative isolate inline-flex items-center gap-3 overflow-hidden rounded-pill font-semibold tracking-wide will-change-transform bg-[length:200%_100%] bg-right bg-no-repeat hover:bg-left focus-visible:bg-left transition-[background-position,color,border-color] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:duration-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent disabled:pointer-events-none disabled:opacity-50 text-inverse shadow-lg shadow-accent-900/25 hover:text-ivory h-10 pl-5 pr-1.5 text-xs sm:h-12 sm:pl-6 sm:pr-2 sm:text-sm w-full justify-between sm:col-span-2 lg:col-span-1 lg:w-auto" type="submit" style="background-image:linear-gradient(to right, var(--color-brand-700) 0 50%, var(--color-accent-500) 50% 100%)"><span class="relative">Search Packages</span><span class="relative grid shrink-0 place-items-center overflow-hidden rounded-full transition-colors duration-300 bg-ivory text-accent-700 group-hover:text-inverse size-7 sm:size-8"><span class="col-start-1 row-start-1 flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-right"><path d="M7 7h10v10"></path><path d="M7 17 17 7"></path></svg></span></span></button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endif
