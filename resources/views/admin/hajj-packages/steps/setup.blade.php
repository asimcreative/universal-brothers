<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 2</div>
    <h2 id="step-title-setup">Package setup</h2>
    <p>The choices that shape the rest of the package.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'setup'])

@php $aziziyaStatus = $state['aziziya']['status'] ?? null; @endphp

<div class="builder-card">
    <header>
        <h3><i class="bi bi-airplane" aria-hidden="true"></i>Arrival city</h3>
        <p>Where the journey starts. This decides the order of cities on the package page.</p>
    </header>
    <div class="builder-card-body">
        <div class="choice-cards" role="radiogroup" aria-label="Arrival city">
            <label class="choice-card">
                <input type="radio" name="medinah_first" value="1" @checked($state['medinah_first'])>
                <span class="choice-card-body"><strong>Arrive in Madinah</strong><small>Madinah first, then travel to Makkah for Hajj.</small></span>
            </label>
            <label class="choice-card">
                <input type="radio" name="medinah_first" value="0" @checked(! $state['medinah_first'])>
                <span class="choice-card-body"><strong>Arrive in Jeddah</strong><small>Makkah first, then Madinah after Hajj.</small></span>
            </label>
        </div>
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-arrow-left-right" aria-hidden="true"></i>Hotel stay in Makkah</h3>
    </header>
    <div class="builder-card-body">
        <input type="hidden" name="is_shifting" value="0">
        <div class="choice-cards" role="radiogroup" aria-label="Makkah stay">
            <label class="choice-card">
                <input type="radio" name="is_shifting" value="0" @checked(! $state['is_shifting'])>
                <span class="choice-card-body"><strong>Non-shifting</strong><small>Pilgrims keep the same Makkah hotel room throughout.</small></span>
            </label>
            <label class="choice-card">
                <input type="radio" name="is_shifting" value="1" @checked($state['is_shifting'])>
                <span class="choice-card-body"><strong>Shifting</strong><small>Pilgrims move between hotels or Aziziya during their stay.</small></span>
            </label>
        </div>
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-house-door" aria-hidden="true"></i>Aziziya accommodation</h3>
        <p>Aziziya is the area near Mina used during the days of Hajj.</p>
    </header>
    <div class="builder-card-body">
        <div class="choice-cards" role="radiogroup" aria-label="Aziziya">
            @foreach([
                'included' => ['Included', 'The package price includes an Aziziya stay.'],
                'optional' => ['Optional upgrade', 'Customers can add Aziziya for an extra charge.'],
                'not_included' => ['Not included', 'No Aziziya stay is offered.'],
                'not_applicable' => ['Not applicable', 'Aziziya is not mentioned for this package.'],
            ] as $value => [$label, $help])
                <label class="choice-card">
                    <input type="radio" name="aziziya[status]" value="{{ $value }}" data-aziziya-status @checked($aziziyaStatus === $value)>
                    <span class="choice-card-body"><strong>{{ $label }}</strong><small>{{ $help }}</small></span>
                </label>
            @endforeach
        </div>
        <div class="form-help mt-2">When Aziziya is included or optional, its details appear in the Hotels step.</div>
    </div>
</div>
