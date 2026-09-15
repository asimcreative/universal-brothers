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
                    <label for="office-maps-embed" class="form-label">Map on the Contact page (optional)</label>
                    <textarea name="google_maps_embed" id="office-maps-embed" class="form-control @error('google_maps_embed') is-invalid @enderror" rows="2" aria-describedby="office-maps-help">{{ old('google_maps_embed', $office->google_maps_embed) }}</textarea>
                    @error('google_maps_embed')<div class="invalid-feedback" id="office-maps-error">{{ $message }}</div>@enderror
                    <div class="form-help" id="office-maps-help">
                        In Google Maps, open the office location, choose <strong>Share</strong> → <strong>Embed a map</strong> → <strong>Copy HTML</strong>, and paste it here. Only the map address is kept.
                        <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-map-from-address>Or make a map from the address above</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
    @push('scripts')
    <script>
        document.querySelector('[data-map-from-address]')?.addEventListener('click', function () {
            var address = document.getElementById('office-address').value.trim();
            var field = document.getElementById('office-maps-embed');
            if (!address) { document.getElementById('office-address').focus(); return; }
            field.value = 'https://www.google.com/maps?q=' + encodeURIComponent(address) + '&output=embed';
            field.focus();
        });
    </script>
    @endpush
@endsection
