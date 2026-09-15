{{-- Call-to-action banner. $data, $sectionId --}}
@php
    use App\Support\PageBuilder\Block;
    $ubStyle = $data['style'] ?? 'navy';
    $ubImage = $ubStyle === 'navy' ? Block::imageUrl($data['image']['path'] ?? null) : null;
    $ubLibrary = $ubStyle === 'navy' && ! $ubImage ? Block::libraryPhoto('haram-panorama') : ['src' => null];
@endphp
@if($ubStyle === 'navy')
    <section id="{{ $sectionId }}" class="page-cta pb-cta">
        @if($ubImage)
            <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true"><img src="{{ $ubImage }}" alt="" loading="lazy" decoding="async"></div>
        @elseif($ubLibrary['src'])
            <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true"><img src="{{ $ubLibrary['src'] }}" srcset="{{ $ubLibrary['srcset'] }}" sizes="100vw" alt="" loading="lazy" decoding="async"></div>
        @else
            <div class="ub-visual ub-visual--stage ub-visual--stage-sm" aria-hidden="true"></div>
        @endif
        <div class="container page-cta-content">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 reveal-on-scroll">
                    <h2 class="page-cta-title">{{ $data['heading'] }}</h2>
                    @if(filled($data['text'] ?? null))<p class="page-cta-copy">{{ $data['text'] }}</p>@endif
                    <div class="page-cta-actions">
                        @include('pages.blocks.partials.button', ['text' => $data['button_text'] ?? null, 'link' => $data['button_link'] ?? null, 'class' => 'btn-secondary btn-lg'])
                        @include('pages.blocks.partials.button', ['text' => $data['second_button_text'] ?? null, 'link' => $data['second_button_link'] ?? null, 'class' => 'btn-outline-light btn-lg'])
                    </div>
                </div>
            </div>
        </div>
    </section>
@else
    <section id="{{ $sectionId }}" class="section-tight pb-cta pb-cta--{{ $ubStyle === 'gold' ? 'gold' : 'cream' }}">
        <div class="container">
            <div class="pb-cta-panel reveal-on-scroll">
                <div>
                    <h2 class="pb-cta-title">{{ $data['heading'] }}</h2>
                    @if(filled($data['text'] ?? null))<p class="pb-cta-copy mb-0">{{ $data['text'] }}</p>@endif
                </div>
                <div class="pb-cta-actions">
                    @include('pages.blocks.partials.button', ['text' => $data['button_text'] ?? null, 'link' => $data['button_link'] ?? null, 'class' => 'btn-primary btn-lg'])
                    @include('pages.blocks.partials.button', ['text' => $data['second_button_text'] ?? null, 'link' => $data['second_button_link'] ?? null, 'class' => 'btn-outline-primary btn-lg'])
                </div>
            </div>
        </div>
    </section>
@endif
