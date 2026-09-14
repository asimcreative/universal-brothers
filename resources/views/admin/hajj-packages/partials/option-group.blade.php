{{--
    A box of rows that belong to one hotel option (or to every option).
    $rowsName  room_options | accommodations
    $code      the option letter, '' for "every option"
    $uid       ties the box to its row in the Hotel options step
    $label     option name
    $rows      [index => row]
    $noun      "Room prices" | "Hotels"
--}}
@php
    $isShared = $code === '';
    $tone = $isShared ? 'shared' : (in_array($code, ['A', 'B', 'C', 'D', 'E'], true) ? $code : 'shared');
    $rowPartial = $rowsName === 'room_options' ? 'room' : 'hotel';
@endphp
<div class="option-group option-tone-{{ $tone }}" data-option-group="{{ $rowsName }}" data-option-uid="{{ $uid }}" data-option-code="{{ $code }}">
    <header>
        <div class="option-group-title">
            @if($isShared)
                <span class="option-chip"><i class="bi bi-asterisk" aria-hidden="true"></i></span>
                <span>{{ $noun }} for every option</span>
            @else
                <span class="option-chip" data-option-chip>{{ $code }}</span>
                <span>{{ $noun }} for <span data-option-title>Option {{ $code }}{{ filled($label) && $label !== '__LABEL__' ? ' — '.$label : '' }}</span></span>
            @endif
        </div>
        <div class="builder-toolbar">
            @if($rowsName === 'room_options')
                <button type="button" class="btn btn-sm btn-outline-secondary" data-add-standard-rooms><i class="bi bi-lightning me-1" aria-hidden="true"></i>Add Quad, Triple &amp; Double</button>
            @endif
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="{{ $rowsName }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ $rowsName === 'room_options' ? 'Add room type' : 'Add hotel' }}</button>
        </div>
    </header>
    <div class="option-group-body">
        <div data-rows="{{ $rowsName }}">
            @foreach($rows as $i => $row)
                @include('admin.hajj-packages.rows.'.$rowPartial, ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="rows-empty" data-rows-empty @if(count($rows)) hidden @endif>
            @if($isShared)
                {{ $rowsName === 'room_options' ? 'No prices shared by every option. Use this when all options cost the same.' : 'No hotels shared by every option — for example the Madinah hotel, when only the Makkah hotel differs.' }}
            @else
                {{ $rowsName === 'room_options' ? 'No room prices for this option yet.' : 'No hotels for this option yet.' }}
            @endif
        </div>
    </div>
</div>
