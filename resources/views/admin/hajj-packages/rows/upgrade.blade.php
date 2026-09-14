{{-- One additional option (upgrade / supplement). $i, $row --}}
@php $included = ! empty($row['is_included']); @endphp
<div class="b-row b-row-upgrade" data-row="upgrades" data-index="{{ $i }}">
    <input type="hidden" name="upgrades[{{ $i }}][upgrade_option_id]" data-field="upgrade_option_id" value="{{ $row['upgrade_option_id'] ?? '' }}">
    <div>
        <label class="form-label" for="upgrade-name-{{ $i }}">Option name</label>
        <input type="text" id="upgrade-name-{{ $i }}" name="upgrades[{{ $i }}][name]" data-field="name" value="{{ $row['name'] ?? '' }}" class="form-control" placeholder="e.g. Kaaba View Supplement" aria-label="Option name">
    </div>
    <div>
        <label class="form-label d-block" for="upgrade-included-{{ $i }}">Included</label>
        <input type="hidden" name="upgrades[{{ $i }}][is_included]" value="0">
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" id="upgrade-included-{{ $i }}" name="upgrades[{{ $i }}][is_included]" data-field="is_included" data-extra-toggle value="1" @checked($included)>
            <label class="form-check-label small" for="upgrade-included-{{ $i }}">In price</label>
        </div>
    </div>
    <div data-extra-only @if($included) hidden @endif>
        <label class="form-label" for="upgrade-price-{{ $i }}">Price</label>
        <input type="number" step="0.01" min="0" id="upgrade-price-{{ $i }}" name="upgrades[{{ $i }}][price]" data-field="price" value="{{ $row['price'] ?? '' }}" class="form-control" placeholder="On request" aria-label="Price">
    </div>
    <div data-extra-only @if($included) hidden @endif>
        <label class="form-label" for="upgrade-currency-{{ $i }}">Currency</label>
        <select id="upgrade-currency-{{ $i }}" name="upgrades[{{ $i }}][currency]" data-field="currency" class="form-select" aria-label="Currency">
            @foreach(['USD', 'SAR', 'PKR'] as $currency)
                <option value="{{ $currency }}" @selected(($row['currency'] ?? 'USD') === $currency)>{{ $currency }}</option>
            @endforeach
        </select>
    </div>
    <div data-extra-only @if($included) hidden @endif>
        <label class="form-label" for="upgrade-basis-{{ $i }}">Price is for</label>
        <input type="text" id="upgrade-basis-{{ $i }}" name="upgrades[{{ $i }}][price_basis]" data-field="price_basis" value="{{ $row['price_basis'] ?? '' }}" class="form-control" placeholder="per person" aria-label="Price is for">
    </div>
    <div class="row-actions-wrap">
        @if(filled($row['upgrade_option_id'] ?? null))<span class="lib-badge mb-1" data-lib-badge><i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved content</span>@endif
        <x-admin.row-actions label="additional option" />
    </div>
    <div class="b-row-wide row g-2">
        <div class="col-md-6">
            <label class="form-label" for="upgrade-description-{{ $i }}">Description</label>
            <input type="text" id="upgrade-description-{{ $i }}" name="upgrades[{{ $i }}][description]" data-field="description" value="{{ $row['description'] ?? '' }}" class="form-control" placeholder="Optional">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="upgrade-notes-{{ $i }}">Conditions</label>
            <input type="text" id="upgrade-notes-{{ $i }}" name="upgrades[{{ $i }}][notes]" data-field="notes" value="{{ $row['notes'] ?? '' }}" class="form-control" placeholder="e.g. Subject to availability">
        </div>
    </div>
</div>
