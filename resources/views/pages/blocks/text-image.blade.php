{{-- Text with image on the right (and, via $imageFirst, image on the left). $data, $sectionId --}}
@php
    $ubImageFirst = $imageFirst ?? false;
    $ubUrl = \App\Support\PageBuilder\Block::imageUrl($data['image']['path'] ?? null);
@endphp
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 {{ $ubImageFirst ? 'order-lg-2' : '' }} reveal-on-scroll">
                @include('pages.blocks.partials.heading', ['data' => $data, 'center' => false])
                <div class="rich-text">
                    {!! \App\Support\Content\RichText::render($data['content'] ?? '', 'standard') !!}
                </div>
                @if(filled($data['button_text'] ?? null))
                    <div class="mt-4">
                        @include('pages.blocks.partials.button', ['text' => $data['button_text'], 'link' => $data['button_link'] ?? null, 'class' => \App\Support\PageBuilder\Block::isDark($data['background'] ?? null) ? 'btn-secondary' : 'btn-primary'])
                    </div>
                @endif
            </div>
            <div class="col-lg-6 {{ $ubImageFirst ? 'order-lg-1' : '' }} reveal-on-scroll reveal-delay-2">
                <figure class="pb-figure mb-0">
                    @if($ubUrl)
                        <img src="{{ $ubUrl }}" alt="{{ $data['image']['alt'] ?? '' }}" loading="lazy" decoding="async" class="pb-figure-img">
                    @else
                        <div class="ub-visual ub-visual--stage pb-figure-img" aria-hidden="true"></div>
                    @endif
                    @if(filled($data['caption'] ?? null))
                        <figcaption class="pb-figure-caption">{{ $data['caption'] }}</figcaption>
                    @endif
                </figure>
            </div>
        </div>
    </div>
</section>
