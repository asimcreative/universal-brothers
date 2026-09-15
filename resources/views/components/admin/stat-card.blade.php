{{-- A figure with an icon, as on the dashboard. Props: label, value, icon, tone (success|warning|info|gold|danger), hint, href --}}
@props(['label', 'value', 'icon' => 'bi-bar-chart', 'tone' => null, 'hint' => null, 'href' => null])

@php($ubTag = $href ? 'a' : 'div')
<{{ $ubTag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['admin-stat-card', 'tone-'.$tone => $tone]) }}>
    <span class="admin-stat-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
    <div class="min-w-0">
        <div class="admin-stat-value">{{ $value }}</div>
        <div class="admin-stat-label">{{ $label }}</div>
        @if($hint)<div class="admin-stat-hint">{{ $hint }}</div>@endif
    </div>
</{{ $ubTag }}>
