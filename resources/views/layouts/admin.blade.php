<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Universal Brothers</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    @php
        $adminNavItems = [
            ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
            ['route' => 'admin.packages.index', 'pattern' => 'admin.packages.*', 'icon' => 'bi-box-seam', 'label' => 'Packages'],
            ['route' => 'admin.hajj-packages.index', 'pattern' => 'admin.hajj-packages.*', 'icon' => 'bi-moon-stars', 'label' => 'Hajj Packages'],
            ['route' => 'admin.categories.index', 'pattern' => 'admin.categories.*', 'icon' => 'bi-tags', 'label' => 'Categories & Series'],
            ['route' => 'admin.pages.index', 'pattern' => 'admin.pages.*', 'icon' => 'bi-file-earmark-text', 'label' => 'Pages'],
            ['route' => 'admin.sliders.index', 'pattern' => 'admin.sliders.*', 'icon' => 'bi-images', 'label' => 'Sliders'],
            ['route' => 'admin.media.index', 'pattern' => 'admin.media.*', 'icon' => 'bi-collection-play', 'label' => 'Media Gallery'],
            ['route' => 'admin.news.index', 'pattern' => 'admin.news.*', 'icon' => 'bi-newspaper', 'label' => 'News'],
            ['route' => 'admin.testimonials.index', 'pattern' => 'admin.testimonials.*', 'icon' => 'bi-chat-quote', 'label' => 'Testimonials'],
            ['route' => 'admin.faqs.index', 'pattern' => 'admin.faqs.*', 'icon' => 'bi-question-circle', 'label' => 'FAQs'],
            ['route' => 'admin.awards.index', 'pattern' => 'admin.awards.*', 'icon' => 'bi-trophy', 'label' => 'Awards'],
            ['route' => 'admin.affiliations.index', 'pattern' => 'admin.affiliations.*', 'icon' => 'bi-diagram-3', 'label' => 'Affiliations'],
            ['route' => 'admin.offices.index', 'pattern' => 'admin.offices.*', 'icon' => 'bi-geo-alt', 'label' => 'Offices'],
            ['route' => 'admin.inquiries.index', 'pattern' => 'admin.inquiries.*', 'icon' => 'bi-envelope', 'label' => 'Inquiries'],
            ['route' => 'admin.settings.index', 'pattern' => 'admin.settings.*', 'icon' => 'bi-gear', 'label' => 'Settings'],
        ];
        if (auth()->user()?->isSuperAdmin()) {
            $adminNavItems[] = ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'bi-people', 'label' => 'Users & Roles'];
        }
    @endphp

    <nav class="navbar navbar-dark bg-dark d-md-none px-3">
        <a href="{{ route('admin.dashboard') }}" class="navbar-brand fw-bold">Universal Brothers</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav" aria-controls="adminMobileNav">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    <div class="offcanvas offcanvas-start bg-dark text-light d-md-none" tabindex="-1" id="adminMobileNav">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav nav-pills flex-column gap-1">
                @foreach($adminNavItems as $item)
                    <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs($item['pattern']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            <hr class="border-secondary">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </form>
        </div>
    </div>

    <div class="d-flex">
        <nav class="bg-dark text-light p-3 vh-100 sticky-top d-none d-md-block" style="width: 250px;">
            <a href="{{ route('admin.dashboard') }}" class="d-block text-white text-decoration-none fw-bold fs-5 mb-4">Universal Brothers</a>
            <ul class="nav nav-pills flex-column gap-1">
                @foreach($adminNavItems as $item)
                    <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs($item['pattern']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            <hr class="border-secondary">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            </form>
        </nav>

        <div class="flex-grow-1 p-4">
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">@yield('title')</h1>
                @hasSection('actions') @yield('actions') @endif
            </div>

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
