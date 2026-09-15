<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 10</div>
    <h2 id="step-title-extras">Additional options</h2>
    <p>Upgrades and supplements customers can add — a Kaaba-view room, an extra night in Madinah, a larger tent. Aziziya rooms are set in the Hotels step.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'extras'])

<div class="builder-card">
    <header>
        <h3><i class="bi bi-plus-square" aria-hidden="true"></i>Additional options</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="extras"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-open-picker="upgrades"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Add saved option</button>
            <button type="button" class="btn btn-sm btn-primary" data-add-row="upgrades"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add your own</button>
        </div>
        <p>These are additional services customers may choose at extra cost. They are not added to the room price — customers see them as separate choices. Leave the price empty when it is "on request". Example: Kaaba View Supplement, US$2,200 per person, subject to availability.</p>
    </header>
    <div class="builder-card-body">
        <div data-rows="upgrades">
            @foreach($state['upgrades'] ?? [] as $i => $row)
                @include('admin.hajj-packages.rows.upgrade', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($state['upgrades'] ?? [])) hidden @endif>No additional options.</div>
    </div>
</div>
