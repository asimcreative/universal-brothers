@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title . ' | Universal Brothers')
@section('meta_description', $page->meta_description ?: Str::limit(strip_tags($page->body), 160))

@push('head')
    @if($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @endif
@endpush

@section('content')
    <div class="bg-primary text-white py-5">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $page->title }}</li>
                </ol>
            </nav>
            <h1>{{ $page->title }}</h1>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                @if($page->featured_image)
                    <img src="{{ Storage::url($page->featured_image) }}" alt="{{ $page->title }}" class="img-fluid rounded mb-4" loading="lazy">
                @endif
                <div class="page-body">
                    {!! $page->body !!}
                </div>
            </div>
        </div>
    </div>
@endsection
