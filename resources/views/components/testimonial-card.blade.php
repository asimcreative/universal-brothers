@props(['testimonial'])

<div class="testimonial-card reveal-on-scroll">
    {{-- Decorative opening quote mark — the card previously opened with a row
         of grey stars, which read as a disabled control rather than a rating. --}}
    <span class="testimonial-quote-mark" aria-hidden="true">&ldquo;</span>

    <div class="testimonial-rating" aria-label="{{ $testimonial->rating ?? 5 }} out of 5">
        @for($i = 0; $i < 5; $i++)
            <i class="bi {{ $i < ($testimonial->rating ?? 5) ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
        @endfor
    </div>

    <blockquote class="testimonial-quote">{{ $testimonial->quote }}</blockquote>

    <div class="testimonial-author">
        <span class="testimonial-avatar" aria-hidden="true">{{ Str::substr($testimonial->name, 0, 1) }}</span>
        <span>
            <span class="testimonial-author-name">{{ $testimonial->name }}</span>
            <span class="testimonial-author-tag">{{ $testimonial->service_tag }}</span>
        </span>
    </div>
</div>
