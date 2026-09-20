<!DOCTYPE html>
{{--
    The designer's template, served directly.

    Six attempts at rebuilding this design in our own Bootstrap/SCSS system were
    rejected, and rightly — a rebuild is a likeness, and what was asked for is
    the template itself. So this layout serves the designer's own compiled
    stylesheet (`resources/css/site.css`, with its self-hosted faces in
    `@fontsource`) and the views that extend it emit the designer's own
    markup and class names. Our data, their design, no approximation in between.

    Our own `app.scss` is deliberately NOT loaded here: it carries Bootstrap's
    reset and several hundred rules for `.btn`, `.container`, `.card` and the
    rest, every one of which would fight the template's utility classes. A page
    is on one system or the other, never both — which is why the layout choice is
    per view and pages not yet ported keep `layouts.app` untouched.

    The four `*-module__*__variable` classes on <html> are not decoration: each
    one declares the custom property (`--ff-fira`, `--ff-inter`, `--ff-playfair`,
    `--ff-hand`) that the template's type scale resolves against. Drop them and
    every heading falls back to a system face.
--}}
<html lang="en" class="no-js fira_sans_condensed_3dec0666-module__9SEJRa__variable inter_41b92583-module__Qs1B3a__variable playfair_display_cc1ae991-module__z-wRHq__variable caveat_220c7240-module__f7_udG__variable">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.className = document.documentElement.className.replace('no-js', 'js');</script>

    @php
        $ubTitle = \Illuminate\Support\Facades\View::yieldContent('title', 'Universal Brothers — Hajj, Umrah & Tourism');
        $ubDescription = \Illuminate\Support\Facades\View::yieldContent('meta_description', 'Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator based in Karachi, Pakistan. 20+ years of trusted service.');
        $ubCanonical = trim(\Illuminate\Support\Facades\View::yieldContent('canonical'));
        $ubRobots = trim(\Illuminate\Support\Facades\View::yieldContent('robots'));
        $ubOgTitle = trim(\Illuminate\Support\Facades\View::yieldContent('og_title')) ?: trim($ubTitle);
        $ubOgDescription = trim(\Illuminate\Support\Facades\View::yieldContent('og_description')) ?: trim($ubDescription);
        $ubOgImage = trim(\Illuminate\Support\Facades\View::yieldContent('og_image'));
    @endphp
    <title>{!! $ubTitle !!}</title>
    <meta name="description" content="{!! $ubDescription !!}">
    <link rel="canonical" href="{{ $ubCanonical ?: url()->current() }}">
    @if($ubRobots)<meta name="robots" content="{!! $ubRobots !!}">@endif
    <meta name="theme-color" content="#0b202a">
    <meta property="og:title" content="{!! $ubOgTitle !!}">
    <meta property="og:description" content="{!! $ubOgDescription !!}">
    @if($ubOgImage)<meta property="og:image" content="{!! $ubOgImage !!}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Universal Brothers (Pvt) Ltd">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 44 44'%3E%3Crect width='44' height='44' fill='%230b202a'/%3E%3Cg fill='none' stroke='%23b9865a' stroke-width='2'%3E%3Crect x='11' y='11' width='22' height='22'/%3E%3Crect x='11' y='11' width='22' height='22' transform='rotate(45 22 22)'/%3E%3C/g%3E%3C/svg%3E">

    {{-- The template animates with Framer Motion, which we do not ship. These
         are the same reveal hooks the rest of the site uses, declared here
         because they live in `app.scss`, which this layout does not load.
         Gated on `.js` so a failed bundle shows content rather than a blank
         page, and disabled outright under reduced motion. --}}
    <style>
        .js .reveal-on-scroll { opacity: 0; transform: translateY(28px); transition: opacity .55s cubic-bezier(.22,1,.36,1), transform .55s cubic-bezier(.22,1,.36,1); }
        .js .reveal-on-scroll.is-visible { opacity: 1; transform: none; }
        .js .reveal-on-scroll.reveal-delay-1 { transition-delay: .08s }
        .js .reveal-on-scroll.reveal-delay-2 { transition-delay: .16s }
        .js .reveal-on-scroll.reveal-delay-3 { transition-delay: .24s }
        .js .reveal-on-scroll.reveal-delay-4 { transition-delay: .32s }
        .js .reveal-on-scroll.reveal-delay-5 { transition-delay: .40s }
        .js .reveal-on-scroll.reveal-delay-6 { transition-delay: .48s }
        @media (prefers-reduced-motion: reduce) {
            .js .reveal-on-scroll { opacity: 1 !important; transform: none !important; transition: none !important; }
        }
    </style>

    {{-- The assistant's own styles. They live in `app.scss`, which this layout
         does not load, so without this it renders unstyled — an invisible,
         unpositioned element over the page that swallows clicks. --}}
    {{-- Our own stylesheet. Tailwind runs in this project's Vite build against
         the theme in `resources/css/site.css` and scans these Blade files, so
         what ships is generated here and can be changed here. --}}
    @vite(['resources/css/motion.css', 'resources/css/site.css', 'resources/scss/assistant-widget.scss', 'resources/js/app.js'])

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "TravelAgency",
        "name": "Universal Brothers (Pvt) Ltd",
        "alternateName": "Crown Packages",
        "url": "{{ url('/') }}",
        @if($primaryOffice?->phone_primary)"telephone": "{{ $primaryOffice->phone_primary }}",@endif
        @if($primaryOffice?->email)"email": "{{ $primaryOffice->email }}",@endif
        "address": {
            "@@type": "PostalAddress",
            "addressLocality": "Karachi",
            "addressCountry": "PK"
        }
    }
    </script>

    @stack('head')
</head>
<body class="bg-background text-ivory">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-ivory focus:px-4 focus:py-2 focus:text-brand-900">Skip to main content</a>

    @include('layouts.partials.template-header')

    <main id="main-content" tabindex="-1">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    @include('layouts.partials.template-footer')

    @include('layouts.partials.currency-choice')

    @include('layouts.partials.back-to-top')

    <x-ai-assistant />

    @stack('scripts')
</body>
</html>
