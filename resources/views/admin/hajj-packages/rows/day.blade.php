{{-- One day of the journey plan. $i, $row, $hasOptionB, $optionALabel, $optionBLabel --}}
<div class="b-row b-row-day {{ $hasOptionB ? 'has-option-b' : '' }}" data-row="itinerary" data-index="{{ $i }}">
    <div>
        <label class="form-label" for="day-number-{{ $i }}">Day</label>
        <input type="number" min="1" max="60" id="day-number-{{ $i }}" name="itinerary[{{ $i }}][day_number]" data-field="day_number" value="{{ $row['day_number'] ?? '' }}" class="form-control" aria-label="Day number">
    </div>
    <div>
        <label class="form-label" for="day-date-{{ $i }}">English date</label>
        <input type="date" id="day-date-{{ $i }}" name="itinerary[{{ $i }}][date_gregorian]" data-field="date_gregorian" value="{{ $row['date_gregorian'] ?? '' }}"
               class="form-control @error("itinerary.$i.date_gregorian") is-invalid @enderror" aria-label="English date">
        <x-admin.error :name="'itinerary.'.$i.'.date_gregorian'" />
    </div>
    <div>
        <label class="form-label" for="day-hijri-{{ $i }}">Islamic date</label>
        <input type="text" id="day-hijri-{{ $i }}" name="itinerary[{{ $i }}][date_hijri_label]" data-field="date_hijri_label" value="{{ $row['date_hijri_label'] ?? '' }}" class="form-control" placeholder="08 Zil Hajj" aria-label="Islamic date">
    </div>
    <div>
        <label class="form-label" for="day-city-{{ $i }}">City / place</label>
        <input type="text" id="day-city-{{ $i }}" name="itinerary[{{ $i }}][city]" data-field="city" value="{{ $row['city'] ?? '' }}" list="dl-journey-places" class="form-control" placeholder="Makkah" aria-label="City or place">
    </div>
    <div class="day-stay-a">
        <label class="form-label" for="day-stay-a-{{ $i }}" data-option-a-label>{{ $hasOptionB ? 'Stay — '.$optionALabel : 'Where they stay' }}</label>
        <input type="text" id="day-stay-a-{{ $i }}" name="itinerary[{{ $i }}][accommodation_a]" data-field="accommodation_a" value="{{ $row['accommodation_a'] ?? '' }}" list="dl-journey-stays" class="form-control" placeholder="Hotel, camp or activity" aria-label="Stay">
    </div>
    <div class="day-stay-b" data-option-b-only @if(! $hasOptionB) hidden @endif>
        <label class="form-label" for="day-stay-b-{{ $i }}" data-option-b-label>Stay — {{ $optionBLabel }}</label>
        <input type="text" id="day-stay-b-{{ $i }}" name="itinerary[{{ $i }}][accommodation_b]" data-field="accommodation_b" value="{{ $row['accommodation_b'] ?? '' }}" list="dl-journey-stays" class="form-control" placeholder="Leave empty if the same" aria-label="Stay for the second option">
    </div>
    <x-admin.row-actions label="day" duplicate />
    <div class="b-row-wide">
        <label class="visually-hidden" for="day-notes-{{ $i }}">Description or note</label>
        <input type="text" id="day-notes-{{ $i }}" name="itinerary[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}" class="form-control form-control-sm" placeholder="Description or note for this day (optional)">
    </div>
</div>
