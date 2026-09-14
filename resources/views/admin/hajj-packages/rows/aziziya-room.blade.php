{{-- One Aziziya room choice or supplement. $i, $row, $options (code => label) --}}
@php
    $pricingTypes = ['included' => 'Included', 'supplement' => 'Extra charge', 'optional' => 'Optional', 'upgrade' => 'Upgrade', 'on_request' => 'On request'];
@endphp
<div class="b-row b-row-az-room" data-row="aziziya_room_options" data-index="{{ $i }}">
    <input type="hidden" name="aziziya_room_options[{{ $i }}][sharing_type]" data-field="sharing_type" value="{{ $row['sharing_type'] ?? '' }}">
    <div>
        <label class="form-label" for="az-room-option-{{ $i }}">For option</label>
        <select id="az-room-option-{{ $i }}" name="aziziya_room_options[{{ $i }}][variant_code]" data-field="variant_code" data-option-select class="form-select" aria-label="Applies to option">
            <option value="">All</option>
            @foreach($options as $code => $label)
                <option value="{{ $code }}" @selected(strtoupper((string) ($row['variant_code'] ?? '')) === $code)>{{ $code }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="az-room-label-{{ $i }}">Room</label>
        <input type="text" id="az-room-label-{{ $i }}" name="aziziya_room_options[{{ $i }}][display_label]" data-field="display_label" data-slug-source value="{{ $row['display_label'] ?? '' }}" class="form-control" placeholder="e.g. Family room" aria-label="Aziziya room">
    </div>
    <div>
        <label class="form-label" for="az-room-pricing-{{ $i }}">Pricing</label>
        <select id="az-room-pricing-{{ $i }}" name="aziziya_room_options[{{ $i }}][pricing_type]" data-field="pricing_type" class="form-select" aria-label="Pricing type">
            @foreach($pricingTypes as $value => $label)
                <option value="{{ $value }}" @selected(($row['pricing_type'] ?? 'included') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @foreach(['price_usd' => 'USD', 'price_sar' => 'SAR', 'price_pkr' => 'PKR'] as $field => $currency)
        <div>
            <label class="form-label" for="az-room-{{ $field }}-{{ $i }}">{{ $currency }}</label>
            <input type="number" step="0.01" min="0" id="az-room-{{ $field }}-{{ $i }}" name="aziziya_room_options[{{ $i }}][{{ $field }}]" data-field="{{ $field }}" value="{{ $row[$field] ?? '' }}" class="form-control" placeholder="—" aria-label="{{ $currency }} price">
        </div>
    @endforeach
    <x-admin.row-actions label="Aziziya room" />
    <div class="b-row-wide row g-2">
        <div class="col-sm-3 col-md-2">
            <label class="form-label" for="az-room-occupancy-{{ $i }}">People per room</label>
            <input type="number" min="1" max="20" id="az-room-occupancy-{{ $i }}" name="aziziya_room_options[{{ $i }}][occupancy]" data-field="occupancy" value="{{ $row['occupancy'] ?? '' }}" class="form-control">
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label" for="az-room-basis-{{ $i }}">Price is for</label>
            <select id="az-room-basis-{{ $i }}" name="aziziya_room_options[{{ $i }}][price_basis]" data-field="price_basis" class="form-select">
                @foreach(['per_person' => 'Per person', 'per_room' => 'Per room', 'flat' => 'One fixed amount'] as $value => $label)
                    <option value="{{ $value }}" @selected(($row['price_basis'] ?? 'per_person') === $value)>{{ $label }}</option>
                @endforeach
                @unless(in_array($row['price_basis'] ?? 'per_person', ['per_person', 'per_room', 'flat'], true))
                    <option value="{{ $row['price_basis'] }}" selected>{{ $row['price_basis'] }}</option>
                @endunless
            </select>
        </div>
        <div class="col">
            <label class="form-label" for="az-room-description-{{ $i }}">Description</label>
            <input type="text" id="az-room-description-{{ $i }}" name="aziziya_room_options[{{ $i }}][description]" data-field="description" value="{{ $row['description'] ?? '' }}" class="form-control" placeholder="Optional">
        </div>
        <input type="hidden" name="aziziya_room_options[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}">
    </div>
</div>
