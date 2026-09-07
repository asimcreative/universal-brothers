{{--
    UB generated visual.

    Renders the real photograph when one exists, and a generated geometric
    composition when one does not — replacing the "PHOTO COMING SOON" boxes
    that previously filled every image slot on the public site (see
    `_visuals.scss` for the full rationale).

    The variant is derived deterministically from `seed`, so a given package
    or award always renders the same composition — stable across page loads,
    pagination and caching — while a grid of twelve cards still shows eight
    genuinely different compositions instead of twelve identical boxes.

    Props
      image        real stored image path; when present it wins outright
      alt          alt text for the real image (ignored by the fallback,
                   which is decorative and marked aria-hidden)
      seed         any stable string (package code, award id, slug)
      surface      card | panel | square | stage | stage-sm
      figure       large ghosted numeral/word — MUST come from real data
      figureLabel  small caps label under the figure
      caption      small caps line at the bottom-left
      mark         corner monogram; pass an empty string to omit
--}}
@props([
    'image' => null,
    'alt' => '',
    'seed' => '',
    'surface' => 'card',
    'variant' => null,
    'figure' => null,
    'figureLabel' => null,
    'caption' => null,
    'mark' => 'UB',
])

@php
    // crc32 keeps this deterministic and cheap; the modulus matches the
    // eight `--vN` variants defined in `_visuals.scss`.
    $ubVariant = $variant ?? (crc32((string) ($seed !== '' ? $seed : 'universal-brothers')) % 8);
@endphp

@if($image)
    <img src="{{ Storage::url($image) }}"
         alt="{{ $alt }}"
         loading="lazy"
         decoding="async"
         {{ $attributes->class(['ub-visual-img', 'ub-visual--' . $surface]) }}>
@else
    <div {{ $attributes->class(['ub-visual', 'ub-visual--' . $surface, 'ub-visual--v' . $ubVariant]) }}
         role="presentation"
         aria-hidden="true">
        @if($mark !== '' && $mark !== null)
            <span class="ub-visual__mark">{{ $mark }}</span>
        @endif
        @if($figure || $caption)
            <div class="ub-visual__inner">
                @if($figure)
                    <span class="ub-visual__figure">
                        {{ $figure }}
                        @if($figureLabel)<small>{{ $figureLabel }}</small>@endif
                    </span>
                @endif
                @if($caption)
                    <span class="ub-visual__rule"></span>
                    <span class="ub-visual__caption">{{ $caption }}</span>
                @endif
            </div>
        @endif
    </div>
@endif
