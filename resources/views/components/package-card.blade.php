@props(['package'])

<div class="card package-card reveal-on-scroll">
    <div class="position-relative">
        @if($package->cover_image)
            <img src="{{ Storage::url($package->cover_image) }}" class="card-img-top package-card-img" alt="{{ $package->name }}" loading="lazy">
        @else
            <div class="package-card-img d-flex align-items-center justify-content-center text-muted">
                <i class="bi bi-image fs-1"></i>
            </div>
        @endif
        @if($package->is_featured)
            <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Featured</span>
        @endif
        @if($package->code)
            <span class="badge bg-dark position-absolute top-0 start-0 m-2">{{ $package->code }}</span>
        @endif
    </div>
    <div class="card-body d-flex flex-column">
        <h3 class="h5">{{ $package->name }}</h3>
        <p class="text-muted small mb-2">
            @if($package->duration_label)<i class="bi bi-calendar-event me-1"></i>{{ $package->duration_label }}@endif
            @if($package->series)<span class="ms-2"><i class="bi bi-tag me-1"></i>{{ $package->series->name }}</span>@endif
        </p>
        @if($package->summary)
            <p class="small text-secondary">{{ Str::limit($package->summary, 110) }}</p>
        @endif
        <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary">
                @if($package->starting_price)
                    From {{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($package->starting_price) }}
                @else
                    Price on request
                @endif
            </span>
            <a href="{{ route('packages.show', [$package->category->slug, $package->slug]) }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
    </div>
</div>
