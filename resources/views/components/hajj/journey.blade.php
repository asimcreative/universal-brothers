{{--
    The shape of the trip in one line, before any detail.

    The stops come strictly from the itinerary's own `city` column (travel days
    like "To Medinah" collapse into the stay that follows), so nothing is
    added: if a package's itinerary never mentions Arafat, Arafat does not
    appear here even though the Mashaer section may describe it.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubStops = $hajj->journeyStops(); @endphp

@if(count($ubStops) > 1)
    <div class="hajj-journey">
        <h2 class="hajj-journey-title">Your Journey</h2>
        <ol class="hajj-journey-stops">
            @foreach($ubStops as $ubStop)
                <li class="hajj-journey-stop">
                    <span class="hajj-journey-dot" aria-hidden="true"></span>
                    <span class="hajj-journey-name">{{ $ubStop }}</span>
                </li>
            @endforeach
        </ol>
    </div>
@endif
