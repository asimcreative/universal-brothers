{{--
    Meals, grouped by where you are when you eat them.

    The old page printed a flat, deduplicated list of every meal_plan string
    on the package, so "Half board" appeared once with no indication of which
    city it applied to. The presenter merges the hotel rows and the Mina/Arafat
    rows and keys them by location instead.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubMeals = $hajj->mealsByLocation(); @endphp

@if($ubMeals->isNotEmpty())
    <x-hajj.section id="meals" eyebrow="Full Board Arrangements" title="Meals">
        <dl class="hajj-facts hajj-facts--wide">
            @foreach($ubMeals as $ubLocation => $ubPlan)
                <div class="hajj-fact">
                    <dt>{{ $hajj->locationLabel($ubLocation) }}</dt>
                    <dd>{{ $ubPlan }}</dd>
                </div>
            @endforeach
        </dl>
    </x-hajj.section>
@endif
