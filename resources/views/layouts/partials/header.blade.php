@php
    $whatsapp = $primaryOffice?->whatsapp;
    $hajjCategory = $navCategories->firstWhere('slug', 'hajj');
    $umrahCategory = $navCategories->firstWhere('slug', 'umrah');
    $tourismCategory = $navCategories->firstWhere('slug', 'tourism');
    $registerNowUrl = 'https://hums.akhg.com.pk/HajiReg/HajiLead';
@endphp

{{--
    The primary nav expands at `xl`, not `lg`.

    Ten top-level items (Home, About Us, Hajj & Umrah, Tourism, Awards &
    Recognition, Affiliations, Media, Testimonials, FAQs, Contact) plus a
    brand and a CTA simply do not fit a 992px bar: on the live site they
    wrapped mid-phrase into "About / Us", "Awards & / Recognition" and
    "WhatsApp / Us", which was one of the most visible signs of an unfinished
    design. Every one of those items is required to stay a *visible link
    inside `nav.navbar`* by the E2E suite, so hiding them behind a dropdown
    was not an option — expanding later, and giving 992–1199px the (better)
    drawer instead, is. The two breakpoint assertions in responsive.spec.js
    (toggler hidden at 1280, visible at 768) both still hold.
--}}
<header class="site-header sticky-top">
    <div class="topbar d-none d-xl-block">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <div class="topbar-contact d-flex align-items-center gap-3">
                    @if($primaryOffice?->phone_primary)
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}">
                            <i class="bi bi-telephone-fill"></i>{{ $primaryOffice->phone_primary }}
                        </a>
                    @endif
                    @if($primaryOffice?->email)
                        <a href="mailto:{{ $primaryOffice->email }}">
                            <i class="bi bi-envelope-fill"></i>{{ $primaryOffice->email }}
                        </a>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="topbar-credential">
                        @if(\App\Models\SiteSetting::get('iata_registered', '1'))
                            <i class="bi bi-patch-check-fill"></i>IATA Registered
                            <span class="topbar-sep">&middot;</span>
                        @endif
                        Hajj License No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}
                    </span>
                    @if($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $whatsapp) }}" class="topbar-whatsapp" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp"></i>WhatsApp Us
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-xl navbar-dark site-navbar" aria-label="Primary">
        <div class="container-fluid px-4">
            <a class="navbar-brand site-brand" href="{{ route('home') }}">
                {{-- Geometric monogram: an eight-point khatim star holding the
                     initials. The brand was previously bare text, which read
                     as an unstyled placeholder rather than an identity. --}}
                <span class="site-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 44 44" width="38" height="38" role="presentation" focusable="false">
                        <rect x="9" y="9" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4"/>
                        <rect x="9" y="9" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4" transform="rotate(45 22 22)"/>
                        <circle cx="22" cy="22" r="7.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    </svg>
                    <span class="site-brand-initials">UB</span>
                </span>
                <span class="site-brand-text">
                    <span class="site-brand-name">Universal Brothers</span>
                    <span class="site-brand-tag">Hajj &middot; Umrah &middot; Tourism</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse d-none d-xl-flex">
                <ul class="navbar-nav me-auto mb-0 align-items-xl-center">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/about-us') }}">About Us</a></li>

                    @if($hajjCategory || $umrahCategory)
                        <li class="nav-item dropdown mega-menu-parent">
                            <a class="nav-link dropdown-toggle" href="#" id="hajjUmrahMegaMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Hajj &amp; Umrah</a>
                            <div class="dropdown-menu mega-menu" aria-labelledby="hajjUmrahMegaMenu">
                                <div class="mega-menu-grid">
                                    @if($hajjCategory)
                                        <div class="mega-menu-col">
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
                                        <div class="mega-menu-col">
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

                                    {{-- Editorial panel. The mega menu was previously two
                                         bare link columns in a white box; this gives it a
                                         visual anchor and a clear primary action. --}}
                                    <div class="mega-menu-feature ub-visual ub-visual--v2">
                                        <div class="mega-menu-feature-body">
                                            <span class="mega-menu-feature-eyebrow">Hajj 2027 &middot; 1448 AH</span>
                                            <p class="mega-menu-feature-title">You focus on your Ibadah.<br>We focus on the journey.</p>
                                            <a href="{{ route('packages.category', 'hajj') }}" class="btn btn-secondary btn-sm">View Hajj 2027 Packages</a>
                                        </div>
                                    </div>
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
                <a href="{{ $registerNowUrl }}" class="btn btn-register-now" target="_blank" rel="noopener">Register Now</a>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end site-mobile-nav" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header">
            <span class="offcanvas-title site-brand-name">Universal Brothers</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/about-us') }}">About Us</a></li>

                @if($hajjCategory)
                    <li class="nav-item">
                        <a class="nav-link nav-link-group" data-bs-toggle="collapse" href="#mobileHajjMenu" role="button" aria-expanded="false" aria-controls="mobileHajjMenu">Hajj Services<i class="bi bi-chevron-down" aria-hidden="true"></i></a>
                        <div class="collapse mobile-subnav" id="mobileHajjMenu">
                            <a href="{{ route('hajj-services') }}">Hajj Services Overview</a>
                            <a href="{{ route('packages.category', 'hajj') }}">Hajj Packages</a>
                            <a href="{{ route('hajj-services') }}#how-to-apply">How to Apply</a>
                            <a href="{{ route('hajj-services') }}#hajj-process">Hajj Process</a>
                            <a href="{{ route('hajj-services') }}#hajj-guidance">Hajj Guidance</a>
                            <a href="{{ route('hajj-services') }}#accommodation-transport">Accommodation &amp; Transport</a>
                            <a href="{{ route('hajj-services') }}#next-flight-date">Next Flight Date</a>
                        </div>
                    </li>
                @endif
                @if($umrahCategory)
                    <li class="nav-item">
                        <a class="nav-link nav-link-group" data-bs-toggle="collapse" href="#mobileUmrahMenu" role="button" aria-expanded="false" aria-controls="mobileUmrahMenu">Umrah Services<i class="bi bi-chevron-down" aria-hidden="true"></i></a>
                        <div class="collapse mobile-subnav" id="mobileUmrahMenu">
                            <a href="{{ route('umrah-services') }}">Umrah Services Overview</a>
                            <a href="{{ route('packages.category', 'umrah') }}">Umrah Packages</a>
                            <a href="{{ route('umrah-services') }}#how-to-apply">How to Apply</a>
                            <a href="{{ route('umrah-services') }}#umrah-process">Umrah Process</a>
                        </div>
                    </li>
                @endif
                @if($tourismCategory)
                    <li class="nav-item">
                        <a class="nav-link nav-link-group" data-bs-toggle="collapse" href="#mobileTourismMenu" role="button" aria-expanded="false" aria-controls="mobileTourismMenu">Tourism<i class="bi bi-chevron-down" aria-hidden="true"></i></a>
                        <div class="collapse mobile-subnav" id="mobileTourismMenu">
                            <a href="{{ route('packages.category', ['tourism', 'series' => 'domestic']) }}">Domestic Tourism</a>
                            <a href="{{ route('packages.category', ['tourism', 'series' => 'international']) }}">International Tourism</a>
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

            {{-- Register Now lives here, once, as an explicit drawer CTA rather
                 than buried inside the collapsed Hajj sub-list where it used to
                 sit. Exactly one occurrence inside `#mobileNav` — a second one
                 would make responsive.spec.js's strict-mode `getByRole` lookup
                 for it ambiguous. --}}
            <div class="mobile-nav-cta">
                <a href="{{ $registerNowUrl }}" class="btn btn-register-now w-100" target="_blank" rel="noopener">Register Now</a>
                @if($whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $whatsapp) }}" class="btn btn-whatsapp w-100" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp me-1"></i>WhatsApp Us
                    </a>
                @endif
                @if($primaryOffice?->phone_primary)
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="mobile-nav-phone">
                        <i class="bi bi-telephone-fill me-1"></i>{{ $primaryOffice->phone_primary }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
