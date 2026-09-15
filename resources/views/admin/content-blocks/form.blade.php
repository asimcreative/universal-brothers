@extends('layouts.admin')

@php
    $ubDefinition = \App\Support\PageBuilder\BlockRegistry::find($block->type);
    $ubIsNew = ! $block->exists;
    $ubValues = old('data', $block->data ?? []);
    $ubLocked = $linkedPages->isNotEmpty() && auth()->user()->cannot('link-saved-sections');
@endphp

@section('title', $ubIsNew ? 'New '.$ubDefinition['name'] : $block->name)
@section('subtitle', $ubDefinition['description'])
@section('guide', 'reusable-sections')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.content-blocks.index') }}">Saved sections</a> / <span>{{ $ubIsNew ? 'New' : 'Edit' }}</span>
@endsection

@section('actions')
    @unless($ubIsNew)
        <a href="{{ route('admin.content-blocks.preview', $block) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-eye me-1" aria-hidden="true"></i>Preview<span class="visually-hidden"> (opens in a new tab)</span></a>
    @endunless
    <a href="{{ route('admin.content-blocks.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>All saved sections</a>
@endsection

@section('content')
    @if($linkedPages->isNotEmpty())
        <div class="alert alert-warning admin-alert" role="note">
            <i class="bi bi-link-45deg" aria-hidden="true"></i>
            <div class="admin-alert-body">
                <strong>Linked on {{ $linkedPages->count() }} {{ \Illuminate\Support\Str::plural('page', $linkedPages->count()) }}.</strong>
                Saving changes here updates these pages straight away, including any that are live:
                @foreach($linkedPages as $linkedPage)
                    <a href="{{ route('admin.pages.edit', $linkedPage->id) }}">{{ $linkedPage->title }}</a>@if($linkedPage->isLive()) <span class="small">(live)</span>@endif{{ $loop->last ? '.' : ',' }}
                @endforeach
                @if($ubLocked)
                    <br>Because of that, only a super admin can change it. To use a different version, duplicate it from the Saved sections list and change the copy.
                @endif
            </div>
        </div>
    @endif

    <x-admin.validation-summary />

    <form method="POST" action="{{ $ubIsNew ? route('admin.content-blocks.store') : route('admin.content-blocks.update', $block) }}" novalidate>
        @csrf
        @unless($ubIsNew) @method('PUT') @endunless
        <input type="hidden" name="type" value="{{ $block->type }}">

        <x-admin.panel title="Name and group" icon="bi-bookmark" class="mb-4" description="Only admins see these, when choosing a saved section.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="block-name" class="form-label">Name <span class="required-mark" aria-hidden="true">*</span></label>
                    <input type="text" id="block-name" name="name" value="{{ old('name', $block->name) }}" maxlength="120" required @class(['form-control', 'is-invalid' => $errors->has('name')]) placeholder="e.g. Hajj call to action">
                    <x-admin.error name="name" />
                </div>
                <div class="col-md-6">
                    <label for="block-category" class="form-label">Group</label>
                    <select id="block-category" name="category" class="form-select">
                        @foreach(\App\Models\ContentBlock::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $block->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="block-description" class="form-label">Description (optional)</label>
                    <input type="text" id="block-description" name="description" value="{{ old('description', $block->description) }}" maxlength="500" class="form-control" placeholder="Where it is meant to be used">
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel :title="$ubDefinition['name']" :icon="$ubDefinition['icon']" class="mb-4">
            <div class="pb-fields-standalone" data-page-builder-fields>
                @include('admin.pages.partials.fields', [
                    'fields' => $ubDefinition['fields'],
                    'values' => is_array($ubValues) ? $ubValues : [],
                    'name' => 'data',
                    'errorKey' => 'data',
                    'idPrefix' => 'block',
                ])
            </div>
        </x-admin.panel>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary" @disabled($ubLocked)
                    @if($linkedPages->isNotEmpty()) data-confirm="The {{ $linkedPages->count() }} linked {{ \Illuminate\Support\Str::plural('page', $linkedPages->count()) }} will show the new content straight away." data-confirm-title="Save and update linked pages?" data-confirm-button="Save" data-confirm-tone="primary" @endif>
                <i class="bi bi-save me-1" aria-hidden="true"></i>{{ $ubIsNew ? 'Create saved section' : 'Save changes' }}
            </button>
            <a href="{{ route('admin.content-blocks.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
