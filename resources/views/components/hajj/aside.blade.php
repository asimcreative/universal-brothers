{{--
    The desktop sticky column: what the package costs, how to reach us, and
    the enquiry form.

    Three defects in the old sidebar are fixed here, all of them measured
    rather than guessed:

    1. `z-index`. Bootstrap's `.sticky-top` sets `z-index: 1020` — the SAME
       value the site header uses. On a z-index tie the element later in the
       document wins the paint order, and the sidebar comes after the header,
       so the enquiry card was painted straight OVER the navigation: the
       visitor could see the form but could not click "Register Now" or any
       nav item underneath it. `.hajj-aside` drops it below the header.

    2. The sticky offset. `top: 100px` was hand-picked. The header actually
       measures 63.97px below 1200px, 125.75px AT 1200px (the nav wraps to two
       lines there) and 107.75px above it — so on every desktop width the
       sidebar sat under the header by 8-26px. The offset now derives from
       `--ub-header-h`, measured from the real element.

    3. Height. The old sidebar stacked the enquiry form AND the related-package
       list into the sticky element, making it 830px tall — taller than the
       usable viewport of any 768px-high laptop, so part of it was always cut
       off. Related packages moved to a full-width band at the foot of the
       page, and what remains gets a `max-height` safety net (the same
       treatment the Hajj listing's sticky filter panel already uses).

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubPackage = $hajj->package();
    $ubFrom = $hajj->startingFrom();
    $ubOffice = app('primary-office');
@endphp

<div class="sticky-top ub-sticky-aside hajj-aside">
    <div class="hajj-aside-card">
        <div class="hajj-aside-top">
            @if($ubPackage->code)
                <span class="hajj-aside-code">{{ $ubPackage->code }}</span>
            @endif
            @if($ubPackage->duration_label)
                <span class="hajj-aside-meta">{{ $ubPackage->duration_label }}</span>
            @endif
        </div>

        @if($ubFrom)
            <p class="hajj-aside-price">
                <span class="hajj-aside-price-label">From</span>
                <span class="hajj-aside-price-value">{{ $hajj->money($ubFrom['amount'], $ubFrom['currency']) }}</span>
                <span class="hajj-aside-price-basis">per person</span>
            </p>
        @endif

        @if($ubOffice?->phone_primary)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $ubOffice->phone_primary) }}" class="btn btn-outline-primary w-100 hajj-aside-call">
                <i class="bi bi-telephone" aria-hidden="true"></i>{{ $ubOffice->phone_primary }}
            </a>
        @endif
    </div>

    <div id="enquire" class="hajj-enquire">
        <x-inquiry-form :package="$ubPackage" :category="$ubPackage->category" title="Enquire About This Package" />
    </div>
</div>
