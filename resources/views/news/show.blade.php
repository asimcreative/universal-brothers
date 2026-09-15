@extends('layouts.app')

@section('title', $article->meta_title ?: $article->title . ' | Universal Brothers')
@section('meta_description', $article->meta_description ?: Str::limit(\App\Support\Content\RichText::toPlainText($article->body), 160))

@section('content')
    <div class="bg-primary text-white py-5">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $article->title }}</li>
                </ol>
            </nav>
            <h1>{{ $article->title }}</h1>
            <p class="text-white-50 mb-0">{{ optional($article->published_at)->format('d M Y') }}</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                @if($article->cover_image)
                    <img src="{{ Storage::url($article->cover_image) }}" alt="{{ $article->title }}" class="img-fluid rounded mb-4" loading="lazy">
                @endif
                <div class="page-body">
                    {!! \App\Support\Content\RichText::render($article->body, 'full') !!}
                </div>
                <a href="{{ route('media') }}" class="btn btn-outline-primary mt-4">Back to Travel News</a>
            </div>
        </div>
    </div>
@endsection
