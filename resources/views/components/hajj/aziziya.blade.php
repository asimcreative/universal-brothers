{{--
    Aziziya, which is a genuinely different thing on different packages:
    `included` on four of the twelve, `optional` on eight, and on UB023 the
    only accommodation row the package has. It therefore gets its own section
    with its own status, rather than being folded into the hotel list where
    "included" and "optional at extra cost" would look identical.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubAziziya = $hajj->package()->aziziya;
    $ubStatus = $hajj->aziziyaLabel();
    $ubFacts = $hajj->aziziyaFacts();
@endphp

@if($ubAziziya && $ubStatus)
    <x-hajj.section id="aziziya" eyebrow="Mina-Side Accommodation" title="Aziziya">
        <div class="hajj-aziziya">
            <div class="hajj-aziziya-head">
                <span class="hajj-status hajj-status--{{ $ubAziziya->status }}">{{ $ubStatus }}</span>
                @if($ubAziziya->accommodation_name)
                    <h3 class="hajj-aziziya-name">{{ $ubAziziya->accommodation_name }}</h3>
                @endif
            </div>

            @if($ubAziziya->description)
                <p class="hajj-aziziya-copy">{{ $ubAziziya->description }}</p>
            @endif

            @if($ubFacts)
                <dl class="hajj-facts">
                    @foreach($ubFacts as $ubFact)
                        <div class="hajj-fact">
                            <dt>{{ $ubFact['label'] }}</dt>
                            <dd>{{ $ubFact['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if($ubAziziya->roomOptions->isNotEmpty())
                <h4 class="hajj-aziziya-subtitle">Aziziya Room Options</h4>
                <ul class="hajj-room-list hajj-room-list--flush">
                    @foreach($ubAziziya->roomOptions as $ubRoom)
                        <li class="hajj-room">
                            <div class="hajj-room-id">
                                <span class="hajj-room-name">{{ $ubRoom->display_label ?: Str::headline((string) $ubRoom->sharing_type) }}</span>
                                <span class="hajj-room-occ">
                                    @if($ubRoom->variant)Package {{ $ubRoom->variant->code }} · @endif
                                    @if($ubRoom->occupancy){{ $ubRoom->occupancy }} persons per room · @endif
                                    {{-- Lower-cased on purpose: `pricing_type` is a stored
                                         enum key ("supplement", "per_person"), and
                                         re-casing it in the markup would misrepresent the
                                         value. CSS capitalises it for display. --}}
                                    <span class="hajj-room-kind">{{ str_replace('_', ' ', (string) $ubRoom->pricing_type) }}</span>
                                </span>
                            </div>
                            <div class="hajj-room-cost">
                                <x-hajj.price class="hajj-room-price" :pkr="$ubRoom->price_pkr" :sar="$ubRoom->price_sar" :usd="$ubRoom->price_usd" />
                                @if($ubRoom->price_basis)
                                    <span class="hajj-room-basis">{{ str_replace('_', ' ', $ubRoom->price_basis) }}</span>
                                @endif
                            </div>
                            @if($ubRoom->description)
                                <p class="hajj-room-note">{{ $ubRoom->description }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($ubAziziya->services->isNotEmpty())
                <h4 class="hajj-aziziya-subtitle">Aziziya Services</h4>
                <ul class="hajj-tick-list">
                    @foreach($ubAziziya->services as $ubService)
                        <li>
                            <i class="bi {{ $ubService->is_included ? 'bi-check2-circle' : 'bi-plus-circle' }}" aria-hidden="true"></i>
                            <span>
                                <strong>{{ $ubService->name }}</strong>
                                @if($ubService->description) — {{ $ubService->description }}@endif
                                @if(! $ubService->is_included && ! is_null($ubService->price))
                                    <span class="hajj-inline-price">{{ $hajj->money($ubService->price, $ubService->currency) }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($ubAziziya->notes)
                <p class="hajj-aziziya-note">{{ $ubAziziya->notes }}</p>
            @endif
        </div>
    </x-hajj.section>
@endif
