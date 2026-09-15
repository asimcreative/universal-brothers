{{-- Affiliations. $data, $extra['affiliations'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'cream') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 justify-content-center align-items-center mb-4">
            @foreach($extra['affiliations'] as $affiliation)
                <x-affiliation-badge :affiliation="$affiliation" />
            @endforeach
        </div>
        @if(! empty($data['show_all']))
            <div class="text-center">
                <a href="{{ route('affiliations') }}" class="btn btn-outline-primary">View All Affiliations</a>
            </div>
        @endif
    </div>
</section>
