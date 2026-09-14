{{-- One included / not-included line. $i, $row, $list ('inclusions' | 'exclusions') --}}
<div class="b-row b-row-service" data-row="{{ $list }}" data-index="{{ $i }}">
    <input type="hidden" name="{{ $list }}[{{ $i }}][service_item_id]" data-field="service_item_id" value="{{ $row['service_item_id'] ?? '' }}">
    <div>
        <label class="visually-hidden" for="{{ $list }}-text-{{ $i }}">{{ $list === 'inclusions' ? 'Included service' : 'Not included item' }}</label>
        <textarea id="{{ $list }}-text-{{ $i }}" name="{{ $list }}[{{ $i }}][description]" data-field="description" rows="2" class="form-control service-text @error("$list.$i.description") is-invalid @enderror"
                  placeholder="{{ $list === 'inclusions' ? 'e.g. Ziyarat in Madinah with guidance' : 'e.g. Airline ticket' }}">{{ $row['description'] ?? '' }}</textarea>
        @if(filled($row['service_item_id'] ?? null))<span class="lib-badge mt-1" data-lib-badge><i class="bi bi-bookmark-check" aria-hidden="true"></i>Saved content</span>@endif
    </div>
    <x-admin.row-actions label="item" />
</div>
