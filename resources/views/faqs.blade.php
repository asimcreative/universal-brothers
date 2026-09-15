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
                        "text": {!! json_encode(\App\Support\Content\RichText::toPlainText($faq->answer)) !!}
                    }
                }@if(!$loop->last),@endif
                @endforeach
            ]
        }
        </script>
    @endpush
@endif

@section('content')
    <x-page-hero
        photo="nabawi-dome"
        eyebrow="Answers & Guidance"
        title="Frequently Asked Questions"
        lead="Clear answers on Hajj and Umrah packages, payments, documentation and travel."
        :breadcrumbs="['Home' => route('home'), 'FAQs' => null]" />

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
                                        <div class="accordion-body text-secondary rich-text">
                                            {!! \App\Support\Content\RichText::render($faq->answer, 'standard') !!}
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

    <x-page-cta />
@endsection
