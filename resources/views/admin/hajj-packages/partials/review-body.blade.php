{{--
    The Review step's content: what blocks publishing, what is worth checking,
    the checklist, and the whole package section by section with an "Edit"
    link back to each step. Rendered on page load and again by
    HajjPackageController::assess as the admin works. $review, $steps
--}}
@php
    $problems = $review->problems();
    $warnings = $review->warnings();
    $checklist = $review->checklist();
    $percent = $review->percent();
    $s = $review->summary();
    $missing = '<span class="review-missing">Not added</span>';
    $edit = fn (string $step) => '<a href="#step-'.$step.'" class="btn btn-sm btn-outline-primary review-edit" data-step-link="'.$step.'">Edit<span class="visually-hidden"> '.e($steps[$step] ?? $step).'</span></a>';
    $value = fn ($v) => filled($v) ? e($v) : $missing;
    $money = fn ($v, $cur) => $v === null ? '<span class="review-na">N/A</span>' : e($cur.' '.number_format($v));
@endphp

<div class="review-status {{ $problems ? 'is-blocked' : 'is-ready' }}" data-review-summary data-problem-count="{{ count($problems) }}" data-warning-count="{{ count($warnings) }}">
    <div class="review-status-percent" aria-hidden="true">{{ $percent }}%</div>
    <div>
        @if($problems)
            <strong>{{ count($problems) }} {{ Str::plural('problem', count($problems)) }} must be fixed before publishing.</strong>
            <span>The package is {{ $percent }}% complete. You can still save it as a draft.</span>
        @else
            <strong>Nothing blocks publishing.</strong>
            <span>The package is {{ $percent }}% complete.{{ $warnings ? ' Check the '.count($warnings).' '.Str::plural('suggestion', count($warnings)).' below before you publish.' : '' }}</span>
        @endif
    </div>
</div>

@if($problems)
    <div class="review-list is-problem" role="group" aria-label="Problems that stop publishing">
        <h3><i class="bi bi-x-octagon-fill" aria-hidden="true"></i>Must fix before publishing</h3>
        <ul>
            @foreach($problems as $problem)
                <li><span>{{ $problem['message'] }}</span> {!! $edit($problem['step']) !!}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($warnings)
    <div class="review-list is-warning" role="group" aria-label="Worth checking">
        <h3><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>Worth checking</h3>
        <ul>
            @foreach($warnings as $warning)
                <li><span>{{ $warning['message'] }}</span> {!! $edit($warning['step']) !!}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="review-card">
    <header><h3><i class="bi bi-list-check" aria-hidden="true"></i>Checklist</h3></header>
    <ul class="review-checklist">
        @foreach($checklist as $item)
            <li class="{{ $item['done'] ? 'is-done' : '' }}">
                <i class="bi {{ $item['done'] ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}<span class="visually-hidden">: {{ $item['done'] ? 'done' : 'not done' }}</span></span>
                @unless($item['done'] || $item['key'] === 'review'){!! $edit($item['step']) !!}@endunless
            </li>
        @endforeach
    </ul>
</div>

<div class="review-card">
    <header><h3><i class="bi bi-card-heading" aria-hidden="true"></i>Basic information &amp; setup</h3>{!! $edit('basics') !!}</header>
    <dl class="review-facts">
        @foreach($s['basics'] as $label => $v)
            <div><dt>{{ $label }}</dt><dd>{!! $value($v) !!}</dd></div>
        @endforeach
        @foreach($s['setup'] as $label => $v)
            <div><dt>{{ $label }}</dt><dd>{!! $value($v) !!}</dd></div>
        @endforeach
    </dl>
</div>

