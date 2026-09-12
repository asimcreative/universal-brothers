{{--
    In-page navigation for a page that is legitimately long.

    The section list comes from the presenter, built from the same conditions
    the template uses to decide whether to render each section — so the nav can
    never advertise a link to a section that was skipped for lack of data.

    Deliberately NOT sticky: the site header is already sticky, and a second
    sticky bar underneath it eats a third of a phone's viewport. It scrolls
    horizontally on narrow screens instead.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubSections = $hajj->sections(); @endphp

@if(count($ubSections) > 3)
    <nav class="hajj-jump" aria-label="Sections of this package">
        <div class="container">
            <ul class="hajj-jump-list">
                @foreach($ubSections as $ubSection)
                    <li><a href="#{{ $ubSection['id'] }}" class="hajj-jump-link">{{ $ubSection['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
