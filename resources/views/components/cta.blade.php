{{--
    The site's call-to-action button.

    One shape (pill), one set of variants, and an optional trailing arrow for
    "this takes you somewhere" actions. Renders an <a> when `href` is given and
    a <button> otherwise, so a CTA never has to be faked with the wrong element.

    @param href     destination; omit for a submit/action button
    @param variant  'gold' (strongest) | 'navy' | 'outline-light' | 'outline-navy' | 'quiet'
    @param size     'sm' | '' | 'lg'
    @param arrow    show the trailing arrow (default true for links)
    @param external opens in a new tab, with the usual rel and a hidden note
--}}
@props([
    'href' => null,
    'variant' => 'gold',
    'size' => '',
    'arrow' => null,
    'external' => false,
    'type' => 'submit',
])

@php
    $ubVariants = [
        'gold' => 'ub-cta-gold',
        'navy' => 'ub-cta-navy',
        'outline-light' => 'ub-cta-outline-light',
        'outline-navy' => 'ub-cta-outline-navy',
        'quiet' => 'ub-cta-quiet',
    ];
    $ubClasses = ['ub-cta', $ubVariants[$variant] ?? $ubVariants['gold']];
    if ($size) {
        $ubClasses[] = 'ub-cta-' . $size;
    }
    $ubArrow = $arrow ?? ($href !== null);
@endphp

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->class($ubClasses) }}
       @if($external) target="_blank" rel="noopener" @endif>
        <span>{{ $slot }}</span>
        @if($ubArrow)<i class="bi bi-arrow-right ub-cta-arrow" aria-hidden="true"></i>@endif
        @if($external)<span class="visually-hidden"> (opens in a new tab)</span>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($ubClasses) }}>
        <span>{{ $slot }}</span>
        @if($ubArrow)<i class="bi bi-arrow-right ub-cta-arrow" aria-hidden="true"></i>@endif
    </button>
@endif
