@extends('layouts.admin')

@section('title', 'Site Settings')
@section('guide', 'settings')
@section('subtitle', 'Company details, integrations and legal content used across the public website.')

@section('content')
    @php
        $groupMeta = [
            'general' => ['icon' => 'bi-building', 'label' => 'General & Company', 'hint' => 'Business figures and identifiers shown publicly (e.g. years of experience, pilgrim count).'],
            'social' => ['icon' => 'bi-share', 'label' => 'Social Links', 'hint' => 'Social media profiles linked from the footer.'],
            'integrations' => ['icon' => 'bi-plug', 'label' => 'Integrations', 'hint' => 'Third-party keys (analytics, forms). Leave blank to keep the current value.'],
            'legal' => ['icon' => 'bi-shield-check', 'label' => 'Legal', 'hint' => 'Registration and licensing details shown in the footer.'],
        ];
    @endphp
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach($settings as $group => $items)
            @php($meta = $groupMeta[$group] ?? ['icon' => 'bi-gear', 'label' => ucfirst($group), 'hint' => null])
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i>{{ $meta['label'] }}
                </div>
                <div class="card-body">
                    @if($meta['hint'])<p class="form-section-hint mb-3">{{ $meta['hint'] }}</p>@endif
                    <div class="row g-3">
                        @foreach($items as $setting)
                            @php($isSecret = str_contains($setting->key, 'secret'))
                            <div class="col-md-6">
                                @php($help = \App\Support\Content\SettingLabels::help($setting->key))
                                <label for="setting-{{ $setting->key }}" class="form-label">{{ \App\Support\Content\SettingLabels::label($setting->key) }}</label>
                                @if($isSecret)
                                    <input type="password" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="form-control" autocomplete="off" placeholder="{{ $setting->value ? '••••••••  (leave blank to keep current value)' : 'Not set' }}" @if($help) aria-describedby="setting-{{ $setting->key }}-help" @endif>
                                @else
                                    <input type="text" id="setting-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="form-control" value="{{ old('settings.'.$setting->key, $setting->value) }}" @if($help) aria-describedby="setting-{{ $setting->key }}-help" @endif>
                                @endif
                                @if($help)<div class="form-help" id="setting-{{ $setting->key }}-help">{{ $help }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save Settings</button>
        </div>
    </form>
@endsection
