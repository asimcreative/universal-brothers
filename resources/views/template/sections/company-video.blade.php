@php
    // The band is driven by the CMS, never by a hardcoded link: the first
    // active video in the Media library wins, and a Site Setting can override
    // it. With neither set the section does not render at all, which is why it
    // is absent on a database that has no media in it yet.
    $ubMedia = \App\Models\MediaItem::where('is_active', true)->whereNotNull('video_url')->orderBy('sort_order')->first();
    $ubVideoUrl = \App\Models\SiteSetting::get('company_video_url') ?: optional($ubMedia)->video_url;

    $ubVideoId = null;
    if ($ubVideoUrl && preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $ubVideoUrl, $ubMatch)) {
        $ubVideoId = $ubMatch[1];
    }

    // The template pulls its backdrop from img.youtube.com. We do not: the
    // site's Content-Security-Policy allows no third-party images, and loading
    // one would hand every visitor's address to Google before they have asked
    // to watch anything. The poster is the Media item's own uploaded still, or
    // a photograph from our library.
    $ubPoster = \App\Support\SiteImagery::resolve(optional($ubMedia)->file_path)
        ?: \App\Support\SiteImagery::url('haram-courtyard');

    $ubEmbed = $ubVideoId ? "https://www.youtube.com/embed/{$ubVideoId}?autoplay=1&rel=0" : $ubVideoUrl;
    $ubGroup = \App\Models\SiteSetting::get('parent_group', "Maxim's Group");
@endphp

{{--
    The designer's company-introduction band.

    Markup and class names are the template's own. The video is ours and comes
    from the Media library, so an admin changing it changes this band — the
    reference hardcodes a YouTube id, which would go stale the first time the
    company recut the film.
--}}
@if($ubVideoUrl)
    <section id="company-video" class="relative overflow-hidden bg-background py-16 sm:py-20 lg:py-24">
        <div aria-hidden="true" class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ $ubPoster }}')"></div>
        <div aria-hidden="true" class="absolute inset-0 bg-brand-950/70"></div>
        <div aria-hidden="true" class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_5%,rgba(6,18,25,0.85)_75%)]"></div>

        <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10 relative">
            <div class="flex min-h-[300px] flex-col items-center justify-center text-center sm:min-h-[340px] lg:min-h-[380px]">
                <div class="reveal-on-scroll">
                    <button type="button" data-ub-video-open aria-label="Play: Universal Brothers &mdash; Company Introduction" class="group relative flex size-[68px] items-center justify-center rounded-full bg-accent-500 text-inverse shadow-[0_18px_46px_-14px_rgba(185,134,90,0.75)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 focus-visible:ring-offset-4 focus-visible:ring-offset-background sm:size-[78px]">
                        <span aria-hidden="true" class="ub-video-ring absolute inset-0 rounded-full border border-accent-400"></span>
                        <span aria-hidden="true" class="ub-video-ring ub-video-ring-2 absolute inset-0 rounded-full border border-accent-400"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play relative ml-1 fill-inverse" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
                    </button>
                </div>

                <p class="reveal-on-scroll reveal-delay-1 mt-7 font-display text-lg font-medium italic tracking-wide text-accent-400 sm:text-xl">{{ $ubGroup }}</p>

                <h2 class="reveal-on-scroll reveal-delay-2 mt-1 font-display text-3xl font-bold leading-[1.08] text-heading sm:text-4xl lg:text-5xl">Company Introduction</h2>

                <p class="reveal-on-scroll reveal-delay-3 mx-auto mt-4 max-w-xl text-sm leading-relaxed text-ivory/70 sm:text-base">A few minutes inside the offices, the team and the ground operation that stands behind every departure.</p>
            </div>
        </div>
    </section>

    {{-- The player only exists once opened, so the page never loads a YouTube
         frame nobody asked for. --}}
    <div id="ub-video-dialog" hidden class="fixed inset-0 z-[70] flex items-center justify-center bg-brand-950/90 p-4">
        <button type="button" data-ub-video-close aria-label="Close video" class="absolute right-5 top-5 flex size-11 items-center justify-center rounded-full border border-ivory/25 text-ivory transition-colors hover:bg-ivory/10">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
        <div class="w-full max-w-4xl" role="dialog" aria-modal="true" aria-label="Universal Brothers — Company Introduction">
            <div class="relative w-full overflow-hidden rounded-card bg-black" style="aspect-ratio: 16 / 9" data-ub-video-frame data-src="{{ $ubEmbed }}"></div>
        </div>
    </div>

    <style>
        .ub-video-ring { transform: scale(1.32); opacity: .55; }
        .ub-video-ring-2 { transform: scale(1.62); opacity: .35; }
        @media (prefers-reduced-motion: no-preference) {
            .ub-video-ring { animation: ub-video-pulse 2.8s ease-out infinite; }
            .ub-video-ring-2 { animation-delay: 1.4s; }
        }
        @keyframes ub-video-pulse {
            0% { transform: scale(1); opacity: .6 }
            100% { transform: scale(1.9); opacity: 0 }
        }
    </style>

    <script>
        (function () {
            var dialog = document.getElementById('ub-video-dialog');
            if (!dialog) return;
            var frame = dialog.querySelector('[data-ub-video-frame]');
            var opener = document.querySelector('[data-ub-video-open]');
            var closer = dialog.querySelector('[data-ub-video-close]');

            function open() {
                // Built on open and torn down on close, so nothing keeps
                // playing behind a closed overlay.
                frame.innerHTML = '<iframe src="' + frame.dataset.src + '" title="Universal Brothers company introduction" '
                    + 'class="absolute inset-0 h-full w-full" frameborder="0" '
                    + 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                dialog.hidden = false;
                document.body.style.overflow = 'hidden';
                closer.focus();
            }

            function close() {
                frame.innerHTML = '';
                dialog.hidden = true;
                document.body.style.overflow = '';
                if (opener) opener.focus();
            }

            if (opener) opener.addEventListener('click', open);
            closer.addEventListener('click', close);
            dialog.addEventListener('click', function (e) { if (e.target === dialog) close(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dialog.hidden) close(); });
        })();
    </script>
@endif
