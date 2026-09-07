@extends('layouts.app')

@section('title', 'Awards & Recognition | Universal Brothers')
@section('meta_description', 'Awards and industry recognition earned by Universal Brothers (Pvt) Ltd over ' . \App\Models\SiteSetting::get('years_in_operation', '20+') . ' years of Hajj, Umrah and Tourism service.')

@section('content')
    <x-page-hero
        eyebrow="Recognized for Excellence"
        title="Excellence Recognized. Trust Earned."
        :lead="'Over ' . \App\Models\SiteSetting::get('industry_awards_count', '20+') . ' Recognitions. One Consistent Commitment.'"
        :breadcrumbs="['Home' => route('home'), 'Awards & Recognition' => null]" />

    <div class="section">
        <div class="container">
            <p class="text-secondary mx-auto text-center mb-5" style="max-width: 720px;">Awards tell part of our story. The greater achievement is maintaining the trust of our pilgrims and travellers year after year.</p>

            @if($awards->isEmpty())
                <x-empty-state icon="bi-trophy">No awards have been published yet. Please check back soon.</x-empty-state>
            @else
                {{--
                    Previously a 3-column grid where each card was 40% "AWARD
                    PHOTO COMING SOON" box, and where cards in the same row had
                    visibly different heights because only some awards carry an
                    issuing organisation. It is now a citation list: a struck
                    medallion beside the award's own details, so a row can never
                    go ragged and no card is mostly empty space.
                --}}
                <div class="award-citation-list">
                    @foreach($awards as $award)
                        @php
                            $ubVariant = crc32((string) $award->id . $award->name) % 8;
                            $ubBase = trim(preg_replace('/\s*\([^)]*\)/', '', (string) $award->name));
                            $ubWords = array_values(array_filter(
                                preg_split('/\s+/', $ubBase),
                                fn ($w) => ! in_array(Str::lower($w), ['award', 'awards', 'of', 'the', 'in', 'for', 'and', '&'], true)
                            ));
                            $ubInitials = Str::upper(implode('', array_map(fn ($w) => Str::substr($w, 0, 1), array_slice($ubWords, 0, 3))));
                        @endphp
                        <article class="award-citation reveal-on-scroll">
                            <div class="award-citation-medal award-medallion-disc ub-visual ub-visual--v{{ $ubVariant }}">
                                @if($award->image)
                                    <img src="{{ Storage::url($award->image) }}" alt="{{ $award->name }}" class="award-medallion-img" loading="lazy" decoding="async">
                                @else
                                    <span class="award-medallion-initials" aria-hidden="true">{{ $ubInitials !== '' ? $ubInitials : 'UB' }}</span>
                                @endif
                            </div>
                            <div class="award-citation-body">
                                <h2 class="award-citation-title">{{ $award->name }}</h2>
                                <div class="award-citation-meta">
                                    @if($award->awarding_organization)
                                        <span><i class="bi bi-building"></i>{{ $award->awarding_organization }}</span>
                                    @endif
                                    @if($award->year)
                                        <span><i class="bi bi-calendar-event"></i>{{ $award->year }}</span>
                                    @endif
                                </div>
                                @if($award->description)
                                    <p class="award-citation-description">{{ $award->description }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-page-cta />
@endsection
