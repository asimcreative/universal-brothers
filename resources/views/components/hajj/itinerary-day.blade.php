{{--
    One day of the itinerary.

    Props
      day     a PackageItineraryDay
      labels  ['a' => 'Package A', 'b' => 'Package B'] from the presenter —
              derived from the package's real variant codes, not hard-coded,
              so a package whose variants are coded "1"/"2" is labelled "1"/"2"
--}}
@props(['day', 'labels'])

@php
    // `accommodation_a` and `accommodation_b` are two columns on the same row,
    // and on the many days where both options stay in the same hotel they hold
    // the identical string. Printing "Package A: X / Package B: X" on every
    // such day is noise, so an identical pair collapses to one line.
    $ubA = trim((string) $day->accommodation_a);
    $ubB = trim((string) $day->accommodation_b);
    $ubSplit = $ubA !== '' && $ubB !== '' && $ubA !== $ubB && $labels['a'] && $labels['b'];
@endphp

<li class="hajj-day">
    <div class="hajj-day-marker" aria-hidden="true">{{ $day->day_number }}</div>

    <div class="hajj-day-body">
        <div class="hajj-day-head">
            <span class="hajj-day-number">Day {{ $day->day_number }}</span>
            @if($day->city)<span class="hajj-day-city">{{ $day->city }}</span>@endif
            @if($day->date_gregorian)<span class="hajj-day-date">{{ $day->date_gregorian->format('d M Y') }}</span>@endif
            @if($day->date_hijri_label)<span class="hajj-day-hijri">{{ $day->date_hijri_label }}</span>@endif
        </div>

        @if($ubSplit)
            <ul class="hajj-day-stays">
                <li><span class="hajj-day-tag">{{ $labels['a'] }}</span>{{ $ubA }}</li>
                <li><span class="hajj-day-tag">{{ $labels['b'] }}</span>{{ $ubB }}</li>
            </ul>
        @elseif($ubA !== '' || $ubB !== '')
            <p class="hajj-day-stay">{{ $ubA !== '' ? $ubA : $ubB }}</p>
        @endif

        @if($day->notes)
            <p class="hajj-day-note">{{ $day->notes }}</p>
        @endif
    </div>
</li>
