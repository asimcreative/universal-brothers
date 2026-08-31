<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Universal Brothers — Hajj, Umrah & Tourism')</title>
    <meta name="description" content="@yield('meta_description', "Universal Brothers (Pvt) Ltd — IATA-registered Hajj, Umrah and Tourism operator based in Karachi, Pakistan. 20+ years of trusted service.")">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Universal Brothers')">
    <meta property="og:description" content="@yield('meta_description', 'Hajj, Umrah & Tourism packages from Universal Brothers.')">
    <meta property="og:type" content="website">
    <link rel="icon" href="data:,">

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
    @include('layouts.partials.header')

    <main>
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    <x-lightbox-modal />

    @stack('scripts')
</body>
</html>
