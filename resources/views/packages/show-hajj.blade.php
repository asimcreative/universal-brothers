@extends('layouts.app')

@section('title', ($package->meta_title ?: $package->name . ' | Universal Brothers'))
@section('meta_description', $package->meta_description ?: Str::limit($package->publicSummary() ?? '', 160))

@php
    $aziziyaLabel = $package->aziziya && $package->aziziya->status !== 'not_applicable'
        ? (['included' => 'With Aziziya', 'not_included' => 'Non-Aziziya', 'optional' => 'Aziziya Optional'][$package->aziziya->status] ?? null)
        : null;

    $mealPlans = $package->accommodations->pluck('meal_plan')
        ->merge($package->mashaerDetails->pluck('meal_plan'))
        ->filter()
        ->unique();

    // Quick-overview strip — only fields the package actually has render a
    // tile here; nothing invented for packages missing a given field.
    $overviewItems = collect([
        $package->duration_label ? ['icon' => 'bi-calendar-event', 'label' => 'Duration', 'value' => $package->duration_label] : null,
        !is_null($package->medinah_first) ? ['icon' => 'bi-geo-alt', 'label' => 'Arrival', 'value' => $package->medinah_first ? 'Madinah First' : 'Makkah First'] : null,
        !is_null($package->is_shifting) ? ['icon' => 'bi-arrow-left-right', 'label' => 'Itinerary', 'value' => $package->is_shifting ? 'Shifting' : 'Non-Shifting'] : null,
        $aziziyaLabel ? ['icon' => 'bi-building', 'label' => 'Aziziya', 'value' => $aziziyaLabel] : null,
        $package->transportation->isNotEmpty() ? ['icon' => 'bi-bus-front', 'label' => 'Transport', 'value' => 'Included'] : null,
        $mealPlans->isNotEmpty() ? ['icon' => 'bi-cup-hot', 'label' => 'Meals', 'value' => $mealPlans->first()] : null,
    ])->filter()->values();
@endphp

