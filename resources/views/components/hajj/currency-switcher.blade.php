{{--
    Which currency the room prices are shown in.

    The switcher is always rendered with all three currencies, because the
    admin can add a SAR or PKR column to any package at any time and the page
    must not need a template change when they do. What IS data-driven is the
    note underneath: across the live catalogue every room price is recorded in
    US dollars and none in SAR or PKR, so switching currency silently turned
    the whole table to "N/A" with no explanation. Now the page says so before
    the visitor clicks.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubAvailable = $hajj->currencyAvailability();
    $ubMissing = array_keys(array_filter($ubAvailable, fn ($has) => ! $has));
@endphp

<div class="hajj-currency">
    <span class="hajj-currency-label" id="hajj-currency-label">Show prices in</span>

    <div class="btn-group hajj-currency-group" role="group" aria-labelledby="hajj-currency-label" id="currency-switcher">
        @foreach(['USD', 'SAR', 'PKR'] as $ubCurrency)
            <button type="button"
                    class="btn btn-outline-primary @if($loop->first) active @endif"
                    data-currency="{{ $ubCurrency }}"
                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $ubCurrency }}</button>
        @endforeach
    </div>

    @if($ubMissing)
        <p class="hajj-currency-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            {{ implode(' and ', $ubMissing) }} {{ count($ubMissing) === 1 ? 'is' : 'are' }} not published for this package — those rows will read N/A. Contact us for an up-to-date conversion.
        </p>
    @endif
</div>
