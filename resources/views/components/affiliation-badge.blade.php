@props(['affiliation'])

<div class="col-4 col-md-2 text-center reveal-on-scroll">
    @if($affiliation->logo)
        <img src="{{ Storage::url($affiliation->logo) }}" alt="{{ $affiliation->organization_name }}" class="img-fluid affiliation-logo">
    @else
        <span class="affiliation-badge">{{ $affiliation->organization_name }}</span>
    @endif
</div>
