@props(['name'])
@php $message = $errors->first($name); @endphp
@if($message)
    {{-- The id lets the field point at its message (aria-describedby), so a
         screen reader reads the error with the field — see initFieldErrors. --}}
    <div class="invalid-feedback d-block" id="error-{{ \Illuminate\Support\Str::slug(str_replace('.', '-', $name)) }}" data-error-for="{{ $name }}">{{ $message }}</div>
@endif
