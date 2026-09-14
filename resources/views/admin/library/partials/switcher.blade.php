<nav class="library-switcher" aria-label="Reusable content sections">
    @foreach($types as $item)
        <a href="{{ route('admin.library.index', $item->key) }}" class="{{ $item->key === $library->key ? 'active' : '' }}" @if($item->key === $library->key) aria-current="page" @endif>
            <i class="bi {{ $item->icon }}" aria-hidden="true"></i>{{ $item->label }}
        </a>
    @endforeach
</nav>
