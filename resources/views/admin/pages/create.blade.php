@extends('layouts.admin')

@section('title', 'New Page')
@section('subtitle', 'Give the page a title and choose how to start. It is saved as a draft; nothing appears on the website until you publish.')
@section('guide', 'pages-builder')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.pages.index') }}">Pages</a> / <span>New page</span>
@endsection

@section('content')
    <x-admin.validation-summary :links="['title' => 'page-title', 'slug' => 'page-slug']" />

    <form method="POST" action="{{ route('admin.pages.store') }}" novalidate data-new-page>
        @csrf

        <x-admin.panel title="Page title and address" icon="bi-card-heading" class="mb-4">
            <div class="row g-3">
                <div class="col-md-7">
                    <label for="page-title" class="form-label">Page title <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="page-title" name="title" value="{{ old('title') }}" maxlength="255" required
                           @class(['form-control', 'form-control-lg', 'is-invalid' => $errors->has('title')]) placeholder="e.g. Hajj Preparation Guide" data-slug-source>
                    <x-admin.error name="title" />
                </div>
                <div class="col-md-5">
                    <label for="page-slug" class="form-label">Page address <span class="required-mark" aria-hidden="true">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text small">/</span>
                        <input type="text" id="page-slug" name="slug" value="{{ old('slug') }}" maxlength="100" required
                               @class(['form-control', 'is-invalid' => $errors->has('slug')]) placeholder="hajj-preparation-guide" data-slug-target aria-describedby="page-slug-help">
                    </div>
                    <div class="form-help" id="page-slug-help">Filled in from the title. Small letters, numbers and dashes only.</div>
                    <x-admin.error name="slug" />
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel title="How would you like to start?" icon="bi-grid-1x2" description="You can add, remove and rearrange sections afterwards." class="mb-4">
            <div class="choice-cards pb-starters" role="radiogroup" aria-label="Starting layout">
                @foreach($starters as $key => $starter)
                    <label class="choice-card">
                        <input type="radio" name="starter" value="{{ $key }}" @checked(old('starter', 'information') === $key)>
                        <span class="choice-card-body">
                            <i class="bi {{ $starter['icon'] }} choice-card-icon" aria-hidden="true"></i>
                            <strong>{{ $starter['name'] }}</strong>
                            <small>{{ $starter['description'] }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
        </x-admin.panel>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>Create draft and start building</button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
