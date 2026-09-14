{{-- One hotel option (Package A / B / C). $i, $row --}}
@php $code = strtoupper((string) ($row['code'] ?? '')); @endphp
<div class="b-row b-row-option option-tone-{{ in_array($code, ['A', 'B', 'C', 'D', 'E'], true) ? $code : 'shared' }}" data-row="variants" data-index="{{ $i }}" data-option-uid="opt-{{ $i }}">
    <div>
        <label class="form-label" for="variant-code-{{ $i }}">Letter</label>
        <input type="text" id="variant-code-{{ $i }}" name="variants[{{ $i }}][code]" data-field="code" value="{{ $code }}" maxlength="3"
               class="form-control text-uppercase fw-bold text-center @error("variants.$i.code") is-invalid @enderror" placeholder="A" aria-label="Option letter" required>
    </div>
    <div>
        <label class="form-label" for="variant-label-{{ $i }}">Option name</label>
        <input type="text" id="variant-label-{{ $i }}" name="variants[{{ $i }}][label]" data-field="label" value="{{ $row['label'] ?? '' }}"
               class="form-control" placeholder="e.g. Fairmont Clock Tower" aria-label="Option name">
    </div>
    <x-admin.row-actions label="option" :move="false" />
</div>
