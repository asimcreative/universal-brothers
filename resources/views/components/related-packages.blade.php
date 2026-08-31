@props(['related'])

@if($related->isNotEmpty())
    <h3 class="h6 mt-4 mb-3">Related Packages</h3>
    @foreach($related as $r)
        <a href="{{ route('packages.show', [$r->category->slug, $r->slug]) }}" class="d-block text-decoration-none mb-2 p-2 border rounded">
            <span class="fw-semibold text-dark">{{ $r->name }}</span>
            <span class="d-block small text-muted">{{ $r->duration_label }}</span>
        </a>
    @endforeach
@endif
