{{--
    A room price, in all three currencies at once.

    `package_room_options` / `package_aziziya_room_options` carry a column per
    currency, so the page ships every currency's real value and the switcher
    only changes which one is shown. Nothing is ever converted — a blank column
    means the brochure does not publish that price, and the cell says N/A.

    The default currency's value is rendered SERVER-SIDE here. The old template
    emitted an empty `<span>` and relied on JS to fill it, so a visitor with
    JS blocked, a crawler, or anyone reading the page before the bundle
    executed saw a package with no prices at all. JS now only swaps a value
    that is already correct in the markup.

    Props
      pkr / sar / usd   raw decimal strings straight from the model
--}}
@props([
    'pkr' => null,
    'sar' => null,
    'usd' => null,
])

@php
    // '' is as good as null here: single-currency callers pass an empty string
    // for the columns that do not apply.
    $ubUsd = ($usd === null || $usd === '') ? null : number_format((float) $usd);
@endphp

<span {{ $attributes->class('currency-price') }} data-pkr="{{ $pkr }}" data-sar="{{ $sar }}" data-usd="{{ $usd }}">{{ $ubUsd ? 'US$'.$ubUsd : 'N/A' }}</span>
