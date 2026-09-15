{{-- Nothing to list yet. Props: icon, title. Slot: explanation and an optional action. --}}
@props(['icon' => 'bi-inbox', 'title' => null])

<div {{ $attributes->class(['admin-empty-state']) }}>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @if($title)<strong class="d-block mb-1">{{ $title }}</strong>@endif
    <div>{{ $slot }}</div>
</div>
