{{--
    Every problem on the form in one list at the top, each linking to the
    field or section it is about.

    Props
      title     heading text
      links     [error key => element id] for errors that should link to a place on the page
      warnings  list of ['message' => ..., 'target' => element id|null], shown as "check before publishing"
--}}
@props(['title' => 'Please fix the following before continuing', 'links' => [], 'warnings' => []])

@if($errors->any())
    <div {{ $attributes->class(['alert', 'alert-danger', 'admin-alert', 'admin-validation-summary']) }} role="alert" tabindex="-1" data-validation-summary>
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div class="admin-alert-body">
            <strong>{{ $title }}</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->getMessages() as $key => $messages)
                    @foreach($messages as $message)
                        <li>
                            @if(! empty($links[$key]))
                                <a href="#{{ $links[$key] }}" data-jump-to="{{ $links[$key] }}">{{ $message }}</a>
                            @else
                                {{ $message }}
                            @endif
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(! empty($warnings))
    <div class="alert alert-warning admin-alert" role="status">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        <div class="admin-alert-body">
            <strong>Saved. Check these before publishing:</strong>
            <ul class="mb-0 mt-1">
                @foreach($warnings as $warning)
                    <li>
                        @if(! empty($warning['target']))
                            <a href="#{{ $warning['target'] }}" data-jump-to="{{ $warning['target'] }}">{{ $warning['message'] }}</a>
                        @else
                            {{ $warning['message'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
