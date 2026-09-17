{{--
    One fact, in one small label: "13 Days", "Madinah First", "Non-Shifting",
    "Aziziya", "5 Star".

    Package attributes used to be written into card markup as sentences, which
    took a paragraph to say what a row of chips says at a glance. Chips are
    always built from real package fields by the card component — never typed
    in by hand — so a chip cannot claim something the data does not.

    @param icon   optional Bootstrap Icon name, decorative
    @param tone   'default' (sand) | 'gold' (Featured) | 'dark' (on navy)
--}}
@props(['icon' => null, 'tone' => 'default'])

<span {{ $attributes->class(['ub-chip', 'ub-chip-' . $tone]) }}>
    @if($icon)
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
