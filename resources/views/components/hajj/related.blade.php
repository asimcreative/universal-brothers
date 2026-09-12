{{--
    Other packages in the same category, as a full-width band at the foot of
    the page.

    These used to live inside the sticky sidebar, where they made the sticky
    element 830px tall — taller than a 768px laptop's viewport — so part of the
    enquiry form was permanently cut off. Down here they get room to be real
    cards, and the sidebar gets short enough to actually stick.

    Props
      related  a collection of Packages (may be empty)
--}}
@props(['related'])

@if($related->isNotEmpty())
    <section class="hajj-related">
        <div class="container">
            <span class="hajj-eyebrow">Also Consider</span>
            <h2 class="hajj-related-title">Related Packages</h2>

            <div class="hajj-related-grid">
                @foreach($related as $ubRelated)
                    <a href="{{ route('packages.show', [$ubRelated->category->slug, $ubRelated->slug]) }}" class="hajj-related-card">
                        {{-- These are package cards, so they get the same
                             data-derived photograph the listing gives a package:
                             the city it arrives in, or the destination named in a
                             tourism package. An uploaded cover still wins. --}}
                        @php $ubRelatedPhoto = \App\Support\SiteImagery::forPackage($ubRelated); @endphp
                        @if($ubRelated->cover_image || \App\Support\SiteImagery::has($ubRelatedPhoto))
                            <span class="photo-media hajj-related-photo">
                                <x-photo :image="$ubRelated->cover_image" :key="$ubRelatedPhoto"
                                         :alt="$ubRelated->name" sizes="(min-width: 768px) 30vw, 92vw" />
                            </span>
                        @endif
                        @if($ubRelated->code)<span class="hajj-related-code">{{ $ubRelated->code }}</span>@endif
                        <span class="hajj-related-name">{{ $ubRelated->name }}</span>
                        <span class="hajj-related-meta">
                            @if($ubRelated->duration_label){{ $ubRelated->duration_label }}@endif
                            @if($ubRelated->starting_price)
                                <span class="hajj-related-price">from {{ $ubRelated->currency === 'USD' ? 'US$' : $ubRelated->currency.' ' }}{{ number_format($ubRelated->starting_price) }}</span>
                            @endif
                        </span>
                        <span class="hajj-related-go" aria-hidden="true">View Details<i class="bi bi-arrow-right"></i></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
