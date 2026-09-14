@props(['name'])
@php $message = $errors->first($name); @endphp
@if($message)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@endif
