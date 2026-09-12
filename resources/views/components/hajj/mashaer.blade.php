{{--
    Mina, Arafat and Muzdalifah — the days of Hajj itself.

    The old page rendered each of these as a bare `<ul>` of "Label: value"
    lines under an `<h2>`, which read like a database dump. Same data, now a
    card per location with a definition grid, and only the fields the row
    actually holds.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubRows = $hajj->mashaerOrdered(); @endphp

@if($ubRows->isNotEmpty())
    <x-hajj.section
        id="mashaer"
        eyebrow="Days of Hajj"
        :title="$hajj->mashaerTitle()"
        lead="Where you stay and what is arranged for you during the days of Hajj.">

        <div class="hajj-mashaer">
            @foreach($ubRows as $ubRow)
                <article class="hajj-mashaer-card">
                    <h3 class="hajj-mashaer-place">{{ Str::title($ubRow->location) }}</h3>

                    @php $ubFacts = $hajj->mashaerFacts($ubRow); @endphp
                    @if($ubFacts)
                        <dl class="hajj-facts">
                            @foreach($ubFacts as $ubFact)
                                <div class="hajj-fact">
                                    <dt>{{ $ubFact['label'] }}</dt>
                                    <dd>{{ $ubFact['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if($ubRow->other_services)
                        <p class="hajj-mashaer-note">{{ $ubRow->other_services }}</p>
                    @endif
                    @if($ubRow->notes)
                        <p class="hajj-mashaer-note">{{ $ubRow->notes }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </x-hajj.section>
@endif
