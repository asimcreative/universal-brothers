@props(['package'])

<div class="card package-card reveal-on-scroll">
    <div class="package-card-img-wrap">
        @if($package->cover_image)
            <img src="{{ Storage::url($package->cover_image) }}" class="package-card-img" alt="{{ $package->name }}" loading="lazy">
        @else
            <div class="package-card-img visual-placeholder">
                <i class="bi bi-image"></i>
                <span>Photo Coming Soon</span>
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
        <div class="package-card-meta">
            @if($package->duration_label)<span><i class="bi bi-calendar-event"></i>{{ $package->duration_label }}</span>@endif
            @if($package->series)<span><i class="bi bi-tag"></i>{{ $package->series->name }}</span>@endif
            @if($package->isHajj())
                @if(!is_null($package->medinah_first))<span><i class="bi bi-geo-alt"></i>{{ $package->medinah_first ? 'Madinah First' : 'Makkah First' }}</span>@endif
                @if(!is_null($package->is_shifting))<span><i class="bi bi-arrow-left-right"></i>{{ $package->is_shifting ? 'Shifting' : 'Non-Shifting' }}</span>@endif
                @if($package->has_aziziya)<span><i class="bi bi-building"></i>Aziziya</span>@endif
            @endif
        </div>
        @if($package->publicSummary())
            <p class="small text-secondary">{{ Str::limit($package->publicSummary(), 110) }}</p>
        @endif
        <div class="mt-auto d-flex justify-content-between align-items-end pt-2">
            <span class="package-card-price">
                @if($package->starting_price)
                    <small>From</small>{{ $package->currency === 'USD' ? 'US$' : 'PKR ' }}{{ number_format($package->starting_price) }}
                @else
                    <small>&nbsp;</small>Price on request
                @endif
            </span>
            <a href="{{ route('packages.show', [$package->category->slug, $package->slug]) }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
    </div>
</div>
