{{-- Photo gallery. Opens in the site's shared lightbox. $data, $extra['images'], $sectionId --}}
@php($ubCol = ($data['columns'] ?? '3') === '4' ? 'col-6 col-md-4 col-lg-3' : 'col-6 col-md-4')
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-3">
            @foreach($extra['images'] as $ubImage)
                @php($ubUrl = \App\Support\PageBuilder\Block::imageUrl($ubImage['path']) ?? \Illuminate\Support\Facades\Storage::url($ubImage['path']))
                <div class="{{ $ubCol }}">
                    <a href="{{ $ubUrl }}" class="gallery-item reveal-on-scroll d-block" data-lightbox-trigger data-lightbox-src="{{ $ubUrl }}" data-lightbox-caption="{{ $ubImage['caption'] ?: $ubImage['alt'] }}">
                        <img src="{{ $ubUrl }}" alt="{{ $ubImage['alt'] }}" loading="lazy" decoding="async">
                    </a>
                    @if(filled($ubImage['caption']))
                        <p class="pb-figure-caption mt-2 mb-0">{{ $ubImage['caption'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
