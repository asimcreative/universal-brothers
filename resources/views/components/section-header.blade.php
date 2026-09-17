{{--
    One section header for the whole site: eyebrow, heading, optional lead.

    Every section used to build its own — `.section-eyebrow` here, a divider
    there, `max-width: 640px` inline somewhere else — so the vertical rhythm
    drifted from section to section. This keeps one shape and one measure.

    @param eyebrow   small uppercase kicker above the heading
    @param title     the heading text (or use the slot for markup)
    @param lead      optional supporting sentence, capped at the reading measure
    @param align     'center' (default) or 'start'
    @param level     heading level, default h2
    @param onDark    lighter colours for navy bands
--}}
@props([
    'eyebrow' => null,
    'title' => null,
    'lead' => null,
    'align' => 'center',
    'level' => 'h2',
    'onDark' => false,
])

@php
    $ubAlign = $align === 'start' ? 'text-start' : 'text-center mx-auto';
@endphp

<div {{ $attributes->class(['ub-section-header', $ubAlign, 'is-on-dark' => $onDark]) }}>
    @if($eyebrow)
        <p class="ub-eyebrow">{{ $eyebrow }}</p>
    @endif

    <{{ $level }} class="ub-section-title">{{ $title ?? $slot }}</{{ $level }}>

    @if($lead)
        <p class="ub-section-lead">{{ $lead }}</p>
    @endif
</div>
