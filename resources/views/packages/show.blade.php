@extends('layouts.app')

@section('title', ($package->meta_title ?: $package->name . ' | Universal Brothers'))
@section('meta_description', $package->meta_description ?: Str::limit($package->summary, 160))

@section('content')
    <div class="bg-primary text-white py-5">
        <div class="container">
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
            @if($package->summary)<p class="lead mb-0">{{ $package->summary }}</p>@endif
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
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Room Type</th>
                                    @foreach($package->priceTiers as $tier)
                                        <th>{{ $tier->label ?: 'Price' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['sharing' => 'Sharing', 'quad' => 'Quad', 'triple' => 'Triple', 'double' => 'Double'] as $type => $label)
                                    @php
                                        $rowHasData = $package->priceTiers->contains(fn ($t) => $t->roomPrices->firstWhere('room_type', $type) !== null);
                                    @endphp
                                    @if($rowHasData)
                                        <tr>
                                            <td class="fw-semibold">{{ $label }} Per Person</td>
                                            @foreach($package->priceTiers as $tier)
                                                @php $price = $tier->roomPrices->firstWhere('room_type', $type); @endphp
                                                <td>
                                                    @if($price && $price->price !== null)
                                                        {{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($price->price) }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted fst-italic">Book early — prices and packages are subject to change.</p>
                @endif

                {{-- Itinerary --}}
                @if($package->itineraryDays->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Day-by-Day Itinerary</h2>
                    <div class="accordion mb-4" id="itineraryAccordion">
                        @foreach($package->itineraryDays as $day)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#day{{ $day->id }}">
                                        Day {{ $day->day_number }}
                                        @if($day->date_gregorian) — {{ $day->date_gregorian->format('d M Y') }} @endif
                                        @if($day->date_hijri_label) ({{ $day->date_hijri_label }}) @endif
                                        @if($day->city) — {{ $day->city }} @endif
                                    </button>
                                </h2>
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
                        @endforeach
                    </div>
                @endif

                {{-- Inclusions / Exclusions --}}
                <div class="row g-4">
                    @if($package->inclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Inclusions</h2>
                            <ul class="list-unstyled">
                                @foreach($package->inclusions as $inclusion)
                                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>{{ $inclusion->description }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($package->exclusions->isNotEmpty())
                        <div class="col-md-6">
                            <h2 class="h5 mb-3"><i class="bi bi-x-circle-fill text-danger me-2"></i>Exclusions</h2>
                            <ul class="list-unstyled">
                                @foreach($package->exclusions as $exclusion)
                                    <li class="mb-2"><i class="bi bi-dash text-danger me-2"></i>{{ $exclusion->description }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <x-inquiry-form :package="$package" :category="$package->category" title="Inquire About This Package" />

                    @if($related->isNotEmpty())
                        <h3 class="h6 mt-4 mb-3">Related Packages</h3>
                        @foreach($related as $r)
                            <a href="{{ route('packages.show', [$r->category->slug, $r->slug]) }}" class="d-block text-decoration-none mb-2 p-2 border rounded">
                                <span class="fw-semibold text-dark">{{ $r->name }}</span>
                                <span class="d-block small text-muted">{{ $r->duration_label }}</span>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
