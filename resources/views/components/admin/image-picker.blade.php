{{--
    An image chosen from the media library.

    Submits two values: {name}[path] (the stored file) and {name}[alt] (the
    description a screen reader reads out). Choose, Replace and Remove use the
    media picker (resources/js/admin/media-picker.js).

    Props
      name        base field name, e.g. sections[s_ab12][data][image]
      label       visible label
      value       ['path' => ..., 'alt' => ...]
      alt         ask for a description (false for decorative backgrounds)
      required    shows the required marker
      help
      errorKey    validation key base (dot notation)
--}}
@props([
    'name',
    'label' => 'Image',
    'value' => [],
    'alt' => true,
    'required' => false,
    'help' => null,
    'errorKey' => null,
])

@php
    $ubPath = is_array($value) ? ($value['path'] ?? null) : $value;
    $ubAlt = is_array($value) ? ($value['alt'] ?? '') : '';
    $ubId = 'img-'.\Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], '-', $name));
    $ubKey = $errorKey ?? trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $ubUrl = $ubPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($ubPath) : null;
@endphp

<div {{ $attributes->class(['media-field', 'has-image' => $ubPath]) }} data-media-field data-media-title="{{ $label }}" data-media-alt="{{ $alt ? '1' : '0' }}" role="group" aria-labelledby="{{ $ubId }}-label">
    <span class="form-label d-block" id="{{ $ubId }}-label">{{ $label }}@if($required) <span class="required-mark" aria-hidden="true">*</span>@endif</span>
    <input type="hidden" name="{{ $name }}[path]" value="{{ $ubPath }}" data-media-path>

    <div class="media-field-body">
        <div class="media-field-preview">
            <img @if($ubUrl) src="{{ $ubUrl }}" @endif alt="{{ $ubAlt }}" data-media-preview data-media-when-set @if(! $ubPath) hidden @endif>
            <span class="media-field-empty" data-media-empty @if($ubPath) hidden @endif><i class="bi bi-image" aria-hidden="true"></i>No image chosen</span>
        </div>
        <div class="media-field-controls">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-media-choose aria-describedby="{{ $ubId }}-label">
                    <i class="bi bi-images me-1" aria-hidden="true"></i><span data-media-choose-label>{{ $ubPath ? 'Replace image' : 'Choose image' }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-media-remove data-media-when-set @if(! $ubPath) hidden @endif aria-label="Remove {{ strtolower($label) }}">
                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Remove
                </button>
            </div>
            @if($alt)
                <div class="mt-2" data-media-when-set @if(! $ubPath) hidden @endif>
                    <label for="{{ $ubId }}-alt" class="form-label small mb-1">Describe the image <span class="text-muted fw-normal">(alt text)</span></label>
                    <input type="text" id="{{ $ubId }}-alt" name="{{ $name }}[alt]" value="{{ $ubAlt }}" maxlength="255" data-media-alt
                           @class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has($ubKey.'.alt')])
                           placeholder="e.g. Pilgrims performing tawaf around the Kaaba">
                    <x-admin.error :name="$ubKey.'.alt'" />
                </div>
            @else
                <input type="hidden" name="{{ $name }}[alt]" value="" data-media-alt>
            @endif
            @if($help)<div class="form-help mt-1">{{ $help }}</div>@endif
            <x-admin.error :name="$ubKey.'.path'" />
            <x-admin.error :name="$ubKey" />
        </div>
    </div>
</div>
