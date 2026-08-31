@extends('layouts.app')

@section('title', ($package->meta_title ?: $package->name . ' | Universal Brothers'))
@section('meta_description', $package->meta_description ?: Str::limit($package->publicSummary() ?? '', 160))

@section('content')
    <div class="hero-slide" style="min-height: 42vh;">
        <div class="hero-slide-bg" @if($package->cover_image) style="background-image: url('{{ Storage::url($package->cover_image) }}')" @else style="background-image: linear-gradient(135deg, #101B45, #0A1230)" @endif></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('packages.category', $package->category->slug) }}" class="text-light">{{ $package->category->name }}</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $package->name }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                @if($package->code)<span class="badge bg-secondary">{{ $package->code }}</span>@endif
                @if($package->season_label)<span class="badge bg-light text-dark">{{ $package->season_label }}</span>@endif
                @if($package->duration_label)<span class="badge bg-light text-dark">{{ $package->duration_label }}</span>@endif
                @if(!is_null($package->is_shifting))<span class="badge bg-light text-dark">{{ $package->is_shifting ? 'Shifting' : 'Non-Shifting' }}</span>@endif
                @if(!is_null($package->has_aziziya))<span class="badge bg-light text-dark">{{ $package->has_aziziya ? 'With Aziziya' : 'Non-Aziziya' }}</span>@endif
            </div>
            <h1>{{ $package->name }}</h1>
            @if($package->publicSummary())<p class="lead mb-0" style="max-width: 720px;">{{ $package->publicSummary() }}</p>@endif
            @if($package->starting_price)
                <p class="fs-5 fw-semibold mt-2 mb-0 text-white">From {{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($package->starting_price) }}</p>
            @endif
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                @if($package->description)
                    <p>{{ $package->description }}</p>
                @endif

                {{-- Pricing --}}
                @if($package->priceTiers->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Room Type Pricing</h2>
                    <div class="row g-3 mb-2">
                        @foreach($package->priceTiers as $tier)
                            <div class="col-md-6">
                                <div class="pricing-card h-100">
                                    <div class="pricing-card-variant">{{ $tier->label ?: 'Price' }}</div>
                                    <ul class="list-unstyled mb-0">
                                        @foreach(['sharing' => 'Sharing', 'quad' => 'Quad', 'triple' => 'Triple', 'double' => 'Double'] as $type => $label)
                                            @php $price = $tier->roomPrices->firstWhere('room_type', $type); @endphp
                                            @if($price)
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                                                    <span class="small text-muted">{{ $label }} Per Person</span>
                                                    <span class="fw-semibold">
                                                        @if($price->price !== null)
                                                            {{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($price->price) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="small text-muted fst-italic">Book early — prices and packages are subject to change.</p>
                @endif

                {{-- Itinerary --}}
                @if($package->itineraryDays->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Day-by-Day Itinerary</h2>
                    <div class="itinerary-timeline mb-4" id="itineraryAccordion">
                        @foreach($package->itineraryDays as $day)
                            <div class="itinerary-day" data-day="{{ $day->day_number }}">
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#day{{ $day->id }}">
                                            Day {{ $day->day_number }}
                                            @if($day->date_gregorian) — {{ $day->date_gregorian->format('d M Y') }} @endif
                                            @if($day->date_hijri_label) ({{ $day->date_hijri_label }}) @endif
                                            @if($day->city) — {{ $day->city }} @endif
                                        </button>
                                    </h3>
                                    <div id="day{{ $day->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-1"><strong>{{ $package->priceTiers->count() > 1 ? 'Package A: ' : '' }}</strong>{{ $day->accommodation_a }}</p>
                                            @if($day->accommodation_b)
                                                <p class="mb-1"><strong>Package B:</strong> {{ $day->accommodation_b }}</p>
                                            @endif
                                            @if($day->notes)
                                                <p class="small text-muted mb-0">{{ $day->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Inclusions / Exclusions --}}
                <div class="row g-4">
                    @if($package->inclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Inclusions</h2>
                            <ul class="list-unstyled inclusion-list">
                                @foreach($package->inclusions as $inclusion)
                                    <li><i class="bi bi-check2-circle text-success"></i><span>{{ $inclusion->description }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($package->exclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-x-circle-fill text-danger me-2"></i>Exclusions</h2>
                            <ul class="list-unstyled exclusion-list">
                                @foreach($package->exclusions as $exclusion)
                                    <li><i class="bi bi-x-circle text-danger"></i><span>{{ $exclusion->description }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <x-inquiry-form :package="$package" :category="$package->category" title="Enquire About This Package" />

                    <x-related-packages :related="$related" />
                </div>
            </div>
        </div>
    </div>
@endsection
