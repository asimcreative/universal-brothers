@php
    $hotelRows = collect($state['accommodations'] ?? []);
    $hotelsFor = fn (string $code) => $hotelRows->filter(fn ($row) => strtoupper(trim((string) ($row['variant_code'] ?? ''))) === $code)->all();
    $sharedHotels = $hotelRows->filter(fn ($row) => blank($row['variant_code'] ?? null) || ! $optionsByCode->has(strtoupper(trim($row['variant_code']))))->all();
    $aziziya = $state['aziziya'] ?? [];
    $showAziziya = in_array($aziziya['status'] ?? null, ['included', 'optional'], true);
@endphp

<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 5</div>
    <h2 id="step-title-hotels">Hotels &amp; accommodation</h2>
    <p>Pick each hotel from your saved list. Its name and stars are filled in for you, and you can still adjust them for this package only.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'hotels'])

<div class="builder-card">
    <header>
        <h3><i class="bi bi-building" aria-hidden="true"></i>Hotels</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#findHotelModal"><i class="bi bi-search me-1" aria-hidden="true"></i>Find a hotel</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newHotelModal"><i class="bi bi-building-add me-1" aria-hidden="true"></i>New hotel</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="hotels"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
        </div>
        <div class="shared-explainer">
            <div><strong><i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved hotel (shared)</strong><span>Your list of hotels, used by many packages. Picking one copies its name and stars into this package.</span></div>
            <div><strong><i class="bi bi-pencil" aria-hidden="true"></i>This package only</strong><span>Anything you type or change in a row below — the name, stars, meals, nights — changes this package only. The saved hotel and other packages are never changed.</span></div>
        </div>
        <p class="mt-2">To change a hotel for every package, edit it under Reusable Content → Hotels and use "Update packages" there.</p>
    </header>
    <div class="builder-card-body" data-option-groups="accommodations">
        @error('publish.hotels')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'accommodations', 'code' => '', 'uid' => 'shared', 'label' => '', 'rows' => $sharedHotels, 'noun' => 'Hotels'])
        @foreach($optionsByCode as $code => $option)
            @include('admin.hajj-packages.partials.option-group', ['rowsName' => 'accommodations', 'code' => $code, 'uid' => $option['uid'], 'label' => $option['label'], 'rows' => $hotelsFor($code), 'noun' => 'Hotels'])
        @endforeach
    </div>
</div>

<div class="builder-card" data-aziziya-card @if(! $showAziziya) hidden @endif>
    <header>
        <h3><i class="bi bi-house-door" aria-hidden="true"></i>Aziziya accommodation</h3>
        <p>Shown because Aziziya is included or offered as an upgrade (Package setup step).</p>
    </header>
    <div class="builder-card-body row g-3">
        <div class="col-md-6">
            <label for="az-name" class="form-label">Accommodation name</label>
            <input type="text" id="az-name" name="aziziya[accommodation_name]" value="{{ $aziziya['accommodation_name'] ?? '' }}" class="form-control" placeholder="e.g. Aziziya Accommodation — A Class">
        </div>
        <div class="col-md-6">
            <label for="az-location" class="form-label">Location</label>
            <input type="text" id="az-location" name="aziziya[location_note]" value="{{ $aziziya['location_note'] ?? '' }}" class="form-control" placeholder="e.g. Opposite the Jamarat escalator">
        </div>
        <div class="col-md-6">
            <label for="az-walk" class="form-label">Walking distance</label>
            <input type="text" id="az-walk" name="aziziya[walk_distance]" value="{{ $aziziya['walk_distance'] ?? '' }}" class="form-control" placeholder="e.g. 30 to 50-minute walk to the Mina camp">
        </div>
        <div class="col-6 col-md-3">
            <label for="az-days" class="form-label">Days in Aziziya</label>
            <input type="number" id="az-days" name="aziziya[duration_days]" value="{{ $aziziya['duration_days'] ?? '' }}" min="0" max="30" class="form-control">
        </div>
        <div class="col-6 col-md-3">
            <label for="az-occupancy" class="form-label">People per room</label>
            <input type="number" id="az-occupancy" name="aziziya[average_occupancy]" value="{{ $aziziya['average_occupancy'] ?? '' }}" min="1" max="20" class="form-control">
        </div>
        <div class="col-md-6">
            <label for="az-description" class="form-label">Description</label>
            <textarea id="az-description" name="aziziya[description]" rows="2" class="form-control">{{ $aziziya['description'] ?? '' }}</textarea>
        </div>
        <div class="col-md-6">
            <label for="az-notes" class="form-label">Notes shown to customers</label>
            <textarea id="az-notes" name="aziziya[notes]" rows="2" class="form-control">{{ $aziziya['notes'] ?? '' }}</textarea>
        </div>

        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                <h4 class="h6 mb-0">Aziziya rooms and supplements</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="aziziya_room_options"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add room</button>
            </div>
            <div data-rows="aziziya_room_options">
                @foreach($state['aziziya_room_options'] ?? [] as $i => $row)
                    @include('admin.hajj-packages.rows.aziziya-room', ['i' => $i, 'row' => $row, 'options' => $optionLabels])
                @endforeach
            </div>
            <div class="rows-empty" data-rows-empty @if(count($state['aziziya_room_options'] ?? [])) hidden @endif>No Aziziya room choices.</div>
        </div>

        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                <h4 class="h6 mb-0">Aziziya facilities and services</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="aziziya_services"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add facility</button>
            </div>
            <div data-rows="aziziya_services">
                @foreach($state['aziziya_services'] ?? [] as $i => $row)
                    @include('admin.hajj-packages.rows.aziziya-service', ['i' => $i, 'row' => $row])
                @endforeach
            </div>
            <div class="rows-empty" data-rows-empty @if(count($state['aziziya_services'] ?? [])) hidden @endif>No Aziziya facilities listed.</div>
        </div>
    </div>
</div>
