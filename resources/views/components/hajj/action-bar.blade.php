{{--
    Mobile action bar.

    Below `lg` the enquiry form falls to the very bottom of a page that runs to
    several thousand pixels, so a phone visitor who decides to enquire while
    reading the itinerary has no way to act on it without scrolling past every
    remaining section. This pins the price and the two actions to the bottom of
    the viewport on touch widths only; it is hidden entirely at `lg` and above,
    where the sticky sidebar already does this job.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubFrom = $hajj->startingFrom();
    $ubOffice = app('primary-office');
@endphp

<div class="hajj-action-bar d-lg-none">
    @if($ubFrom)
        <div class="hajj-action-price">
            <span class="hajj-action-price-label">From</span>
            <span class="hajj-action-price-value">{{ $hajj->money($ubFrom['amount'], $ubFrom['currency']) }}</span>
        </div>
    @endif

    <div class="hajj-action-buttons">
        @if($ubOffice?->phone_primary)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $ubOffice->phone_primary) }}" class="btn btn-outline-primary hajj-action-call" aria-label="Call Universal Brothers">
                <i class="bi bi-telephone" aria-hidden="true"></i><span>Call</span>
            </a>
        @endif
        <a href="#enquire" class="btn btn-primary hajj-action-enquire">Enquire</a>
    </div>
</div>
