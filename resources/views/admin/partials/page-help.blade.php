{{--
    "Need help with this page?" — a closed panel at the top of an admin page,
    drawn from the guide section the page names with @section('guide', 'key').
--}}
@php $help = \App\Support\Guide\GuideContent::section($key); @endphp

@if($help)
    <details class="page-help">
        <summary>
            <i class="bi bi-life-preserver" aria-hidden="true"></i>
            <span>Need help with this page?</span>
        </summary>
        <div class="page-help-body">
            <p class="mb-2">{{ $help['summary'] }}</p>
            @if($help['steps'])
                <ol class="mb-2">
                    @foreach(array_slice($help['steps'], 0, 4) as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            @endif
            @if(filled($help['example'] ?? null))
                <p class="page-help-example mb-2"><strong>Example:</strong> {{ $help['example'] }}</p>
            @endif
            <a href="{{ route('admin.guide.show', $key) }}">Read the full guide: {{ $help['title'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
    </details>
@endif
