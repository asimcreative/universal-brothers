{{--
    Admin navigation, rendered twice by layouts.admin: once in the desktop
    sidebar and once in the phone off-canvas menu. Kept in one place so the two
    can never list different pages.
--}}
@php
    $currentType = request()->route('type');
    $libraryLink = fn (string $key, string $label, string $icon) => [
        'url' => route('admin.library.index', $key),
        'active' => request()->routeIs('admin.library.*') && $currentType === $key,
        'icon' => $icon,
        'label' => $label,
    ];
    $link = fn (string $route, string $pattern, string $icon, string $label, array $extra = []) => array_merge([
        'url' => route($route),
        'active' => request()->routeIs($pattern),
        'icon' => $icon,
        'label' => $label,
    ], $extra);

    $newInquiries ??= \App\Models\Inquiry::where('status', 'new')->count();

    $groups = [
        'Hajj Packages' => [
            $link('admin.hajj-packages.index', 'admin.hajj-packages.index', 'bi-moon-stars', 'Hajj Packages', [
                'active' => request()->routeIs('admin.hajj-packages.*') && ! request()->routeIs('admin.hajj-packages.create'),
            ]),
            $link('admin.hajj-packages.create', 'admin.hajj-packages.create', 'bi-plus-circle', 'Add Hajj Package'),
            $link('admin.package-templates.index', 'admin.package-templates.*', 'bi-files', 'Package Templates', ['tour' => 'nav-templates']),
        ],
        'Reusable Content' => [
            $libraryLink('hotels', 'Hotels & Accommodation', 'bi-building'),
            $libraryLink('transport', 'Transport', 'bi-bus-front'),
            $libraryLink('meal-plans', 'Meal Plans', 'bi-cup-hot'),
            $libraryLink('inclusions', 'Included Services', 'bi-check2-circle'),
            $libraryLink('exclusions', 'Not Included', 'bi-x-circle'),
            $libraryLink('upgrades', 'Additional Options', 'bi-plus-square'),
            $libraryLink('mashaer', 'Mina, Arafat & Muzdalifah', 'bi-geo-alt'),
            $libraryLink('notes', 'Notes & Policies', 'bi-journal-text'),
            $libraryLink('journey-templates', 'Journey Templates', 'bi-calendar-week'),
        ],
        'Umrah & Tourism' => [
            $link('admin.packages.index', 'admin.packages.*', 'bi-box-seam', 'Umrah & Tourism Packages'),
            $link('admin.categories.index', 'admin.categories.*', 'bi-tags', 'Categories & Series'),
        ],
        'Enquiries' => [
            $link('admin.inquiries.index', 'admin.inquiries.*', 'bi-envelope', 'Inquiries', ['badge' => $newInquiries]),
        ],
        'Website Content' => [
            $link('admin.pages.index', 'admin.pages.*', 'bi-file-earmark-text', 'Pages'),
            $link('admin.news.index', 'admin.news.*', 'bi-newspaper', 'News'),
            $link('admin.faqs.index', 'admin.faqs.*', 'bi-question-circle', 'FAQs'),
            $link('admin.testimonials.index', 'admin.testimonials.*', 'bi-chat-quote', 'Testimonials'),
            $link('admin.media.index', 'admin.media.*', 'bi-collection-play', 'Media Gallery'),
            $link('admin.sliders.index', 'admin.sliders.*', 'bi-images', 'Homepage Sliders'),
        ],
        'Company' => [
            $link('admin.awards.index', 'admin.awards.*', 'bi-trophy', 'Awards'),
            $link('admin.affiliations.index', 'admin.affiliations.*', 'bi-diagram-3', 'Affiliations'),
            $link('admin.offices.index', 'admin.offices.*', 'bi-geo', 'Offices'),
            $link('admin.settings.index', 'admin.settings.*', 'bi-gear', 'Site Settings', ['tour' => 'nav-settings']),
        ],
        'AI Assistant' => [
            $link('admin.ai.index', 'admin.ai.index', 'bi-stars', 'AI Assistant'),
            $link('admin.ai.test', 'admin.ai.test', 'bi-play-circle', 'AI Test Panel'),
            $link('admin.ai.conversations', 'admin.ai.conversation*', 'bi-chat-dots', 'AI Conversations'),
        ],
    ];

    if (auth()->user()?->isSuperAdmin()) {
        $groups['System'] = [
            $link('admin.users.index', 'admin.users.*', 'bi-people', 'Users & Roles'),
        ];
    }

    $groups['Help'] = [
        $link('admin.guide.index', 'admin.guide.*', 'bi-life-preserver', 'Admin Guide'),
        $link('admin.training.index', 'admin.training.*', 'bi-play-btn', 'Video Training'),
    ];
@endphp

@php
    // Anchors for the guided tour (resources/js/admin/tour.js).
    $groupTour = ['Hajj Packages' => 'nav-hajj', 'Reusable Content' => 'nav-reusable', 'Enquiries' => 'nav-enquiries', 'AI Assistant' => 'nav-ai'];
@endphp

<nav class="admin-nav-scroll" aria-label="{{ $label ?? 'Admin' }}" data-tour="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
        <i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard
    </a>
    @foreach($groups as $group => $items)
        <div class="admin-nav-group" @isset($groupTour[$group]) data-tour="{{ $groupTour[$group] }}" @endisset>
        <div class="admin-nav-group-label">{{ $group }}</div>
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" class="admin-nav-link {{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif @isset($item['tour']) data-tour="{{ $item['tour'] }}" @endisset>
                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                @if(!empty($item['badge']))
                    <span class="badge bg-danger rounded-pill admin-nav-badge" aria-label="{{ $item['badge'] }} new">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
        </div>
    @endforeach
</nav>
