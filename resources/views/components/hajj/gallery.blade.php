{{--
    Package photos and videos.

    The whole section — heading included — is omitted when the package has no
    media rows. A "Gallery" heading over an empty grid is the single most
    obvious sign of an unfinished site, and most of this catalogue has no
    photography yet.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubPackage = $hajj->package();
    $ubMedia = $ubPackage->media;
@endphp

@if($ubMedia->isNotEmpty())
    <x-hajj.section id="gallery" eyebrow="See It For Yourself" title="Package Gallery">
        <div class="hajj-gallery">
            @foreach($ubMedia as $ubItem)
                <figure class="hajj-gallery-item">
                    @if($ubItem->image_path)
                        <a href="{{ Storage::url($ubItem->image_path) }}"
                           class="gallery-item d-block"
                           data-lightbox-trigger
                           data-lightbox-src="{{ Storage::url($ubItem->image_path) }}"
                           data-lightbox-caption="{{ $ubItem->alt_text ?? $ubPackage->name }}">
                            <img src="{{ Storage::url($ubItem->image_path) }}" alt="{{ $ubItem->alt_text ?? $ubPackage->name }}" loading="lazy" decoding="async">
                        </a>
                    @elseif($ubItem->video_url)
                        <div class="ratio ratio-16x9 rounded overflow-hidden">
                            <iframe src="{{ $ubItem->video_url }}" title="{{ $ubItem->caption ?? $ubPackage->name }}" loading="lazy" allowfullscreen></iframe>
                        </div>
                    @endif

                    @if($ubItem->caption)
                        <figcaption class="hajj-gallery-caption">{{ $ubItem->caption }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </x-hajj.section>
@endif
