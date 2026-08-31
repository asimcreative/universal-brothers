@php
    $whatsapp = $primaryOffice?->whatsapp;
    $hajjCategory = $navCategories->firstWhere('slug', 'hajj');
    $umrahCategory = $navCategories->firstWhere('slug', 'umrah');
    $tourismCategory = $navCategories->firstWhere('slug', 'tourism');
    $registerNowUrl = 'https://hums.akhg.com.pk/HajiReg/HajiLead';
@endphp

<header class="site-header sticky-top">
    <div class="topbar bg-dark text-light py-1 d-none d-lg-block">
        <div class="container d-flex justify-content-between small">
            <div>
                @if($primaryOffice?->phone_primary)
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="text-light text-decoration-none me-3">
                        <i class="bi bi-telephone-fill me-1"></i>{{ $primaryOffice->phone_primary }}
                    </a>
                @endif
                @if($primaryOffice?->email)
                    <a href="mailto:{{ $primaryOffice->email }}" class="text-light text-decoration-none">
                        <i class="bi bi-envelope-fill me-1"></i>{{ $primaryOffice->email }}
                    </a>
                @endif
            </div>
            <div>
                <span class="text-warning"><i class="bi bi-patch-check-fill me-1"></i>@if(\App\Models\SiteSetting::get('iata_registered', '1'))IATA Registered · @endif Hajj License No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}</span>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary py-2 site-navbar" aria-label="Primary">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="{{ route('home') }}">Universal Brothers</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse d-none d-lg-flex">
                <ul class="navbar-nav me-auto mb-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/about-us') }}">About Us</a></li>

                    @if($hajjCategory || $umrahCategory)
                        <li class="nav-item dropdown mega-menu-parent">
                            <a class="nav-link dropdown-toggle" href="#" id="hajjUmrahMegaMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Hajj &amp; Umrah</a>
                            <div class="dropdown-menu mega-menu p-4 shadow-lg" aria-labelledby="hajjUmrahMegaMenu">
                                <div class="row g-4">
                                    @if($hajjCategory)
                                        <div class="col-md-6">
                                            <a href="{{ route('hajj-services') }}" class="mega-menu-title">UB Hajj Services</a>
                                            <ul class="list-unstyled mega-menu-links">
                                                <li><a href="{{ route('packages.category', 'hajj') }}">Hajj Packages</a></li>
                                                <li><a href="{{ route('hajj-services') }}#how-to-apply">How to Apply</a></li>
                                                <li><a href="{{ route('hajj-services') }}#hajj-process">Hajj Process</a></li>
                                                <li><a href="{{ route('hajj-services') }}#hajj-guidance">Hajj Guidance</a></li>
                                                <li><a href="{{ route('hajj-services') }}#accommodation-transport">Accommodation &amp; Transport</a></li>
                                                <li><a href="{{ route('hajj-services') }}#next-flight-date">Next Flight Date</a></li>
                                                <li><a href="{{ $registerNowUrl }}" target="_blank" rel="noopener">Register Now</a></li>
                                                <li><a href="{{ route('hajj-services') }}#faqs">FAQs</a></li>
                                            </ul>
                                        </div>
                                    @endif
                                    @if($umrahCategory)
                                        <div class="col-md-6">
                                            <a href="{{ route('umrah-services') }}" class="mega-menu-title">UB Umrah Services</a>
                                            <ul class="list-unstyled mega-menu-links">
                                                <li><a href="{{ route('packages.category', 'umrah') }}">Umrah Packages</a></li>
                                                <li><a href="{{ route('umrah-services') }}#how-to-apply">How to Apply</a></li>
                                                <li><a href="{{ route('umrah-services') }}#umrah-process">Umrah Process</a></li>
                                                <li><a href="{{ route('umrah-services') }}#umrah-guidance">Umrah Guidance</a></li>
                                                <li><a href="{{ route('umrah-services') }}#accommodation-transport">Accommodation &amp; Transport</a></li>
                                                <li><a href="{{ route('umrah-services') }}#next-flight-date">Next Flight Date</a></li>
                                                <li><a href="{{ route('faqs') }}">FAQs</a></li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endif

                    @if($tourismCategory)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="{{ route('packages.category', 'tourism') }}" id="tourismMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Tourism</a>
                            <ul class="dropdown-menu shadow-lg" aria-labelledby="tourismMenu">
                                <li><a class="dropdown-item" href="{{ route('packages.category', ['tourism', 'series' => 'domestic']) }}">Domestic Tourism</a></li>
                                <li><a class="dropdown-item" href="{{ route('packages.category', ['tourism', 'series' => 'international']) }}">International Tourism</a></li>
                            </ul>
                        </li>
                    @endif

                    <li class="nav-item"><a class="nav-link" href="{{ route('awards') }}">Awards &amp; Recognition</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('affiliations') }}">Affiliations</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('media') }}">Media</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('testimonials') }}">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('faqs') }}">FAQs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    @if($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $whatsapp) }}" class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp me-1"></i>WhatsApp Us
                        </a>
                    @endif
                    <a href="{{ $registerNowUrl }}" class="btn btn-register-now btn-sm" target="_blank" rel="noopener">Register Now</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/about-us') }}">About Us</a></li>

                @if($hajjCategory)
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="collapse" href="#mobileHajjMenu" role="button" aria-expanded="false" aria-controls="mobileHajjMenu">Hajj Services</a>
                        <div class="collapse ps-3" id="mobileHajjMenu">
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}">Hajj Services Overview</a>
                            <a class="d-block py-1 small" href="{{ route('packages.category', 'hajj') }}">Hajj Packages</a>
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}#how-to-apply">How to Apply</a>
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}#hajj-process">Hajj Process</a>
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}#hajj-guidance">Hajj Guidance</a>
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}#accommodation-transport">Accommodation &amp; Transport</a>
                            <a class="d-block py-1 small" href="{{ route('hajj-services') }}#next-flight-date">Next Flight Date</a>
                            <a class="d-block py-1 small" href="{{ $registerNowUrl }}" target="_blank" rel="noopener">Register Now</a>
                        </div>
                    </li>
                @endif
                @if($umrahCategory)
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="collapse" href="#mobileUmrahMenu" role="button" aria-expanded="false" aria-controls="mobileUmrahMenu">Umrah Services</a>
                        <div class="collapse ps-3" id="mobileUmrahMenu">
                            <a class="d-block py-1 small" href="{{ route('umrah-services') }}">Umrah Services Overview</a>
                            <a class="d-block py-1 small" href="{{ route('packages.category', 'umrah') }}">Umrah Packages</a>
                            <a class="d-block py-1 small" href="{{ route('umrah-services') }}#how-to-apply">How to Apply</a>
                            <a class="d-block py-1 small" href="{{ route('umrah-services') }}#umrah-process">Umrah Process</a>
                        </div>
                    </li>
                @endif
                @if($tourismCategory)
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="collapse" href="#mobileTourismMenu" role="button" aria-expanded="false" aria-controls="mobileTourismMenu">Tourism</a>
                        <div class="collapse ps-3" id="mobileTourismMenu">
                            <a class="d-block py-1 small" href="{{ route('packages.category', ['tourism', 'series' => 'domestic']) }}">Domestic Tourism</a>
                            <a class="d-block py-1 small" href="{{ route('packages.category', ['tourism', 'series' => 'international']) }}">International Tourism</a>
                        </div>
                    </li>
                @endif

                <li class="nav-item"><a class="nav-link" href="{{ route('awards') }}">Awards &amp; Recognition</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('affiliations') }}">Affiliations</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('media') }}">Media</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('testimonials') }}">Testimonials</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('faqs') }}">FAQs</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}">Contact</a></li>
            </ul>
            @if($whatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $whatsapp) }}" class="btn btn-whatsapp w-100 mt-3" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i>WhatsApp Us
                </a>
            @endif
        </div>
    </div>
</header>
