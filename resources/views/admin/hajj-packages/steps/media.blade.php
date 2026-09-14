<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 12</div>
    <h2 id="step-title-media">Photos &amp; search engines</h2>
    <p>The main photo, extra photos, and how the package appears on Google and when shared.</p>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-image" aria-hidden="true"></i>Main photos</h3>
        <p>JPG, PNG or WebP, up to 4 MB. A wide (landscape) photo of at least 1600 × 900 pixels looks best. Without a main photo the website uses a suitable stock photograph.</p>
    </header>
    <div class="builder-card-body row g-4">
        @foreach(['cover_image' => ['Main photo', 'Shown at the top of the package page and on package cards.'], 'social_image' => ['Social sharing image', 'Shown when the page is shared on WhatsApp or Facebook. Uses the main photo when empty.']] as $field => [$label, $help])
            <div class="col-md-6" data-image-field>
                <label for="pkg-{{ $field }}" class="form-label">{{ $label }}</label>
                <input type="file" id="pkg-{{ $field }}" name="{{ $field }}" accept="image/jpeg,image/png,image/webp" class="form-control @error($field) is-invalid @enderror">
                <x-admin.error :name="$field" />
                <div class="form-help">{{ $help }}</div>
                <div class="image-preview" @if(! $package->{$field}) hidden @endif>
                    <img src="{{ $package->{$field} ? Storage::url($package->{$field}) : '' }}" alt="{{ $label }} preview" data-image-preview>
                    @if($package->{$field})
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_{{ $field }}" value="1" id="remove-{{ $field }}">
                            <label class="form-check-label small" for="remove-{{ $field }}">Remove this photo</label>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-images" aria-hidden="true"></i>More photos and videos</h3>
        <button type="button" class="btn btn-sm btn-primary" data-add-row="media"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add photo</button>
        <p>Up to 8 MB each. "Describe the photo" helps visitors using screen readers and helps Google.</p>
    </header>
    <div class="builder-card-body">
        <div data-rows="media">
            @foreach($state['media'] ?? [] as $i => $row)
                @include('admin.hajj-packages.rows.media', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($state['media'] ?? [])) hidden @endif>No extra photos.</div>
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-google" aria-hidden="true"></i>Search engines</h3>
        <p>Optional. When empty, the package title and short description are used.</p>
    </header>
    <div class="builder-card-body row g-3">
        <div class="col-lg-7">
            <label for="pkg-meta-title" class="form-label">Title on Google</label>
            <input type="text" id="pkg-meta-title" name="meta_title" value="{{ $state['meta_title'] }}" maxlength="255" class="form-control @error('meta_title') is-invalid @enderror" data-char-count="60" data-seo-title>
            <div class="d-flex justify-content-between"><div class="form-help">About 60 characters fit.</div><span class="char-count" data-char-count-for="pkg-meta-title"></span></div>

            <label for="pkg-meta-description" class="form-label mt-3">Description on Google</label>
            <textarea id="pkg-meta-description" name="meta_description" rows="3" maxlength="500" class="form-control @error('meta_description') is-invalid @enderror" data-char-count="160" data-seo-description>{{ $state['meta_description'] }}</textarea>
            <div class="d-flex justify-content-between"><div class="form-help">About 160 characters fit.</div><span class="char-count" data-char-count-for="pkg-meta-description"></span></div>

            <label for="pkg-slug" class="form-label mt-3">Web address</label>
            <div class="input-group">
                <span class="input-group-text small">/hajj/</span>
                <input type="text" id="pkg-slug" name="slug" value="{{ $state['slug'] }}" maxlength="255" class="form-control @error('slug') is-invalid @enderror" pattern="[a-z0-9\-_]*" data-seo-slug placeholder="made from the title">
            </div>
            <x-admin.error name="slug" />
            <div class="form-help">
                Leave empty to make it from the title. Only lowercase letters, numbers and dashes.
                @if($isPublished)<strong class="text-warning-emphasis d-block mt-1"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> This package is live — changing its web address breaks links people have already shared.</strong>@endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="form-label">Preview</div>
            <div class="seo-preview" aria-hidden="true">
                <div class="seo-preview-url">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'universalbrothers' }} › hajj › <span data-seo-preview-slug>{{ $state['slug'] }}</span></div>
                <div class="seo-preview-title" data-seo-preview-title>{{ $state['meta_title'] ?: ($state['name'] ?: 'Package title') }}</div>
                <div class="seo-preview-description" data-seo-preview-description>{{ $state['meta_description'] ?: ($state['summary'] ?: 'The short description appears here.') }}</div>
            </div>
        </div>
    </div>
</div>
