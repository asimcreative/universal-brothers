{{--
    One band of the Hajj package detail page.

    Every section on the old page was hand-rolled — `<h2 class="h4 mt-4 mb-3">`
    here, `<h2 class="h5 mt-4 mb-3">` with a stray icon there — so the page had
    no consistent rhythm and the heading levels drifted. One component now owns
    the spacing, the eyebrow, the heading level and the lead line, which is
    also what lets the page be reordered without re-tuning margins.

    Props
      id       anchor target, and the key the in-page nav jumps to
      eyebrow  small caps kicker above the heading
      title    the section's h2
      lead     one supporting sentence, rendered under the heading
--}}
@props([
    'id' => null,
    'eyebrow' => null,
    'title',
    'lead' => null,
])

<section @if($id) id="{{ $id }}" @endif {{ $attributes->class('hajj-section') }}>
    <div class="hajj-section-head">
        @if($eyebrow)
            <span class="hajj-eyebrow">{{ $eyebrow }}</span>
        @endif

        <h2 class="hajj-section-title">{{ $title }}</h2>

        @if($lead)
            <p class="hajj-section-lead">{{ $lead }}</p>
        @endif
    </div>

    {{ $slot }}
</section>
