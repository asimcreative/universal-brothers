{{--
    What the price covers, and what it does not — side by side, because a
    customer comparing two operators reads them together.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php
    $ubPackage = $hajj->package();
    $ubIn = $ubPackage->inclusions;
    $ubOut = $ubPackage->exclusions;
@endphp

@if($ubIn->isNotEmpty() || $ubOut->isNotEmpty())
    <x-hajj.section id="included" eyebrow="Before You Book" title="What's Included">
        <div class="hajj-inex">
            @if($ubIn->isNotEmpty())
                <div class="hajj-inex-col hajj-inex-col--in">
                    <h3 class="hajj-inex-title"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Included</h3>
                    <ul class="hajj-tick-list hajj-tick-list--in">
                        @foreach($ubIn as $ubItem)
                            <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>{{ $ubItem->description }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($ubOut->isNotEmpty())
                <div class="hajj-inex-col hajj-inex-col--out">
                    <h3 class="hajj-inex-title"><i class="bi bi-x-circle-fill" aria-hidden="true"></i>Not Included</h3>
                    <ul class="hajj-tick-list hajj-tick-list--out">
                        @foreach($ubOut as $ubItem)
                            <li><i class="bi bi-x-circle" aria-hidden="true"></i><span>{{ $ubItem->description }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-hajj.section>
@endif
