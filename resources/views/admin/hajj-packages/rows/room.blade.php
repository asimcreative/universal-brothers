{{--
    One room type with its prices. $i, $row

    The visible "Room type" list fills three hidden values the database keeps
    (sharing_type, occupancy, display_label), so the admin picks "Triple
    sharing" instead of typing a code, a number and a label that must agree.
--}}
@php
    $roomTypes = \App\Support\Packages\RoomTypes::ALL;
    $selectedType = \App\Support\Packages\RoomTypes::match($row['sharing_type'] ?? null, $row['display_label'] ?? null);
    $isAvailable = array_key_exists('is_available', $row) ? ! empty($row['is_available']) : true;
@endphp
<div class="b-row b-row-room" data-row="room_options" data-index="{{ $i }}">
    <input type="hidden" name="room_options[{{ $i }}][variant_code]" data-field="variant_code" value="{{ $row['variant_code'] ?? '' }}">
    <input type="hidden" name="room_options[{{ $i }}][sharing_type]" data-field="sharing_type" value="{{ $row['sharing_type'] ?? '' }}">
    <input type="hidden" name="room_options[{{ $i }}][occupancy]" data-field="occupancy" value="{{ $row['occupancy'] ?? '' }}">
    <input type="hidden" name="room_options[{{ $i }}][price_basis]" data-field="price_basis" value="{{ $row['price_basis'] ?? 'per_person' }}">

    <div>
        <label class="form-label" for="room-type-{{ $i }}">Room type</label>
        <select id="room-type-{{ $i }}" class="form-select" data-room-type aria-label="Room type">
            <option value="">Choose…</option>
            @foreach($roomTypes as $key => $type)
                <option value="{{ $key }}" @selected($selectedType === $key)>{{ $type['label'] }}</option>
            @endforeach
            <option value="custom" @selected($selectedType === 'custom')>Other (type a name)</option>
        </select>
        <div data-room-custom @if($selectedType !== 'custom') hidden @endif>
            <input type="text" name="room_options[{{ $i }}][display_label]" data-field="display_label" value="{{ $row['display_label'] ?? '' }}"
                   class="form-control mt-1" placeholder="e.g. Quint sharing" aria-label="Room type name shown to customers">
        </div>
    </div>
    <div>
        <label class="form-label d-block" for="room-available-{{ $i }}">Available</label>
        <input type="hidden" name="room_options[{{ $i }}][is_available]" value="0">
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" id="room-available-{{ $i }}" name="room_options[{{ $i }}][is_available]" data-field="is_available" value="1" @checked($isAvailable)>
            <label class="form-check-label small" for="room-available-{{ $i }}">{{ $isAvailable ? 'Yes' : 'No' }}</label>
        </div>
    </div>
    @foreach(['price_usd' => 'USD', 'price_sar' => 'SAR', 'price_pkr' => 'PKR'] as $field => $currency)
        <div>
            <label class="form-label" for="room-{{ $field }}-{{ $i }}">Price ({{ $currency }})</label>
            <input type="number" step="0.01" min="0" inputmode="decimal" id="room-{{ $field }}-{{ $i }}" name="room_options[{{ $i }}][{{ $field }}]" data-field="{{ $field }}"
                   value="{{ $row[$field] ?? '' }}" class="form-control @error("room_options.$i.$field") is-invalid @enderror" placeholder="—" aria-label="{{ $currency }} price">
            <x-admin.error :name="'room_options.'.$i.'.'.$field" />
        </div>
    @endforeach
    <div>
        <label class="form-label" for="room-notes-{{ $i }}">Note</label>
        <input type="text" id="room-notes-{{ $i }}" name="room_options[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}" class="form-control" placeholder="Optional" aria-label="Room note">
    </div>
    <x-admin.row-actions label="room type" duplicate />
</div>
