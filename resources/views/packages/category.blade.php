@extends('layouts.app')

@section('title', $category->name . ' Packages | Universal Brothers')
@section('meta_description', 'Browse real ' . $category->name . ' packages from Universal Brothers — IATA-registered Hajj, Umrah and Tourism operator.')

@section('content')
    <div class="hero-slide" style="min-height: 42vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $category->name }}</li>
                </ol>
            </nav>
            <span class="hero-eyebrow">{{ $packages->total() }} {{ Str::plural('Package', $packages->total()) }} Available</span>
            <h1>{{ $category->name }} Packages</h1>
            @if($category->description)<p class="lead mb-0" style="max-width: 720px;">{{ $category->description }}</p>@endif
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4">
            @if($hajjFilters)
                <div class="col-lg-3">
                    {{-- Bootstrap's responsive offcanvas: a slide-in drawer below
                         `lg`, a plain static sidebar panel at `lg` and above —
                         one form, no duplicated fields/ids between breakpoints. --}}
                    <div class="offcanvas offcanvas-end offcanvas-lg filter-panel" tabindex="-1" id="hajjFilterPanel">
                        <div class="offcanvas-header d-lg-none">
                            <h2 class="offcanvas-title h5 mb-0">Filter Packages</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#hajjFilterPanel" aria-label="Close filters"></button>
                        </div>
                        <div class="offcanvas-body d-block">
                            <form method="GET" action="{{ route('packages.category', $category->slug) }}" class="row g-3">
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
                                <div class="col-12">
                                    <label for="filter-price-min" class="form-label">Min Price (US$)</label>
                                    <input type="number" name="price_min" id="filter-price-min" class="form-control" value="{{ request('price_min') }}">
                                </div>
                                <div class="col-12">
                                    <label for="filter-price-max" class="form-label">Max Price (US$)</label>
                                    <input type="number" name="price_max" id="filter-price-max" class="form-control" value="{{ request('price_max') }}">
                                </div>
                                <div class="col-12 form-check ps-4">
                                    <input type="checkbox" name="star5" value="1" id="filter-star5" class="form-check-input" {{ request('star5') ? 'checked' : '' }}>
                                    <label for="filter-star5" class="form-check-label">5-Star Only</label>
                                </div>
                                <div class="col-12 d-grid gap-2 pt-2">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    <a href="{{ route('packages.category', $category->slug) }}" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="{{ $hajjFilters ? 'col-lg-9' : 'col-12' }}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    @if($series->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('packages.category', $category->slug) }}" class="btn series-pill {{ request('series') ? 'btn-outline-primary' : 'btn-primary' }}">All</a>
                            @foreach($series as $s)
                                <a href="{{ route('packages.category', [$category->slug, 'series' => $s->slug]) }}" class="btn series-pill {{ request('series') === $s->slug ? 'btn-primary' : 'btn-outline-primary' }}">{{ $s->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if($hajjFilters)
                        <button type="button" class="btn btn-outline-primary filter-trigger-btn d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#hajjFilterPanel" aria-controls="hajjFilterPanel">
                            <i class="bi bi-sliders"></i>Filters
                        </button>
                    @endif
                </div>

                @if($packages->isEmpty())
                    <x-empty-state icon="bi-bag">
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
