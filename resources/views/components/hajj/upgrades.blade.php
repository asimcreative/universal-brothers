{{--
    Optional extras.

    Exactly half of the catalogue's upgrade rows carry no price at all (36 of
    72), so "Price on Request" is not an edge case here — it is the normal
    state for half the rows and needs to read as a deliberate answer rather
    than a missing value.

    Like transport, upgrade prices are single-currency (`price` + `currency`),
    so they are shown in the currency they are recorded in rather than being
    pushed through the room-price switcher.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubUpgrades = $hajj->package()->upgrades->sortBy('sort_order'); @endphp

@if($ubUpgrades->isNotEmpty())
    <x-hajj.section id="upgrades" eyebrow="Make It Yours" title="Optional Upgrades">
        <div class="hajj-upgrades">
            @foreach($ubUpgrades as $ubUpgrade)
                <article class="hajj-upgrade">
                    <h3 class="hajj-upgrade-name">{{ $ubUpgrade->name }}</h3>
                    @if($ubUpgrade->description)
                        <p class="hajj-upgrade-copy">{{ $ubUpgrade->description }}</p>
                    @endif

                    <p class="hajj-upgrade-price">
                        @if($ubUpgrade->is_included)
                            <span class="hajj-chip hajj-chip--yes">Included</span>
                        @elseif(! is_null($ubUpgrade->price))
                            {{ $hajj->money($ubUpgrade->price, $ubUpgrade->currency) }}
                            @if($ubUpgrade->price_basis)<small>{{ str_replace('_', ' ', $ubUpgrade->price_basis) }}</small>@endif
                        @else
                            <span class="hajj-upgrade-onrequest">Price on request</span>
                        @endif
                    </p>

                    @if($ubUpgrade->notes)
                        <p class="hajj-upgrade-note">{{ $ubUpgrade->notes }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </x-hajj.section>
@endif
