{{-- Package cards for one category. $data, $extra['packages'], $extra['category'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 justify-content-center">
            @foreach($extra['packages'] as $package)
                <div class="col-md-6 col-lg-4">
                    <x-package-card :package="$package" />
                </div>
            @endforeach
        </div>
        @if(! empty($data['show_all']))
            <div class="text-center mt-5">
                <a href="{{ route('packages.category', $extra['category']->slug) }}" class="btn btn-outline-primary">View all {{ strtolower($extra['category']->name) }} packages</a>
            </div>
        @endif
    </div>
</section>
