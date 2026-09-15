<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 3</div>
    <h2 id="step-title-options">Hotel options (A / B / C)</h2>
    <p>Use options when customers can choose between different hotels for the same package — for example Option A at Dar Al Tawhid and Option B at Fairmont.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'options'])

<div class="builder-card">
    <header><h3><i class="bi bi-signpost-split" aria-hidden="true"></i>Does this package offer a choice of hotels?</h3></header>
    <div class="builder-card-body">
        <div class="choice-cards" role="radiogroup" aria-label="Hotel options">
            <label class="choice-card">
                <input type="radio" name="_has_options" value="0" data-has-options @checked(! $hasOptions) data-no-submit>
                <span class="choice-card-body"><strong>No — one set of hotels</strong><small>Every customer gets the same hotels and prices.</small></span>
            </label>
            <label class="choice-card">
                <input type="radio" name="_has_options" value="1" data-has-options @checked($hasOptions) data-no-submit>
                <span class="choice-card-body"><strong>Yes — customers choose an option</strong><small>Each option has its own hotels and room prices.</small></span>
            </label>
        </div>
    </div>
</div>

<div class="builder-card" data-options-card @if(! $hasOptions) hidden @endif>
    <header>
        <h3><i class="bi bi-list-ol" aria-hidden="true"></i>Options</h3>
        <button type="button" class="btn btn-sm btn-outline-primary" data-add-option><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add option</button>
        <p>Give each option a letter and a name customers will recognise, usually its hotel. The Prices and Hotels steps then show a separate box for each option.</p>
    </header>
    <div class="builder-card-body">
        @error('variants')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        <div data-rows="variants">
            @foreach($variantRows as $i => $row)
                @include('admin.hajj-packages.rows.option', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($variantRows)) hidden @endif>No options yet.</div>
    </div>
</div>

<div class="builder-card" data-options-card-summary @if(! $hasOptions) hidden @endif>
    <header>
        <h3><i class="bi bi-card-checklist" aria-hidden="true"></i>What each option has so far</h3>
        <p>Updates as you work. Every option needs its own room price and hotel before the package is complete.</p>
    </header>
    <div class="builder-card-body">
        <div class="option-summaries" data-option-summaries aria-live="polite"></div>
    </div>
</div>
