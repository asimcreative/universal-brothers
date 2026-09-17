{{--
    The credibility facts, in one place.

    These five facts used to be spread across the top bar, a panel in the hero
    and a ticker strip, and the same figures then appeared again in two later
    sections. They are all approved company claims and every value comes from
    Site Settings — nothing here is written into the markup.

    The two counted figures keep `x-stat-number`, so the approved value is in
    the HTML from the start and JavaScript only counts up to it.

    @param stats     the homepage `$stats` array
    @param counters  the homepage `$counters` array (integers to count up to)
--}}
@props(['stats' => [], 'counters' => []])

@php
    $ubLicence = \App\Models\SiteSetting::get('government_license_no', '2014');
@endphp

<section {{ $attributes->class('ub-trust-strip') }} aria-label="Why pilgrims trust Universal Brothers">
    <div class="container">
        <ul class="ub-trust-list">
            @if($stats['years'] ?? null)
                <li class="ub-trust-item">
                    <i class="bi bi-patch-check-fill" aria-hidden="true"></i>
                    <x-stat-number :display="$stats['years']" :target="$counters['years'] ?? null" class="ub-trust-value" />
                    <span class="ub-trust-label">Years serving pilgrims</span>
                </li>
            @endif

            @if($stats['pilgrims'] ?? null)
                <li class="ub-trust-item">
                    <i class="bi bi-people-fill" aria-hidden="true"></i>
                    <x-stat-number :display="$stats['pilgrims']" :target="$counters['pilgrims'] ?? null" class="ub-trust-value" />
                    <span class="ub-trust-label">Hajis served</span>
                </li>
            @endif

            @if($stats['mina_camp_location'] ?? null)
                <li class="ub-trust-item">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <span class="ub-trust-value">{{ $stats['mina_camp_location'] }}</span>
                    <span class="ub-trust-label">Mina camp</span>
                </li>
            @endif

            @if($stats['iata_registered'] ?? null)
                <li class="ub-trust-item">
                    <i class="bi bi-award-fill" aria-hidden="true"></i>
                    <span class="ub-trust-value">IATA</span>
                    <span class="ub-trust-label">Registered operator</span>
                </li>
            @endif

            @if($ubLicence)
                <li class="ub-trust-item">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <span class="ub-trust-value">Licence {{ $ubLicence }}</span>
                    <span class="ub-trust-label">Hajj licence number</span>
                </li>
            @endif
        </ul>
    </div>
</section>
