{{--
    The day rows of a journey plan template. Uses the same row partial as the
    package builder, named days[i][…] instead of itinerary[i][…], so a template
    and a package's journey look and behave the same.
--}}
@php $days = array_values(is_array($days) ? $days : []); @endphp
<div class="builder-card mb-0" id="template-days" data-days-editor>
    <header>
        <div class="builder-toolbar">
            <button type="button" class="btn btn-sm btn-primary" data-days-add><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add day</button>
        </div>
        <p>English dates are optional in a template — packages set their own dates when they apply it.</p>
    </header>
    <div class="builder-card-body">
        <div data-days-rows>
            @foreach($days as $i => $day)
                {!! str_replace('itinerary[', 'days[', view('admin.hajj-packages.rows.day', ['i' => $i, 'row' => $day, 'hasOptionB' => true, 'optionALabel' => 'Option A', 'optionBLabel' => 'Option B'])->render()) !!}
            @endforeach
        </div>
        <div class="rows-empty" data-days-empty @if(count($days)) hidden @endif>No days yet.</div>
    </div>
    <template data-days-template>{!! str_replace('itinerary[', 'days[', view('admin.hajj-packages.rows.day', ['i' => '__INDEX__', 'row' => [], 'hasOptionB' => true, 'optionALabel' => 'Option A', 'optionBLabel' => 'Option B'])->render()) !!}</template>
    <datalist id="dl-journey-places">
        @foreach(['Makkah', 'Madinah', 'Mina', 'Arafat', 'Muzdalifah', 'Aziziya', 'Jeddah', 'To Makkah', 'To Madinah', 'To Mina', 'Departure'] as $place)
            <option value="{{ $place }}">
        @endforeach
    </datalist>
</div>

@push('scripts')
    <script>
        (function () {
            const editor = document.querySelector('[data-days-editor]');
            if (!editor) return;
            const rows = editor.querySelector('[data-days-rows]');
            const empty = editor.querySelector('[data-days-empty]');
            let next = rows.querySelectorAll('[data-row]').length;
            const refresh = () => { empty.hidden = rows.querySelector('[data-row]') !== null; };

            editor.querySelector('[data-days-add]').addEventListener('click', () => {
                const holder = document.createElement('div');
                holder.innerHTML = editor.querySelector('[data-days-template]').innerHTML.replaceAll('__INDEX__', next++);
                const row = holder.firstElementChild;
                row.querySelector('[data-field="day_number"]').value = rows.querySelectorAll('[data-row]').length + 1;
                rows.appendChild(row);
                refresh();
            });

            rows.addEventListener('click', (event) => {
                const action = event.target.closest('[data-row-action]');
                if (!action) return;
                const row = action.closest('[data-row]');
                if (action.dataset.rowAction === 'remove') row.remove();
                if (action.dataset.rowAction === 'up' && row.previousElementSibling) row.previousElementSibling.before(row);
                if (action.dataset.rowAction === 'down' && row.nextElementSibling) row.nextElementSibling.after(row);
                if (action.dataset.rowAction === 'duplicate') {
                    const copy = row.cloneNode(true);
                    const index = next++;
                    copy.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replace(/days\[\d+\]/, `days[${index}]`); });
                    copy.querySelectorAll('[id]').forEach((el) => { el.id = `${el.id}-c${index}`; });
                    row.querySelectorAll('input').forEach((field, i) => { copy.querySelectorAll('input')[i].value = field.value; });
                    row.after(copy);
                }
                refresh();
            });
        })();
    </script>
@endpush