@section('content')
    <div class="hero-slide" style="min-height: 48vh;">
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
                @if($package->package_type)<span class="badge bg-light text-dark">{{ $package->package_type }}</span>@endif
                @if($package->season_label)<span class="badge bg-light text-dark">{{ $package->season_label }}</span>@endif
                @if($package->duration_label)<span class="badge bg-light text-dark">{{ $package->duration_label }}</span>@endif
                @if(!is_null($package->medinah_first))<span class="badge bg-light text-dark">{{ $package->medinah_first ? 'Medinah First' : 'Makkah First' }}</span>@endif
                @if(!is_null($package->is_shifting))<span class="badge bg-light text-dark">{{ $package->is_shifting ? 'Shifting' : 'Non-Shifting' }}</span>@endif
                @if($aziziyaLabel)<span class="badge bg-light text-dark">{{ $aziziyaLabel }}</span>@endif
            </div>
            <h1>{{ $package->name }}</h1>
            @if($package->publicSummary())<p class="lead mb-0" style="max-width: 720px;">{{ $package->publicSummary() }}</p>@endif
            @if($package->starting_price)
                <p class="fs-5 fw-semibold mt-2 mb-0 text-white">From {{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($package->starting_price) }}</p>
            @endif
        </div>
    </div>

    <div class="container py-5">
        @if($overviewItems->isNotEmpty())
            <div class="quick-overview mb-5">
                @foreach($overviewItems as $item)
                    <div class="quick-overview-item">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <div class="quick-overview-label">{{ $item['label'] }}</div>
                        <div class="quick-overview-value">{{ $item['value'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-8">
                @if($package->description)
                    <p>{{ $package->description }}</p>
                @endif

                {{-- Currency switcher --}}
                <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
                    <span class="fw-semibold small text-uppercase" style="letter-spacing:0.04em;">Currency:</span>
                    <div class="btn-group" role="group" id="currency-switcher">
                        @foreach(['USD', 'SAR', 'PKR'] as $cur)
                            <button type="button" class="btn btn-outline-primary {{ $loop->first ? 'active' : '' }}" data-currency="{{ $cur }}">{{ $cur }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Main package room / sharing pricing --}}
                @if($package->roomOptions->isNotEmpty())
                    <h2 class="h4 mb-3">Room Type Pricing</h2>
                    <div class="row g-3 mb-2">
                        @foreach($package->roomOptions as $option)
                            <div class="col-md-6">
                                <div class="pricing-card h-100 {{ $option->is_available ? '' : 'pricing-card-na' }}">
                                    @if($package->variants->isNotEmpty())
                                        <div class="pricing-card-variant">{{ $option->variant?->label ?: ($option->variant?->code ? 'Package '.$option->variant->code : '—') }}</div>
                                    @endif
                                    <div class="pricing-card-room">{{ $option->display_label }}</div>
                                    @if($option->occupancy)<div class="pricing-card-occupancy">{{ $option->occupancy }} Persons</div>@endif
                                    @if($option->is_available)
                                        <div class="pricing-card-price"><span class="currency-price" data-pkr="{{ $option->price_pkr }}" data-sar="{{ $option->price_sar }}" data-usd="{{ $option->price_usd }}"></span></div>
                                        @if($option->price_basis)<div class="pricing-card-basis">{{ str_replace('_', ' ', $option->price_basis) }}</div>@endif
                                    @else
                                        <div class="pricing-card-price">N/A</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="small text-muted fst-italic">Book early — prices and packages are subject to change.</p>
                @endif

                {{-- Package A/B variants --}}
                @if($package->variants->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Package Options</h2>
                    <div class="row g-3 mb-2">
                        @foreach($package->variants as $variant)
                            <div class="col-md-6">
                                <div class="pricing-card h-100">
                                    <span class="badge bg-primary mb-2">Package {{ $variant->code }}</span>
                                    @if($variant->label)<p class="mb-0 small text-muted">{{ $variant->label }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Accommodation --}}
                @php $accommodationsByLocation = $package->accommodations->groupBy('location'); @endphp
                @if($accommodationsByLocation->isNotEmpty())
                    <h2 class="h4 mt-4 mb-3">Accommodation</h2>
                    <div class="row g-3">
                        @foreach(['makkah' => 'Makkah', 'medinah' => 'Medinah', 'aziziya' => 'Aziziya'] as $loc => $label)
                            @if($accommodationsByLocation->has($loc))
                                <div class="col-md-6">
                                    <div class="pricing-card h-100">
                                        <div class="pricing-card-variant">{{ $label }}</div>
                                        <ul class="list-unstyled mb-0">
                                            @foreach($accommodationsByLocation[$loc] as $acc)
                                                <li class="mb-2">
                                                    @if($acc->variant)<span class="badge bg-light text-dark me-1">Package {{ $acc->variant->code }}</span>@endif
                                                    <span class="fw-semibold">{{ $acc->hotel_name }}</span>
                                                    @if($acc->star_rating) <span class="text-warning">{{ str_repeat('★', $acc->star_rating) }}</span> @endif
                                                    @if($acc->nights) <span class="text-muted small d-block">{{ $acc->nights }} nights</span> @endif
                                                    @if($acc->meal_plan) <span class="text-muted small d-block">{{ $acc->meal_plan }}</span> @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Aziziya --}}
                @if($package->aziziya && $package->aziziya->status !== 'not_applicable')
                    <h2 class="h4 mt-4 mb-3">Aziziya</h2>
                    <div class="pricing-card mb-3">
                        <p class="mb-2">
                            <span class="badge bg-info text-dark">{{ ['included' => 'Included', 'not_included' => 'Not Included', 'optional' => 'Optional Upgrade'][$package->aziziya->status] ?? $package->aziziya->status }}</span>
                            @if($package->aziziya->accommodation_name) <span class="fw-semibold">{{ $package->aziziya->accommodation_name }}</span> @endif
                        </p>
                        @if($package->aziziya->description)<p class="small mb-2">{{ $package->aziziya->description }}</p>@endif
                        <ul class="list-unstyled small text-muted mb-0">
                            @if($package->aziziya->location_note)<li>{{ $package->aziziya->location_note }}</li>@endif
                            @if($package->aziziya->walk_distance)<li>{{ $package->aziziya->walk_distance }}</li>@endif
                            @if($package->aziziya->average_occupancy)<li>Average {{ $package->aziziya->average_occupancy }} persons per room</li>@endif
                            @if($package->aziziya->duration_days)<li>Duration: {{ $package->aziziya->duration_days }} days of Hajj</li>@endif
                        </ul>
                    </div>

                    @if($package->aziziya->roomOptions->isNotEmpty())
                        <div class="row g-3 mb-3">
                            @foreach($package->aziziya->roomOptions as $option)
                                <div class="col-md-6">
                                    <div class="pricing-card h-100">
                                        <div class="pricing-card-room">
                                            {{ $option->display_label }}
                                            @if($option->variant) <span class="badge bg-light text-dark ms-1">Package {{ $option->variant->code }}</span> @endif
                                        </div>
                                        <div class="pricing-card-occupancy text-capitalize">{{ str_replace('_', ' ', $option->pricing_type) }}</div>
                                        <div class="pricing-card-price"><span class="currency-price" data-pkr="{{ $option->price_pkr }}" data-sar="{{ $option->price_sar }}" data-usd="{{ $option->price_usd }}"></span></div>
                                        @if($option->price_basis)<div class="pricing-card-basis">{{ str_replace('_', ' ', $option->price_basis) }}</div>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($package->aziziya->services->isNotEmpty())
                        <ul class="list-unstyled">
                            @foreach($package->aziziya->services as $service)
                                <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>{{ $service->name }}@if($service->description) — <span class="text-muted small">{{ $service->description }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                @endif

                {{-- Mina / Arafat --}}
                @foreach($package->mashaerDetails as $mashaer)
                    <h2 class="h4 mt-4 mb-3">{{ ucfirst($mashaer->location) }}</h2>
                    <ul class="list-unstyled small">
                        @foreach(['maktab' => 'Maktab', 'category' => 'Category', 'zone' => 'Zone', 'tent_type' => 'Tent Type', 'accommodation_type' => 'Accommodation', 'meal_plan' => 'Meals', 'bathroom' => 'Bathroom', 'air_conditioning' => 'Air Conditioning', 'transportation' => 'Transportation'] as $field => $label)
                            @if($mashaer->{$field})<li><strong>{{ $label }}:</strong> {{ $mashaer->{$field} }}</li>@endif
                        @endforeach
                        @if($mashaer->other_services)<li>{{ $mashaer->other_services }}</li>@endif
                    </ul>
                @endforeach

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
                                            <p class="mb-1"><strong>{{ $package->variants->isNotEmpty() ? 'Package A: ' : '' }}</strong>{{ $day->accommodation_a }}</p>
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

                {{-- Transportation --}}
                @if($package->transportation->isNotEmpty())
                    <h2 class="h5 mt-4 mb-3"><i class="bi bi-bus-front text-primary me-2"></i>Transportation</h2>
                    <ul class="list-unstyled">
                        @foreach($package->transportation as $t)
                            <li class="mb-2">
                                <i class="bi {{ $t->is_included ? 'bi-check2 text-success' : 'bi-plus-circle text-primary' }} me-2"></i>
                                {{ $t->transport_type }}
                                @if($t->from_location || $t->to_location) ({{ $t->from_location }} @if($t->from_location && $t->to_location) → @endif {{ $t->to_location }}) @endif
                                @if(!$t->is_included && $t->price)
                                    — <span class="currency-price" data-pkr="{{ $t->currency === 'PKR' ? $t->price : '' }}" data-sar="{{ $t->currency === 'SAR' ? $t->price : '' }}" data-usd="{{ $t->currency === 'USD' ? $t->price : '' }}"></span>
                                    @if($t->price_basis) <span class="text-muted small">({{ $t->price_basis }})</span> @endif
                                @endif
                                @if($t->notes) <span class="text-muted small d-block">{{ $t->notes }}</span> @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Meals (consolidated from real accommodation/mashaer meal plans) --}}
                @if($mealPlans->isNotEmpty())
                    <h2 class="h5 mt-4 mb-3"><i class="bi bi-cup-hot text-primary me-2"></i>Meals</h2>
                    <ul class="list-unstyled">
                        @foreach($mealPlans as $mealPlan)
                            <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>{{ $mealPlan }}</li>
                        @endforeach
                    </ul>
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

                {{-- Upgrades --}}
                @if($package->upgrades->isNotEmpty())
                    <h2 class="h5 mt-4 mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Optional Upgrades</h2>
                    <div class="row g-3">
                        @foreach($package->upgrades as $upgrade)
                            <div class="col-md-6">
                                <div class="pricing-card h-100">
                                    <div class="pricing-card-room">{{ $upgrade->name }}</div>
                                    @if($upgrade->description)<p class="small text-muted mb-2">{{ $upgrade->description }}</p>@endif
                                    @if($upgrade->price !== null)
                                        <div class="pricing-card-price"><span class="currency-price" data-pkr="{{ $upgrade->currency === 'PKR' ? $upgrade->price : '' }}" data-sar="{{ $upgrade->currency === 'SAR' ? $upgrade->price : '' }}" data-usd="{{ $upgrade->currency === 'USD' ? $upgrade->price : '' }}"></span></div>
                                        @if($upgrade->price_basis)<div class="pricing-card-basis">{{ $upgrade->price_basis }}</div>@endif
                                    @else
                                        <div class="pricing-card-price">On request</div>
                                    @endif
                                    @if($upgrade->notes)<p class="small text-muted mt-2 mb-0">{{ $upgrade->notes }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Notes --}}
                @if($package->packageNotes->isNotEmpty())
                    <h2 class="h5 mt-4 mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Notes</h2>
                    @foreach($package->packageNotes as $note)
                        <div class="package-note {{ $note->is_important ? 'package-note-important' : '' }}">
                            @if($note->title)<strong class="d-block mb-1">{{ $note->title }}</strong>@endif
                            {{ $note->content }}
                        </div>
                    @endforeach
                @endif

                {{-- Gallery --}}
                @if($package->media->isNotEmpty())
                    <h2 class="h5 mt-4 mb-3"><i class="bi bi-images text-primary me-2"></i>Gallery</h2>
                    <div class="row g-2 mb-2">
                        @foreach($package->media as $item)
                            <div class="col-md-4 col-6">
                                @if($item->image_path)
                                    <a href="{{ Storage::url($item->image_path) }}" class="gallery-item d-block" data-lightbox-trigger data-lightbox-src="{{ Storage::url($item->image_path) }}" data-lightbox-caption="{{ $item->alt_text ?? $package->name }}">
                                        <img src="{{ Storage::url($item->image_path) }}" alt="{{ $item->alt_text ?? $package->name }}" loading="lazy">
                                    </a>
                                @elseif($item->video_url)
                                    <div class="ratio ratio-16x9 rounded overflow-hidden">
                                        <iframe src="{{ $item->video_url }}" title="{{ $item->caption ?? $package->name }}" allowfullscreen></iframe>
                                    </div>
                                @endif
                                @if($item->caption)<p class="small text-muted mt-1 mb-0">{{ $item->caption }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <x-inquiry-form :package="$package" :category="$package->category" title="Enquire About This Package" />

                    <x-related-packages :related="$related" />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const symbols = { USD: 'US$', SAR: 'SAR ', PKR: 'PKR ' };
            let currentCurrency = 'USD';

            function render() {
                document.querySelectorAll('.currency-price').forEach(function (el) {
                    const value = el.dataset[currentCurrency.toLowerCase()];
                    el.textContent = value ? symbols[currentCurrency] + Number(value).toLocaleString() : 'N/A';
                });
            }

            document.querySelectorAll('#currency-switcher [data-currency]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('#currency-switcher [data-currency]').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentCurrency = btn.dataset.currency;
                    render();
                });
            });

            render();
        })();
    </script>
    @endpush
@endsection
