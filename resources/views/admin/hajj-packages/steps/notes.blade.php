<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 11</div>
    <h2 id="step-title-notes">Notes &amp; policies</h2>
    <p>Notes customers must read before booking, and a private box for notes that are only for your team.</p>
</div>

@include('admin.hajj-packages.partials.need-help', ['step' => 'notes'])

<div class="note-kinds" aria-label="Four kinds of notes">
    <div class="note-kind"><span class="note-kind-badge is-customer">Customer note</span><p>Shown on the package page, for example visa or luggage advice.</p></div>
    <div class="note-kind"><span class="note-kind-badge is-important">Important policy</span><p>Shown first and highlighted. Switch on "Important".</p></div>
    <div class="note-kind"><span class="note-kind-badge is-terms">Terms &amp; conditions</span><p>Choose the kind "Terms &amp; conditions", for example price-change rules.</p></div>
    <div class="note-kind"><span class="note-kind-badge is-internal">Internal admin note</span><p>Only in the private box at the bottom. Never shown to visitors.</p></div>
</div>

<div class="builder-card">
    <header>
        <h3><i class="bi bi-journal-text" aria-hidden="true"></i>Notes for customers</h3>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="notes"><i class="bi bi-box-arrow-in-down me-1" aria-hidden="true"></i>Copy from another package</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-open-picker="notes"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Add saved note</button>
            <button type="button" class="btn btn-sm btn-primary" data-add-row="notes"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Write a note</button>
        </div>
        <p>Saved notes are shared across packages — ticket and Qurbani notes, hotel and room policies, terms. A note written here, or a saved note you change here, applies to this package only.</p>
    </header>
    <div class="builder-card-body">
        <div data-rows="notes">
            @foreach($state['notes'] ?? [] as $i => $row)
                @include('admin.hajj-packages.rows.note', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($state['notes'] ?? [])) hidden @endif>No notes yet.</div>
    </div>
</div>

@unless($isTemplate)
    <div class="builder-card internal-note-card">
        <header>
            <h3><i class="bi bi-lock" aria-hidden="true"></i>Internal admin notes — private</h3>
            <p class="internal-note-warning" id="internal-notes-warning"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i><span><strong>Internal admin notes are never shown to website visitors.</strong> They are also never given to the AI assistant, never copied into templates, and never included when content is copied to another package.</span></p>
            <p>Use them for reminders, brochure checks and supplier details.</p>
        </header>
        <div class="builder-card-body">
            <label for="pkg-internal-notes" class="visually-hidden">Internal notes</label>
            <textarea id="pkg-internal-notes" aria-describedby="internal-notes-warning" name="internal_notes" rows="4" maxlength="10000" class="form-control @error('internal_notes') is-invalid @enderror" placeholder="e.g. Hotel contract to be confirmed by March.">{{ $state['internal_notes'] }}</textarea>
            <x-admin.error name="internal_notes" />
        </div>
    </div>
@endunless
