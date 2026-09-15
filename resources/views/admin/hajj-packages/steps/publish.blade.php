<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 14</div>
    <h2 id="step-title-publish">Save, preview &amp; publish</h2>
    <p>Choose how to finish. Nothing reaches the website until you publish, and a published package can be moved back to draft at any time.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'publish'])

@php $blocked = ! $review->canPublish(); @endphp

<div class="finish-cards">
    <div class="finish-card">
        <span class="finish-card-icon" aria-hidden="true"><i class="bi bi-save"></i></span>
        @if($isPublished)
            <h3>Save changes</h3>
            <p>This package is <strong>live</strong>. Saving updates the website straight away.</p>
            <button type="submit" class="btn btn-primary" name="_intent" value="save"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save changes</button>
        @else
            <h3>Save as draft</h3>
            <p>Keeps everything without showing it on the website. Drafts can be unfinished; the package reopens on the step you were on.</p>
            <button type="submit" class="btn btn-outline-secondary" name="_intent" value="draft"><i class="bi bi-save me-1" aria-hidden="true"></i>Save draft</button>
        @endif
    </div>

    <div class="finish-card">
        <span class="finish-card-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <h3>Preview package</h3>
        <p>Saves, then opens the page exactly as a visitor would see it, with a yellow "Preview" bar. Check the prices in USD, SAR and PKR.</p>
        <button type="submit" class="btn btn-outline-primary" name="_intent" value="preview"><i class="bi bi-eye me-1" aria-hidden="true"></i>Save &amp; preview</button>
    </div>

    <div class="finish-card {{ $isPublished ? 'is-live' : '' }}">
        <span class="finish-card-icon" aria-hidden="true"><i class="bi bi-globe2"></i></span>
        @if($isPublished)
            <h3>Live on the website</h3>
            <p>To hide it, use More → Move to draft at the top of the page.</p>
            @if($package->slug)
                <a href="{{ route('packages.show', ['category' => 'hajj', 'package' => $package->slug]) }}" class="btn btn-outline-success" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>View on website</a>
            @endif
        @else
            <h3>Publish package</h3>
            <p>Puts the package on the Hajj page straight away. Only possible when the Review step shows no red problems.</p>
            <button type="submit" class="btn btn-primary" name="_intent" value="publish" data-publish-button
                    data-confirm-publish="Once published, this package appears on the website straight away."
                    @if($blocked) disabled aria-describedby="publish-blockers" @endif>
                <i class="bi bi-globe2 me-1" aria-hidden="true"></i>Publish package
            </button>
            <div id="publish-blockers" class="publish-blockers" data-publish-blockers @unless($blocked) hidden @endunless>
                <strong>Fix these first:</strong>
                <ul>
                    @foreach($review->problems() as $problem)
                        <li><a href="#step-{{ $problem['step'] }}" data-step-link="{{ $problem['step'] }}">{{ $problem['message'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
