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

<div class="builder-card">
    <header>
        <h3><i class="bi bi-cash-coin" aria-hidden="true"></i>Prices</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="pricing"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
        </div>
        <p>Untick "Available" for a room type that is sold out or not offered, instead of removing it. Kaaba-view and other supplements go in the Additional options step.</p>
    </header>
    <div class="builder-card-body" data-option-groups="room_options">
        @error('publish.pricing')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'room_options', 'code' => '', 'uid' => 'shared', 'label' => '', 'rows' => $sharedRooms, 'noun' => 'Room prices'])
        @foreach($optionsByCode as $code => $option)
            @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'room_options', 'code' => $code, 'uid' => $option['uid'], 'label' => $option['label'], 'rows' => $roomsFor($code), 'noun' => 'Room prices'])
        @endforeach
    </div>
</div>
