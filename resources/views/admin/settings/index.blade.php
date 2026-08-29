@extends('layouts.admin')

@section('title', 'Website Settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach($settings as $group => $items)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold text-capitalize">{{ $group }}</div>
                <div class="card-body row g-3">
                    @foreach($items as $setting)
                        <div class="col-md-6">
                            <label class="form-label text-capitalize">{{ str_replace('_', ' ', $setting->key) }}</label>
                            <input type="text" name="settings[{{ $setting->key }}]" class="form-control" value="{{ old('settings.'.$setting->key, $setting->value) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
@endsection
