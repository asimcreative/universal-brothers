@extends('layouts.app')

{{-- An administrator's look at one saved section in the site's own design. --}}

@section('title', 'Preview: '.$block->name)
@section('robots', 'noindex, nofollow')

@push('before_header')
    <div class="admin-preview-banner" role="status">
        <span><i class="bi bi-eye" aria-hidden="true"></i> <strong>Preview</strong> — the saved section “{{ $block->name }}” as it looks on a page.</span>
        <a href="{{ route('admin.content-blocks.edit', $block) }}">Back to editing</a>
    </div>
@endpush

@section('content')
    @forelse($sections as $section)
        @include($section['view'], [
            'data' => $section['data'],
            'extra' => $section['extra'],
            'sectionId' => 'section-preview',
            'headingTag' => 'h1',
            'pageTitle' => $block->name,
            'pageBanner' => null,
            'defaultPhoto' => 'haram-panorama',
        ])
    @empty
        <div class="container section">
            <x-empty-state icon="bi-eye-slash">This section has nothing to show yet — for example, there are no published items of this kind. It will appear on pages once there is.</x-empty-state>
        </div>
    @endforelse
@endsection
