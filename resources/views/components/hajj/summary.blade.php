{{--
    The at-a-glance strip directly under the hero.

    Everything here comes from HajjPackagePresenter::quickFacts(), which only
    emits a tile for a field the package genuinely has — so UB023, which has no
    Makkah accommodation row at all, shows no "Makkah Stay" tile rather than an
    empty or invented one.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubFacts = $hajj->quickFacts(); @endphp

@if($ubFacts)
    <div class="hajj-summary">
        <div class="container">
            <dl class="quick-overview">
                @foreach($ubFacts as $ubFact)
                    <div class="quick-overview-item">
                        <i class="bi {{ $ubFact['icon'] }}" aria-hidden="true"></i>
                        <dt class="quick-overview-label">{{ $ubFact['label'] }}</dt>
                        <dd class="quick-overview-value">{{ $ubFact['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
@endif
