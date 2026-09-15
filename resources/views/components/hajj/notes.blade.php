{{--
    Terms and conditions the customer has to see before enquiring.

    Ordered important-first by the presenter, since a note flagged
    `is_important` is usually a cancellation or price-change condition and
    burying it fourth in a list of eight defeats the point of the flag.

    Props
      hajj  the presenter
--}}
@props(['hajj'])

@php $ubNotes = $hajj->importantNotes(); @endphp

@if($ubNotes->isNotEmpty())
    <x-hajj.section id="notes" eyebrow="Please Read" title="Important Notes">
        <div class="hajj-notes">
            @foreach($ubNotes as $ubNote)
                <div class="package-note @if($ubNote->is_important) package-note-important @endif">
                    @if($ubNote->title)<strong class="package-note-title">{{ $ubNote->title }}</strong>@endif
                    <div class="package-note-text rich-text">{!! \App\Support\Content\RichText::render($ubNote->content, 'basic') !!}</div>
                </div>
            @endforeach
        </div>
    </x-hajj.section>
@endif
