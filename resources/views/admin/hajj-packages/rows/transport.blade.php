{{-- One transport leg. $i, $row --}}
@php
    $included = array_key_exists('is_included', $row) ? ! empty($row['is_included']) : true;
    $types = \App\Models\TransportOption::TYPES;
    $type = $row['transport_type'] ?? 'airport_transfer';
@endphp
<div class="b-row b-row-transport" data-row="transportation" data-index="{{ $i }}">
    <input type="hidden" name="transportation[{{ $i }}][transport_option_id]" data-field="transport_option_id" value="{{ $row['transport_option_id'] ?? '' }}">
    <div>
        <label class="form-label" for="transport-from-{{ $i }}">From</label>
        <input type="text" id="transport-from-{{ $i }}" name="transportation[{{ $i }}][from_location]" data-field="from_location" value="{{ $row['from_location'] ?? '' }}" class="form-control" placeholder="Jeddah Airport" aria-label="From">
    </div>
    <div>
        <label class="form-label" for="transport-to-{{ $i }}">To</label>
        <input type="text" id="transport-to-{{ $i }}" name="transportation[{{ $i }}][to_location]" data-field="to_location" value="{{ $row['to_location'] ?? '' }}" class="form-control" placeholder="Makkah hotel" aria-label="To">
    </div>
    <div>
        <label class="form-label" for="transport-type-{{ $i }}">Type</label>
        <select id="transport-type-{{ $i }}" name="transportation[{{ $i }}][transport_type]" data-field="transport_type" class="form-select" aria-label="Type of transport">
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
            @unless(array_key_exists($type, $types))
                <option value="{{ $type }}" selected>{{ \Illuminate\Support\Str::headline($type) }}</option>
            @endunless
        </select>
    </div>
    <div>
        <label class="form-label d-block" for="transport-included-{{ $i }}">Included</label>
        <input type="hidden" name="transportation[{{ $i }}][is_included]" value="0">
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" id="transport-included-{{ $i }}" name="transportation[{{ $i }}][is_included]" data-field="is_included" data-extra-toggle value="1" @checked($included)>
            <label class="form-check-label small" for="transport-included-{{ $i }}">In price</label>
        </div>
    </div>
    <div class="row-actions-wrap">
        @if(filled($row['transport_option_id'] ?? null))<span class="lib-badge mb-1" data-lib-badge><i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved content</span>@endif
        <x-admin.row-actions label="transport" />
    </div>
    <div class="b-row-wide row g-2">
        <div class="col-sm-4 col-md-2" data-extra-only @if($included) hidden @endif>
            <label class="form-label" for="transport-price-{{ $i }}">Extra price</label>
            <input type="number" step="0.01" min="0" id="transport-price-{{ $i }}" name="transportation[{{ $i }}][price]" data-field="price" value="{{ $row['price'] ?? '' }}" class="form-control" placeholder="On request">
        </div>
        <div class="col-sm-4 col-md-2" data-extra-only @if($included) hidden @endif>
            <label class="form-label" for="transport-currency-{{ $i }}">Currency</label>
            <select id="transport-currency-{{ $i }}" name="transportation[{{ $i }}][currency]" data-field="currency" class="form-select">
                @foreach(['USD', 'SAR', 'PKR'] as $currency)
                    <option value="{{ $currency }}" @selected(($row['currency'] ?? 'USD') === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3" data-extra-only @if($included) hidden @endif>
            <label class="form-label" for="transport-basis-{{ $i }}">Price is for</label>
            <input type="text" id="transport-basis-{{ $i }}" name="transportation[{{ $i }}][price_basis]" data-field="price_basis" value="{{ $row['price_basis'] ?? '' }}" class="form-control" placeholder="per person, round trip">
        </div>
        <div class="col">
            <label class="form-label" for="transport-notes-{{ $i }}">Details shown to customers</label>
            <input type="text" id="transport-notes-{{ $i }}" name="transportation[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}" class="form-control" placeholder="Optional">
        </div>
    </div>
</div>
