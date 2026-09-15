{{-- Video. $data, $extra['embed'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row justify-content-center">
            <div class="col-lg-9 reveal-on-scroll">
                <div class="ratio ratio-16x9 pb-video">
                    <iframe src="{{ $extra['embed'] }}" title="{{ $data['caption'] ?: ($data['heading'] ?: 'Video') }}" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
                @if(filled($data['caption'] ?? null))
                    <p class="pb-figure-caption text-center mt-2">{{ $data['caption'] }}</p>
                @endif
            </div>
        </div>
    </div>
</section>
