@extends('layouts.admin')

@php $isNew = ! $record->exists; @endphp

@section('title', $isNew ? 'Add '.$library->singular : $record->libraryTitle())
@section('subtitle', $library->intro)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
    <a href="{{ route('admin.library.index', $library->key) }}">{{ $library->label }}</a> /
    <span>{{ $isNew ? 'Add' : 'Edit' }}</span>
@endsection

@section('actions')
    <a href="{{ route('admin.library.index', $library->key) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to {{ strtolower($library->label) }}</a>
@endsection

@section('content')
    @if(! $isNew && $library->tracksUsage())
        <div class="alert {{ ($usageCount ?? 0) > 0 ? 'alert-info' : 'alert-light border' }} admin-alert" role="note">
            <i class="bi bi-diagram-2" aria-hidden="true"></i>
            <div class="admin-alert-body">
                @if(($usageCount ?? 0) > 0)
                    Used in <strong>{{ $usageCount }} {{ Str::plural('package', $usageCount) }}</strong>.
                    Saving changes here does <strong>not</strong> change those packages straight away — each package keeps its own copy.
                    @if($library->canPushToPackages())
                        After saving, open <a href="{{ route('admin.library.usage', [$library->key, $record->getKey()]) }}">Where it is used</a> to update them.
                    @endif
                @else
                    Not used in any package yet.
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $isNew ? route('admin.library.store', $library->key) : route('admin.library.update', [$library->key, $record->getKey()]) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <div class="card">
            <div class="card-body row g-3">
                @foreach($library->fields as $field)
                    @php
                        $name = $field['name'];
                        $id = 'lib-'.$name;
                        $value = old($name, $record->{$name});
                        $required = ! empty($field['required']);
                    @endphp
                    <div class="col-md-{{ $field['col'] ?? 12 }}" @if($field['type'] === 'image') data-image-field @endif>
                        @switch($field['type'])
                            @case('checkbox')
                                <input type="hidden" name="{{ $name }}" value="0">
                                <div class="form-check form-switch {{ ($field['col'] ?? 12) < 12 ? 'mt-md-4 pt-md-2' : '' }}">
                                    <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $value)>
                                    <label class="form-check-label fw-semibold" for="{{ $id }}">{{ $field['label'] }}</label>
                                </div>
                                @break

                            @case('days')
                                <label class="form-label">{{ $field['label'] }}</label>
                                @include('admin.library.partials.days', ['days' => old($name, $record->{$name} ?? [])])
                                @break

                            @default
                                <label for="{{ $id }}" class="form-label">{{ $field['label'] }} @if($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
                                @if($field['type'] === 'textarea')
                                    <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="form-control @error($name) is-invalid @enderror" @if($required) required @endif>{{ $value }}</textarea>
                                @elseif($field['type'] === 'select')
                                    <select id="{{ $id }}" name="{{ $name }}" class="form-select @error($name) is-invalid @enderror" @if($required) required @endif>
                                        @unless($required)<option value="">—</option>@endunless
                                        @foreach($field['options'] as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field['type'] === 'image')
                                    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" class="form-control @error($name) is-invalid @enderror">
                                    <div class="image-preview" @unless($record->{$name}) hidden @endunless>
                                        <img src="{{ $record->{$name} ? Storage::url($record->{$name}) : '' }}" alt="{{ $field['label'] }} preview" data-image-preview>
                                        @if($record->{$name})
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="remove-{{ $id }}" name="remove_{{ $name }}" value="1">
                                                <label class="form-check-label small" for="remove-{{ $id }}">Remove photo</label>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <input type="{{ ['number' => 'number', 'price' => 'number', 'url' => 'url'][$field['type']] ?? 'text' }}"
                                           id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                                           @if($field['type'] === 'price') step="0.01" min="0" @endif
                                           @if($field['type'] === 'number') min="{{ $field['min'] ?? 0 }}" @endif
                                           placeholder="{{ $field['placeholder'] ?? '' }}"
                                           class="form-control @error($name) is-invalid @enderror" @if($required) required @endif>
                                @endif
                                <x-admin.error :name="$name" />
                        @endswitch
                        @if(! empty($field['help']))<div class="form-help">{{ $field['help'] }}</div>@endif
                    </div>
                @endforeach
            </div>
            <div class="admin-card-footer d-flex flex-wrap gap-2 justify-content-between">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save</button>
                    <a href="{{ route('admin.library.index', $library->key) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
                @unless($isNew)
                    @if($library->tracksUsage())
                        <a href="{{ route('admin.library.usage', [$library->key, $record->getKey()]) }}" class="btn btn-link">Where it is used</a>
                    @endif
                @endunless
            </div>
        </div>
    </form>
@endsection
