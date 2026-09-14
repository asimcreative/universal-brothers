{{-- One customer-facing note. $i, $row --}}
<div class="b-row b-row-note" data-row="notes" data-index="{{ $i }}">
    <input type="hidden" name="notes[{{ $i }}][note_template_id]" data-field="note_template_id" value="{{ $row['note_template_id'] ?? '' }}">
    <div>
        <label class="form-label" for="note-type-{{ $i }}">Kind of note</label>
        <select id="note-type-{{ $i }}" name="notes[{{ $i }}][note_type]" data-field="note_type" class="form-select" aria-label="Kind of note">
            @foreach(\App\Models\NoteTemplate::NOTE_TYPES as $value => $label)
                <option value="{{ $value }}" @selected(($row['note_type'] ?? 'general') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="note-title-{{ $i }}">Heading (optional)</label>
        <input type="text" id="note-title-{{ $i }}" name="notes[{{ $i }}][title]" data-field="title" value="{{ $row['title'] ?? '' }}" class="form-control" aria-label="Note heading">
    </div>
    <div>
        <label class="form-label d-block" for="note-important-{{ $i }}">Highlight</label>
        <input type="hidden" name="notes[{{ $i }}][is_important]" value="0">
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" role="switch" id="note-important-{{ $i }}" name="notes[{{ $i }}][is_important]" data-field="is_important" value="1" @checked(! empty($row['is_important']))>
            <label class="form-check-label small" for="note-important-{{ $i }}">Important</label>
        </div>
    </div>
    <div class="row-actions-wrap">
        @if(filled($row['note_template_id'] ?? null))<span class="lib-badge mb-1" data-lib-badge><i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved content</span>@endif
        <x-admin.row-actions label="note" />
    </div>
    <div class="b-row-wide">
        <label class="visually-hidden" for="note-content-{{ $i }}">Note text</label>
        <textarea id="note-content-{{ $i }}" name="notes[{{ $i }}][content]" data-field="content" rows="2" class="form-control @error("notes.$i.content") is-invalid @enderror" placeholder="The note customers will read">{{ $row['content'] ?? '' }}</textarea>
    </div>
</div>
