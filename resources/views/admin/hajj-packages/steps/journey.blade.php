<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 6</div>
    <h2 id="step-title-journey">Journey plan</h2>
    <p>Day by day: the date, the Islamic date, where pilgrims are, and where they stay. Start from a template or another package to save typing.</p>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-magic" aria-hidden="true"></i>Start faster</h3>
    </header>
    <div class="builder-card-body row g-2 align-items-end">
        <div class="col-md-5">
            <label for="journey-template" class="form-label">Journey plan template</label>
            <select id="journey-template" class="form-select" data-journey-template data-no-dirty>
                <option value="">Choose a template…</option>
                @foreach($library['journeyTemplates'] as $item)
                    <option value="{{ $item['id'] }}">{{ $item['name'] }} ({{ count($item['days'] ?? []) }} days)</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-outline-primary" data-apply-journey-template>Apply template</button>
        </div>
        <div class="col-md-auto">
            <button type="button" class="btn btn-outline-secondary" data-open-copy="journey"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
        </div>
        <div class="col-12 form-help">Applying a template or copying replaces the days below. Nothing is saved until you press a save button.</div>
    </div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-calendar-week" aria-hidden="true"></i>Days</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-renumber-days title="Number the days 1, 2, 3… in their current order"><i class="bi bi-sort-numeric-down me-1" aria-hidden="true"></i>Renumber</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#fillDatesPanel" aria-expanded="false" aria-controls="fillDatesPanel"><i class="bi bi-calendar-plus me-1" aria-hidden="true"></i>Fill dates</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#saveJourneyModal"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Save as template</button>
            <button type="button" class="btn btn-sm btn-primary" data-add-row="itinerary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add day</button>
        </div>
        <p>Dates: pick the English date from the calendar. Write the Islamic date as it appears in the brochure, for example "08 Zil Hajj".</p>
    </header>
    <div class="collapse" id="fillDatesPanel">
        <div class="builder-card-body border-bottom row g-2 align-items-end">
            <div class="col-sm-5 col-md-4">
                <label for="fill-dates-start" class="form-label">English date of the first day</label>
                <input type="date" id="fill-dates-start" class="form-control" data-fill-dates-start data-no-dirty>
            </div>
            <div class="col-sm-auto">
                <button type="button" class="btn btn-outline-primary" data-fill-dates>Fill every day's date</button>
            </div>
            <div class="col-12 form-help">Each following day gets the next calendar date, in the order shown.</div>
        </div>
    </div>
    <div class="builder-card-body">
        <div data-rows="itinerary" data-option-b="{{ $hasOptionB ? '1' : '0' }}">
            @foreach($state['itinerary'] ?? [] as $i => $row)
                @include('admin.hajj-packages.rows.day', ['i' => $i, 'row' => $row, 'hasOptionB' => $hasOptionB, 'optionALabel' => $optionALabel, 'optionBLabel' => $optionBLabel])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($state['itinerary'] ?? [])) hidden @endif>No days yet. Add a day, apply a template, or copy from another package.</div>
    </div>
</div>
