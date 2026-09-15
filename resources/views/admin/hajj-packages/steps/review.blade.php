<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 13</div>
    <h2 id="step-title-review">Review everything</h2>
    <p>The whole package on one page. Red problems must be fixed before publishing; amber suggestions are worth checking. Use "Edit" beside any section to go straight to it.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'review'])

<p class="visually-hidden" role="status" aria-live="polite" data-review-announce></p>

<div class="review-body" data-review-body>
    @include('admin.hajj-packages.partials.review-body', ['review' => $review, 'steps' => $steps])
</div>

<p class="form-help mt-2"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> This page updates as you change the package. "Final review completed" ticks while you look at this step with no red problems, and is kept when you save. If the package changes afterwards, it un-ticks until you review again.</p>
