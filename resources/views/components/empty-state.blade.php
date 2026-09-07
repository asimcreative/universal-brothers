@props(['icon' => 'bi-info-circle'])

<div class="empty-state text-center">
    <div class="empty-state-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
    <p class="mb-0">{{ $slot }}</p>
</div>
