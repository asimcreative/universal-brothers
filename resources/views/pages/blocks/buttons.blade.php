{{-- Buttons. $data, $sectionId --}}
@php
    $ubButtons = array_values(array_filter($data['buttons'] ?? [], fn ($b) => filled($b['text'] ?? null) && filled($b['link'] ?? null)));
    $ubDark = \App\Support\PageBuilder\Block::isDark($data['background'] ?? null);
    $ubClass = fn (string $style) => match ($style) {
        'gold' => 'btn-secondary',
        'outline' => $ubDark ? 'btn-outline-light' : 'btn-outline-primary',
        default => $ubDark ? 'btn-light' : 'btn-primary',
    };
@endphp
@if($ubButtons !== [])
    <section id="{{ $sectionId }}" class="section-tight pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
        <div class="container">
            <div class="pb-buttons pb-buttons--{{ in_array($data['align'] ?? 'center', ['left', 'center', 'right'], true) ? $data['align'] : 'center' }}">
                @foreach($ubButtons as $ubButton)
                    @include('pages.blocks.partials.button', ['text' => $ubButton['text'], 'link' => $ubButton['link'], 'class' => $ubClass($ubButton['style'] ?? 'primary').' btn-lg', 'newTab' => ! empty($ubButton['new_tab'])])
                @endforeach
            </div>
        </div>
    </section>
@endif
