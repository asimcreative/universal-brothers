@extends('layouts.app')

@section('title', 'FAQs | Universal Brothers')
@section('meta_description', 'Frequently asked questions about Hajj, Umrah and Tourism packages from Universal Brothers (Pvt) Ltd.')

@php($allFaqs = $faqsByCategory->flatten(1))
@if($allFaqs->isNotEmpty())
    @push('head')
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "FAQPage",
            "mainEntity": [
                @foreach($allFaqs as $faq)
                {
                    "@@type": "Question",
                    "name": {!! json_encode($faq->question) !!},
                    "acceptedAnswer": {
                        "@@type": "Answer",
                        "text": {!! json_encode($faq->answer) !!}
                    }
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
        </script>
    @endpush
@endif

@section('content')
    <div class="hero-slide" style="min-height: 38vh;">
        <div class="hero-slide-bg" style="background-image: linear-gradient(135deg, #101B45, #0A1230)"></div>
        <div class="container hero-content py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">FAQs</li>
                </ol>
            </nav>
            <span class="hero-eyebrow">Answers &amp; Guidance</span>
            <h1>Frequently Asked Questions</h1>
        </div>
    </div>

    <div class="container section-tight">
        @if($faqsByCategory->isEmpty())
            <x-empty-state icon="bi-question-circle">No FAQs have been published yet. Please <a href="{{ route('contact') }}">contact us</a> with any questions.</x-empty-state>
        @else
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @foreach($faqsByCategory as $category => $faqs)
                        <span class="section-eyebrow">{{ $category }}</span>
                        <h2 id="{{ $category }}" class="h4 text-capitalize mb-3">{{ $category }}</h2>
                        <div class="accordion faq-accordion mb-5" id="faqAccordion{{ Str::studly($category) }}">
                            @foreach($faqs as $i => $faq)
                                @php($id = $category.'-'.$faq->id)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $id }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-{{ $id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="faq-{{ $id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion{{ Str::studly($category) }}">
                                        <div class="accordion-body text-secondary">
                                            {{ $faq->answer }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
