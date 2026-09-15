{{--
    A status pill with one colour per meaning, used the same way on every screen.
    Props: status (a key below, or any other word), label (override the text)
--}}
@props(['status', 'label' => null])

@php
    $ubMap = [
        'published' => ['success', 'Published', 'bi-check-circle'],
        'live' => ['success', 'Live', 'bi-check-circle'],
        'enabled' => ['success', 'On', 'bi-check-circle'],
        'active' => ['success', 'Active', 'bi-check-circle'],
        'ok' => ['success', 'Working', 'bi-check-circle'],
        'draft' => ['secondary', 'Draft', 'bi-pencil'],
        'disabled' => ['secondary', 'Off', 'bi-slash-circle'],
        'hidden' => ['secondary', 'Hidden', 'bi-eye-slash'],
        'scheduled' => ['info', 'Scheduled', 'bi-clock'],
        'info' => ['info', '', null],
        'archived' => ['warning', 'Archived', 'bi-archive'],
        'changes' => ['warning', 'Unpublished changes', 'bi-pencil-square'],
        'warning' => ['warning', '', 'bi-exclamation-circle'],
        'failed' => ['danger', 'Failed', 'bi-exclamation-triangle'],
        'missing' => ['danger', 'Not set', 'bi-exclamation-triangle'],
    ];
    [$ubTone, $ubLabel, $ubIcon] = $ubMap[$status] ?? ['info', ucfirst((string) $status), null];
@endphp

<span {{ $attributes->class(['status-pill', 'status-pill-'.$ubTone]) }}>@if($ubIcon)<i class="bi {{ $ubIcon }}" aria-hidden="true"></i>@endif{{ $label ?? $ubLabel }}</span>
