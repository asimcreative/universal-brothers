@extends('layouts.app')

{{--
    A CMS page built from sections in the admin's page builder.

    $sections comes from PageRenderer::prepare(): only visible sections, each
    with its view and the live data it shows. Pages built before the builder
    existed still use page.blade.php until they are published from the builder.
--}}

@section('title', $seo['title'])
@section('meta_description', $seo['description'])
@include('partials.page-seo', ['seo' => $seo])

@if($preview ?? false)
    @push('before_header')
        <div class="admin-preview-banner" role="status">
            <span><i class="bi bi-eye" aria-hidden="true"></i> <strong>Preview</strong> — this is how the page will look.
                {{ $page->isLive() ? 'The published version is on the website; these changes are not yet.' : 'It is not published, so visitors cannot see it.' }}</span>
            <a href="{{ $previewEditUrl }}" target="_top">Back to editing</a>
        </div>
    @endpush
@endif

@section('content')
    @unless($opensWithBanner)
        <x-page-hero
            :photo="$page->featured_image ? null : ($page->template === 'about' ? 'haram-dusk' : 'haram-panorama')"
            :eyebrow="$page->template === 'about' ? 'Our Story' : null"
            :title="$page->title"
            :breadcrumbs="['Home' => route('home'), $page->title => null]" />
    @endunless

    @forelse($sections as $index => $section)
        @include($section['view'], [
            'data' => $section['data'],
            'extra' => $section['extra'],
            'sectionId' => 'section-'.$section['id'],
            'headingTag' => $index === 0 && $opensWithBanner ? 'h1' : 'h2',
            'pageTitle' => $page->title,
            'pageBanner' => $page->featured_image,
            'defaultPhoto' => $page->template === 'about' ? 'haram-dusk' : 'haram-panorama',
        ])
    @empty
        <div class="container section-tight">
            <x-empty-state icon="bi-file-earmark-text">This page has no content yet.</x-empty-state>
        </div>
    @endforelse
@endsection
