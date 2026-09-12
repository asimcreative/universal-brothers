{{--
    Package options and room pricing — the section the whole redesign is for.

    Order matters here and used to be backwards: the live page listed six room
    prices first and only afterwards explained what "Package A" and "Package B"
    meant. The explanation now comes first, and each price sits inside the
    option it belongs to.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubPackage = $hajj->package();
    $ubGroups = $hajj->optionGroups();
    $ubVariants = $ubPackage->variants->sortBy('sort_order');

    // The lead sentence is assembled from the package's own variant codes and
    // from what those variants actually differ on, so it stays true for a
    // package that varies its Madinah hotel (UB004, UB006, UB008, UB010) as
    // much as for one that varies Makkah (UB001, UB003).
    $ubLead = $hajj->hasChoice()
        ? $ubVariants->map(fn ($v) => 'Package '.$v->code)->join(', ', ' and ')
            .' are the same journey — only the '.$hajj->choiceScope().' changes. '
            .'Every price below sits under the option it belongs to.'
        : 'Every room type published for this package, with the price it is sold at.';
@endphp

@if($ubGroups->isNotEmpty())
    <x-hajj.section
        id="pricing"
        :eyebrow="$hajj->hasChoice() ? 'Package Options' : null"
        title="Room Type Pricing"
        :lead="$ubLead">

        <x-hajj.currency-switcher :hajj="$hajj" />

        @if($hajj->hasChoice())
            <h3 class="hajj-choice-title">{{ $hajj->choiceHeading() }}</h3>
        @endif

        {{-- Both options are always on screen together rather than behind
             tabs. The customer's job here is to COMPARE, and a tab hides
             exactly the half they need to compare against — it also hides
             real published prices from anyone without JS. --}}
        <div class="hajj-options" data-option-count="{{ $ubGroups->count() }}">
            @foreach($ubGroups as $ubGroup)
                <x-hajj.option-card :group="$ubGroup" :hajj="$hajj" />
            @endforeach
        </div>

        <p class="hajj-fineprint">
            Prices are per person unless a room states otherwise, and are subject to change until your booking is confirmed. Book early.
        </p>
    </x-hajj.section>
@endif
