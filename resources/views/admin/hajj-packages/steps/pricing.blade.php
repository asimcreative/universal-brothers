@php
    // Put each room row in the box of the option it belongs to.
    $roomRows = collect($state['room_options'] ?? []);
    $roomsFor = fn (string $code) => $roomRows->filter(fn ($row) => strtoupper(trim((string) ($row['variant_code'] ?? ''))) === $code)->all();
    $sharedRooms = $roomRows->filter(fn ($row) => blank($row['variant_code'] ?? null) || ! $optionsByCode->has(strtoupper(trim($row['variant_code']))))->all();
@endphp

<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 4</div>
    <h2 id="step-title-pricing">Room prices</h2>
    <p>Add each room type the package offers, with its price per person. Fill in only the currencies you have prices for — leave the others empty rather than guessing.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'pricing'])

<div class="builder-card">
    <header>
        <h3><i class="bi bi-cash-coin" aria-hidden="true"></i>Prices</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="pricing"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
        </div>
        <p>Prices are per person. Untick "Available" for a room type that is sold out or not offered, instead of removing it.</p>
        <p class="price-hint"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Supplements such as a Kaaba-view room or an extra night are <strong>not</strong> room types — add them in step 10, Additional options, so they show as a separate choice with their own price.</span></p>
    </header>
    <div class="builder-card-body" data-option-groups="room_options">
        @error('publish.pricing')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'room_options', 'code' => '', 'uid' => 'shared', 'label' => '', 'rows' => $sharedRooms, 'noun' => 'Room prices'])
        @foreach($optionsByCode as $code => $option)
            @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'room_options', 'code' => $code, 'uid' => $option['uid'], 'label' => $option['label'], 'rows' => $roomsFor($code), 'noun' => 'Room prices'])
        @endforeach
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-receipt" aria-hidden="true"></i>Price summary</h3>
        <p>How the prices will read on the website. The lowest available US dollar price becomes the "From" price on package cards.</p>
    </header>
    <div class="builder-card-body" data-price-summary aria-live="polite"></div>
</div>
