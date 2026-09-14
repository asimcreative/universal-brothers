<div class="builder-panel-header">
    <div class="builder-panel-eyebrow">Step 9</div>
    <h2 id="step-title-services">Included &amp; not included</h2>
    <p>What the price covers, and what customers pay for separately. Add saved items with one click; the same line is never added twice.</p>
</div>

<div class="row g-3">
    @foreach(['inclusions' => ['Included services', 'bi-check2-circle', 'included'], 'exclusions' => ['Not included', 'bi-x-circle', 'not included']] as $list => [$heading, $icon, $noun])
        <div class="col-xl-6">
            <div class="builder-card h-100">
                <header>
                    <h3><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $heading }}</h3>
                    <div class="builder-toolbar">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-open-copy="{{ $list }}" title="Copy from another package"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i><span class="visually-hidden">Copy from another package</span></button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-open-picker="{{ $list }}"><i class="bi bi-bookmark-plus me-1" aria-hidden="true"></i>Add saved</button>
                        <button type="button" class="btn btn-sm btn-primary" data-add-row="{{ $list }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add your own</button>
                    </div>
                </header>
                <div class="builder-card-body">
                    <div data-rows="{{ $list }}">
                        @foreach($state[$list] ?? [] as $i => $row)
                            @include('admin.hajj-packages.rows.service', ['i' => $i, 'row' => $row, 'list' => $list])
                        @endforeach
                    </div>
                    <div class="rows-empty" data-rows-empty @if(count($state[$list] ?? [])) hidden @endif>Nothing {{ $noun }} listed yet.</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
