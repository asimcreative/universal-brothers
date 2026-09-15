{{-- Figures. $data, $extra['items'], $sectionId --}}
@php
    use App\Support\PageBuilder\Block;
    $ubDark = Block::isDark($data['background'] ?? 'navy');
    $ubCol = match (count($extra['items'])) { 1 => 'col-md-6', 2 => 'col-md-6', 4 => 'col-6 col-md-3', 5, 6 => 'col-6 col-md-4', default => 'col-md-4' };
@endphp
<section id="{{ $sectionId }}" class="section pb-section position-relative {{ $ubDark ? 'ub-stats-band pb-stats--dark' : Block::background($data['background'] ?? 'white') }}">
    @if($ubDark)
        @php($ubPhoto = Block::libraryPhoto('haram-panorama'))
        @if($ubPhoto['src'])
            <div class="ub-photo-bg ub-photo-bg--centered" aria-hidden="true">
                <img src="{{ $ubPhoto['src'] }}" srcset="{{ $ubPhoto['srcset'] }}" sizes="100vw" alt="" loading="lazy" decoding="async">
            </div>
        @endif
    @endif
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 text-center justify-content-center">
            @foreach($extra['items'] as $ubItem)
                <div class="{{ $ubCol }}">
                    <div class="stat-tile">
                        <x-stat-number :display="(string) $ubItem['value']" />
                        <div class="small text-uppercase fw-semibold">{{ $ubItem['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
