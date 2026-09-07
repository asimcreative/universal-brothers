{{--
    Animated statistic.

    The server renders the FINAL, approved figure as the element's text — not
    "0". The live site shipped `<div class="stat-number" data-counter-target="20">0</div>`,
    so the HTML delivered to every visitor and every crawler literally read
    "0 Years of Experience", "0 Pilgrims Served" and "0 Awards & Recognitions"
    for a company trading for over two decades. Anyone whose JavaScript was
    slow, blocked or errored saw those zeros, and so did Google.

    Now the correct value is in the markup from the start; app.js only takes
    over to count *up* to it, and restores the exact display string when it
    finishes. No JS, no animation, still correct.

    Props
      display  the approved public string, e.g. "20+", "10,000+"
      target   the integer to count up to, e.g. 20, 10000
--}}
@props(['display', 'target' => null])

@php
    $ubTarget = $target !== null
        ? (int) $target
        : (int) preg_replace('/\D/', '', (string) $display);
@endphp

<div class="stat-number"
     data-counter-target="{{ $ubTarget }}"
     data-counter-display="{{ $display }}">{{ $display }}</div>
