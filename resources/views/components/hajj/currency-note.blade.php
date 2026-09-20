{{--
    Says so when this package has no price in the currency being read.

    The three brochures are separate price lists, not conversions: a package
    can appear in the rupee and riyal lists and not in the dollar one. The
    listing hides those, so nobody arrives here by browsing — but a saved link,
    a search result or a currency changed while already on the page all land
    someone on a table of "N/A" with no explanation. This is the explanation.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    use App\Support\Currency;

    $ubCurrent = Currency::current();
    $ubAvailable = $hajj->currencyAvailability();
    $ubHasCurrent = $ubAvailable[$ubCurrent] ?? false;
    $ubElsewhere = array_keys(array_filter($ubAvailable, fn ($has, $code) => $has && $code !== $ubCurrent, ARRAY_FILTER_USE_BOTH));
@endphp

@unless($ubHasCurrent)
    <p class="hajj-currency-note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        This package is not published in {{ Currency::label($ubCurrent) }}.
        @if($ubElsewhere)
            It is available in {{ implode(' and ', $ubElsewhere) }} — change the currency at the top of the page to see its prices.
        @else
            Please contact us for its current pricing.
        @endif
    </p>
@endunless
