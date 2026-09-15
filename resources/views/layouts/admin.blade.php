<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') | Universal Brothers</title>
    {{-- Until admin.js has loaded and taken over with its styled dialog, a
         form marked data-confirm still asks — with the browser's own confirm.
         Without this, a delete pressed while the page was still loading went
         straight through with no question at all. --}}
    <script>
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (window.ubAdminConfirmReady || !form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;
            var question = (form.getAttribute('data-confirm-title') || 'Are you sure?') + '\n\n' + form.getAttribute('data-confirm');
            if (!window.confirm(question)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    </script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js', 'resources/js/admin.js'])
</head>
<body class="admin-body">
    @php
        $currentUser = auth()->user();
        $userInitial = $currentUser ? strtoupper(substr($currentUser->name, 0, 1)) : '?';
        $newInquiries = \App\Models\Inquiry::where('status', 'new')->count();
    @endphp

    <a href="#admin-main-content" class="visually-hidden-focusable btn btn-light position-absolute m-2" style="z-index:2000">Skip to content</a>

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
            @include('admin.partials.nav', ['label' => 'Admin', 'newInquiries' => $newInquiries])
            <div class="admin-sidebar-footer">Universal Brothers CMS</div>
        </aside>

        {{-- Phone off-canvas menu --}}
        <div class="offcanvas offcanvas-start admin-sidebar d-md-none" tabindex="-1" id="adminMobileNav" aria-label="Admin menu">
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
            @include('admin.partials.nav', ['label' => 'Admin menu', 'newInquiries' => $newInquiries])
        </div>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav" aria-controls="adminMobileNav" aria-label="Open menu">
                        <i class="bi bi-list fs-5" aria-hidden="true"></i>
                    </button>
                    <div class="min-w-0">
                        <nav class="admin-breadcrumb" aria-label="breadcrumb">
                            @hasSection('breadcrumb')
                                @yield('breadcrumb')
                            @else
                                <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <span>@yield('title')</span>
                            @endif
                        </nav>
                        <h1 class="admin-page-title text-truncate">@yield('title')</h1>
                        @hasSection('subtitle')<p class="admin-page-subtitle mb-0">@yield('subtitle')</p>@endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    @hasSection('actions') @yield('actions') @endif

                    <a href="{{ route('home') }}" class="admin-topbar-icon d-none d-sm-inline-flex" target="_blank" rel="noopener" title="View the website" aria-label="View the website (opens in a new tab)">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('admin.guide.index') }}" class="admin-topbar-icon" title="Guide and help" aria-label="Guide and help" data-tour="help">
                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                    </a>

                    <div class="dropdown">
                        <button class="admin-user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu — {{ $currentUser?->name }}">
                            <span class="admin-user-avatar">{{ $userInitial }}</span>
                            <span class="admin-user-meta d-none d-lg-block">
                                <strong>{{ $currentUser?->name }}</strong>
                                <span>{{ $currentUser?->isSuperAdmin() ? 'Super Admin' : 'Admin' }}</span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">{{ $currentUser?->email }}</h6></li>
                            <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>View website</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.guide.index') }}"><i class="bi bi-book me-2" aria-hidden="true"></i>Admin guide</a></li>
                            <li>
                                <form method="POST" action="{{ route('admin.onboarding.restart') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-signpost-split me-2" aria-hidden="true"></i>Take the guided tour</button>
                                </form>
                            </li>
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

            <main class="admin-content" id="admin-main-content" tabindex="-1">
                @if(session('status'))
                    <div class="alert alert-success admin-alert alert-dismissible fade show" role="status">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <div class="admin-alert-body">{{ session('status') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
                    </div>
                @endif

                {{-- Pages with their own step-aware error summary (the package
                     builder) opt out of this generic one. --}}
                @if($errors->any() && ! $__env->hasSection('own_error_summary'))
                    <div class="alert alert-danger admin-alert alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <div class="admin-alert-body">
                            <strong>Please check the following:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
                    </div>
                @endif

                @hasSection('guide')
                    @include('admin.partials.page-help', ['key' => trim($__env->yieldContent('guide')), 'video' => trim($__env->yieldContent('guide_video'))])
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- One confirmation dialog for every destructive or irreversible action
         in the admin. Any form with `data-confirm="message"` opens it. --}}
    <div class="modal fade admin-confirm-modal" id="adminConfirmModal" tabindex="-1" aria-labelledby="adminConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="admin-confirm-icon" data-confirm-icon><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                    <h2 class="modal-title fs-5" id="adminConfirmTitle" data-confirm-title>Are you sure?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
                </div>
                <div class="modal-body" data-confirm-message></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" data-confirm-accept>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    @auth
        {{-- The guided tour's steps and this admin's place in it. --}}
        @php
            $tourData = [
                'steps' => \App\Support\Guide\GuideContent::tour(),
                'status' => $currentUser->tour_status,
                'step' => (int) $currentUser->tour_step,
                'stateUrl' => route('admin.onboarding.tour'),
                'dashboardUrl' => route('admin.dashboard'),
                'guideUrl' => route('admin.guide.index'),
            ];
        @endphp
        <script type="application/json" id="admin-tour-data">@json($tourData)</script>
    @endauth

    @stack('modals')
    @stack('scripts')
</body>
</html>
