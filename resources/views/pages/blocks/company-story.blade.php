{{-- Story with company details — the About page's opening, built from sections. $data, $extra['facts'], $sectionId --}}
<section id="{{ $sectionId }}" class="section pb-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7 reveal-on-scroll">
                @if(filled($data['eyebrow'] ?? null))
                    <span class="section-eyebrow">{{ $data['eyebrow'] }}</span>
                @endif
                @if(filled($data['heading'] ?? null))
                    <h2>{{ $data['heading'] }}</h2>
                @endif
                <div class="page-body rich-text">
                    {!! \App\Support\Content\RichText::render($data['content'] ?? '', 'full') !!}
                </div>
            </div>

            <div class="col-lg-5 reveal-on-scroll reveal-delay-2">
                <div class="about-aside">
                    <div class="photo-figure photo-media photo-media--panel about-aside-visual">
                        <x-photo
                            :image="$data['image']['path'] ?? null"
                            key="nabawi-aerial"
                            :alt="$data['image']['alt'] ?? null"
                            sizes="(min-width: 992px) 38vw, 92vw" />
                        @if(filled($data['caption_label'] ?? null) || filled($data['caption_title'] ?? null))
                            <div class="photo-caption">
                                @if(filled($data['caption_label'] ?? null))<span class="photo-caption-eyebrow">{{ $data['caption_label'] }}</span>@endif
                                @if(filled($data['caption_title'] ?? null))<p class="photo-caption-title">{{ $data['caption_title'] }}</p>@endif
                            </div>
                        @endif
                    </div>

                    @if(! empty($data['show_facts']) && ! empty($extra['facts']))
                        <dl class="about-facts">
                            @foreach($extra['facts'] as $ubLabel => $ubValue)
                                <div>
                                    <dt>{{ $ubLabel }}</dt>
                                    <dd>{{ $ubValue }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
