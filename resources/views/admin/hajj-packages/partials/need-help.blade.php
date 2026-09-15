{{--
    The "Need help?" panel under each builder step's heading. Words come from
    resources/data/package-builder-help.php. $step
--}}
@php $help = \App\Support\Guide\GuideContent::builderHelp($step); @endphp

@if($help)
    @include('admin.partials.watch-guide', ['video' => $help['video'] ?? null, 'class' => 'mb-2', 'newTab' => true])
    <details class="need-help" data-need-help="{{ $step }}">
        <summary><i class="bi bi-life-preserver" aria-hidden="true"></i>Need help with this step?</summary>
        <dl class="need-help-body">
            <div><dt>What is this for?</dt><dd>{{ $help['purpose'] }}</dd></div>
            <div><dt>What should I enter?</dt><dd>{{ $help['enter'] }}</dd></div>
            <div><dt>Is it required?</dt><dd>{{ $help['required'] }}</dd></div>
            <div><dt>Example</dt><dd>{{ $help['example'] }}</dd></div>
            <div><dt>What happens after saving?</dt><dd>{{ $help['after'] }}</dd></div>
        </dl>
        @if(filled($help['guide'] ?? null) && \App\Support\Guide\GuideContent::section($help['guide']))
            <a class="need-help-link" href="{{ route('admin.guide.show', $help['guide']) }}" target="_blank" rel="noopener">Open the full guide in a new tab <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        @endif
    </details>
@endif
