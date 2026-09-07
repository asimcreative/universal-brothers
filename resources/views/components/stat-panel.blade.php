{{--
    A statistic presented as a composed panel rather than a bare number.

    On the live site these sections were a 50/50 split where one half held a
    single numeral floating in an otherwise empty column — several hundred
    pixels of blank white or blank cream per section, and one of the clearest
    reasons the page read as unfinished. Pairing the figure with the generated
    geometric composition gives that half real visual weight without inventing
    any content.

    Props
      display  approved public string, e.g. "20+"
      target   integer to count up to (optional; derived from display)
      label    caption under the figure
      note     optional supporting line
      variant  which generated composition to use (0-7)
--}}
@props([
    'display',
    'target' => null,
    'label',
    'note' => null,
    'variant' => 1,
])

<div {{ $attributes->class(['stat-panel']) }}>
    <div class="ub-visual ub-visual--v{{ $variant }} stat-panel-bg" aria-hidden="true"></div>
    <div class="stat-panel-body">
        <x-stat-number :display="$display" :target="$target" />
        <div class="stat-panel-label">{{ $label }}</div>
        @if($note)
            <p class="stat-panel-note">{{ $note }}</p>
        @endif
    </div>
</div>
