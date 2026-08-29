@props(['testimonial'])

<div class="card h-100 border-0 shadow-sm reveal-on-scroll">
    <div class="card-body">
        <div class="mb-2 text-secondary">
            @for($i = 0; $i < 5; $i++)
                <i class="bi {{ $i < ($testimonial->rating ?? 5) ? 'bi-star-fill' : 'bi-star' }}"></i>
            @endfor
        </div>
        <p class="fst-italic">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
        <div class="d-flex align-items-center mt-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;">
                {{ Str::substr($testimonial->name, 0, 1) }}
            </div>
            <div>
                <div class="fw-semibold">{{ $testimonial->name }}</div>
                <div class="small text-muted text-capitalize">{{ $testimonial->service_tag }}</div>
            </div>
        </div>
    </div>
</div>
