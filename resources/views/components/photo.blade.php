{{--
    A real photograph.

    Everything visual on this site used to be a generated geometric composition
    because the database holds no images at all. This renders an actual
    photograph from the shipped library (App\Support\SiteImagery), or an
    uploaded CMS image when one exists, and falls back to the generated visual
    only when neither is available — so a slot is never empty and never a
    "photo coming soon" box.

    Three things it always does, because a site that suddenly ships thirty
    photographs will otherwise get slower and jumpier:
      - `srcset` + `sizes`, so a phone fetches the 800px rendition, not 1600px.
      - explicit `width`/`height`, so the browser reserves the right box and the
        page does not reflow as each photograph arrives (cumulative layout
        shift).
      - `loading="lazy"` by default, with `eager` + `fetchpriority="high"` for
        the one image above the fold, which is the opposite mistake — lazy
        loading the hero delays the largest contentful paint.

    Props
      key       registry key (e.g. 'kaaba-tawaf')
      image     a stored CMS path; wins over `key` when present
      alt       overrides the registry's own alt text
      sizes     CSS `sizes` hint; defaults to full viewport width
      eager     true for above-the-fold imagery
      position  object-position, for art-directing a wide crop
--}}
@props([
    'key' => null,
    'image' => null,
    'alt' => null,
    'sizes' => '100vw',
    'eager' => false,
    'position' => null,
])

@php
    use App\Support\SiteImagery;

    $ubUploaded = SiteImagery::resolve($image);
    $ubEntry = $ubUploaded ? null : SiteImagery::get($key);
    $ubAlt = $alt ?? ($ubEntry ? SiteImagery::alt($key) : '');
    $ubDims = $ubEntry ? SiteImagery::dimensions($key) : null;
@endphp

@if($ubUploaded)
    <img src="{{ $ubUploaded }}"
         alt="{{ $ubAlt }}"
         {{ $attributes->class('ub-photo') }}
         @if($position) style="object-position: {{ $position }};" @endif
         loading="{{ $eager ? 'eager' : 'lazy' }}"
         @if($eager) fetchpriority="high" @endif
         decoding="async">
@elseif($ubEntry)
    <img src="{{ SiteImagery::url($key) }}"
         srcset="{{ SiteImagery::srcset($key) }}"
         sizes="{{ $sizes }}"
         alt="{{ $ubAlt }}"
         @if($ubDims) width="{{ $ubDims['w'] }}" height="{{ $ubDims['h'] }}" @endif
         {{ $attributes->class('ub-photo') }}
         @if($position) style="object-position: {{ $position }};" @endif
         loading="{{ $eager ? 'eager' : 'lazy' }}"
         @if($eager) fetchpriority="high" @endif
         decoding="async">
@else
    {{-- Neither an upload nor a library photograph: the generated composition
         is still better than an empty box. --}}
    <div {{ $attributes->class('ub-visual ub-visual--stage') }} aria-hidden="true"></div>
@endif
