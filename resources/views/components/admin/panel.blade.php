{{--
    A titled card: the admin's standard block for a group of fields or one part
    of a page. Slots: default (body), actions (header, right side).

    Props: title, icon, description, id, flush (no body padding)
--}}
@props(['title' => null, 'icon' => null, 'description' => null, 'id' => null, 'flush' => false])

<section {{ $attributes->class(['card', 'admin-panel']) }} @if($id) id="{{ $id }}" @endif @if($title && $id) aria-labelledby="{{ $id }}-title" @endif>
    @if($title || isset($actions))
        <header class="admin-panel-header">
            <div class="min-w-0">
                @if($title)
                    <h2 class="admin-panel-title" @if($id) id="{{ $id }}-title" @endif>@if($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif{{ $title }}</h2>
                @endif
                @if($description)<p class="admin-panel-description">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="admin-panel-actions">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['admin-panel-body' => ! $flush])>
        {{ $slot }}
    </div>
</section>
