<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 1</div>
    <h2 id="step-title-basics">Basic information</h2>
    <p>The name customers see, the package code your office uses, and how long the package lasts.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'basics'])

@if($isTemplate)
    <div class="builder-card">
        <header><h3><i class="bi bi-files" aria-hidden="true"></i>About this template</h3></header>
        <div class="builder-card-body row g-3">
            <div class="col-md-7">
                <label for="template-name" class="form-label">Template name <span class="required-mark">*</span></label>
                <input type="text" id="template-name" name="template_name" value="{{ old('template_name', $template->name) }}" class="form-control @error('template_name') is-invalid @enderror" required maxlength="255" placeholder="e.g. Platinum 14 days — Madinah first">
                <x-admin.error name="template_name" />
            </div>
            <div class="col-12">
                <label for="template-description" class="form-label">When to use it</label>
                <textarea id="template-description" name="template_description" rows="2" class="form-control">{{ old('template_description', $template->description) }}</textarea>
            </div>
        </div>
    </div>
@endif

<div class="builder-card">
    <header>
        <h3><i class="bi bi-card-heading" aria-hidden="true"></i>Package details</h3>
        @if($isTemplate)<p>Optional. Anything filled in here is copied into packages created from this template.</p>@endif
    </header>
    <div class="builder-card-body row g-3">
        @unless($isTemplate)
            <div class="col-md-8">
                <label for="pkg-name" class="form-label">Package title <span class="required-mark">*</span></label>
                <input type="text" id="pkg-name" name="name" value="{{ $state['name'] }}" class="form-control @error('name') is-invalid @enderror @error('publish.basics') is-invalid @enderror" required maxlength="255" placeholder="e.g. Executive Platinum Swissotel — Madinah First" data-complete="basics">
                <x-admin.error name="name" />
                <div class="form-help">Shown as the main heading on the package page.</div>
            </div>
            <div class="col-md-4">
                <label for="pkg-code" class="form-label">Package code <span class="required-mark" title="Needed before publishing">*</span></label>
                <input type="text" id="pkg-code" name="code" value="{{ $state['code'] }}" class="form-control text-uppercase @error('code') is-invalid @enderror" maxlength="50" placeholder="e.g. UB025" data-complete="basics">
                <x-admin.error name="code" />
                <div class="form-help">Your office's reference. Must be different for every package.</div>
            </div>
        @endunless

        <div class="col-md-6">
            <label for="pkg-type" class="form-label">Package tier / short title</label>
            <input type="text" id="pkg-type" name="package_type" value="{{ $state['package_type'] }}" class="form-control" maxlength="255" placeholder="e.g. Executive Platinum">
            <div class="form-help">A short label shown on package cards.</div>
        </div>
        <div class="col-md-6">
            <label for="pkg-series" class="form-label">Package series</label>
            <select id="pkg-series" name="package_series_id" class="form-select">
                <option value="">No series</option>
                @foreach($library['series'] as $item)
                    <option value="{{ $item['id'] }}" @selected((string) $state['package_series_id'] === (string) $item['id'])>{{ $item['name'] }}</option>
                @endforeach
            </select>
            <div class="form-help">Groups similar packages together on the Hajj page.</div>
        </div>

        <div class="col-12">
            <label for="pkg-summary" class="form-label">Short description</label>
            <textarea id="pkg-summary" name="summary" rows="2" maxlength="1000" class="form-control" data-char-count="200">{{ $state['summary'] }}</textarea>
            <div class="d-flex justify-content-between">
                <div class="form-help">One or two sentences for package cards and the top of the page.</div>
                <span class="char-count" data-char-count-for="pkg-summary"></span>
            </div>
        </div>
        <div class="col-12">
            <label for="pkg-description" class="form-label">Full description</label>
            <textarea id="pkg-description" name="description" rows="4" class="form-control">{{ $state['description'] }}</textarea>
            <div class="form-help">Shown at the start of the package page. Leave empty if the details below say enough.</div>
        </div>
    </div>
</div>

<div class="builder-card">
    <header><h3><i class="bi bi-calendar3" aria-hidden="true"></i>Length and season</h3></header>
    <div class="builder-card-body row g-3">
        <div class="col-sm-6 col-md-3">
            <label for="pkg-days" class="form-label">Number of days @unless($isTemplate)<span class="required-mark" title="Needed before publishing">*</span>@endunless</label>
            <input type="number" id="pkg-days" name="duration_days" value="{{ $state['duration_days'] }}" min="1" max="60" class="form-control @error('duration_days') is-invalid @enderror" data-complete="basics">
            <x-admin.error name="duration_days" />
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="pkg-duration-label" class="form-label">Length as shown</label>
            <input type="text" id="pkg-duration-label" name="duration_label" value="{{ $state['duration_label'] }}" class="form-control" maxlength="255" placeholder="e.g. 14 Days Package" data-suggest-from="pkg-days" data-suggest-pattern="{n} Days Package">
            <div class="form-help">Filled in for you from the number of days.</div>
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="pkg-year" class="form-label">Hajj year</label>
            <input type="number" id="pkg-year" name="season_year" value="{{ $state['season_year'] }}" min="2020" max="2100" class="form-control @error('season_year') is-invalid @enderror">
            <x-admin.error name="season_year" />
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="pkg-season-label" class="form-label">Season as shown</label>
            <input type="text" id="pkg-season-label" name="season_label" value="{{ $state['season_label'] }}" class="form-control" maxlength="255" placeholder="e.g. Hajj 2027 / 1448 AH" data-suggest-from="pkg-year" data-suggest-pattern="Hajj {n}">
        </div>
    </div>
</div>

@unless($isTemplate)
    <div class="builder-card">
        <header>
            <h3><i class="bi bi-star" aria-hidden="true"></i>On the website</h3>
            <p>Publishing is done with the buttons at the bottom of the page, once the package is complete.</p>
        </header>
        <div class="builder-card-body row g-3 align-items-end">
            <div class="col-md-5">
                <input type="hidden" name="is_featured" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="pkg-featured" name="is_featured" value="1" @checked($state['is_featured'])>
                    <label class="form-check-label fw-semibold" for="pkg-featured">Feature this package</label>
                </div>
                <div class="form-help">Featured packages are shown first and highlighted on the Hajj page.</div>
            </div>
            <div class="col-md-3">
                <label for="pkg-order" class="form-label">Display order</label>
                <input type="number" id="pkg-order" name="sort_order" value="{{ $state['sort_order'] }}" min="0" class="form-control">
                <div class="form-help">Lower numbers appear first.</div>
            </div>
        </div>
    </div>
@endunless
