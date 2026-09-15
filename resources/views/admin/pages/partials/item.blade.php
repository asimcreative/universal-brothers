{{-- One entry in a section's repeating list (a slide, a card, a photo…). $field, $item, $index, $name, $errorKey, $idPrefix --}}
@php
    $label = $field['item_label'] ?? 'Item';
    $number = is_int($index) ? $index + 1 : '';
@endphp
<div class="pb-item" data-item>
    <div class="pb-item-head">
        <strong class="pb-item-title"><span data-item-label>{{ $label }}</span> <span data-item-number>{{ $number }}</span></strong>
        <div class="pb-item-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-item-action="up" aria-label="Move {{ strtolower($label) }} {{ $number }} up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary admin-icon-btn" data-item-action="down" aria-label="Move {{ strtolower($label) }} {{ $number }} down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger admin-icon-btn" data-item-action="remove" aria-label="Remove {{ strtolower($label) }} {{ $number }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </div>
    </div>
    @include('admin.pages.partials.fields', [
        'fields' => $field['fields'],
        'values' => is_array($item) ? $item : [],
        'name' => $name.'['.$index.']',
        'errorKey' => $errorKey.'.'.$index,
        'idPrefix' => $idPrefix.'-'.$index,
    ])
</div>
