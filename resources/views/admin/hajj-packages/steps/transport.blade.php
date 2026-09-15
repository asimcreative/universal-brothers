<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 8</div>
    <h2 id="step-title-transport">Transport &amp; meals</h2>
    <p>Transfers and travel between cities, and the meal plan at each hotel.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'transport'])

<div class="builder-card">
    <header>
        <h3><i class="bi bi-bus-front" aria-hidden="true"></i>Transport</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="transport"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-open-picker="transportation"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Add saved transport</button>
            <button type="button" class="btn btn-sm btn-primary" data-add-row="transportation"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add your own</button>
        </div>
        <p>Untick "In price" for a paid extra (for example a private car) and enter its price.</p>
    </header>
    <div class="builder-card-body">
        <div data-rows="transportation">
            @foreach($state['transportation'] ?? [] as $i => $row)
                @include('admin.hajj-packages.rows.transport', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($state['transportation'] ?? [])) hidden @endif>No transport added yet.</div>
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-cup-hot" aria-hidden="true"></i>Meals</h3>
        <p>Meal plans belong to each hotel. Set them all at once here, or one by one in the Hotels step.</p>
    </header>
    <div class="builder-card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-6">
                <label for="meal-all" class="form-label">Meal plan for every hotel in Makkah and Madinah</label>
                <select id="meal-all" class="form-select" data-meal-all data-no-dirty>
                    <option value="">Choose a meal plan…</option>
                    @foreach($library['mealPlans'] as $plan)
                        <option value="{{ $plan['id'] }}">{{ $plan['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-outline-primary" data-apply-meal-all>Apply to all</button>
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-outline-secondary" data-open-copy="meals">Copy from another package</button>
            </div>
        </div>
        <div data-meal-summary class="small"></div>
    </div>
</div>
