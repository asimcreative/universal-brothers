@php
    // One row, mixing what the template mixes: the bodies that accredit us and
    // the awards the work has earned. Affiliations lead, as they do in the
    // reference, because their logos read at a glance; awards follow.
    $ubPlates = collect();

    foreach ($affiliations as $ubItem) {
        $ubPlates->push([
            'label' => 'Accreditation',
            'image' => \App\Support\SiteImagery::resolve($ubItem->logo),
            'alt' => $ubItem->organization_name,
            'caption' => $ubItem->description,
            'href' => $ubItem->link,
        ]);
    }

    foreach ($awards as $ubItem) {
        $ubPlates->push([
            'label' => 'Award',
            'image' => \App\Support\SiteImagery::resolve($ubItem->image),
            'alt' => $ubItem->name,
            'caption' => $ubItem->name . ($ubItem->year ? ' — ' . $ubItem->year : ''),
            'href' => null,
        ]);
    }

    // The track animates by exactly -50%, so it needs two identical halves. A
    // short list would leave each half narrower than the viewport and drag a
    // visible gap across the band, so the list repeats until a half holds at
    // least six plates.
    $ubRow = collect();
    if ($ubPlates->isNotEmpty()) {
        while ($ubRow->count() < 6) {
            $ubRow = $ubRow->concat($ubPlates);
        }
    }

    // Roughly six seconds per plate, never faster than half a minute a lap.
    $ubDuration = max(30, $ubRow->count() * 6);
@endphp

{{--
    The designer's awards-and-affiliations band, carrying our real records.

    Markup and class names are the template's own. What is ours: the plates come
    from the Affiliation and Award tables, so nothing here can name a body we
    are not accredited by or an award we have not won. The row is a continuous
    marquee, as in the reference — an earlier pass left it as a scrollable strip,
    which put a scrollbar across the band and stopped at the last card.
--}}
@if($ubRow->isNotEmpty())
    <style>
        .ub-plate-track { animation: ub-plate-scroll var(--ub-plate-duration, 48s) linear infinite; }
        @keyframes ub-plate-scroll { from { transform: translateX(0) } to { transform: translateX(-50%) } }
        .ub-plate-marquee:hover .ub-plate-track,
        .ub-plate-marquee:focus-within .ub-plate-track { animation-play-state: paused; }
        @media (prefers-reduced-motion: reduce) { .ub-plate-track { animation: none } }
    </style>

    <section id="affiliations" class="bg-sand-100 py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
            <header class="mx-auto max-w-2xl text-center">
                <p class="reveal-on-scroll mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-accent-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkle fill-accent-800 text-accent-800" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>Awards &amp; Affiliations
                </p>

                <h2 class="reveal-on-scroll reveal-delay-1 font-display text-3xl font-bold uppercase leading-tight sm:text-4xl lg:text-5xl">
                    <span class="text-inverse">Recognised, Accredited</span> <span class="text-accent-700">&amp; Well Connected</span>
                </h2>

                <p class="reveal-on-scroll reveal-delay-2 mt-4 text-sm leading-relaxed text-ink-400 sm:text-base">The awards our work has earned and the bodies that accredit us.</p>
            </header>
        </div>

        <div class="ub-plate-marquee relative overflow-hidden mt-11 py-3 [mask-image:linear-gradient(to_right,transparent,#000_7%,#000_93%,transparent)] lg:mt-14" style="--ub-plate-duration: {{ $ubDuration }}s">
            <div class="ub-plate-track flex w-max flex-nowrap items-stretch">
                @foreach([false, true] as $ubCopy)
                    <div class="flex shrink-0 flex-nowrap items-center" @if($ubCopy) aria-hidden="true" @endif>
                        @foreach($ubRow as $ubPlate)
                            <div class="mr-4 shrink-0 sm:mr-5">
                                @php
                                    $ubPlateClass = 'group flex h-[172px] w-[224px] flex-col items-center gap-2 rounded-card border border-inverse/12 bg-white px-4 py-4 text-center transition-all duration-300 sm:h-[190px] sm:w-[268px] sm:px-5';
                                @endphp

                                @if($ubPlate['href'] && ! $ubCopy)
                                    <a href="{{ $ubPlate['href'] }}" target="_blank" rel="noopener" class="{{ $ubPlateClass }}">
                                @else
                                    <div class="{{ $ubPlateClass }}">
                                @endif

                                <span class="text-[11px] sm:text-[9px] font-bold uppercase tracking-[0.18em] text-accent-800">{{ $ubPlate['label'] }}</span>

                                <span class="flex max-h-[72px] w-full min-h-0 flex-1 items-center justify-center sm:max-h-[92px]">
                                    @if($ubPlate['image'])
                                        <img src="{{ $ubPlate['image'] }}" alt="{{ $ubCopy ? '' : $ubPlate['alt'] }}" class="max-h-full max-w-full object-contain" loading="lazy" decoding="async">
                                    @else
                                        <span class="font-display text-lg font-bold text-inverse">{{ $ubPlate['alt'] }}</span>
                                    @endif
                                </span>

                                @if($ubPlate['caption'])
                                    <span class="text-[11px] leading-snug text-ink-400">{{ \Illuminate\Support\Str::limit($ubPlate['caption'], 90) }}</span>
                                @endif

                                @if($ubPlate['href'] && ! $ubCopy)
                                    </a>
                                @else
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
