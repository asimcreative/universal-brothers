<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 7</div>
    <h2 id="step-title-mashaer">Mina, Arafat &amp; Muzdalifah</h2>
    <p>The camps and arrangements for the days of Hajj: Mina is the tent city where pilgrims stay, Arafat is the day of standing, and Muzdalifah is the night under the sky. Pick a saved arrangement to fill a card, then change anything that is different for this package.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'mashaer'])

<div class="d-flex justify-content-end mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="mashaer"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
</div>

@foreach(['mina' => ['Mina', 'bi-tent'], 'arafat' => ['Arafat', 'bi-sun'], 'muzdalifah' => ['Muzdalifah', 'bi-moon']] as $location => [$placeName, $icon])
    @php
        $row = $state['mashaer'][$location] ?? [];
        $saved = collect($library['mashaer'])->where('location', $location);
    @endphp
    <div class="builder-card" data-mashaer-card="{{ $location }}">
        <header>
            <h3><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $placeName }}</h3>
            <div class="builder-toolbar">
                <label class="visually-hidden" for="mashaer-{{ $location }}-pick">Saved {{ $placeName }} arrangement</label>
                <select id="mashaer-{{ $location }}-pick" name="mashaer[{{ $location }}][mashaer_location_id]" class="form-select form-select-sm" style="min-width: 220px" data-mashaer-picker="{{ $location }}">
                    <option value="">{{ $saved->isEmpty() ? 'No saved arrangements' : 'Use a saved arrangement…' }}</option>
                    @foreach($saved as $item)
                        <option value="{{ $item['id'] }}" @selected((string) ($row['mashaer_location_id'] ?? '') === (string) $item['id'])>{{ $item['name'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-mashaer-clear="{{ $location }}">Clear</button>
            </div>
            <p>Leave every box empty if the package does not describe {{ $placeName }}.</p>
            <p class="shared-warning" data-mashaer-shared="{{ $location }}" hidden>
                <i class="bi bi-exclamation-diamond" aria-hidden="true"></i>
                <span><strong data-mashaer-shared-name></strong> is saved information <span data-mashaer-shared-count></span>. The boxes below are this package's own copy: changing them changes <strong>this package only</strong>. To change the arrangement for every package, <a href="#" data-mashaer-shared-link target="_blank" rel="noopener">edit the saved arrangement</a> and use "Update packages" there.</span>
            </p>
        </header>
        <div class="builder-card-body row g-3">
            @foreach([
                'maktab' => ['Maktab', 'e.g. A'],
                'category' => ['Category', 'e.g. Category A'],
                'zone' => ['Zone', 'e.g. Zone 1'],
                'tent_type' => ['Tent type', 'e.g. Air-conditioned marquee'],
                'accommodation_type' => ['Sleeping arrangement', 'e.g. Sofa cum bed'],
                'meal_plan' => ['Meals', 'e.g. Full board buffet'],
                'bathroom' => ['Bathroom', 'e.g. Shared, attached'],
                'air_conditioning' => ['Air conditioning', 'e.g. Yes'],
                'transportation' => ['Transport', 'e.g. Private luxury buses'],
            ] as $field => [$label, $placeholder])
                <div class="col-sm-6 col-lg-4">
                    <label for="mashaer-{{ $location }}-{{ $field }}" class="form-label">{{ $label }}</label>
                    <input type="text" id="mashaer-{{ $location }}-{{ $field }}" name="mashaer[{{ $location }}][{{ $field }}]" data-mashaer-field="{{ $field }}" value="{{ $row[$field] ?? '' }}" class="form-control" placeholder="{{ $placeholder }}" maxlength="255">
                </div>
            @endforeach
            <div class="col-md-6">
                <label for="mashaer-{{ $location }}-other_services" class="form-label">Other services</label>
                <textarea id="mashaer-{{ $location }}-other_services" name="mashaer[{{ $location }}][other_services]" data-mashaer-field="other_services" rows="2" class="form-control">{{ $row['other_services'] ?? '' }}</textarea>
            </div>
            <div class="col-md-6">
                <label for="mashaer-{{ $location }}-notes" class="form-label">Notes shown to customers</label>
                <textarea id="mashaer-{{ $location }}-notes" name="mashaer[{{ $location }}][notes]" data-mashaer-field="notes" rows="2" class="form-control">{{ $row['notes'] ?? '' }}</textarea>
            </div>
        </div>
    </div>
@endforeach
