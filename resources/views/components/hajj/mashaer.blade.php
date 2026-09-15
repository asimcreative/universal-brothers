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
                @php $ubPhoto = \App\Support\SiteImagery::forMashaer($ubRow->location); @endphp
                <article class="hajj-mashaer-card">
                    {{-- Mina, Arafat and Muzdalifah are places a pilgrim can
                         actually be shown. The Mina photograph is the real
                         air-conditioned tent city, which is precisely what the
                         Tent Type / Air Conditioning / Maktab fields below
                         describe — the image and the data say the same thing. --}}
                    @if($ubPhoto)
                        <div class="photo-media hajj-mashaer-photo">
                            <x-photo :key="$ubPhoto" sizes="(min-width: 768px) 30vw, 92vw" />
                        </div>
                    @endif

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
                        <div class="hajj-mashaer-note rich-text">{!! \App\Support\Content\RichText::render($ubRow->other_services, 'basic') !!}</div>
                    @endif
                    @if($ubRow->notes)
                        <div class="hajj-mashaer-note rich-text">{!! \App\Support\Content\RichText::render($ubRow->notes, 'basic') !!}</div>
                    @endif
                </article>
            @endforeach
        </div>
    </x-hajj.section>
@endif
