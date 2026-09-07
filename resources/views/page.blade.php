@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title . ' | Universal Brothers')
@section('meta_description', $page->meta_description ?: Str::limit(strip_tags($page->body ?? ''), 160))

@push('head')
    @if($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @endif
@endpush

@section('content')
    <x-page-hero
        :eyebrow="$page->template === 'about' ? 'Our Story' : null"
        :title="$page->title"
        :breadcrumbs="['Home' => route('home'), $page->title => null]" />

    @if($page->template === 'about')
        {{-- Beginning.
             Two-column editorial rather than one narrow centred text column:
             `.page-body` caps prose at 46rem, so inside the old `col-lg-9` the
             right-hand third of the page was left permanently blank. The story
             now sits beside a composed visual and a credentials panel built
             from the same CMS settings the rest of the site reads. --}}
        <section class="section">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-7 reveal-on-scroll">
                        <span class="section-eyebrow">The Beginning</span>
                        <div class="page-body">
                            {!! $page->body !!}
                        </div>
                    </div>

                    <div class="col-lg-5 reveal-on-scroll reveal-delay-2">
                        <div class="about-aside">
                            <x-visual
                                :image="$page->featured_image"
                                :alt="$page->title"
                                seed="about-universal-brothers"
                                surface="panel"
                                caption="Serving the Guests of Allah"
                                mark=""
                                class="about-aside-visual" />

                            <dl class="about-facts">
                                <div>
                                    <dt>Parent Group</dt>
                                    <dd>{{ \App\Models\SiteSetting::get('parent_group', "Maxim's Group") }}</dd>
                                </div>
                                <div>
                                    <dt>Operating Brand</dt>
                                    <dd>{{ \App\Models\SiteSetting::get('brand_name', 'Crown Packages') }}</dd>
                                </div>
                                <div>
                                    <dt>Hajj Licence No.</dt>
                                    <dd>{{ \App\Models\SiteSetting::get('government_license_no', '2014') }}</dd>
                                </div>
                                <div>
                                    <dt>Hajj Registration No.</dt>
                                    <dd>{{ \App\Models\SiteSetting::get('hajj_registration_no', '4143') }}</dd>
                                </div>
                                @if(\App\Models\SiteSetting::get('mina_camp_location'))
                                    <div>
                                        <dt>Mina Camp</dt>
                                        <dd>{{ \App\Models\SiteSetting::get('mina_camp_location') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Experiences --}}
        <section class="section bg-light">
            <div class="container">
                <div class="text-center mb-5 reveal-on-scroll">
                    <span class="section-eyebrow d-flex justify-content-center">By the Numbers</span>
                    <h2>Our Experience</h2>
                </div>
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <x-stat-number :display="$stats['years']" />
                            <div class="small text-uppercase fw-semibold">Years of Experience</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <x-stat-number :display="$stats['pilgrims']" />
                            <div class="small text-uppercase fw-semibold">Pilgrims Served</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-tile">
                            <x-stat-number :display="(string) $stats['awards_count']" />
                            <div class="small text-uppercase fw-semibold">Awards &amp; Recognitions</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Awards --}}
        @if($awards->isNotEmpty())
            <section class="section">
                <div class="container">
                    <div class="text-center mb-5 reveal-on-scroll">
                        <span class="section-eyebrow d-flex justify-content-center">Recognized for Excellence</span>
                        <h2>Awards &amp; Recognition</h2>
                    </div>
                    <div class="row g-4 mb-4">
                        @foreach($awards as $award)
                            <x-award-badge :award="$award" />
                        @endforeach
                    </div>
                    <div class="text-center">
                        <a href="{{ route('awards') }}" class="btn btn-outline-primary">View All Awards</a>
                    </div>
                </div>
            </section>
        @endif

        {{-- Affiliations --}}
        @if($affiliations->isNotEmpty())
            <section class="section bg-light">
                <div class="container">
                    <div class="text-center mb-5 reveal-on-scroll">
                        <span class="section-eyebrow d-flex justify-content-center">Trusted Institutions</span>
                        <h2>Affiliations</h2>
                    </div>
                    <div class="row g-4 justify-content-center align-items-center mb-4">
                        @foreach($affiliations as $affiliation)
                            <x-affiliation-badge :affiliation="$affiliation" />
                        @endforeach
                    </div>
                    <div class="text-center">
                        <a href="{{ route('affiliations') }}" class="btn btn-outline-primary">View All Affiliations</a>
                    </div>
                </div>
            </section>
        @endif
    @else
        <div class="container section-tight">
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
    @endif
@endsection
