@php
    $stages = [
        'Registration', 'Documentation', 'Orientation', 'Departure',
        'Arrival in Saudi Arabia', 'Makkah', 'Days of Hajj', 'Madinah', 'Return Home',
    ];
@endphp

<div class="hajj-process-timeline">
    @foreach($stages as $stage)
        <div class="hajj-process-stage reveal-on-scroll">
            <span class="hajj-process-marker">{{ $loop->iteration }}</span>
            <span class="hajj-process-label">{{ $stage }}</span>
        </div>
    @endforeach
</div>
