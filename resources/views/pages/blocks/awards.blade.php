{{-- Awards and recognition. $data, $extra['awards'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 mb-4 justify-content-center">
            @foreach($extra['awards'] as $award)
                <x-award-badge :award="$award" />
            @endforeach
        </div>
        @if(! empty($data['show_all']))
            <div class="text-center">
                <a href="{{ route('awards') }}" class="btn btn-outline-primary">View All Awards</a>
            </div>
        @endif
    </div>
</section>
