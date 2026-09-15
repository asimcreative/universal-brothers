{{-- Latest news. $data, $extra['news'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'white') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 justify-content-center">
            @foreach($extra['news'] as $article)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('news.show', $article->slug) }}" class="news-card reveal-on-scroll">
                        <div class="news-card-media">
                            <x-visual :image="$article->cover_image" :alt="$article->title" :seed="$article->slug" surface="card"
                                      :caption="optional($article->published_at)->format('M Y')" mark="" class="news-card-img" />
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
        @if(! empty($data['show_all']))
            <div class="text-center mt-5">
                <a href="{{ route('media') }}" class="btn btn-outline-primary">More news</a>
            </div>
        @endif
    </div>
</section>
