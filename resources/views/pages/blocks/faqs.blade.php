{{-- Questions and answers. $data, $extra['faqs'], $sectionId --}}
@php($ubAccordion = 'faq-'.$sectionId)
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion faq-accordion" id="{{ $ubAccordion }}">
                    @foreach($extra['faqs'] as $i => $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $ubAccordion }}-{{ $faq->id }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="{{ $ubAccordion }}-{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h3>
                            <div id="{{ $ubAccordion }}-{{ $faq->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#{{ $ubAccordion }}">
                                <div class="accordion-body text-secondary rich-text">
                                    {!! \App\Support\Content\RichText::render($faq->answer, 'standard') !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(! empty($data['show_all']))
                    <div class="text-center mt-4">
                        <a href="{{ route('faqs') }}" class="btn btn-outline-primary">See all questions</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
