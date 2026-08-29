<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Universal Brothers</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="d-flex">
        <nav class="bg-dark text-light p-3 vh-100 sticky-top d-none d-md-block" style="width: 250px;">
            <a href="{{ route('admin.dashboard') }}" class="d-block text-white text-decoration-none fw-bold fs-5 mb-4">Universal Brothers</a>
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}" href="{{ route('admin.packages.index') }}"><i class="bi bi-box-seam me-2"></i>Packages</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags me-2"></i>Categories &amp; Series</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}"><i class="bi bi-file-earmark-text me-2"></i>Pages</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.sliders.*') ? 'active' : '' }}" href="{{ route('admin.sliders.index') }}"><i class="bi bi-images me-2"></i>Sliders</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}"><i class="bi bi-newspaper me-2"></i>News</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}"><i class="bi bi-chat-quote me-2"></i>Testimonials</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" href="{{ route('admin.faqs.index') }}"><i class="bi bi-question-circle me-2"></i>FAQs</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.offices.*') ? 'active' : '' }}" href="{{ route('admin.offices.index') }}"><i class="bi bi-geo-alt me-2"></i>Offices</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.inquiries.*') ? 'active' : '' }}" href="{{ route('admin.inquiries.index') }}"><i class="bi bi-envelope me-2"></i>Inquiries</a></li>
                <li class="nav-item"><a class="nav-link text-light {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
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
