@php
    use App\Support\SiteImagery;

    /*
     * Photograph attribution for this page.
     *
     * 38 of the 45 library photographs are CC BY or CC BY-SA. Those licences
     * permit commercial use freely, but they are conditional: the licence is
     * granted only while the author is credited, the licence named and linked,
     * and modifications indicated. Using the work without that is simply using
     * it without a licence.
     *
     * This renders only the photographs actually emitted on THIS page — see
     * SiteImagery::credits() — so the block stays a few lines rather than a
     * wall of 38 names on every page. The CC0 images are omitted because they
     * are public domain and crediting them would bury the real obligations.
     *
     * It must render AFTER the page content, which is why it lives in the
     * footer partial: SiteImagery only knows a photograph was used once it has
     * handed out its URL.
     */
    $ubCredits = SiteImagery::credits();
@endphp

@if($ubCredits)
    <div class="photo-credits">
        <span class="photo-credits-label">{{ __('Photographs on this page') }}:</span>
        @foreach($ubCredits as $ubCredit)
            <span class="photo-credit">
                <span class="photo-credit-title">{{ $ubCredit['title'] }}</span>
                @if($ubCredit['page'])
                    {{-- rel="nofollow noopener": these are outbound links on
                         every page of the site, and they are attribution, not
                         endorsement. --}}
                    <a href="{{ $ubCredit['page'] }}" target="_blank" rel="nofollow noopener">{{ __('source') }}</a>
                @endif
                {{ __('by') }} {{ $ubCredit['artist'] }},
                @if($ubCredit['licenceUrl'])
                    <a href="{{ $ubCredit['licenceUrl'] }}" target="_blank" rel="nofollow noopener">{{ $ubCredit['licence'] }}</a>
                @else
                    {{ $ubCredit['licence'] }}
                @endif
                {{-- Separator as plain output rather than `@endif@if(...)`,
                     which Blade compiles into adjacent PHP tags and chokes on. --}}
                {{ $loop->last ? '' : ';' }}
            </span>
        @endforeach
        {{-- Required by the ShareAlike licences, and true of every image here:
             all were resized and re-encoded. --}}
        <span class="photo-credits-note">{{ __('Images resized and converted to WebP.') }}</span>
    </div>
@endif
