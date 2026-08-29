@extends('layouts.admin')

@section('title', $office->exists ? 'Edit Office' : 'New Office')

@section('content')
    <form method="POST" action="{{ $office->exists ? route('admin.offices.update', $office) : route('admin.offices.store') }}">
        @csrf
        @if($office->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="office-label" class="form-label">Label</label>
                    <input type="text" name="label" id="office-label" class="form-control" value="{{ old('label', $office->label) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="office-phone-primary" class="form-label">Phone (Primary)</label>
                    <input type="text" name="phone_primary" id="office-phone-primary" class="form-control" value="{{ old('phone_primary', $office->phone_primary) }}">
                </div>
                <div class="col-md-3">
                    <label for="office-phone-secondary" class="form-label">Phone (Secondary)</label>
                    <input type="text" name="phone_secondary" id="office-phone-secondary" class="form-control" value="{{ old('phone_secondary', $office->phone_secondary) }}">
                </div>
                <div class="col-12">
                    <label for="office-address" class="form-label">Address</label>
                    <textarea name="address" id="office-address" class="form-control" rows="2" required>{{ old('address', $office->address) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label for="office-whatsapp" class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp" id="office-whatsapp" class="form-control" value="{{ old('whatsapp', $office->whatsapp) }}">
                </div>
                <div class="col-md-4">
                    <label for="office-email" class="form-label">Email</label>
                    <input type="email" name="email" id="office-email" class="form-control" value="{{ old('email', $office->email) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_domestic" class="form-check-input" id="is_domestic" value="1" {{ old('is_domestic', $office->is_domestic ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_domestic">Domestic</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $office->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="office-maps-embed" class="form-label">Google Maps Embed (iframe src or full embed code)</label>
                    <textarea name="google_maps_embed" id="office-maps-embed" class="form-control" rows="2">{{ old('google_maps_embed', $office->google_maps_embed) }}</textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
        <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary mt-3">Cancel</a>
    </form>
@endsection
