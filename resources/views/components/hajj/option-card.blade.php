{{--
    One package option — the hotel it is built around, and every room price
    that belongs to it.

    This card is the fix for the page's worst problem. Prices used to be one
    flat grid with the hotel name as a small gold eyebrow on each card, so
    Package A and Package B rows alternated down the page and a customer had
    to read every caption to work out which hotel a price belonged to — while
    the block that explained what A and B even were sat BELOW the prices.
    Here the option, its hotel and its prices are one object: a price can no
    longer be read apart from the option it is for.

    Props
      group  one entry from HajjPackagePresenter::optionGroups()
      hajj   the presenter
--}}
@props(['group', 'hajj'])

@php
    $ubHeadline = $group['subtitle'];
    $ubHotels = $group['hotels'];
@endphp

<article class="hajj-option" data-hajj-option="{{ $group['key'] }}">
    <header class="hajj-option-head">
        <div class="hajj-option-topline">
            <span class="hajj-option-tag">{{ $group['title'] }}</span>

            @if(! is_null($group['fromPrice']))
                <span class="hajj-option-from">
                    <span class="hajj-option-from-label">From</span>
                    <x-hajj.price class="hajj-option-from-value" :usd="$group['fromPrice']" />
                </span>
            @endif
        </div>

        @if($ubHeadline)
            <h4 class="hajj-option-title">{{ $ubHeadline }}</h4>
        @endif

        @if($ubHotels->isNotEmpty())
            <ul class="hajj-option-hotels">
                @foreach($ubHotels as $hotel)
                    @php
                        // In this catalogue the variant's own label IS the hotel
                        // name, so printing both would repeat it word for word.
                        // Compared as data rather than assumed, so a package
                        // whose variant is labelled "Deluxe" still shows which
                        // hotel that means.
                        $ubRepeatsHeadline = $ubHeadline
                            && Str::lower(trim((string) $hotel->hotel_name)) === Str::lower(trim((string) $ubHeadline));
                    @endphp
                    <li class="hajj-option-hotel">
                        <span class="hajj-option-city">{{ $hajj->locationLabel($hotel->location) }}</span>
                        @if(! $ubRepeatsHeadline && $hotel->hotel_name)
                            <span class="hajj-option-hotel-name">{{ $hotel->hotel_name }}</span>
                        @endif
                        @if($hotel->star_rating)
                            <span class="star-rating" aria-label="{{ $hotel->star_rating }} star hotel">{{ str_repeat('★', $hotel->star_rating) }}</span>
                        @endif
                        @if($hotel->nights)
                            <span class="hajj-option-nights">{{ $hotel->nights }} {{ Str::plural('night', $hotel->nights) }}</span>
                        @endif
                        @if($hotel->distance_note)
                            <span class="hajj-option-distance">{{ $hotel->distance_note }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </header>

    <ul class="hajj-room-list">
        @foreach($group['rooms'] as $room)
            <x-hajj.room-row :room="$room" />
        @endforeach
    </ul>

    <footer class="hajj-option-foot">
        {{-- A plain anchor to the enquiry form, so it works with no JS at all.
             The JS enhancement pre-fills the message with this option's name
             so the office receives the customer's actual choice. --}}
        {{-- Named only when there is a real choice to name. A package with no
             variants has one unscoped group, and "Enquire about Room Options"
             is not a thing a customer would ever say. --}}
        <a href="#enquire"
           class="btn btn-outline-primary w-100 hajj-option-cta"
           data-hajj-choose="{{ $hajj->optionLabel($group) }}">{{ $group['variant'] ? 'Enquire about '.$group['title'] : 'Enquire About This Package' }}</a>
    </footer>
</article>
