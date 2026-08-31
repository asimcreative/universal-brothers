@props(['award'])

<div class="col-md-4 col-lg-2 text-center reveal-on-scroll">
    <div class="award-badge">
        @if($award->image)
            <img src="{{ Storage::url($award->image) }}" alt="{{ $award->name }}" class="img-fluid">
        @else
            <i class="bi bi-trophy-fill"></i>
        @endif
    </div>
    <p class="small fw-semibold mb-0 mt-2">{{ $award->name }}</p>
</div>
