{{--
    A formatted-text field: the rich text editor over a plain textarea.

    The textarea is what the form submits, and what a browser without
    JavaScript shows. The server cleans whatever arrives (RichText::clean).

    Props
      name, label          field name and visible label
      id                   defaults to a slug of the name
      value                stored value (HTML or older plain text)
      profile              full | standard | basic | inline — how much formatting is offered
      max                  length limit shown in the counter
      help, placeholder, required, rows
      errorKey             validation key when it differs from the name (dot notation)
      hideLabel            keep the label for screen readers only
      inputAttributes      extra attributes for the textarea itself (e.g. data-field for scripts)
--}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'profile' => 'standard',
    'max' => null,
    'help' => null,
    'placeholder' => null,
    'required' => false,
    'rows' => 6,
    'errorKey' => null,
    'hideLabel' => false,
    'inputAttributes' => [],
])

@php
    $ubId = $id ?? 'rt-'.\Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], '-', $name));
    $ubErrorKey = $errorKey ?? trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $ubHasError = $errors->has($ubErrorKey);
    $ubValue = \App\Support\Content\RichText::forEditor($value, $profile);
    $ubAllowSource = $profile === 'full' && auth()->user()?->can('edit-source');
    $ubDescribedBy = trim(($help ? $ubId.'-help ' : '').($ubHasError ? 'error-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $ubErrorKey)) : ''));
@endphp

<div {{ $attributes->class(['rt-field']) }}>
    <label for="{{ $ubId }}" @class(['form-label', 'visually-hidden' => $hideLabel])>{{ $label }}@if($required) <span class="required-mark" aria-hidden="true">*</span>@endif</label>
    <div class="rt" data-rich-text data-profile="{{ $profile }}" data-label="{{ $label }}"
         @if($max) data-max="{{ $max }}" @endif
         @if($placeholder) data-placeholder="{{ $placeholder }}" @endif
         @if($ubAllowSource) data-allow-source="1" @endif>
        <textarea name="{{ $name }}" id="{{ $ubId }}" rows="{{ $rows }}" @class(['form-control', 'is-invalid' => $ubHasError])
                  @if($ubDescribedBy) aria-describedby="{{ $ubDescribedBy }}" @endif
                  @if($required) aria-required="true" @endif
                  @foreach($inputAttributes as $ubAttr => $ubAttrValue) {{ $ubAttr }}="{{ $ubAttrValue }}" @endforeach>{{ $ubValue }}</textarea>
    </div>
    @if($help)<div class="form-help" id="{{ $ubId }}-help">{{ $help }}</div>@endif
    <x-admin.error :name="$ubErrorKey" />
</div>
