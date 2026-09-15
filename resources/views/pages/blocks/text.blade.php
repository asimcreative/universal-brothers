{{-- Text. $data, $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        <div class="row {{ ($data['width'] ?? 'reading') === 'reading' ? 'justify-content-center' : '' }}">
            <div class="{{ ($data['width'] ?? 'reading') === 'reading' ? 'col-lg-9' : 'col-12' }}">
                @include('pages.blocks.partials.heading', ['data' => $data, 'center' => false])
                <div class="page-body rich-text reveal-on-scroll">
                    {!! \App\Support\Content\RichText::render($data['content'] ?? '', 'full') !!}
                </div>
            </div>
        </div>
    </div>
</section>
