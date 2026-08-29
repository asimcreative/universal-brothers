@php($whatsapp = $primaryOffice?->whatsapp)

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
                <span class="text-warning"><i class="bi bi-patch-check-fill me-1"></i>IATA Registered · Hajj License No. {{ \App\Models\SiteSetting::get('government_license_no', '2014') }}</span>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="{{ route('home') }}">Universal Brothers</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse d-none d-lg-flex">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    @foreach($navCategories as $navCategory)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('packages.category', $navCategory->slug) }}">{{ $navCategory->name }}</a>
                        </li>
                    @endforeach
                    <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    @if($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', $whatsapp) }}" class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp me-1"></i>WhatsApp Us
                        </a>
                    @endif
                    <a href="{{ route('contact') }}" class="btn btn-secondary btn-sm">Get a Quote</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                @foreach($navCategories as $navCategory)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('packages.category', $navCategory->slug) }}">{{ $navCategory->name }}</a>
                    </li>
                @endforeach
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
