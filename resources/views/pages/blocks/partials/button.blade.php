{{-- One button from a text/link pair. Needs $text, $link; optional $class, $newTab. --}}
@if(filled($text ?? null) && filled($link ?? null))
    @php($external = \App\Support\PageBuilder\Block::isExternal($link))
    <a href="{{ \App\Support\PageBuilder\Block::href($link) }}" class="btn {{ $class ?? 'btn-primary' }}"
       @if(($newTab ?? false) || $external) target="_blank" rel="noopener noreferrer" @endif>{{ $text }}@if(($newTab ?? false) || $external)<span class="visually-hidden"> (opens in a new tab)</span>@endif</a>
@endif
