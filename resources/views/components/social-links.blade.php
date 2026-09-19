@props(['size' => 14, 'class' => '', 'linkClass' => ''])

@php
    /*
     * One list, used by both the header and the footer, so the two can never
     * drift apart — which is what happened when each kept its own copy and only
     * three platforms were ever wired up.
     *
     * A platform appears only when an admin has saved a link for it in
     * Settings. Nothing is hardcoded and nothing is shown for a profile the
     * company does not have.
     */
    $ubPlatforms = [
        'social_facebook'  => ['Facebook',  '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>'],
        'social_instagram' => ['Instagram', '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>'],
        'social_youtube'   => ['YouTube',   '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path><path d="m10 15 5-3-5-3z"></path>'],
        'social_tiktok'    => ['TikTok',    '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>'],
        'social_linkedin'  => ['LinkedIn',  '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect width="4" height="12" x="2" y="9"></rect><circle cx="4" cy="4" r="2"></circle>'],
        'social_x'         => ['X',         '<path d="M18 6 6 18"></path><path d="m6 6 5.2 6.6L18 18"></path>'],
        'social_threads'   => ['Threads',   '<path d="M12 3c-4.5 0-7 3-7 9s2.5 9 7 9c3.5 0 5.6-1.9 5.6-4.3 0-2.3-1.9-3.7-4.6-3.7-2 0-3.3.9-3.3 2.1 0 .9.7 1.5 1.7 1.5"></path>'],
        'social_pinterest' => ['Pinterest', '<path d="M12 3a8 8 0 0 0-3 15.4"></path><path d="M9.5 18.5 12 9"></path><path d="M9.6 12.4c-.4-1.6.5-3.4 2.4-3.4 1.6 0 2.6 1.2 2.6 2.8 0 1.9-1 3.6-2.6 3.6-1 0-1.8-.8-1.6-1.8"></path>'],
    ];

    $ubLinks = [];
    foreach ($ubPlatforms as $ubKey => [$ubLabel, $ubPath]) {
        $ubUrl = trim((string) \App\Models\SiteSetting::get($ubKey));
        if ($ubUrl !== '') {
            $ubLinks[] = ['url' => $ubUrl, 'label' => $ubLabel, 'path' => $ubPath];
        }
    }
@endphp

@if($ubLinks)
    <span {{ $attributes->merge(['class' => $class]) }}>
        @foreach($ubLinks as $ubLink)
            <a href="{{ $ubLink['url'] }}" target="_blank" rel="noopener"
               aria-label="Universal Brothers on {{ $ubLink['label'] }}"
               class="{{ $linkClass }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ubLink['path'] !!}</svg>
            </a>
        @endforeach
    </span>
@endif
