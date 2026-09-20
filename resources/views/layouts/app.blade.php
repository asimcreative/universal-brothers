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
    {{-- A page may name its own canonical address, sharing text and image, or
         ask search engines not to list it (CMS pages set these from the admin).

         Each value is worked out here instead of inside the attribute. Blade
         does not compile a directive written straight after another one, so
         `@else@yield(...)` reached the page as literal text and every shared
         link showed the directive instead of the page's name.

         Section values are already escaped — Blade escapes the inline
         `@section('title', $value)` form — so they are printed with {!! !!},
         exactly as @yield would print them. Escaping again would turn "&"
         into "&amp;amp;". --}}
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
    <meta name="theme-color" content="#101b45">
    <meta property="og:title" content="{!! $ubOgTitle !!}">
    <meta property="og:description" content="{!! $ubOgDescription !!}">
    @if($ubOgImage)<meta property="og:image" content="{!! $ubOgImage !!}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Universal Brothers (Pvt) Ltd">
    {{-- The brand's khatim monogram as an inline SVG favicon — the previous
         `data:,` was a deliberately blank icon, which renders as the browser's
         generic "no favicon" placeholder in every tab and bookmark. --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 44 44'%3E%3Crect width='44' height='44' fill='%23101b45'/%3E%3Cg fill='none' stroke='%23c9a227' stroke-width='2'%3E%3Crect x='11' y='11' width='22' height='22'/%3E%3Crect x='11' y='11' width='22' height='22' transform='rotate(45 22 22)'/%3E%3C/g%3E%3C/svg%3E">

    @vite(['resources/css/motion.css', 'resources/scss/app.scss', 'resources/css/header.css', 'resources/js/app.js'])

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

    {{-- The same header the homepage uses. `ubOverlay` is false because
         these pages have no photographic hero behind the bar for it to sit
         over, so here it is a solid band in the normal flow. --}}
    @include('layouts.partials.template-header', ['ubOverlay' => false])

    <main id="main-content" tabindex="-1">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    {{-- The same footer the homepage uses, for the same reason as the header. --}}
    @include('layouts.partials.template-footer')

    <x-lightbox-modal />

    {{-- Renders nothing at all unless the assistant is enabled, has public
         access switched on, and has an API key — see AiConfig::publiclyAvailable(). --}}
    <x-ai-assistant />

    @stack('scripts')
</body>
</html>
