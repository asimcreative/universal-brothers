{{--
    Package detail hero.

    Carries the real cover photograph when one exists and the generated
    geometric stage when it does not. The badge row is built only from fields
    the package actually holds, so a package missing `season_label` or
    `is_shifting` simply shows fewer badges rather than an empty chip.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubPackage = $hajj->package();
    $ubFrom = $hajj->startingFrom();
@endphp

<x-page-hero
    class="package-hero"
    :eyebrow="$ubPackage->series?->name"
    :title="$ubPackage->name"
    :lead="$ubPackage->publicSummary()"
    :breadcrumbs="['Home' => route('home'), $ubPackage->category->name => route('packages.category', $ubPackage->category->slug), $ubPackage->name => null]">

    @if($ubPackage->cover_image)
        <img src="{{ Storage::url($ubPackage->cover_image) }}" alt="{{ $ubPackage->name }}" class="package-hero-photo" decoding="async">
    @endif

    {{-- Identity chips only. The badge row used to also carry duration,
         arrival, shifting and Aziziya — every one of which the at-a-glance
         strip repeats verbatim about 200px further down the same screen, so
         four of the seven badges were pure duplication. Those facts belong in
         the strip, which labels them; these three identify the product. --}}
    <div class="package-hero-badges">
        @if($ubPackage->code)<span class="pkg-badge pkg-badge-featured">{{ $ubPackage->code }}</span>@endif
        @if($ubPackage->package_type)<span class="pkg-badge pkg-badge-outline">{{ $ubPackage->package_type }}</span>@endif
        @if($ubPackage->season_label)<span class="pkg-badge pkg-badge-outline">{{ $ubPackage->season_label }}</span>@endif
    </div>

    {{-- Price and the two things a customer wants to do next, together. The
         old hero showed the price and then left them to scroll for a way to
         act on it. --}}
    <div class="package-hero-cta">
        @if($ubFrom)
            <p class="package-hero-price">
                <span>From</span>{{ $hajj->money($ubFrom['amount'], $ubFrom['currency']) }}
                <small>per person</small>
            </p>
        @endif

        <div class="package-hero-actions">
            <a href="#enquire" class="btn btn-secondary btn-lg">Enquire About This Package</a>
            @if($ubPackage->roomOptions->isNotEmpty())
                <a href="#pricing" class="btn btn-outline-light btn-lg">See Prices</a>
            @endif
        </div>
    </div>
</x-page-hero>
