{{--
    One hotel stay. $i, $row, $library

    "Hotel from your list" links the row to the reusable hotel and copies its
    name and stars in. Both stay editable here — a change made in this row
    applies to this package only and never alters the saved hotel.
--}}
@php
    $location = $row['location'] ?? 'makkah';
    $hotels = collect($library['hotels'] ?? []);
    $stars = $row['star_rating'] ?? null;
@endphp
<div class="b-row b-row-hotel" data-row="accommodations" data-index="{{ $i }}">
    <input type="hidden" name="accommodations[{{ $i }}][variant_code]" data-field="variant_code" value="{{ $row['variant_code'] ?? '' }}">

    <div>
        <label class="form-label" for="hotel-location-{{ $i }}">City / place</label>
        <select id="hotel-location-{{ $i }}" name="accommodations[{{ $i }}][location]" data-field="location" class="form-select @error("accommodations.$i.location") is-invalid @enderror" aria-label="Hotel location">
            @foreach(['makkah' => 'Makkah', 'medinah' => 'Madinah', 'aziziya' => 'Aziziya', 'mina' => 'Mina', 'arafat' => 'Arafat'] as $value => $label)
                <option value="{{ $value }}" @selected($location === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="hotel-pick-{{ $i }}">Hotel from your list</label>
        <select id="hotel-pick-{{ $i }}" name="accommodations[{{ $i }}][hotel_id]" data-field="hotel_id" data-hotel-picker class="form-select" aria-label="Hotel from your list">
            <option value="">Not in the list</option>
            @foreach($hotels->where('location', $location) as $hotel)
                <option value="{{ $hotel['id'] }}" @selected((string) ($row['hotel_id'] ?? '') === (string) $hotel['id'])>{{ $hotel['name'] }}{{ $hotel['archived'] ? ' (archived)' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="hotel-name-{{ $i }}">Name shown on the website</label>
        <input type="text" id="hotel-name-{{ $i }}" name="accommodations[{{ $i }}][hotel_name]" data-field="hotel_name" value="{{ $row['hotel_name'] ?? '' }}"
               class="form-control @error("accommodations.$i.hotel_name") is-invalid @enderror" placeholder="Hotel name" aria-label="Hotel name shown on the website">
    </div>
    <div>
        <label class="form-label" for="hotel-stars-{{ $i }}">Stars</label>
        <select id="hotel-stars-{{ $i }}" name="accommodations[{{ $i }}][star_rating]" data-field="star_rating" class="form-select" aria-label="Star rating">
            <option value="">—</option>
            @foreach([5, 4, 3, 2, 1] as $star)
                <option value="{{ $star }}" @selected((string) $stars === (string) $star)>{{ $star }} ★</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="hotel-meal-{{ $i }}">Meal plan</label>
        <select id="hotel-meal-{{ $i }}" name="accommodations[{{ $i }}][meal_plan_id]" data-field="meal_plan_id" data-meal-picker class="form-select" aria-label="Meal plan">
            <option value="">Choose or type below</option>
            @foreach($library['mealPlans'] ?? [] as $plan)
                <option value="{{ $plan['id'] }}" @selected((string) ($row['meal_plan_id'] ?? '') === (string) $plan['id'])>{{ $plan['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="hotel-nights-{{ $i }}">Nights</label>
        <input type="number" min="0" max="60" id="hotel-nights-{{ $i }}" name="accommodations[{{ $i }}][nights]" data-field="nights" value="{{ $row['nights'] ?? '' }}" class="form-control" aria-label="Nights">
    </div>
    <x-admin.row-actions label="hotel" duplicate />

    <div class="b-row-wide row g-2">
        <div class="col-md-4">
            <label class="form-label" for="hotel-meal-text-{{ $i }}">Meals, as shown to customers</label>
            <input type="text" id="hotel-meal-text-{{ $i }}" name="accommodations[{{ $i }}][meal_plan]" data-field="meal_plan" value="{{ $row['meal_plan'] ?? '' }}" class="form-control" placeholder="e.g. Half board (breakfast & dinner)">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="hotel-distance-{{ $i }}">Distance</label>
            <input type="text" id="hotel-distance-{{ $i }}" name="accommodations[{{ $i }}][distance_note]" data-field="distance_note" value="{{ $row['distance_note'] ?? '' }}" class="form-control" placeholder="e.g. 200 m from the Haram">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="hotel-notes-{{ $i }}">Note for this package</label>
            <input type="text" id="hotel-notes-{{ $i }}" name="accommodations[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}" class="form-control" placeholder="Optional">
        </div>
    </div>
</div>
