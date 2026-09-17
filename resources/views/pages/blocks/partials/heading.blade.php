{{-- Eyebrow, heading and introduction shared by most sections. Needs $data; optional $center.

     Uses the same section header as the rest of the site, so a page built in
     the admin has the same rhythm and reading measure as a coded page. --}}
@php($center = $center ?? true)
@if(filled($data['eyebrow'] ?? null) || filled($data['heading'] ?? null) || filled($data['intro'] ?? null))
    <x-section-header
        :eyebrow="$data['eyebrow'] ?? null"
        :title="$data['heading'] ?? ''"
        :lead="$data['intro'] ?? null"
        :align="$center ? 'center' : 'start'"
        class="pb-heading reveal-on-scroll" />
@endif
