{{--
    Back to top.

    Sits directly above the assistant launcher rather than beside it: both are
    fixed to the bottom-right corner, and at the same height they would touch.
    The offset in `nav.css` is the launcher's own `bottom` plus its height plus
    a gap, so the two move together if either is ever resized.

    Hidden — with the `hidden` attribute, so it is out of the tab order too —
    until there is enough page behind the reader for it to be worth offering.
--}}
<button type="button" class="ub-to-top" data-ub-to-top hidden aria-label="{{ __('Back to top') }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5"></path>
        <path d="m5 12 7-7 7 7"></path>
    </svg>
</button>
