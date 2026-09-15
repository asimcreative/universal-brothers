{{-- Testimonials. $data, $extra['videos'], $extra['texts'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'cream') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        @if($extra['videos']->isNotEmpty())
            <div class="row g-4 mb-4 justify-content-center">
                @foreach($extra['videos'] as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-video-testimonial-card :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif
        @if($extra['texts']->isNotEmpty())
            <div class="row g-4 justify-content-center">
                @foreach($extra['texts'] as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-testimonial-card :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif
        @if(! empty($data['show_all']))
            <div class="text-center mt-5">
                <a href="{{ route('testimonials') }}" class="btn btn-outline-primary">Read all testimonials</a>
            </div>
        @endif
    </div>
</section>
