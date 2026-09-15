{{-- Cards (also used for Services). $data, $sectionId; optional $itemsKey --}}
@php
    use App\Support\PageBuilder\Block;
    $ubItems = array_values(array_filter($data[$itemsKey ?? 'cards'] ?? [], fn ($c) => filled($c['title'] ?? null)));
    $ubDark = Block::isDark($data['background'] ?? null);
@endphp
@if($ubItems !== [])
    <section id="{{ $sectionId }}" class="section pb-section {{ Block::background($data['background'] ?? 'white') }}">
        <div class="container">
            @include('pages.blocks.partials.heading', ['data' => $data])
            <div class="row g-4 justify-content-center">
                @foreach($ubItems as $i => $ubCard)
                    <div class="col-md-6 col-lg-4">
                        <div class="icon-pillar pb-card h-100 reveal-on-scroll reveal-delay-{{ ($i % 3) + 1 }} {{ $ubDark ? 'pb-card--on-dark' : '' }}">
                            <div class="icon-pillar-icon"><i class="bi {{ array_key_exists($ubCard['icon'] ?? '', \App\Support\PageBuilder\BlockRegistry::ICONS) ? $ubCard['icon'] : 'bi-star' }}" aria-hidden="true"></i></div>
                            <h3 class="h6">{{ $ubCard['title'] }}</h3>
                            @if(filled($ubCard['text'] ?? null))
                                <p class="small mb-0 {{ $ubDark ? '' : 'text-muted' }}">{{ $ubCard['text'] }}</p>
                            @endif
                            @if(filled($ubCard['link'] ?? null))
                                <a href="{{ Block::href($ubCard['link']) }}" class="pb-card-link" @if(Block::isExternal($ubCard['link'])) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $ubCard['link_text'] ?: 'Find out more' }}<span class="visually-hidden"> about {{ $ubCard['title'] }}</span><i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
