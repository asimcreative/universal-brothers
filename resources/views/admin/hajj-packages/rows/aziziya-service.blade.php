{{-- One Aziziya facility or service. $i, $row --}}
@php $included = array_key_exists('is_included', $row) ? ! empty($row['is_included']) : true; @endphp
<div class="b-row b-row-az-service" data-row="aziziya_services" data-index="{{ $i }}">
    <div>
        <label class="form-label" for="az-service-name-{{ $i }}">Facility or service</label>
        <input type="text" id="az-service-name-{{ $i }}" name="aziziya_services[{{ $i }}][name]" data-field="name" value="{{ $row['name'] ?? '' }}" class="form-control" placeholder="e.g. Shuttle service to the Haram" aria-label="Service name">
    </div>
    <div>
        <label class="form-label d-block" for="az-service-included-{{ $i }}">Included</label>
        <input type="hidden" name="aziziya_services[{{ $i }}][is_included]" value="0">
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" id="az-service-included-{{ $i }}" name="aziziya_services[{{ $i }}][is_included]" data-field="is_included" data-extra-toggle value="1" @checked($included)>
            <label class="form-check-label small" for="az-service-included-{{ $i }}">In price</label>
        </div>
    </div>
    <div data-extra-only @if($included) hidden @endif>
        <label class="form-label" for="az-service-price-{{ $i }}">Price</label>
        <input type="number" step="0.01" min="0" id="az-service-price-{{ $i }}" name="aziziya_services[{{ $i }}][price]" data-field="price" value="{{ $row['price'] ?? '' }}" class="form-control" aria-label="Service price">
    </div>
    <div data-extra-only @if($included) hidden @endif>
        <label class="form-label" for="az-service-currency-{{ $i }}">Currency</label>
        <select id="az-service-currency-{{ $i }}" name="aziziya_services[{{ $i }}][currency]" data-field="currency" class="form-select">
            @foreach(['USD', 'SAR', 'PKR'] as $currency)
                <option value="{{ $currency }}" @selected(($row['currency'] ?? 'USD') === $currency)>{{ $currency }}</option>
            @endforeach
        </select>
    </div>
    <x-admin.row-actions label="service" />
    <input type="hidden" name="aziziya_services[{{ $i }}][description]" data-field="description" value="{{ $row['description'] ?? '' }}">
    <input type="hidden" name="aziziya_services[{{ $i }}][price_basis]" data-field="price_basis" value="{{ $row['price_basis'] ?? '' }}">
    <input type="hidden" name="aziziya_services[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}">
</div>
