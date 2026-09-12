{{--
    One room type and what it costs, inside the option it belongs to.

    Props
      room  a PackageRoomOption
--}}
@props(['room'])

@php
    $ubName = $room->display_label ?: Str::headline((string) $room->sharing_type);
    $ubBasis = $room->price_basis ? str_replace('_', ' ', $room->price_basis) : null;
@endphp

<li class="hajj-room @if(! $room->is_available) hajj-room--unavailable @endif">
    <div class="hajj-room-id">
        <span class="hajj-room-name">{{ $ubName }}</span>
        {{-- `occupancy` is genuinely null for some sharing types (UB008 and
             UB010's "Sharing Room" is sold without a stated headcount), so the
             line is omitted rather than printed as an empty or invented one. --}}
        @if($room->occupancy)
            <span class="hajj-room-occ">{{ $room->occupancy }} persons per room</span>
        @endif
    </div>

    <div class="hajj-room-cost">
        @if($room->is_available)
            <x-hajj.price class="hajj-room-price" :pkr="$room->price_pkr" :sar="$room->price_sar" :usd="$room->price_usd" />
            @if($ubBasis)
                <span class="hajj-room-basis">{{ $ubBasis }}</span>
            @endif
        @else
            {{-- A room the brochure lists but does not sell on this option.
                 It is kept on the page (silently dropping it would make two
                 options look like they offer the same room types) but is
                 clearly marked, rather than shown as a blank price cell. --}}
            <span class="hajj-room-price hajj-room-price--na">N/A</span>
            <span class="hajj-room-basis">not offered on this option</span>
        @endif
    </div>

    @if($room->notes)
        <p class="hajj-room-note">{{ $room->notes }}</p>
    @endif
</li>
