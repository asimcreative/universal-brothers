@extends('layouts.admin')

@section('title', 'How to use the admin')
@section('subtitle', 'A plain-language guide to building and looking after Hajj packages.')

@section('content')
    <div class="card">
        <div class="card-body admin-help">
            <p class="lead mb-0">You never need to know anything technical to manage packages. Everything is done with the step-by-step builder and the reusable content in the menu.</p>

            <h2>Adding a Hajj package</h2>
            <p>Go to <a href="{{ route('admin.hajj-packages.create') }}">Hajj Packages → Add Hajj Package</a>. The builder has twelve steps on the left. Fill them in any order — the tick next to a step shows it has what it needs.</p>

            @foreach([
                ['Basic information', 'The title customers see, your package code (for example UB025), and how many days it lasts.'],
                ['Package setup', 'Madinah first or Makkah first, shifting or non-shifting, and whether Aziziya is included.'],
                ['Hotel options', 'If customers choose between hotels — Option A at one hotel, Option B at another — say "Yes" and add the options. Otherwise leave it as "No".'],
                ['Room prices', 'Add each room type (Quad, Triple, Double…) and its price. Each option has its own box, so you can always see which price belongs to which hotel. Fill in only the currencies you have.'],
                ['Hotels & accommodation', 'Pick hotels from your saved list. Their names and stars are filled in for you. Missing hotel? Press "New hotel".'],
                ['Journey plan', 'The day-by-day plan. Apply a saved journey template or copy it from another package, then use "Fill dates" to set all the dates at once.'],
                ['Mina, Arafat & Muzdalifah', 'Pick a saved arrangement to fill the card, then change anything that is different.'],
                ['Transport & meals', 'Add saved transport with one click. Set one meal plan for every hotel at once.'],
                ['Included & not included', 'Tick the saved lines this package includes. The same line cannot be added twice.'],
                ['Additional options', 'Upgrades such as a Kaaba-view room. Leave the price empty for "on request".'],
                ['Notes & policies', 'Notes customers must read, plus a private "Internal notes" box.'],
                ['Photos & search engines', 'The main photo, more photos, and how the package looks on Google.'],
            ] as $i => [$title, $text])
                <div class="help-step">
                    <span class="help-step-number">{{ $i + 1 }}</span>
                    <div><strong>{{ $title }}.</strong> {{ $text }}</div>
                </div>
            @endforeach

            <h2>Saving, previewing and publishing</h2>
            <ul>
                <li><strong>Save draft</strong> keeps your work without showing it on the website. A draft can be unfinished.</li>
                <li><strong>Save &amp; continue</strong> saves and moves to the next step.</li>
                <li><strong>Save &amp; preview</strong> saves and opens the package exactly as visitors will see it, with a yellow "Preview" bar. Preview links only work for logged-in administrators and stop working after an hour.</li>
                <li><strong>Publish</strong> puts the package on the website. It only works when the package has a title, a code, a number of days, at least one hotel, and a price for every option. If something is missing, the message tells you which step to open.</li>
                <li>If you close the page by accident, the builder offers to <strong>restore</strong> what you had typed the next time you open it on the same computer.</li>
            </ul>

            <h2>Duplicating and templates</h2>
            <ul>
                <li><strong>Duplicate</strong> (in the package list or the "More" menu) makes a draft copy with a new code and web address. Change the title, code and dates, then publish. The original is never changed.</li>
                <li><strong>Save as template</strong> keeps a package's options, prices, hotels, journey, services, transport and notes as a starting point. Use it from <a href="{{ route('admin.package-templates.index') }}">Package Templates</a> or "From a template" on the package list.</li>
                <li>A template can be applied to a <strong>draft</strong> only, so a live package can never be replaced by accident.</li>
                <li><strong>Copy from another package</strong> buttons inside the builder copy just one part — the prices, the hotels, the journey plan and so on.</li>
            </ul>

            <h2>Reusable content</h2>
            <p>Things many packages share are written once under <strong>Reusable Content</strong> in the menu:</p>
            <ul>
                @foreach($types as $type)
                    <li><a href="{{ route('admin.library.index', $type->key) }}">{{ $type->label }}</a> — {{ $type->intro }}</li>
                @endforeach
            </ul>

            <h3>How shared information is kept safe</h3>
            <p>When you pick a saved hotel, note or transport leg, the package keeps its <strong>own copy</strong>. That means:</p>
            <ul>
                <li>Changing a name or price inside one package changes <strong>only that package</strong>. The saved item is not touched.</li>
                <li>Editing a saved item does <strong>not</strong> silently change live packages. Each saved item shows <strong>"Used in N packages"</strong>. Open it and press <strong>Update N packages</strong> when you want those packages to get the new details. You are told how many are live before anything changes.</li>
                <li>A saved item used by packages cannot be deleted — <strong>archive</strong> it instead. It stops appearing in the builder, and packages that use it keep their details.</li>
            </ul>

            <h2>Internal notes</h2>
            <p>Each package has an <strong>Internal notes</strong> box (Notes &amp; policies step). What you write there is only for administrators. It is never shown on the website and never given to the AI assistant. Customer-facing notes go in the list above it.</p>

            <h2>Archiving and deleting</h2>
            <ul>
                <li><strong>Archive</strong> removes a package from the website and the main list, but keeps it. Find it again in the <strong>Archived</strong> tab and choose <strong>Restore</strong>.</li>
                <li>A <strong>published</strong> package cannot be deleted. Move it to draft or archive it first.</li>
            </ul>
        </div>
    </div>
@endsection
