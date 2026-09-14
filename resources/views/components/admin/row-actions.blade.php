@props(['label' => 'row', 'move' => true, 'duplicate' => false])
<div class="row-actions">
    @if($move)
        <button type="button" data-row-action="up" title="Move up" aria-label="Move {{ $label }} up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
        <button type="button" data-row-action="down" title="Move down" aria-label="Move {{ $label }} down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
    @endif
    @if($duplicate)
        <button type="button" data-row-action="duplicate" title="Duplicate" aria-label="Duplicate {{ $label }}"><i class="bi bi-copy" aria-hidden="true"></i></button>
    @endif
    <button type="button" class="row-remove" data-row-action="remove" title="Remove" aria-label="Remove {{ $label }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
</div>
