<!DOCTYPE html>
{{--
    `no-js` is swapped to `js` by the inline script below, before first paint.

    Every scroll-revealed element starts at `opacity: 0` and is only made
    visible when app.js's IntersectionObserver adds `.is-visible`. If that
    bundle fails to load, is blocked, or simply errors, the entire page body
    used to render permanently invisible. Gating the hidden state on `.js`
    means the no-JS/failed-JS path now renders fully visible content instead
    of a blank page.
--}}
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    {{-- Read by the AI assistant's fetch() calls. The public site is otherwise
         entirely form-based, so this is the first thing here that posts from
         JavaScript and needs the token out of band. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.className = document.documentElement.className.replace('no-js', 'js');</script>
    <title>@yield('title', 'Universal Brothers — Hajj, Umrah & Tourism')</title>
    <meta name="description" content="@yield('meta_description', "Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator based in Karachi, Pakistan. 20+ years of trusted service.")">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#101b45">
    <meta property="og:title" content="@yield('title', 'Universal Brothers')">
    <meta property="og:description" content="@yield('meta_description', 'Hajj, Umrah & Tourism packages from Universal Brothers.')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Universal Brothers (Pvt) Ltd">
    {{-- The brand's khatim monogram as an inline SVG favicon — the previous
         `data:,` was a deliberately blank icon, which renders as the browser's
         generic "no favicon" placeholder in every tab and bookmark. --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 44 44'%3E%3Crect width='44' height='44' fill='%23101b45'/%3E%3Cg fill='none' stroke='%23c9a227' stroke-width='2'%3E%3Crect x='11' y='11' width='22' height='22'/%3E%3Crect x='11' y='11' width='22' height='22' transform='rotate(45 22 22)'/%3E%3C/g%3E%3C/svg%3E">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

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
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @stack('before_header')

    @include('layouts.partials.header')

    <main id="main-content" tabindex="-1">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    <x-lightbox-modal />

    {{-- Renders nothing at all unless the assistant is enabled, has public
         access switched on, and has an API key — see AiConfig::publiclyAvailable(). --}}
    <x-ai-assistant />

    @stack('scripts')
</body>
</html>
