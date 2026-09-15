{{--
    "Watch Guide" link to a Video Training chapter. $video (chapter key), optional $class,
    and $newTab where leaving the page would lose unsaved work (the package builder).
    Shows nothing when the chapter does not exist.
--}}
@php $watch = filled($video ?? null) ? \App\Support\Training\TrainingCatalog::chapter($video) : null; @endphp

@if($watch)
    <a href="{{ route('admin.training.show', $watch['key']) }}" class="watch-guide-link {{ $class ?? '' }}" data-watch-guide="{{ $watch['key'] }}" @if($newTab ?? false) target="_blank" rel="noopener" @endif>
        <i class="bi bi-play-circle-fill" aria-hidden="true"></i>
        <span>Watch Guide: {{ $watch['title'] }}@if($newTab ?? false)<span class="visually-hidden"> (opens in a new tab)</span>@endif</span>
        @if($watch['video'])<span class="watch-guide-duration">{{ \App\Support\Training\TrainingCatalog::formatDuration($watch['video']['duration']) }}</span>@endif
    </a>
@endif
