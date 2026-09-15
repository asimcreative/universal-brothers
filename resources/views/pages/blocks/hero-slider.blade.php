{{-- Banner slider. $data, $extra['slides'], $sectionId, $headingTag --}}
@php($ubCarousel = 'carousel-'.$sectionId)
<div id="{{ $sectionId }}" class="pb-slider">
    <div id="{{ $ubCarousel }}" class="carousel slide" @if(! empty($data['autoplay'])) data-bs-ride="carousel" @endif aria-label="{{ $data['heading'] ?? 'Featured' }} banner">
        <div class="carousel-inner">
            @foreach($extra['slides'] as $i => $slide)
                @php($ubUrl = \App\Support\PageBuilder\Block::imageUrl($slide['image']) ?? (filled($slide['image']) ? \Illuminate\Support\Facades\Storage::url($slide['image']) : null))
                <div class="carousel-item hero-slide {{ $i === 0 ? 'active' : '' }}">
                    @if($ubUrl)
                        <div class="hero-slide-bg" style="background-image: url('{{ $ubUrl }}')" aria-hidden="true"></div>
                    @else
                        <div class="ub-visual ub-visual--stage" aria-hidden="true"></div>
                    @endif
                    <div class="container hero-content text-center py-5">
                        <div class="mx-auto" style="max-width: 800px;">
                            @if($i === 0)
                                <{{ $headingTag }} class="display-5 fw-bold">{{ $slide['heading'] }}</{{ $headingTag }}>
                            @else
                                <p class="display-5 fw-bold" role="heading" aria-level="2">{{ $slide['heading'] }}</p>
                            @endif
                            @if(filled($slide['text']))<p class="lead">{{ $slide['text'] }}</p>@endif
                            @if(filled($slide['button_text']))
                                <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">
                                    @include('pages.blocks.partials.button', ['text' => $slide['button_text'], 'link' => $slide['button_link'], 'class' => 'btn-secondary btn-lg'])
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if(count($extra['slides']) > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#{{ $ubCarousel }}" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous slide</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#{{ $ubCarousel }}" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next slide</span>
            </button>
        @endif
    </div>
</div>
