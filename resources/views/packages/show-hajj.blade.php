@extends('layouts.app')

@section('title', ($package->meta_title ?: $package->name . ' | Universal Brothers'))
@section('meta_description', $package->meta_description ?: Str::limit($package->publicSummary() ?? '', 160))

{{--
    Hajj package detail.

    This template is deliberately thin. Every decision about WHAT to show is
    made by App\Support\HajjPackagePresenter from the package's own rows, and
    every decision about HOW to show it lives in the `x-hajj.*` components. The
    twelve published packages do not share a structure — three have no variants
    at all, six vary their Madinah hotel while two vary Makkah, Aziziya is
    included on four and optional on eight, and one has no Makkah hotel row —
    so a layout that assumed any of that would be wrong on most of the
    catalogue. There is no per-package branching anywhere in this file, and a
    thirteenth package added through the admin tomorrow renders correctly with
    no template work.
--}}

@push('head')
    @php $ubShareImage = $package->social_image ?: $package->cover_image; @endphp
    @if($ubShareImage)
        <meta property="og:image" content="{{ url(Storage::url($ubShareImage)) }}">
    @endif
    @if($preview ?? false)
        <meta name="robots" content="noindex, nofollow">
    @endif
    {{-- Structured data built only from fields the package actually holds. No
         rating, no review count and no availability are emitted, because the
         site has no data for them and inventing them would be a policy
         violation as well as a lie. --}}
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $package->name,
            'sku' => $package->code,
            'description' => $package->publicSummary(),
            'category' => $package->category->name,
            'brand' => ['@type' => 'Organization', 'name' => 'Universal Brothers (Pvt) Ltd'],
            'image' => $package->cover_image ? url(Storage::url($package->cover_image)) : null,
            'offers' => $hajj->startingFrom() ? [
                '@type' => 'Offer',
                'price' => $hajj->startingFrom()['amount'],
                'priceCurrency' => $hajj->startingFrom()['currency'],
                'url' => url()->current(),
            ] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@if($preview ?? false)
    @push('before_header')
        {{-- An administrator's signed preview. Never reachable by visitors:
             the route needs both an admin session and a valid signature. --}}
        <div class="admin-preview-banner" role="status">
            <span><i class="bi bi-eye" aria-hidden="true"></i> <strong>Preview</strong> — this is how the package will look.
                {{ $package->isPublished() ? 'It is live on the website.' : 'It is a draft and visitors cannot see it.' }}</span>
            <a href="{{ $previewEditUrl }}">Back to editing</a>
        </div>
    @endpush
@endif

@section('content')
    <x-hajj.hero :hajj="$hajj" />
    <x-hajj.summary :hajj="$hajj" />
    <x-hajj.jump-nav :hajj="$hajj" />

    <div class="hajj-detail">
        <div class="container">
            <div class="row g-4 g-xl-5">
                <div class="col-lg-8">
                    @if($package->description)
                        <div class="hajj-intro">
                            <p>{{ $package->description }}</p>
                        </div>
                    @endif

                    <x-hajj.journey :hajj="$hajj" />

                    {{-- Order is the decision the customer actually makes:
                         which option, then which room, then where they stay,
                         then what happens day by day, then the terms. The old
                         page put six room prices first and only explained what
                         "Package A" and "Package B" meant underneath them. --}}
                    <x-hajj.pricing :hajj="$hajj" />
                    <x-hajj.accommodation :hajj="$hajj" />
                    <x-hajj.aziziya :hajj="$hajj" />
                    <x-hajj.mashaer :hajj="$hajj" />
                    <x-hajj.itinerary :hajj="$hajj" />
                    <x-hajj.transport :hajj="$hajj" />
                    <x-hajj.meals :hajj="$hajj" />
                    <x-hajj.inclusions :hajj="$hajj" />
                    <x-hajj.upgrades :hajj="$hajj" />
                    <x-hajj.notes :hajj="$hajj" />
                    <x-hajj.gallery :hajj="$hajj" />
                </div>

                <div class="col-lg-4">
                    <x-hajj.aside :hajj="$hajj" />
                </div>
            </div>
        </div>
    </div>

    <x-hajj.related :related="$related" />

    <x-page-cta />

    <x-hajj.action-bar :hajj="$hajj" />
@endsection
