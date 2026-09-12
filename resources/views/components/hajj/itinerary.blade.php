{{--
    Day-by-day itinerary.

    Previously a Bootstrap accordion with `data-bs-parent`, so only one day
    could be open at a time: reading a fourteen-day itinerary meant fourteen
    clicks, and comparing day 3 against day 9 was impossible. Each day's
    content is only a line or two, so it is simply shown. Long itineraries keep
    the first eight days open and put the rest behind a native `<details>` —
    which needs no JavaScript, stays in the DOM for search engines, and is
    keyboard-accessible for free.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubDays = $hajj->package()->itineraryDays->sortBy('day_number')->values();
    $ubLabels = $hajj->itineraryColumnLabels();

    // Only fold when there is enough to be worth folding; a 10-day package
    // would otherwise hide two days behind a control, which is just friction.
    $ubVisible = $ubDays->count() > 12 ? 8 : $ubDays->count();
    $ubHead = $ubDays->take($ubVisible);
    $ubRest = $ubDays->slice($ubVisible);
@endphp

@if($ubDays->isNotEmpty())
    <x-hajj.section
        id="itinerary"
        eyebrow="Every Day, Planned"
        title="Day-by-Day Itinerary"
        :lead="'The full '.$ubDays->count().'-day plan, including where you stay each night.'">

        <ol class="hajj-timeline">
            @foreach($ubHead as $ubDay)
                <x-hajj.itinerary-day :day="$ubDay" :labels="$ubLabels" />
            @endforeach
        </ol>

        @if($ubRest->isNotEmpty())
            <details class="hajj-timeline-more">
                <summary>Show the remaining {{ $ubRest->count() }} days</summary>
                <ol class="hajj-timeline hajj-timeline--continued">
                    @foreach($ubRest as $ubDay)
                        <x-hajj.itinerary-day :day="$ubDay" :labels="$ubLabels" />
                    @endforeach
                </ol>
            </details>
        @endif
    </x-hajj.section>
@endif
