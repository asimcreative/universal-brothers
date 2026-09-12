{{--
    The hotels that are the same whichever option the customer picks.

    Hotel names used to appear three times on this page — once as an eyebrow
    on every price card, again under "Package Options", and a third time under
    "Accommodation". They now appear exactly once each: a hotel that BELONGS to
    an option is shown inside that option's card, and the hotels shared across
    every option are shown here. That split is read from `variant_id`, so a
    package with no variants at all simply shows its whole stay in this one
    section.

    Meal plans are deliberately not repeated here — they have their own
    section, grouped by city, which is where a visitor looks for them.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubShared = $hajj->sharedAccommodation();
    $ubLead = $hajj->hasChoice()
        ? 'Included with every option — these do not change whichever package you choose.'
        : null;
@endphp

@if($ubShared->isNotEmpty())
    <x-hajj.section id="stay" eyebrow="Accommodation" title="Your Stay" :lead="$ubLead">
        <div class="hajj-stay">
            @foreach($ubShared as $ubLocation => $ubHotels)
                @php $ubCityPhoto = \App\Support\SiteImagery::forCity($ubLocation, $hajj->package()->code ?? ''); @endphp
                <div class="hajj-stay-city">
                    {{-- A photograph of the CITY, captioned as the city. We do not
                         hold photography of the individual properties, and dressing
                         a stock hotel interior up as "your hotel" would be inventing
                         a fact — so the image shows Makkah or Madinah, which is true
                         of every package, and the hotel's own name, star rating and
                         distance stay as text beside it. A property photograph
                         uploaded through the admin later replaces this. --}}
                    @if($ubCityPhoto)
                        <div class="photo-media photo-media--wide hajj-stay-photo">
                            <x-photo :key="$ubCityPhoto" sizes="(min-width: 992px) 62vw, 92vw" />
                            <div class="photo-caption">
                                <span class="photo-caption-eyebrow">Your stay in</span>
                                <p class="photo-caption-title">{{ $hajj->locationLabel($ubLocation) }}</p>
                            </div>
                        </div>
                    @endif

                    <h3 class="hajj-stay-city-name">{{ $hajj->locationLabel($ubLocation) }}</h3>

                    <ul class="hajj-stay-list">
                        @foreach($ubHotels as $ubHotel)
                            <li class="hajj-stay-hotel">
                                <span class="hajj-stay-hotel-name">{{ $ubHotel->hotel_name }}</span>

                                <span class="hajj-stay-meta">
                                    @if($ubHotel->star_rating)
                                        {{-- `.star-rating`, not Bootstrap's `.text-warning`
                                             (#ffc107 measures 1.63:1 on white). The rating
                                             carries real information about the hotel, so it
                                             has to be legible, not merely decorative. --}}
                                        <span class="star-rating" aria-label="{{ $ubHotel->star_rating }} star hotel">{{ str_repeat('★', $ubHotel->star_rating) }}</span>
                                    @endif
                                    @if($ubHotel->nights)
                                        <span class="hajj-stay-nights">{{ $ubHotel->nights }} {{ Str::plural('night', $ubHotel->nights) }}</span>
                                    @endif
                                    @if($ubHotel->distance_note)
                                        <span class="hajj-stay-distance">{{ $ubHotel->distance_note }}</span>
                                    @endif
                                </span>

                                @if($ubHotel->notes)
                                    <p class="hajj-stay-note">{{ $ubHotel->notes }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-hajj.section>
@endif
