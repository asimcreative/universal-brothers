{{--
    Transport legs.

    Prices here are single-currency: `package_transportation` stores ONE
    `price` and ONE `currency`. The old template still wired them into the
    room-price currency switcher, so choosing SAR blanked every transport
    price on the page to "N/A" even though the fare was perfectly well
    known — it was simply recorded in dollars. They are now shown in the
    currency they are actually sold in.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubLegs = $hajj->package()->transportation->sortBy('sort_order'); @endphp

@if($ubLegs->isNotEmpty())
    <x-hajj.section id="transport" eyebrow="Getting Around" title="Transportation">
        <ul class="hajj-tick-list">
            @foreach($ubLegs as $ubLeg)
                <li>
                    <i class="bi {{ $ubLeg->is_included ? 'bi-check2-circle' : 'bi-plus-circle' }}" aria-hidden="true"></i>
                    <span>
                        {{-- `transportLabel()`, not the raw column: the stored values
                             are snake_case enum keys and were being rendered to
                             visitors as "airport_transfer" and "vip_gmc". --}}
                        <strong>{{ $ubLeg->transportLabel() }}</strong>
                        @if($ubLeg->from_location || $ubLeg->to_location)
                            <span class="hajj-route">{{ $ubLeg->from_location }}@if($ubLeg->from_location && $ubLeg->to_location) → @endif{{ $ubLeg->to_location }}</span>
                        @endif
                        @if($ubLeg->is_included)
                            <span class="hajj-chip hajj-chip--yes">Included</span>
                        @elseif(! is_null($ubLeg->price))
                            <span class="hajj-inline-price">{{ $hajj->money($ubLeg->price, $ubLeg->currency) }}@if($ubLeg->price_basis) <small>{{ str_replace('_', ' ', $ubLeg->price_basis) }}</small>@endif</span>
                        @else
                            <span class="hajj-chip">On request</span>
                        @endif
                        @if($ubLeg->notes)<span class="hajj-tick-note">{{ $ubLeg->notes }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    </x-hajj.section>
@endif
