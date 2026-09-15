{{-- Two columns of text. $data, $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-5">
            @foreach(['left', 'right'] as $ubSide)
                <div class="col-lg-6 reveal-on-scroll {{ $ubSide === 'right' ? 'reveal-delay-2' : '' }}">
                    @if(filled($data[$ubSide.'_heading'] ?? null))
                        <h3 class="h4 mb-3">{{ $data[$ubSide.'_heading'] }}</h3>
                    @endif
                    <div class="rich-text">
                        {!! \App\Support\Content\RichText::render($data[$ubSide.'_content'] ?? '', 'standard') !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
