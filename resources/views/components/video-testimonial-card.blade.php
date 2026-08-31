@props(['testimonial'])

<div class="video-testimonial-card reveal-on-scroll" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#testimonialVideoModal{{ $testimonial->id }}">
    <div class="video-testimonial-thumb" @if($testimonial->video_thumbnail) style="background-image:url('{{ Storage::url($testimonial->video_thumbnail) }}')" @endif>
        <span class="video-play-icon"><i class="bi bi-play-fill"></i></span>
    </div>
    <div class="p-3">
        <div class="fw-semibold">{{ $testimonial->name }}</div>
        <div class="small text-muted text-capitalize">{{ $testimonial->package_label ?? $testimonial->service_tag }}</div>
    </div>
</div>
<div class="modal fade" id="testimonialVideoModal{{ $testimonial->id }}" tabindex="-1" aria-labelledby="testimonialVideoModalLabel{{ $testimonial->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-black">
            <div class="modal-header border-0">
                <h2 class="modal-title visually-hidden" id="testimonialVideoModalLabel{{ $testimonial->id }}">{{ $testimonial->name }}'s Story</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close video"></button>
            </div>
            <div class="modal-body p-0 ratio ratio-16x9">
                <iframe src="{{ $testimonial->video_url }}" title="{{ $testimonial->name }}'s Story" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>
