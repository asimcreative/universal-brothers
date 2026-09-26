@extends('layouts.app')

@section('title', $category->name . ' Packages | Universal Brothers')
@section('meta_description', 'Browse real ' . $category->name . ' packages from Universal Brothers — IATA-registered Hajj, Umrah and Tourism operator.')

@section('content')
    {{-- The banner shows what this category is actually about, chosen from the
         category's own slug rather than hard-coded per page. --}}
    @php $ubCategoryPhoto = match($category->slug) {
        'hajj' => 'kaaba-tawaf',
        'umrah' => 'haram-dusk',
        'tourism' => 'hunza-valley',
        default => 'haram-panorama',
    }; @endphp

    <x-page-hero
        :photo="$ubCategoryPhoto"
        :title="$category->name . ' Packages'"
        :eyebrow="$packages->total() > 0 ? $packages->total() . ' ' . Str::plural('Package', $packages->total()) . ' Available' : null"
        :lead="$category->description"
        :breadcrumbs="['Home' => route('home'), $category->name => null]" />

    <div class="container py-5">
        <div class="row g-4 g-xl-5">
            @if($hajjFilters)
                <div class="col-lg-3">
                    {{-- `offcanvas-lg` ONLY — the plain `offcanvas` class must not
                         be applied alongside it.

                         Bootstrap's `.offcanvas` sets `position: fixed`,
                         `visibility: hidden` and `transform: translateX(100%)`
                         unconditionally, and `.offcanvas-lg`'s ≥992px block
                         resets neither `position` nor `visibility`. With both
                         classes present the panel was therefore still hidden and
                         parked 400px off-screen at desktop widths, while its
                         `.d-lg-none` trigger button was simultaneously display:none
                         — so desktop visitors had NO way to filter packages at all,
                         and the reserved col-lg-3 rendered as a dead empty column.
                         Verified on the live site by reading the computed styles.
                         The existing Playwright coverage only exercised 375px, so
                         it never saw this. --}}
                    <div class="offcanvas-end offcanvas-lg filter-panel" tabindex="-1" id="hajjFilterPanel">
                        <div class="offcanvas-header d-lg-none">
                            <h2 class="offcanvas-title h5 mb-0">Filter Packages</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#hajjFilterPanel" aria-label="Close filters"></button>
                        </div>
                        <div class="offcanvas-body d-block">
                            <p class="filter-panel-heading d-none d-lg-block">Refine Packages</p>
                            <form method="GET" action="{{ route('packages.category', $category->slug) }}" class="row g-3">
                                {{-- Series leads the form. It used to be a separate
                                     row of pills above the grid, which meant the page
                                     asked for the same thing in two places — pills for
                                     the tier, a form for everything else — and the two
                                     did not look or behave alike. One filter, one place. --}}
                                @if($series->isNotEmpty())
                                    <div class="col-12">
                                        <label for="filter-series" class="form-label">Package Series</label>
                                        <select name="series" id="filter-series" class="form-select">
                                            <option value="">All series</option>
                                            @foreach($series as $s)
                                                <option value="{{ $s->slug }}" {{ request('series') === $s->slug ? 'selected' : '' }}>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-6">
                                    <label for="filter-price-min" class="form-label">Min price ({{ \App\Support\Currency::current() }})</label>
                                    <input type="number" inputmode="numeric" name="price_min" id="filter-price-min" class="form-control" value="{{ request('price_min') }}">
                                </div>
                                <div class="col-6">
                                    <label for="filter-price-max" class="form-label">Max price ({{ \App\Support\Currency::current() }})</label>
                                    <input type="number" inputmode="numeric" name="price_max" id="filter-price-max" class="form-control" value="{{ request('price_max') }}">
                                </div>
                                <div class="col-12">
                                    <label for="filter-days" class="form-label">Duration</label>
                                    <select name="days" id="filter-days" class="form-select">
                                        <option value="">Any</option>
                                        @foreach($hajjFilters['days'] as $days)
                                            <option value="{{ $days }}" {{ (string) request('days') === (string) $days ? 'selected' : '' }}>{{ $days }} days</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="filter-variant" class="form-label">Package Type</label>
                                    <select name="variant" id="filter-variant" class="form-select">
                                        <option value="">Any</option>
                                        @foreach($hajjFilters['variants'] as $code)
                                            <option value="{{ $code }}" {{ request('variant') === $code ? 'selected' : '' }}>Package {{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="filter-shifting" class="form-label">Shifting</label>
                                    <select name="shifting" id="filter-shifting" class="form-select">
                                        <option value="">Any</option>
                                        <option value="non_shifting" {{ request('shifting') === 'non_shifting' ? 'selected' : '' }}>Non-Shifting</option>
                                        <option value="shifting" {{ request('shifting') === 'shifting' ? 'selected' : '' }}>Shifting</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="filter-arrival" class="form-label">Arrival</label>
                                    <select name="arrival" id="filter-arrival" class="form-select">
                                        <option value="">Any</option>
                                        <option value="jeddah" {{ request('arrival') === 'jeddah' ? 'selected' : '' }}>Jeddah First</option>
                                        <option value="madina" {{ request('arrival') === 'madina' ? 'selected' : '' }}>Madina First</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="filter-aziziya" class="form-label">Aziziya</label>
                                    <select name="aziziya" id="filter-aziziya" class="form-select">
                                        <option value="any" {{ !request('aziziya') || request('aziziya') === 'any' ? 'selected' : '' }}>Any</option>
                                        <option value="included" {{ request('aziziya') === 'included' ? 'selected' : '' }}>Included</option>
                                        <option value="optional" {{ request('aziziya') === 'optional' ? 'selected' : '' }}>Optional</option>
                                        <option value="not_included" {{ request('aziziya') === 'not_included' ? 'selected' : '' }}>Not Included</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="filter-sharing" class="form-label">Sharing</label>
                                    <select name="sharing" id="filter-sharing" class="form-select">
                                        <option value="">Any</option>
                                        @foreach($hajjFilters['sharingTypes'] as $sharing)
                                            <option value="{{ $sharing }}" {{ request('sharing') === $sharing ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $sharing)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 form-check ps-4">
                                    <input type="checkbox" name="star5" value="1" id="filter-star5" class="form-check-input" {{ request('star5') ? 'checked' : '' }}>
                                    <label for="filter-star5" class="form-check-label">5-Star Only</label>
                                </div>
                                <div class="col-12 d-grid gap-2 pt-2">
                                    <x-cta variant="navy" :arrow="false">Apply Filters</x-cta>
                                    <x-cta :href="route('packages.category', $category->slug)" variant="outline-navy" :arrow="false">Reset</x-cta>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="{{ $hajjFilters ? 'col-lg-9' : 'col-12' }}">

                @if($hajjFilters && $hajjFilters['days']->count() > 1)
                    {{-- Duration first, as tabs rather than a dropdown: it is
                         the thing people narrow by before anything else, and
                         the brochure itself is organised by it.

                         Built from the durations that actually exist, so a tab
                         can never lead to an empty grid — and a new length
                         appears here on its own the day a package has it. --}}
                    {{-- A nav, not a `role="tablist"`. These look like tabs and
                         are described as tabs, but each one is a link that
                         reloads the listing with a `days` filter — there is no
                         tab panel, and nothing responds to an arrow key.
                         Announcing them as a tab list would promise a widget
                         that is not here; `aria-current="page"` says which one
                         is in force, which is what is actually true. --}}
                    <nav class="day-tab-bar" aria-label="Filter by duration">
                        <a href="{{ route('packages.category', array_merge([$category->slug], request()->except(['days', 'page']))) }}"
                           class="day-tab {{ request()->filled('days') ? '' : 'is-active' }}"
                           @unless(request()->filled('days')) aria-current="page" @endunless>All</a>
                        @foreach($hajjFilters['days'] as $ubDays)
                            <a href="{{ route('packages.category', array_merge([$category->slug], request()->except(['days', 'page']), ['days' => $ubDays])) }}"
                               class="day-tab {{ (string) request('days') === (string) $ubDays ? 'is-active' : '' }}"
                               @if((string) request('days') === (string) $ubDays) aria-current="page" @endif>{{ $ubDays }} Days</a>
                        @endforeach
                    </nav>
                @endif

                @php
                    $ubApplied = [];
                    $ubLabels = [
                        'days' => fn ($v) => $v . ' days',
                        'variant' => fn ($v) => 'Package ' . $v,
                        'arrival' => fn ($v) => $v === 'madina' ? 'Madina first' : 'Jeddah first',
                        'aziziya' => fn ($v) => 'Aziziya: ' . str_replace('_', ' ', $v),
                        'shifting' => fn ($v) => $v === 'shifting' ? 'Shifting' : 'Non-Shifting',
                        'sharing' => fn ($v) => ucfirst($v) . ' sharing',
                        'price_min' => fn ($v) => 'From ' . \App\Support\Currency::format((int) $v),
                        'price_max' => fn ($v) => 'Up to ' . \App\Support\Currency::format((int) $v),
                        'star5' => fn ($v) => '5-star only',
                        'series' => fn ($v) => optional($series->firstWhere('slug', $v))->name ?? $v,
                    ];

                    foreach ($ubLabels as $ubKey => $ubFormat) {
                        $ubValue = request($ubKey);
                        if (filled($ubValue) && $ubValue !== 'any') {
                            $ubApplied[] = [
                                'label' => $ubFormat($ubValue),
                                'remove' => route('packages.category', array_merge(
                                    ['category' => $category->slug],
                                    collect(request()->query())->except($ubKey, 'page')->all()
                                )),
                            ];
                        }
                    }
                @endphp

                @if($ubApplied)
                    <div class="listing-applied" role="group" aria-label="Filters you have applied">
                        <span class="listing-applied-label">Filtered by</span>
                        @foreach($ubApplied as $ubFilter)
                            <a href="{{ $ubFilter['remove'] }}" class="ub-chip ub-chip-default listing-applied-chip">
                                {{ $ubFilter['label'] }}
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                <span class="visually-hidden">— remove this filter</span>
                            </a>
                        @endforeach
                        <a href="{{ route('packages.category', $category->slug) }}" class="listing-applied-clear">Clear all</a>
                    </div>
                @endif

                <div class="listing-toolbar">
                    {{-- Suppressed at zero: "Showing 0 of 0 umrah packages" directly
                         above an honest empty state that already says the same thing
                         only made the page read as broken. --}}
                    @if($packages->total() > 0)
                        <p class="listing-count">
                            Showing <strong>{{ $packages->count() }}</strong> of <strong>{{ $packages->total() }}</strong> {{ Str::lower($category->name) }} {{ Str::plural('package', $packages->total()) }}
                        </p>
                    @endif
                    @if($hajjFilters)
                        <button type="button" class="btn btn-outline-primary filter-trigger-btn d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#hajjFilterPanel" aria-controls="hajjFilterPanel">
                            <i class="bi bi-sliders"></i>Filters
                        </button>
                    @endif
                </div>

                @if($packages->isEmpty())
                    <x-empty-state icon="bi-bag" photo="haram-dusk">
                        No {{ strtolower($category->name) }} packages are published yet. Please check back soon or <a href="{{ route('contact') }}">contact us</a> for the latest availability.
                    </x-empty-state>
                @else
                    <div class="row g-4">
                        @foreach($packages as $package)
                            <div class="col-md-6 col-xl-4">
                                <x-package-card :package="$package" />
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 d-flex justify-content-center">{{ $packages->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
