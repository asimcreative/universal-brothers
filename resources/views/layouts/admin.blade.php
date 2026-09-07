<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Universal Brothers</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="admin-body">
    @php
        $adminNavGroups = [
            'Content' => [
                ['route' => 'admin.pages.index', 'pattern' => 'admin.pages.*', 'icon' => 'bi-file-earmark-text', 'label' => 'Pages'],
                ['route' => 'admin.news.index', 'pattern' => 'admin.news.*', 'icon' => 'bi-newspaper', 'label' => 'News'],
                ['route' => 'admin.faqs.index', 'pattern' => 'admin.faqs.*', 'icon' => 'bi-question-circle', 'label' => 'FAQs'],
                ['route' => 'admin.testimonials.index', 'pattern' => 'admin.testimonials.*', 'icon' => 'bi-chat-quote', 'label' => 'Testimonials'],
                ['route' => 'admin.media.index', 'pattern' => 'admin.media.*', 'icon' => 'bi-collection-play', 'label' => 'Media Gallery'],
                ['route' => 'admin.sliders.index', 'pattern' => 'admin.sliders.*', 'icon' => 'bi-images', 'label' => 'Homepage Sliders'],
            ],
            'Packages' => [
                ['route' => 'admin.hajj-packages.index', 'pattern' => 'admin.hajj-packages.*', 'icon' => 'bi-moon-stars', 'label' => 'Hajj Packages'],
                ['route' => 'admin.packages.index', 'pattern' => 'admin.packages.*', 'icon' => 'bi-box-seam', 'label' => 'Umrah & Tourism Packages'],
                ['route' => 'admin.categories.index', 'pattern' => 'admin.categories.*', 'icon' => 'bi-tags', 'label' => 'Categories & Series'],
            ],
            'Company' => [
                ['route' => 'admin.awards.index', 'pattern' => 'admin.awards.*', 'icon' => 'bi-trophy', 'label' => 'Awards'],
                ['route' => 'admin.affiliations.index', 'pattern' => 'admin.affiliations.*', 'icon' => 'bi-diagram-3', 'label' => 'Affiliations'],
                ['route' => 'admin.offices.index', 'pattern' => 'admin.offices.*', 'icon' => 'bi-geo-alt', 'label' => 'Offices'],
                ['route' => 'admin.settings.index', 'pattern' => 'admin.settings.*', 'icon' => 'bi-gear', 'label' => 'Site Settings'],
            ],
            'Leads' => [
                ['route' => 'admin.inquiries.index', 'pattern' => 'admin.inquiries.*', 'icon' => 'bi-envelope', 'label' => 'Inquiries', 'badge' => \App\Models\Inquiry::where('status', 'new')->count()],
            ],
        ];
        if (auth()->user()?->isSuperAdmin()) {
            $adminNavGroups['System'] = [
                ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'bi-people', 'label' => 'Users & Roles'],
            ];
        }
        $currentUser = auth()->user();
        $userInitial = $currentUser ? strtoupper(substr($currentUser->name, 0, 1)) : '?';
    @endphp

    <div class="d-flex">
        {{-- Desktop sidebar --}}
        <aside class="admin-sidebar d-none d-md-flex vh-100 sticky-top">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <span class="admin-brand-mark">UB</span>
                <span class="admin-brand-text">
                    <strong>Universal Brothers</strong>
                    <span>Administration</span>
                </span>
            </a>
            <div class="admin-nav-scroll">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
                </a>
                @foreach($adminNavGroups as $group => $items)
                    <div class="admin-nav-group-label">{{ $group }}</div>
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}" class="admin-nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                            @if(!empty($item['badge']))
                                <span class="badge bg-danger rounded-pill admin-nav-badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </div>
            <div class="admin-sidebar-footer">Universal Brothers CMS</div>
        </aside>

        {{-- Mobile offcanvas sidebar --}}
        <div class="offcanvas offcanvas-start admin-sidebar d-md-none" tabindex="-1" id="adminMobileNav">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand mb-0">
                    <span class="admin-brand-mark">UB</span>
                    <span class="admin-brand-text">
                        <strong>Universal Brothers</strong>
                        <span>Administration</span>
                    </span>
                </a>
                <button type="button" class="btn-close btn-close-white me-3" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
            </div>
            <div class="admin-nav-scroll">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
                </a>
                @foreach($adminNavGroups as $group => $items)
                    <div class="admin-nav-group-label">{{ $group }}</div>
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}" class="admin-nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                            @if(!empty($item['badge']))
                                <span class="badge bg-danger rounded-pill admin-nav-badge">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav" aria-controls="adminMobileNav" aria-label="Open menu">
                        <i class="bi bi-list fs-5" aria-hidden="true"></i>
                    </button>
                    <div>
                        @hasSection('breadcrumb')
                            <nav class="admin-breadcrumb" aria-label="breadcrumb">@yield('breadcrumb')</nav>
                        @else
                            <nav class="admin-breadcrumb" aria-label="breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> / <span>@yield('title')</span></nav>
                        @endif
                        <h1 class="admin-page-title">@yield('title')</h1>
                        @hasSection('subtitle')<p class="admin-page-subtitle">@yield('subtitle')</p>@endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @hasSection('actions') @yield('actions') @endif

                    <div class="dropdown">
                        <button class="admin-user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="admin-user-avatar">{{ $userInitial }}</span>
                            <span class="admin-user-meta d-none d-lg-block">
                                <strong>{{ $currentUser?->name }}</strong>
                                <span>{{ $currentUser?->isSuperAdmin() ? 'Super Admin' : 'Admin' }}</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">{{ $currentUser?->email }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <div class="admin-content">
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

                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
