{{--
    A room price, written in the currency the visitor chose.

    `package_room_options` / `package_aziziya_room_options` carry a column per
    currency, so the page ships every currency's real value and the switcher
    only changes which one is shown. Nothing is ever converted — a blank column
    means the brochure does not publish that price, and the cell says N/A.

    The value is rendered SERVER-SIDE. An earlier template emitted an empty
    `<span>` and relied on JS to fill it, so a visitor with JS blocked, a
    crawler, or anyone reading the page before the bundle executed saw a
    package with no prices at all.

    Which currency that is comes from the session now, not from a switcher on
    this page: the choice is made once for the whole site and decides which
    packages are listed as well as how they are priced. The `data-` attributes
    stay because the admin preview and the tests read them.

    Props
      pkr / sar / usd   raw decimal strings straight from the model
--}}
@props([
    'pkr' => null,
    'sar' => null,
    'usd' => null,
])

@php
    use App\Support\Currency;

    // '' is as good as null here: single-currency callers pass an empty string
    // for the columns that do not apply.
    $ubValues = ['PKR' => $pkr, 'SAR' => $sar, 'USD' => $usd];
    $ubRaw = $ubValues[Currency::current()] ?? null;
    $ubShown = ($ubRaw === null || $ubRaw === '') ? null : Currency::format($ubRaw);
@endphp

<span {{ $attributes->class('currency-price') }} data-pkr="{{ $pkr }}" data-sar="{{ $sar }}" data-usd="{{ $usd }}">{{ $ubShown ?? 'N/A' }}</span>
