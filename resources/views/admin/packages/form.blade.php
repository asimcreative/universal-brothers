@extends('layouts.admin')

@section('title', $package->exists ? 'Edit Package' : 'New Package')
@section('subtitle', 'For Umrah and Tourism packages. Hajj packages have their own dedicated form.')

@section('actions')
    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Packages</a>
@endsection

@section('content')
    <form method="POST" action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}" enctype="multipart/form-data">
        @csrf
        @if($package->exists) @method('PUT') @endif

        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Basic Information</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label for="pkg-category" class="form-label">Category <span class="required-mark">*</span></label>
                    <select name="package_category_id" id="pkg-category" class="form-select" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('package_category_id', $package->package_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="pkg-series" class="form-label">Series</label>
                    <select name="package_series_id" id="pkg-series" class="form-select">
                        <option value="">— None —</option>
                        @foreach($series as $s)
                            <option value="{{ $s->id }}" {{ old('package_series_id', $package->package_series_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="pkg-code" class="form-label">Package Code</label>
                    <input type="text" name="code" id="pkg-code" class="form-control" value="{{ old('code', $package->code) }}" placeholder="e.g. UB001">
                </div>

                <div class="col-md-8">
                    <label for="pkg-name" class="form-label">Name <span class="required-mark">*</span></label>
                    <input type="text" name="name" id="pkg-name" class="form-control" value="{{ old('name', $package->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="pkg-slug" class="form-label">Slug <span class="required-mark">*</span></label>
                    <input type="text" name="slug" id="pkg-slug" class="form-control" value="{{ old('slug', $package->slug) }}" required>
                </div>

                <div class="col-12">
                    <label for="pkg-summary" class="form-label">Summary</label>
                    <textarea name="summary" id="pkg-summary" class="form-control" rows="2">{{ old('summary', $package->summary) }}</textarea>
                    <div class="form-text">Shown on the package card and at the top of the package page.</div>
                </div>
                <div class="col-12">
                    <label for="pkg-description" class="form-label">Description</label>
                    <textarea name="description" id="pkg-description" class="form-control" rows="3">{{ old('description', $package->description) }}</textarea>
                </div>

                <div class="col-md-3">
                    <label for="pkg-duration-days" class="form-label">Duration (days)</label>
                    <input type="number" name="duration_days" id="pkg-duration-days" class="form-control" value="{{ old('duration_days', $package->duration_days) }}">
                </div>
                <div class="col-md-3">
                    <label for="pkg-duration-label" class="form-label">Duration Label</label>
                    <input type="text" name="duration_label" id="pkg-duration-label" class="form-control" value="{{ old('duration_label', $package->duration_label) }}" placeholder="e.g. 13 Days Package">
                </div>
                <div class="col-md-3">
                    <label for="pkg-season-year" class="form-label">Season Year</label>
                    <input type="number" name="season_year" id="pkg-season-year" class="form-control" value="{{ old('season_year', $package->season_year) }}">
                </div>
                <div class="col-md-3">
                    <label for="pkg-season-label" class="form-label">Season Label</label>
                    <input type="text" name="season_label" id="pkg-season-label" class="form-control" value="{{ old('season_label', $package->season_label) }}" placeholder="e.g. Hajj 2027 / 1448 AH">
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_shifting" class="form-check-input" id="is_shifting" value="1" {{ old('is_shifting', $package->is_shifting) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_shifting">Shifting Itinerary</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="has_aziziya" class="form-check-input" id="has_aziziya" value="1" {{ old('has_aziziya', $package->has_aziziya) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_aziziya">Has Aziziya Leg</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" value="1" {{ old('is_featured', $package->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_featured">Featured</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_seasonal" class="form-check-input" id="is_seasonal" value="1" {{ old('is_seasonal', $package->is_seasonal) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_seasonal">Seasonal</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="pkg-currency" class="form-label">Currency</label>
                    <select name="currency" id="pkg-currency" class="form-select">
                        <option value="USD" {{ old('currency', $package->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="PKR" {{ old('currency', $package->currency) === 'PKR' ? 'selected' : '' }}>PKR</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="pkg-status" class="form-label">Status</label>
                    <select name="status" id="pkg-status" class="form-select">
                        <option value="draft" {{ old('status', $package->status) === 'draft' ? 'selected' : '' }}>Draft (hidden from the public site)</option>
                        <option value="published" {{ old('status', $package->status) === 'published' ? 'selected' : '' }}>Published (live on the public site)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="pkg-cover-image" class="form-label">Cover Image</label>
                    <input type="file" name="cover_image" id="pkg-cover-image" class="form-control" accept="image/*">
                    @if($package->cover_image)
                        <img src="{{ Storage::url($package->cover_image) }}" class="mt-2 rounded" style="height:60px;" alt="Current cover image">
                    @endif
                </div>

                <div class="col-md-6">
                    <label for="pkg-meta-title" class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" id="pkg-meta-title" class="form-control" value="{{ old('meta_title', $package->meta_title) }}">
                </div>
                <div class="col-md-6">
                    <label for="pkg-meta-description" class="form-label">Meta Description</label>
                    <input type="text" name="meta_description" id="pkg-meta-description" class="form-control" value="{{ old('meta_description', $package->meta_description) }}">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-calendar-week me-2" aria-hidden="true"></i>Itinerary</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-itinerary-row">+ Add Day</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="itinerary-table">
                        <thead>
                            <tr><th>Day #</th><th>English Date</th><th>Islamic Date</th><th>City</th><th>Accommodation A</th><th>Accommodation B</th><th></th></tr>
                        </thead>
                        <tbody>
                            @php
                                $itineraryRows = old('itinerary', $package->itineraryDays?->map(fn ($d) => [
                                    'day_number' => $d->day_number,
                                    'date_gregorian' => $d->date_gregorian?->format('Y-m-d'),
                                    'date_hijri_label' => $d->date_hijri_label,
                                    'city' => $d->city,
                                    'accommodation_a' => $d->accommodation_a,
                                    'accommodation_b' => $d->accommodation_b,
                                ])->toArray() ?? []);
                            @endphp
                            @forelse($itineraryRows as $i => $row)
                                <tr>
                                    <td><input type="number" name="itinerary[{{ $i }}][day_number]" class="form-control form-control-sm" value="{{ $row['day_number'] ?? '' }}"></td>
                                    <td><input type="date" name="itinerary[{{ $i }}][date_gregorian]" class="form-control form-control-sm" value="{{ $row['date_gregorian'] ?? '' }}"></td>
                                    <td><input type="text" name="itinerary[{{ $i }}][date_hijri_label]" class="form-control form-control-sm" value="{{ $row['date_hijri_label'] ?? '' }}"></td>
                                    <td><input type="text" name="itinerary[{{ $i }}][city]" class="form-control form-control-sm" value="{{ $row['city'] ?? '' }}"></td>
                                    <td><input type="text" name="itinerary[{{ $i }}][accommodation_a]" class="form-control form-control-sm" value="{{ $row['accommodation_a'] ?? '' }}"></td>
                                    <td><input type="text" name="itinerary[{{ $i }}][accommodation_b]" class="form-control form-control-sm" value="{{ $row['accommodation_b'] ?? '' }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-cash-coin me-2" aria-hidden="true"></i>Price Tiers</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-tier-row">+ Add Tier</button>
            </div>
            <div class="card-body">
                <p class="form-section-hint">Leave a room type blank if this package doesn't offer it — it will show as "N/A" rather than an invented price.</p>
                <div id="tiers-container">
                    @php $tierRows = old('tiers', $package->priceTiers?->map(fn ($t) => ['label' => $t->label, 'prices' => $t->roomPrices->pluck('price', 'room_type')])->toArray() ?? []); @endphp
                    @forelse($tierRows as $i => $tier)
                        <div class="row g-2 align-items-end mb-2 tier-row border-bottom pb-2">
                            <div class="col-md-3">
                                <label for="tier-{{ $i }}-label" class="form-label small">Tier Label</label>
                                <input type="text" name="tiers[{{ $i }}][label]" id="tier-{{ $i }}-label" class="form-control form-control-sm" value="{{ $tier['label'] ?? '' }}" placeholder="e.g. Package A — Hotel Name">
                            </div>
                            <div class="col-md-2">
                                <label for="tier-{{ $i }}-sharing" class="form-label small">Sharing</label>
                                <input type="number" step="0.01" name="tiers[{{ $i }}][prices][sharing]" id="tier-{{ $i }}-sharing" class="form-control form-control-sm" value="{{ $tier['prices']['sharing'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="tier-{{ $i }}-quad" class="form-label small">Quad</label>
                                <input type="number" step="0.01" name="tiers[{{ $i }}][prices][quad]" id="tier-{{ $i }}-quad" class="form-control form-control-sm" value="{{ $tier['prices']['quad'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="tier-{{ $i }}-triple" class="form-label small">Triple</label>
                                <input type="number" step="0.01" name="tiers[{{ $i }}][prices][triple]" id="tier-{{ $i }}-triple" class="form-control form-control-sm" value="{{ $tier['prices']['triple'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="tier-{{ $i }}-double" class="form-label small">Double</label>
                                <input type="number" step="0.01" name="tiers[{{ $i }}][prices][double]" id="tier-{{ $i }}-double" class="form-control form-control-sm" value="{{ $tier['prices']['double'] ?? '' }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
                            </div>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-check2-square me-2" aria-hidden="true"></i>Inclusions &amp; Exclusions</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label for="pkg-inclusions" class="form-label">Inclusions (one per line)</label>
                    <textarea name="inclusions_text" id="pkg-inclusions" class="form-control" rows="8">{{ old('inclusions_text', $package->inclusions?->pluck('description')->implode("\n")) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="pkg-exclusions" class="form-label">Exclusions (one per line)</label>
                    <textarea name="exclusions_text" id="pkg-exclusions" class="form-control" rows="8">{{ old('exclusions_text', $package->exclusions?->pluck('description')->implode("\n")) }}</textarea>
                </div>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">{{ $package->exists ? 'Update Package' : 'Create Package' }}</button>
            <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    {{-- Hidden templates for JS-added rows --}}
    <template id="itinerary-row-template">
        <tr>
            <td><input type="number" name="itinerary[__INDEX__][day_number]" class="form-control form-control-sm"></td>
            <td><input type="date" name="itinerary[__INDEX__][date_gregorian]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][date_hijri_label]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][city]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][accommodation_a]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][accommodation_b]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="tier-row-template">
        <div class="row g-2 align-items-end mb-2 tier-row border-bottom pb-2">
            <div class="col-md-3">
                <label for="tier-__INDEX__-label" class="form-label small">Tier Label</label>
                <input type="text" name="tiers[__INDEX__][label]" id="tier-__INDEX__-label" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label for="tier-__INDEX__-sharing" class="form-label small">Sharing</label>
                <input type="number" step="0.01" name="tiers[__INDEX__][prices][sharing]" id="tier-__INDEX__-sharing" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label for="tier-__INDEX__-quad" class="form-label small">Quad</label>
                <input type="number" step="0.01" name="tiers[__INDEX__][prices][quad]" id="tier-__INDEX__-quad" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label for="tier-__INDEX__-triple" class="form-label small">Triple</label>
                <input type="number" step="0.01" name="tiers[__INDEX__][prices][triple]" id="tier-__INDEX__-triple" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label for="tier-__INDEX__-double" class="form-label small">Double</label>
                <input type="number" step="0.01" name="tiers[__INDEX__][prices][double]" id="tier-__INDEX__-double" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
            </div>
        </div>
    </template>

    @push('scripts')
    <script>
        (function () {
            let itineraryIndex = {{ count($itineraryRows) }};
            let tierIndex = {{ count($tierRows) }};

            document.getElementById('add-itinerary-row').addEventListener('click', function () {
                const template = document.getElementById('itinerary-row-template').innerHTML.replaceAll('__INDEX__', itineraryIndex++);
                document.querySelector('#itinerary-table tbody').insertAdjacentHTML('beforeend', template);
            });

            document.getElementById('add-tier-row').addEventListener('click', function () {
                const template = document.getElementById('tier-row-template').innerHTML.replaceAll('__INDEX__', tierIndex++);
                document.getElementById('tiers-container').insertAdjacentHTML('beforeend', template);
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-row')) {
                    e.target.closest('tr, .tier-row')?.remove();
                }
            });

            const nameInput = document.getElementById('pkg-name');
            const slugInput = document.getElementById('pkg-slug');
            let slugManuallyEdited = slugInput.value.length > 0;
            slugInput.addEventListener('input', () => { slugManuallyEdited = true; });
            nameInput.addEventListener('input', function () {
                if (slugManuallyEdited) return;
                slugInput.value = this.value.toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            });
        })();
    </script>
    @endpush
@endsection
