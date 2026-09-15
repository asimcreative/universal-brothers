{{-- Office locations. $data, $extra['offices'], $sectionId --}}
@if($extra['offices']->isNotEmpty())
    <section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
        <div class="container">
            @include('pages.blocks.partials.heading', ['data' => $data])
            <div class="row g-4 justify-content-center">
                @foreach($extra['offices'] as $office)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 reveal-on-scroll">
                            <div class="card-body p-4 text-body">
                                <h3 class="h6">{{ $office->label }}</h3>
                                <p class="mb-1"><i class="bi bi-geo-alt-fill me-2" aria-hidden="true"></i>{{ $office->address }}</p>
                                @if($office->phone_primary)<p class="mb-1"><i class="bi bi-telephone-fill me-2" aria-hidden="true"></i>{{ $office->phone_primary }}@if($office->phone_secondary) / {{ $office->phone_secondary }}@endif</p>@endif
                                @if($office->whatsapp)<p class="mb-1"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>{{ $office->whatsapp }}</p>@endif
                                @if($office->email)<p class="mb-0"><i class="bi bi-envelope-fill me-2" aria-hidden="true"></i>{{ $office->email }}</p>@endif
                                @if(! empty($data['show_map']) && $office->mapEmbedUrl())
                                    <div class="map-embed mt-3" data-map-embed>
                                        <a class="map-embed-facade ub-visual ub-visual--v3" href="{{ $office->mapsUrl() ?: 'https://www.google.com/maps' }}" target="_blank" rel="noopener" data-map-load>
                                            <span class="map-embed-body">
                                                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                                <span class="map-embed-title">Show map</span>
                                                <span class="map-embed-note">{{ $office->label ?: 'Office location' }}</span>
                                            </span>
                                        </a>
                                        <template data-map-source><iframe src="{{ $office->mapEmbedUrl() }}" title="Map of {{ $office->label ?: 'our office' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></template>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
