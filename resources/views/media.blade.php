@extends('layouts.app')

@section('title', 'Media | Universal Brothers')
@section('meta_description', 'News, videos and photo gallery from Universal Brothers (Pvt) Ltd — Hajj, Umrah and Tourism operator.')

@section('content')
    <x-page-hero
        photo="mina-tents"
        eyebrow="News · Gallery · Videos"
        title="Media"
        lead="Press coverage, event photography and pilgrim stories from Universal Brothers."
        :breadcrumbs="['Home' => route('home'), 'Media' => null]" />

    <div class="container section-tight">
        <ul class="nav nav-pills mb-4" id="mediaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="news-tab" data-bs-toggle="tab" data-bs-target="#news-pane" type="button" role="tab" aria-controls="news-pane" aria-selected="true">News</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery-pane" type="button" role="tab" aria-controls="gallery-pane" aria-selected="false">Gallery</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos-pane" type="button" role="tab" aria-controls="videos-pane" aria-selected="false">Videos</button>
            </li>
        </ul>

        <div class="tab-content" id="mediaTabsContent">
            <div class="tab-pane fade show active" id="news-pane" role="tabpanel" aria-labelledby="news-tab" tabindex="0">
                @if($news->isEmpty())
                    <x-empty-state icon="bi-newspaper" photo="mina-tents">No news articles have been published yet.</x-empty-state>
                @else
                    <div class="row g-4">
                        @foreach($news as $article)
                            <div class="col-md-4">
                                {{-- The news card had NO image fallback at all: an
                                     article without a cover simply rendered a
                                     headline floating on white with no media
                                     area, so a mixed grid went ragged. --}}
                                <a href="{{ route('news.show', $article->slug) }}" class="news-card reveal-on-scroll">
                                    <div class="news-card-media">
                                        <x-visual
                                            :image="$article->cover_image"
                                            :alt="$article->title"
                                            :seed="$article->slug"
                                            surface="card"
                                            :caption="optional($article->published_at)->format('M Y')"
                                            mark=""
                                            class="news-card-img" />
                                    </div>
                                    <div class="news-card-body">
                                        <p class="news-card-date">{{ optional($article->published_at)->format('d M Y') }}</p>
                                        <h3 class="news-card-title">{{ $article->title }}</h3>
                                        @if($article->excerpt)
                                            <p class="news-card-excerpt">{{ Str::limit($article->excerpt, 100) }}</p>
                                        @endif
                                        <span class="news-card-cta">Read More<i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="gallery-pane" role="tabpanel" aria-labelledby="gallery-tab" tabindex="0">
                @if($gallery->isEmpty())
                    <x-empty-state icon="bi-images">No gallery photos have been published yet.</x-empty-state>
                @else
                    <div class="row g-3">
                        @foreach($gallery as $item)
                            <div class="col-md-4 col-6">
                                <a href="{{ Storage::url($item->file_path) }}" class="gallery-item reveal-on-scroll d-block" data-lightbox-trigger data-lightbox-src="{{ Storage::url($item->file_path) }}" data-lightbox-caption="{{ $item->title ?? 'Universal Brothers gallery photo' }}">
                                    <img src="{{ Storage::url($item->file_path) }}" alt="{{ $item->title ?? 'Universal Brothers gallery photo' }}" loading="lazy">
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="tab-pane fade" id="videos-pane" role="tabpanel" aria-labelledby="videos-tab" tabindex="0">
                @if($videos->isEmpty())
                    <x-empty-state icon="bi-play-btn">No videos have been published yet.</x-empty-state>
                @else
                    <div class="row g-4">
                        @foreach($videos as $item)
                            @if($item->video_url)
                                <div class="col-md-6">
                                    <div class="ratio ratio-16x9 reveal-on-scroll">
                                        <iframe src="{{ $item->video_url }}" title="{{ $item->title ?? 'Universal Brothers video' }}" allowfullscreen></iframe>
                                    </div>
                                    @if($item->title)<p class="small text-muted mt-2">{{ $item->title }}</p>@endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-page-cta />
@endsection
