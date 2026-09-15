{{-- Page banner. $data, $extra, $sectionId, $headingTag --}}
@php
    use App\Support\PageBuilder\Block;
    $ubImage = Block::imageUrl($data['image']['path'] ?? null) ?? Block::imageUrl($pageBanner ?? null);
    $ubLibrary = $ubImage ? null : Block::libraryPhoto($defaultPhoto ?? 'haram-panorama');
    $ubCentered = ($data['align'] ?? 'left') === 'center';
@endphp
<section id="{{ $sectionId }}" @class([
    'page-hero', 'page-hero--photo', 'pb-hero',
    'pb-hero--tall' => ($data['size'] ?? 'standard') === 'tall',
    'page-hero--centered' => $ubCentered,
])>
    <div class="ub-photo-bg {{ $ubCentered ? 'ub-photo-bg--centered' : '' }}" aria-hidden="true">
        @if($ubImage)
            <img src="{{ $ubImage }}" alt="" loading="eager" fetchpriority="high" decoding="async">
        @elseif($ubLibrary['src'])
            <img src="{{ $ubLibrary['src'] }}" srcset="{{ $ubLibrary['srcset'] }}" sizes="100vw" alt="" loading="eager" fetchpriority="high" decoding="async">
        @endif
    </div>

    <div class="container page-hero-content">
        <div class="row {{ $ubCentered ? 'justify-content-center' : '' }}">
            <div class="{{ $ubCentered ? 'col-lg-9' : 'col-lg-8' }}">
                @if(filled($data['eyebrow'] ?? null))
                    <span class="hero-eyebrow page-hero-eyebrow">{{ $data['eyebrow'] }}</span>
                @endif

                <{{ $headingTag }} class="page-hero-title">{{ $data['heading'] ?: ($pageTitle ?? '') }}</{{ $headingTag }}>

                @if(filled($data['subheading'] ?? null))
                    <p class="page-hero-lead">{{ $data['subheading'] }}</p>
                @endif

                @if(filled($data['button_text'] ?? null) || filled($data['second_button_text'] ?? null))
                    <div class="page-hero-actions">
                        @include('pages.blocks.partials.button', ['text' => $data['button_text'] ?? null, 'link' => $data['button_link'] ?? null, 'class' => 'btn-secondary btn-lg'])
                        @include('pages.blocks.partials.button', ['text' => $data['second_button_text'] ?? null, 'link' => $data['second_button_link'] ?? null, 'class' => 'btn-outline-light btn-lg'])
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
