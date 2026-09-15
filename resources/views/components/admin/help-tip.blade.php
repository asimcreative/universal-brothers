{{-- A small "?" that explains a field. Opens on click, tap or keyboard, never only on hover. Slot: the explanation. --}}
@props(['label' => 'More information'])

@php($ubId = 'tip-'.\Illuminate\Support\Str::random(8))
<span class="help-tip">
    <button type="button" class="help-tip-button" aria-expanded="false" aria-controls="{{ $ubId }}" aria-label="{{ $label }}" data-help-tip>
        <i class="bi bi-question-circle" aria-hidden="true"></i>
    </button>
    <span class="help-tip-text" id="{{ $ubId }}" role="note" hidden>{{ $slot }}</span>
</span>