<div class="review-card">
    <header><h3><i class="bi bi-cash-coin" aria-hidden="true"></i>Options &amp; room prices</h3>{!! $edit('pricing') !!}</header>
    @if($s['options'])
        <ul class="review-options">
            @foreach($s['options'] as $code => $option)
                <li><span class="option-chip">{{ $code }}</span> {!! $value($option['label']) !!} <small>· {{ count($option['hotels']) }} {{ Str::plural('hotel', count($option['hotels'])) }} · {{ $option['rooms'] }} {{ Str::plural('room type', $option['rooms']) }}</small></li>
            @endforeach
        </ul>
    @endif
    @if($s['rooms'])
        <div class="table-responsive">
            <table class="table table-sm review-table mb-0">
                <thead><tr><th scope="col">Room</th>@if($s['options'])<th scope="col">Option</th>@endif<th scope="col">USD</th><th scope="col">SAR</th><th scope="col">PKR</th><th scope="col">Available</th></tr></thead>
                <tbody>
                    @foreach($s['rooms'] as $room)
                        <tr class="{{ $room['available'] ? '' : 'text-muted' }}">
                            <td>{{ $room['label'] }}</td>
                            @if($s['options'])<td>{{ $room['option'] ?? 'Every option' }}</td>@endif
                            <td>{!! $money($room['usd'], 'USD') !!}</td>
                            <td>{!! $money($room['sar'], 'SAR') !!}</td>
                            <td>{!! $money($room['pkr'], 'PKR') !!}</td>
                            <td>{{ $room['available'] ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
</div>

<div class="review-card">
    <header><h3><i class="bi bi-building" aria-hidden="true"></i>Hotels &amp; meals</h3>{!! $edit('hotels') !!}</header>
    @if($s['hotels'])
        <ul class="review-rows">
            @foreach($s['hotels'] as $hotel)
                <li>
                    <strong>{{ $hotel['name'] }}</strong>
                    <span>{{ $hotel['city'] }}{{ $hotel['option'] ? ' · Option '.$hotel['option'] : '' }}{{ $hotel['stars'] ? ' · '.$hotel['stars'].' stars' : '' }}{{ $hotel['nights'] ? ' · '.$hotel['nights'].' '.Str::plural('night', $hotel['nights']) : '' }}</span>
                    <span>Meals: {!! $value($hotel['meal_plan']) !!}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
</div>

<div class="review-card">
    <header><h3><i class="bi bi-calendar-week" aria-hidden="true"></i>Journey plan</h3>{!! $edit('journey') !!}</header>
    @if($s['days'])
        <div class="table-responsive">
            <table class="table table-sm review-table mb-0">
                <thead><tr><th scope="col">Day</th><th scope="col">Date</th><th scope="col">Islamic date</th><th scope="col">Place</th><th scope="col">Stay</th><th scope="col">Transport</th></tr></thead>
                <tbody>
                    @foreach($s['days'] as $day)
                        <tr>
                            <td>{{ $day['number'] }}</td>
                            <td class="text-nowrap">{!! $day['date'] ? e($day['date']->format('d/m/Y')) : $missing !!}</td>
                            <td>{{ $day['hijri'] }}</td>
                            <td>{{ $day['city'] }}</td>
                            <td>{{ $day['stay'] }}</td>
                            <td>{{ $day['transport'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
</div>

<div class="review-card">
    <header><h3><i class="bi bi-geo-alt" aria-hidden="true"></i>Mina, Arafat &amp; Muzdalifah</h3>{!! $edit('mashaer') !!}</header>
    @if($s['mashaer'])
        <dl class="review-facts">
            @foreach($s['mashaer'] as $place => $facts)
                <div><dt>{{ ucfirst($place) }}</dt><dd>{{ collect($facts)->except(['notes', 'other_services'])->implode(' · ') }}</dd></div>
            @endforeach
        </dl>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
</div>

<div class="review-card">
    <header><h3><i class="bi bi-bus-front" aria-hidden="true"></i>Transport</h3>{!! $edit('transport') !!}</header>
    @if($s['transport'])
        <ul class="review-rows">
            @foreach($s['transport'] as $leg)
                <li><strong>{{ $leg['route'] }}</strong><span>{{ $leg['type'] }} · {{ $leg['cost'] }}</span></li>
            @endforeach
        </ul>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
</div>

<div class="row g-3 mb-3">
    @foreach(['inclusions' => 'Included in this package', 'exclusions' => 'Not included in this package'] as $list => $heading)
        <div class="col-lg-6">
            <div class="review-card h-100 mb-0 service-panel {{ $list === 'inclusions' ? 'is-included' : 'is-excluded' }}">
                <header><h3>{{ $heading }}</h3>{!! $edit('services') !!}</header>
                @if($s[$list])
                    <ul class="review-bullets">
                        @foreach($s[$list] as $line)<li>{{ $line }}</li>@endforeach
                    </ul>
                @else
                    <p class="review-empty">{!! $missing !!}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="review-card">
    <header><h3><i class="bi bi-plus-square" aria-hidden="true"></i>Additional options</h3>{!! $edit('extras') !!}</header>
    @if($s['upgrades'])
        <ul class="review-rows">
            @foreach($s['upgrades'] as $upgrade)
                <li><strong>{{ $upgrade['name'] }}</strong><span>{{ $upgrade['price'] }}</span></li>
            @endforeach
        </ul>
    @else
        <p class="review-empty text-muted">None — this is optional.</p>
    @endif
</div>

<div class="review-card">
    <header><h3><i class="bi bi-journal-text" aria-hidden="true"></i>Notes &amp; policies</h3>{!! $edit('notes') !!}</header>
    @if($s['notes'])
        <ul class="review-rows">
            @foreach($s['notes'] as $note)
                <li>
                    <strong>{{ $note['title'] ?: \Illuminate\Support\Str::limit($note['content'], 70) }}</strong>
                    <span>@if($note['important'])<span class="note-kind-badge is-important">Important</span> @endif{{ $note['saved'] ? 'Saved note' : 'Written for this package' }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="review-empty">{!! $missing !!}</p>
    @endif
    <p class="review-private mb-0"><i class="bi bi-lock" aria-hidden="true"></i>Internal admin notes: {{ $s['internal_notes'] ? 'written' : 'none' }} — never shown to website visitors, so they are not listed here.</p>
</div>

<div class="review-card">
    <header><h3><i class="bi bi-image" aria-hidden="true"></i>Photos &amp; search engines</h3>{!! $edit('media') !!}</header>
    <dl class="review-facts">
        @foreach($s['media'] as $label => $v)
            <div><dt>{{ $label }}</dt><dd>{!! $value($v) !!}</dd></div>
        @endforeach
    </dl>
</div>
