@extends('layouts.template')

@section('title', 'Universal Brothers — Hajj, Umrah & Tourism')
@section('meta_description', 'Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator in Karachi, Pakistan. Real Hajj 2027 packages, ' . $stats['years'] . ' years of trusted service, ' . $stats['pilgrims'] . ' pilgrims served.')

@section('content')
    {{--
        The homepage on the designer's template.

        Each section is the designer's own markup in its own partial, with our
        CMS data substituted. The order is the template's order. `@includeIf` is
        deliberate: a section that has not been ported yet simply does not
        render, so the page is never half-broken while the port is in progress.
    --}}

    @includeIf('template.sections.hero')

    <div id="ub-after-hero"></div>

    @includeIf('template.sections.services')
    @includeIf('template.sections.finder')
    @includeIf('template.sections.marquee')
    @includeIf('template.sections.about')
    @includeIf('template.sections.packages')
    @includeIf('template.sections.company-video')
    @includeIf('template.sections.impact')
    @includeIf('template.sections.why-us')
    @includeIf('template.sections.testimonials')
    @includeIf('template.sections.recognition')
    @includeIf('template.sections.cta')
@endsection
