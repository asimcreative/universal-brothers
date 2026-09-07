@extends('layouts.admin')

@section('title', $package->exists ? 'Edit Hajj Package' : 'New Hajj Package')
@section('subtitle', $package->exists ? $package->name : 'Build a complete Hajj 2027 package — pricing, accommodation, itinerary and more.')

@section('actions')
    <a href="{{ route('admin.hajj-packages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Hajj Packages</a>
@endsection

@section('content')
    @php
        $hajjFormSections = [
            ['id' => 'sec-basics', 'label' => 'Basic Information', 'icon' => 'bi-info-circle'],
            ['id' => 'sec-variants', 'label' => 'Package Variants (A / B)', 'icon' => 'bi-layers'],
            ['id' => 'sec-accommodation', 'label' => 'Accommodation', 'icon' => 'bi-building'],
            ['id' => 'sec-aziziya', 'label' => 'Aziziya', 'icon' => 'bi-house-heart'],
            ['id' => 'sec-mina', 'label' => 'Mina', 'icon' => 'bi-geo-alt'],
            ['id' => 'sec-arafat', 'label' => 'Arafat', 'icon' => 'bi-geo-alt-fill'],
            ['id' => 'sec-room-pricing', 'label' => 'Room Type Pricing', 'icon' => 'bi-cash-coin'],
            ['id' => 'sec-itinerary', 'label' => 'Day-by-Day Itinerary', 'icon' => 'bi-calendar-week'],
            ['id' => 'sec-transport', 'label' => 'Transportation', 'icon' => 'bi-bus-front'],
            ['id' => 'sec-inclusions', 'label' => 'Inclusions & Exclusions', 'icon' => 'bi-check2-square'],
            ['id' => 'sec-upgrades', 'label' => 'Optional Upgrades', 'icon' => 'bi-plus-circle'],
            ['id' => 'sec-notes', 'label' => 'Notes', 'icon' => 'bi-sticky'],
            ['id' => 'sec-media', 'label' => 'Media', 'icon' => 'bi-images'],
            ['id' => 'sec-seo', 'label' => 'SEO', 'icon' => 'bi-search'],
        ];
    @endphp

    <form method="POST" action="{{ $package->exists ? route('admin.hajj-packages.update', $package) : route('admin.hajj-packages.store') }}" enctype="multipart/form-data">
        @csrf
        @if($package->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="admin-section-nav nav flex-column">
                    @foreach($hajjFormSections as $section)
                        <a class="nav-link" href="#{{ $section['id'] }}"><i class="bi {{ $section['icon'] }} me-2" aria-hidden="true"></i>{{ $section['label'] }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="col-lg-9">
                {{-- Mobile section jump list --}}
                <div class="d-lg-none mb-3">
                    <select class="form-select" onchange="if(this.value) document.getElementById(this.value).scrollIntoView({behavior:'smooth'})">
                        <option value="">Jump to section…</option>
                        @foreach($hajjFormSections as $section)
                            <option value="{{ $section['id'] }}">{{ $section['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 1/2. Basic Information & Package Flags --}}
                <div class="card mb-4 admin-form-section" id="sec-basics">
                    <div class="card-header"><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Basic Information</div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label for="pkg-code" class="form-label">Package Code</label>
                            <input type="text" name="code" id="pkg-code" class="form-control" value="{{ old('code', $package->code) }}" placeholder="e.g. UB001">
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
                            <label for="pkg-package-type" class="form-label">Package Type</label>
                            <input type="text" name="package_type" id="pkg-package-type" class="form-control" value="{{ old('package_type', $package->package_type) }}" placeholder="e.g. Executive Platinum">
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
                            <label for="pkg-summary" class="form-label">Short Description</label>
                            <textarea name="summary" id="pkg-summary" class="form-control" rows="2">{{ old('summary', $package->summary) }}</textarea>
                            <div class="form-text">Shown on the package card and at the top of the package page.</div>
                        </div>
                        <div class="col-12">
                            <label for="pkg-description" class="form-label">Full Description</label>
                            <textarea name="description" id="pkg-description" class="form-control" rows="3">{{ old('description', $package->description) }}</textarea>
                        </div>

                        <div class="col-md-3">
                            <label for="pkg-duration-days" class="form-label">Number of Days</label>
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
                            <label for="pkg-medinah-first" class="form-label">Medinah / Makkah First</label>
                            <select name="medinah_first" id="pkg-medinah-first" class="form-select">
                                <option value="1" {{ old('medinah_first', $package->medinah_first) ? 'selected' : '' }}>Medinah First</option>
                                <option value="0" {{ ! old('medinah_first', $package->medinah_first) ? 'selected' : '' }}>Makkah First</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_shifting" class="form-check-input" id="is_shifting" value="1" {{ old('is_shifting', $package->is_shifting) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_shifting">Shifting Itinerary</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" value="1" {{ old('is_featured', $package->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Featured</label>
                            </div>
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
                    </div>
                </div>

                {{-- 3. Package A / Package B --}}
                <div class="card mb-4 admin-form-section" id="sec-variants">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-layers me-2" aria-hidden="true"></i>Package Variants (A / B)</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="variants-container" data-repeater-template="variant-row-template">+ Add Variant</button>
                    </div>
                    <div class="card-body">
                        <p class="form-section-hint">Leave empty if this package has no Package A/B split (e.g. a single accommodation column throughout). Other sections below reference a variant by typing the same Code (e.g. "A") — not by picking from a list — so rows can be reordered freely.</p>
                        <div id="variants-container">
                            @php $variantRows = old('variants', $package->variants?->map(fn ($v) => ['code' => $v->code, 'label' => $v->label])->toArray() ?? []); @endphp
                            @foreach($variantRows as $i => $row)
                                <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
                                    <div class="col-md-2">
                                        <label class="form-label small">Code</label>
                                        <input type="text" name="variants[{{ $i }}][code]" aria-label="Variant code" class="form-control form-control-sm" value="{{ $row['code'] ?? '' }}" placeholder="A">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small">Label</label>
                                        <input type="text" name="variants[{{ $i }}][label]" aria-label="Variant label" class="form-control form-control-sm" value="{{ $row['label'] ?? '' }}" placeholder="e.g. Dar Al Tawhid Intercontinental">
                                    </div>
                                    <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 4. Accommodation (Makkah / Medinah / Aziziya / Mina / Arafat) --}}
                <div class="card mb-4 admin-form-section" id="sec-accommodation">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-building me-2" aria-hidden="true"></i>Accommodation</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="accommodations-container" data-repeater-template="accommodation-row-template">+ Add Accommodation</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Location</th><th>Variant Code</th><th>Hotel / Label</th><th>Stars</th><th>Meal Plan</th><th>Nights</th><th>Notes</th><th></th></tr></thead>
                                <tbody id="accommodations-container">
                                    @php $accommodationRows = old('accommodations', $package->accommodations?->map(fn ($a) => ['location' => $a->location, 'variant_code' => $a->variant?->code, 'hotel_name' => $a->hotel_name, 'star_rating' => $a->star_rating, 'meal_plan' => $a->meal_plan, 'distance_note' => $a->distance_note, 'nights' => $a->nights, 'notes' => $a->notes])->toArray() ?? []); @endphp
                                    @foreach($accommodationRows as $i => $row)
                                        <tr class="repeater-row">
                                            <td>
                                                <select name="accommodations[{{ $i }}][location]" class="form-select form-select-sm">
                                                    @foreach(['makkah','medinah','aziziya','mina','arafat'] as $loc)
                                                        <option value="{{ $loc }}" {{ ($row['location'] ?? '') === $loc ? 'selected' : '' }}>{{ ucfirst($loc) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="accommodations[{{ $i }}][variant_code]" class="form-control form-control-sm" value="{{ $row['variant_code'] ?? '' }}" style="width:70px" placeholder="A/B"></td>
                                            <td><input type="text" name="accommodations[{{ $i }}][hotel_name]" class="form-control form-control-sm" value="{{ $row['hotel_name'] ?? '' }}"></td>
                                            <td><input type="number" name="accommodations[{{ $i }}][star_rating]" class="form-control form-control-sm" value="{{ $row['star_rating'] ?? '' }}" style="width:60px" min="1" max="5"></td>
                                            <td><input type="text" name="accommodations[{{ $i }}][meal_plan]" class="form-control form-control-sm" value="{{ $row['meal_plan'] ?? '' }}"></td>
                                            <td><input type="number" name="accommodations[{{ $i }}][nights]" class="form-control form-control-sm" value="{{ $row['nights'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="text" name="accommodations[{{ $i }}][notes]" class="form-control form-control-sm" value="{{ $row['notes'] ?? '' }}"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 5. Aziziya --}}
                @php $az = $package->aziziya; @endphp
                <div class="card mb-4 admin-form-section" id="sec-aziziya">
                    <div class="card-header"><i class="bi bi-house-heart me-2" aria-hidden="true"></i>Aziziya</div>
                    <div class="card-body row g-3">
                        <div class="col-md-3">
                            <label for="az-status" class="form-label">Aziziya Status</label>
                            <select name="aziziya[status]" id="az-status" class="form-select">
                                <option value="">— Not Set —</option>
                                @foreach(['included' => 'Included', 'not_included' => 'Not Included', 'optional' => 'Optional Upgrade', 'not_applicable' => 'Not Applicable'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('aziziya.status', $az?->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="az-accommodation-name" class="form-label">Accommodation Name</label>
                            <input type="text" name="aziziya[accommodation_name]" id="az-accommodation-name" class="form-control" value="{{ old('aziziya.accommodation_name', $az?->accommodation_name) }}" placeholder="e.g. AZIZIYA Accommodation - A Class">
                        </div>
                        <div class="col-md-4">
                            <label for="az-location-note" class="form-label">Location Note</label>
                            <input type="text" name="aziziya[location_note]" id="az-location-note" class="form-control" value="{{ old('aziziya.location_note', $az?->location_note) }}" placeholder="e.g. Opposite Jamarat escalator">
                        </div>
                        <div class="col-md-4">
                            <label for="az-walk-distance" class="form-label">Walk Distance</label>
                            <input type="text" name="aziziya[walk_distance]" id="az-walk-distance" class="form-control" value="{{ old('aziziya.walk_distance', $az?->walk_distance) }}" placeholder="e.g. 30 to 50-minute walk to Mina">
                        </div>
                        <div class="col-md-2">
                            <label for="az-duration-days" class="form-label">Duration (days)</label>
                            <input type="number" name="aziziya[duration_days]" id="az-duration-days" class="form-control" value="{{ old('aziziya.duration_days', $az?->duration_days) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="az-occupancy" class="form-label">Avg. Occupancy</label>
                            <input type="number" name="aziziya[average_occupancy]" id="az-occupancy" class="form-control" value="{{ old('aziziya.average_occupancy', $az?->average_occupancy) }}">
                        </div>
                        <div class="col-12">
                            <label for="az-description" class="form-label">Description</label>
                            <textarea name="aziziya[description]" id="az-description" class="form-control" rows="2">{{ old('aziziya.description', $az?->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="az-notes" class="form-label">Notes</label>
                            <textarea name="aziziya[notes]" id="az-notes" class="form-control" rows="2">{{ old('aziziya.notes', $az?->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="card-header d-flex justify-content-between border-top">
                        <span>Aziziya Room / Sharing Options</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="aziziya-room-options-container" data-repeater-template="aziziya-room-option-row-template">+ Add Option</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Variant</th><th>Sharing Type</th><th>Occ.</th><th>Label</th><th>Pricing Type</th><th>Basis</th><th>PKR</th><th>SAR</th><th>USD</th><th></th></tr></thead>
                                <tbody id="aziziya-room-options-container">
                                    @php $azRoomRows = old('aziziya_room_options', $az?->roomOptions?->map(fn ($r) => ['variant_code' => $r->variant?->code, 'sharing_type' => $r->sharing_type, 'occupancy' => $r->occupancy, 'display_label' => $r->display_label, 'pricing_type' => $r->pricing_type, 'price_basis' => $r->price_basis, 'price_pkr' => $r->price_pkr, 'price_sar' => $r->price_sar, 'price_usd' => $r->price_usd])->toArray() ?? []); @endphp
                                    @foreach($azRoomRows as $i => $row)
                                        <tr class="repeater-row">
                                            <td><input type="text" name="aziziya_room_options[{{ $i }}][variant_code]" class="form-control form-control-sm" value="{{ $row['variant_code'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="text" name="aziziya_room_options[{{ $i }}][sharing_type]" class="form-control form-control-sm" value="{{ $row['sharing_type'] ?? '' }}"></td>
                                            <td><input type="number" name="aziziya_room_options[{{ $i }}][occupancy]" class="form-control form-control-sm" value="{{ $row['occupancy'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="text" name="aziziya_room_options[{{ $i }}][display_label]" class="form-control form-control-sm" value="{{ $row['display_label'] ?? '' }}"></td>
                                            <td>
                                                <select name="aziziya_room_options[{{ $i }}][pricing_type]" class="form-select form-select-sm">
                                                    @foreach(['included','supplement','optional','upgrade','on_request'] as $pt)
                                                        <option value="{{ $pt }}" {{ ($row['pricing_type'] ?? '') === $pt ? 'selected' : '' }}>{{ $pt }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="aziziya_room_options[{{ $i }}][price_basis]" class="form-control form-control-sm" value="{{ $row['price_basis'] ?? 'per_person' }}" style="width:100px"></td>
                                            <td><input type="number" step="0.01" name="aziziya_room_options[{{ $i }}][price_pkr]" class="form-control form-control-sm" value="{{ $row['price_pkr'] ?? '' }}" style="width:90px"></td>
                                            <td><input type="number" step="0.01" name="aziziya_room_options[{{ $i }}][price_sar]" class="form-control form-control-sm" value="{{ $row['price_sar'] ?? '' }}" style="width:90px"></td>
                                            <td><input type="number" step="0.01" name="aziziya_room_options[{{ $i }}][price_usd]" class="form-control form-control-sm" value="{{ $row['price_usd'] ?? '' }}" style="width:90px"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-header d-flex justify-content-between border-top">
                        <span>Aziziya Optional Services</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="aziziya-services-container" data-repeater-template="aziziya-service-row-template">+ Add Service</button>
                    </div>
                    <div class="card-body">
                        <div id="aziziya-services-container">
                            @php $azServiceRows = old('aziziya_services', $az?->services?->map(fn ($s) => ['name' => $s->name, 'description' => $s->description, 'is_included' => $s->is_included, 'price' => $s->price, 'currency' => $s->currency, 'price_basis' => $s->price_basis])->toArray() ?? []); @endphp
                            @foreach($azServiceRows as $i => $row)
                                <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
                                    <div class="col-md-3"><label class="form-label small">Name</label><input type="text" name="aziziya_services[{{ $i }}][name]" aria-label="Service name" class="form-control form-control-sm" value="{{ $row['name'] ?? '' }}"></div>
                                    <div class="col-md-4"><label class="form-label small">Description</label><input type="text" name="aziziya_services[{{ $i }}][description]" aria-label="Service description" class="form-control form-control-sm" value="{{ $row['description'] ?? '' }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Price</label><input type="number" step="0.01" name="aziziya_services[{{ $i }}][price]" aria-label="Service price" class="form-control form-control-sm" value="{{ $row['price'] ?? '' }}"></div>
                                    <div class="col-md-2">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" name="aziziya_services[{{ $i }}][is_included]" aria-label="Included" class="form-check-input" value="1" {{ ($row['is_included'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Included</label>
                                        </div>
                                    </div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 6/7. Mina & Arafat --}}
                @php
                    $mina = $package->mashaerDetails?->firstWhere('location', 'mina');
                    $arafat = $package->mashaerDetails?->firstWhere('location', 'arafat');
                @endphp
                <div class="card mb-4 admin-form-section" id="sec-mina">
                    <div class="card-header"><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Mina</div>
                    <div class="card-body row g-3">
                        @foreach(['maktab' => 'Maktab', 'category' => 'Category', 'zone' => 'Zone', 'tent_type' => 'Tent Type', 'accommodation_type' => 'Accommodation Type', 'meal_plan' => 'Meal Plan', 'bathroom' => 'Bathroom', 'air_conditioning' => 'Air Conditioning', 'transportation' => 'Transportation'] as $field => $label)
                            <div class="col-md-4">
                                <label for="mina-{{ $field }}" class="form-label">{{ $label }}</label>
                                <input type="text" name="mashaer[mina][{{ $field }}]" id="mina-{{ $field }}" class="form-control" value="{{ old("mashaer.mina.$field", $mina?->{$field}) }}">
                            </div>
                        @endforeach
                        <div class="col-12"><label for="mina-other-services" class="form-label">Other Services</label><textarea name="mashaer[mina][other_services]" id="mina-other-services" class="form-control" rows="2">{{ old('mashaer.mina.other_services', $mina?->other_services) }}</textarea></div>
                        <div class="col-12"><label for="mina-notes" class="form-label">Notes</label><textarea name="mashaer[mina][notes]" id="mina-notes" class="form-control" rows="2">{{ old('mashaer.mina.notes', $mina?->notes) }}</textarea></div>
                    </div>
                </div>

                <div class="card mb-4 admin-form-section" id="sec-arafat">
                    <div class="card-header"><i class="bi bi-geo-alt-fill me-2" aria-hidden="true"></i>Arafat</div>
                    <div class="card-body row g-3">
                        @foreach(['maktab' => 'Maktab', 'category' => 'Service Category', 'tent_type' => 'Tent Type', 'meal_plan' => 'Meal Plan', 'bathroom' => 'Bathroom', 'air_conditioning' => 'Air Conditioning'] as $field => $label)
                            <div class="col-md-4">
                                <label for="arafat-{{ $field }}" class="form-label">{{ $label }}</label>
                                <input type="text" name="mashaer[arafat][{{ $field }}]" id="arafat-{{ $field }}" class="form-control" value="{{ old("mashaer.arafat.$field", $arafat?->{$field}) }}">
                            </div>
                        @endforeach
                        <div class="col-12"><label for="arafat-other-services" class="form-label">Other Services</label><textarea name="mashaer[arafat][other_services]" id="arafat-other-services" class="form-control" rows="2">{{ old('mashaer.arafat.other_services', $arafat?->other_services) }}</textarea></div>
                        <div class="col-12"><label for="arafat-notes" class="form-label">Notes</label><textarea name="mashaer[arafat][notes]" id="arafat-notes" class="form-control" rows="2">{{ old('mashaer.arafat.notes', $arafat?->notes) }}</textarea></div>
                    </div>
                </div>

                {{-- 9. Main Package Room / Sharing Prices --}}
                <div class="card mb-4 admin-form-section" id="sec-room-pricing">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-cash-coin me-2" aria-hidden="true"></i>Room Type Pricing</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="room-options-container" data-repeater-template="room-option-row-template">+ Add Room Type</button>
                    </div>
                    <div class="card-body">
                        <p class="form-section-hint">Add as many sharing types as this package actually offers (Quad, Triple, Double, Sharing Room, Twin, Single, ...) — not limited to a fixed list. Leave a currency blank if the brochure gives no value for it (do not guess). Uncheck "Available" for a real "N/A" cell rather than leaving the row out.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Variant</th><th>Sharing Type</th><th>Occ.</th><th>Label</th><th>Basis</th><th>PKR</th><th>SAR</th><th>USD</th><th>Avail.</th><th></th></tr></thead>
                                <tbody id="room-options-container">
                                    @php $roomOptionRows = old('room_options', $package->roomOptions?->map(fn ($r) => ['variant_code' => $r->variant?->code, 'sharing_type' => $r->sharing_type, 'occupancy' => $r->occupancy, 'display_label' => $r->display_label, 'price_basis' => $r->price_basis, 'price_pkr' => $r->price_pkr, 'price_sar' => $r->price_sar, 'price_usd' => $r->price_usd, 'is_available' => $r->is_available])->toArray() ?? []); @endphp
                                    @foreach($roomOptionRows as $i => $row)
                                        <tr class="repeater-row">
                                            <td><input type="text" name="room_options[{{ $i }}][variant_code]" class="form-control form-control-sm" value="{{ $row['variant_code'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="text" name="room_options[{{ $i }}][sharing_type]" class="form-control form-control-sm" value="{{ $row['sharing_type'] ?? '' }}"></td>
                                            <td><input type="number" name="room_options[{{ $i }}][occupancy]" class="form-control form-control-sm" value="{{ $row['occupancy'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="text" name="room_options[{{ $i }}][display_label]" class="form-control form-control-sm" value="{{ $row['display_label'] ?? '' }}"></td>
                                            <td><input type="text" name="room_options[{{ $i }}][price_basis]" class="form-control form-control-sm" value="{{ $row['price_basis'] ?? 'per_person' }}" style="width:100px"></td>
                                            <td><input type="number" step="0.01" name="room_options[{{ $i }}][price_pkr]" class="form-control form-control-sm" value="{{ $row['price_pkr'] ?? '' }}" style="width:90px"></td>
                                            <td><input type="number" step="0.01" name="room_options[{{ $i }}][price_sar]" class="form-control form-control-sm" value="{{ $row['price_sar'] ?? '' }}" style="width:90px"></td>
                                            <td><input type="number" step="0.01" name="room_options[{{ $i }}][price_usd]" class="form-control form-control-sm" value="{{ $row['price_usd'] ?? '' }}" style="width:90px"></td>
                                            <td class="text-center"><input type="checkbox" name="room_options[{{ $i }}][is_available]" class="form-check-input" value="1" {{ ($row['is_available'] ?? false) ? 'checked' : '' }}></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 10. Itinerary --}}
                <div class="card mb-4 admin-form-section" id="sec-itinerary">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-calendar-week me-2" aria-hidden="true"></i>Day-by-Day Itinerary</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="itinerary-container" data-repeater-template="itinerary-row-template">+ Add Day</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Day #</th><th>English Date</th><th>Islamic Date</th><th>City</th><th>Accommodation A</th><th>Accommodation B</th><th>Notes</th><th></th></tr></thead>
                                <tbody id="itinerary-container">
                                    @php $itineraryRows = old('itinerary', $package->itineraryDays?->map(fn ($d) => ['day_number' => $d->day_number, 'date_gregorian' => $d->date_gregorian?->format('Y-m-d'), 'date_hijri_label' => $d->date_hijri_label, 'city' => $d->city, 'accommodation_a' => $d->accommodation_a, 'accommodation_b' => $d->accommodation_b, 'notes' => $d->notes])->toArray() ?? []); @endphp
                                    @foreach($itineraryRows as $i => $row)
                                        <tr class="repeater-row">
                                            <td><input type="number" name="itinerary[{{ $i }}][day_number]" class="form-control form-control-sm" value="{{ $row['day_number'] ?? '' }}" style="width:60px"></td>
                                            <td><input type="date" name="itinerary[{{ $i }}][date_gregorian]" class="form-control form-control-sm" value="{{ $row['date_gregorian'] ?? '' }}"></td>
                                            <td><input type="text" name="itinerary[{{ $i }}][date_hijri_label]" class="form-control form-control-sm" value="{{ $row['date_hijri_label'] ?? '' }}"></td>
                                            <td><input type="text" name="itinerary[{{ $i }}][city]" class="form-control form-control-sm" value="{{ $row['city'] ?? '' }}"></td>
                                            <td><input type="text" name="itinerary[{{ $i }}][accommodation_a]" class="form-control form-control-sm" value="{{ $row['accommodation_a'] ?? '' }}"></td>
                                            <td><input type="text" name="itinerary[{{ $i }}][accommodation_b]" class="form-control form-control-sm" value="{{ $row['accommodation_b'] ?? '' }}"></td>
                                            <td><input type="text" name="itinerary[{{ $i }}][notes]" class="form-control form-control-sm" value="{{ $row['notes'] ?? '' }}"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 11. Transportation --}}
                <div class="card mb-4 admin-form-section" id="sec-transport">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-bus-front me-2" aria-hidden="true"></i>Transportation</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="transportation-container" data-repeater-template="transportation-row-template">+ Add Transport</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>From</th><th>To</th><th>Type</th><th>Included</th><th>Price</th><th>Currency</th><th>Basis</th><th>Notes</th><th></th></tr></thead>
                                <tbody id="transportation-container">
                                    @php $transportRows = old('transportation', $package->transportation?->map(fn ($t) => ['from_location' => $t->from_location, 'to_location' => $t->to_location, 'transport_type' => $t->transport_type, 'is_included' => $t->is_included, 'price' => $t->price, 'currency' => $t->currency, 'price_basis' => $t->price_basis, 'notes' => $t->notes])->toArray() ?? []); @endphp
                                    @foreach($transportRows as $i => $row)
                                        <tr class="repeater-row">
                                            <td><input type="text" name="transportation[{{ $i }}][from_location]" class="form-control form-control-sm" value="{{ $row['from_location'] ?? '' }}"></td>
                                            <td><input type="text" name="transportation[{{ $i }}][to_location]" class="form-control form-control-sm" value="{{ $row['to_location'] ?? '' }}"></td>
                                            <td><input type="text" name="transportation[{{ $i }}][transport_type]" class="form-control form-control-sm" value="{{ $row['transport_type'] ?? '' }}"></td>
                                            <td class="text-center"><input type="checkbox" name="transportation[{{ $i }}][is_included]" class="form-check-input" value="1" {{ ($row['is_included'] ?? false) ? 'checked' : '' }}></td>
                                            <td><input type="number" step="0.01" name="transportation[{{ $i }}][price]" class="form-control form-control-sm" value="{{ $row['price'] ?? '' }}" style="width:90px"></td>
                                            <td>
                                                <select name="transportation[{{ $i }}][currency]" class="form-select form-select-sm">
                                                    @foreach(['USD','SAR','PKR'] as $cur)
                                                        <option value="{{ $cur }}" {{ ($row['currency'] ?? 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="transportation[{{ $i }}][price_basis]" class="form-control form-control-sm" value="{{ $row['price_basis'] ?? '' }}" style="width:110px"></td>
                                            <td><input type="text" name="transportation[{{ $i }}][notes]" class="form-control form-control-sm" value="{{ $row['notes'] ?? '' }}"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 12/13. Inclusions & Exclusions --}}
                <div class="card mb-4 admin-form-section" id="sec-inclusions">
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

                {{-- 15. Upgrades --}}
                <div class="card mb-4 admin-form-section" id="sec-upgrades">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Optional Upgrades</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="upgrades-container" data-repeater-template="upgrade-row-template">+ Add Upgrade</button>
                    </div>
                    <div class="card-body">
                        <p class="form-section-hint">Main-package upgrades only (e.g. Kaba view supplement, additional Medinah night) — Aziziya-specific upgrades belong in the Aziziya section above, not here.</p>
                        <div id="upgrades-container">
                            @php $upgradeRows = old('upgrades', $package->upgrades?->map(fn ($u) => ['name' => $u->name, 'description' => $u->description, 'price' => $u->price, 'currency' => $u->currency, 'price_basis' => $u->price_basis, 'is_included' => $u->is_included, 'notes' => $u->notes])->toArray() ?? []); @endphp
                            @foreach($upgradeRows as $i => $row)
                                <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
                                    <div class="col-md-3"><label class="form-label small">Name</label><input type="text" name="upgrades[{{ $i }}][name]" aria-label="Upgrade name" class="form-control form-control-sm" value="{{ $row['name'] ?? '' }}"></div>
                                    <div class="col-md-3"><label class="form-label small">Description</label><input type="text" name="upgrades[{{ $i }}][description]" aria-label="Upgrade description" class="form-control form-control-sm" value="{{ $row['description'] ?? '' }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Price</label><input type="number" step="0.01" name="upgrades[{{ $i }}][price]" aria-label="Upgrade price" class="form-control form-control-sm" value="{{ $row['price'] ?? '' }}"></div>
                                    <div class="col-md-1">
                                        <label class="form-label small">Cur.</label>
                                        <select name="upgrades[{{ $i }}][currency]" aria-label="Upgrade currency" class="form-select form-select-sm">
                                            @foreach(['USD','SAR','PKR'] as $cur)
                                                <option value="{{ $cur }}" {{ ($row['currency'] ?? 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2"><label class="form-label small">Basis</label><input type="text" name="upgrades[{{ $i }}][price_basis]" aria-label="Upgrade price basis" class="form-control form-control-sm" value="{{ $row['price_basis'] ?? '' }}"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 16. Notes --}}
                <div class="card mb-4 admin-form-section" id="sec-notes">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-sticky me-2" aria-hidden="true"></i>Package Notes</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="notes-container" data-repeater-template="note-row-template">+ Add Note</button>
                    </div>
                    <div class="card-body">
                        <div id="notes-container">
                            @php $noteRows = old('notes', $package->packageNotes?->map(fn ($n) => ['note_type' => $n->note_type, 'title' => $n->title, 'content' => $n->content, 'is_important' => $n->is_important])->toArray() ?? []); @endphp
                            @foreach($noteRows as $i => $row)
                                <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
                                    <div class="col-md-2">
                                        <label class="form-label small">Type</label>
                                        <select name="notes[{{ $i }}][note_type]" aria-label="Note type" class="form-select form-select-sm">
                                            @foreach(['general','pricing','accommodation','booking','travel','important','disclaimer'] as $nt)
                                                <option value="{{ $nt }}" {{ ($row['note_type'] ?? 'general') === $nt ? 'selected' : '' }}>{{ $nt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label small">Title</label><input type="text" name="notes[{{ $i }}][title]" aria-label="Note title" class="form-control form-control-sm" value="{{ $row['title'] ?? '' }}"></div>
                                    <div class="col-md-5"><label class="form-label small">Content</label><input type="text" name="notes[{{ $i }}][content]" aria-label="Note content" class="form-control form-control-sm" value="{{ $row['content'] ?? '' }}"></div>
                                    <div class="col-md-1">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" name="notes[{{ $i }}][is_important]" aria-label="Important note" class="form-check-input" value="1" {{ ($row['is_important'] ?? false) ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 17. Media --}}
                <div class="card mb-4 admin-form-section" id="sec-media">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-images me-2" aria-hidden="true"></i>Media</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-repeater-add="media-container" data-repeater-template="media-row-template">+ Add Media</button>
                    </div>
                    <div class="card-body">
                        <div id="media-container">
                            @php
                                $mediaRows = old('media', $package->media?->map(fn ($m) => ['id' => $m->id, 'media_type' => $m->media_type, 'image_path' => $m->image_path, 'video_url' => $m->video_url, 'alt_text' => $m->alt_text, 'caption' => $m->caption])->toArray() ?? []);
                                // old('media') never carries `image_path` — there is no
                                // such form field (a file input can never be
                                // re-populated from old input, see the C-2 media-
                                // persistence fix this repeater already relies on).
                                // Backfill it from the real DB record via each row's
                                // own hidden `id` so a validation-failure redisplay
                                // still shows the "keep current image" hint/thumbnail
                                // instead of silently losing it.
                                if (old('media')) {
                                    $existingMediaById = $package->media?->keyBy('id') ?? collect();
                                    foreach ($mediaRows as $idx => $row) {
                                        $mediaRows[$idx]['image_path'] = $existingMediaById->get($row['id'] ?? null)?->image_path;
                                    }
                                }
                            @endphp
                            @foreach($mediaRows as $i => $row)
                                <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
                                    <input type="hidden" name="media[{{ $i }}][id]" value="{{ $row['id'] }}">
                                    <div class="col-md-2">
                                        <label class="form-label small">Type</label>
                                        <select name="media[{{ $i }}][media_type]" aria-label="Media type" class="form-select form-select-sm">
                                            @foreach(['gallery','hotel','accommodation','aziziya','other'] as $mt)
                                                <option value="{{ $mt }}" {{ $row['media_type'] === $mt ? 'selected' : '' }}>{{ $mt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Image {{ $row['image_path'] ? '(leave blank to keep current)' : '' }}</label>
                                        <input type="file" name="media[{{ $i }}][file]" aria-label="Media image" class="form-control form-control-sm" accept="image/*">
                                        @if($row['image_path'])
                                            <img src="{{ Storage::url($row['image_path']) }}" class="mt-1 rounded" style="height:40px;" alt="Current media image">
                                        @endif
                                    </div>
                                    <div class="col-md-2"><label class="form-label small">Video URL</label><input type="text" name="media[{{ $i }}][video_url]" aria-label="Media video URL" class="form-control form-control-sm" value="{{ $row['video_url'] ?? '' }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Alt Text</label><input type="text" name="media[{{ $i }}][alt_text]" aria-label="Media alt text" class="form-control form-control-sm" value="{{ $row['alt_text'] ?? '' }}"></div>
                                    <div class="col-md-2"><label class="form-label small">Caption</label><input type="text" name="media[{{ $i }}][caption]" aria-label="Media caption" class="form-control form-control-sm" value="{{ $row['caption'] ?? '' }}"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 18. SEO --}}
                <div class="card mb-4 admin-form-section" id="sec-seo">
                    <div class="card-header"><i class="bi bi-search me-2" aria-hidden="true"></i>SEO</div>
                    <div class="card-body row g-3">
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

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary">{{ $package->exists ? 'Update Hajj Package' : 'Create Hajj Package' }}</button>
                    <a href="{{ route('admin.hajj-packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Repeater row templates --}}
    <template id="variant-row-template">
        <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
            <div class="col-md-2"><label class="form-label small">Code</label><input type="text" name="variants[__INDEX__][code]" aria-label="Variant code" class="form-control form-control-sm" placeholder="A"></div>
            <div class="col-md-8"><label class="form-label small">Label</label><input type="text" name="variants[__INDEX__][label]" aria-label="Variant label" class="form-control form-control-sm"></div>
            <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
        </div>
    </template>

    <template id="accommodation-row-template">
        <tr class="repeater-row">
            <td>
                <select name="accommodations[__INDEX__][location]" class="form-select form-select-sm">
                    <option value="makkah">Makkah</option><option value="medinah">Medinah</option><option value="aziziya">Aziziya</option><option value="mina">Mina</option><option value="arafat">Arafat</option>
                </select>
            </td>
            <td><input type="text" name="accommodations[__INDEX__][variant_code]" class="form-control form-control-sm" style="width:70px" placeholder="A/B"></td>
            <td><input type="text" name="accommodations[__INDEX__][hotel_name]" class="form-control form-control-sm"></td>
            <td><input type="number" name="accommodations[__INDEX__][star_rating]" class="form-control form-control-sm" style="width:60px" min="1" max="5"></td>
            <td><input type="text" name="accommodations[__INDEX__][meal_plan]" class="form-control form-control-sm"></td>
            <td><input type="number" name="accommodations[__INDEX__][nights]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="accommodations[__INDEX__][notes]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="aziziya-room-option-row-template">
        <tr class="repeater-row">
            <td><input type="text" name="aziziya_room_options[__INDEX__][variant_code]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="aziziya_room_options[__INDEX__][sharing_type]" class="form-control form-control-sm"></td>
            <td><input type="number" name="aziziya_room_options[__INDEX__][occupancy]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="aziziya_room_options[__INDEX__][display_label]" class="form-control form-control-sm"></td>
            <td>
                <select name="aziziya_room_options[__INDEX__][pricing_type]" class="form-select form-select-sm">
                    <option value="included">included</option><option value="supplement">supplement</option><option value="optional">optional</option><option value="upgrade">upgrade</option><option value="on_request">on_request</option>
                </select>
            </td>
            <td><input type="text" name="aziziya_room_options[__INDEX__][price_basis]" class="form-control form-control-sm" value="per_person" style="width:100px"></td>
            <td><input type="number" step="0.01" name="aziziya_room_options[__INDEX__][price_pkr]" class="form-control form-control-sm" style="width:90px"></td>
            <td><input type="number" step="0.01" name="aziziya_room_options[__INDEX__][price_sar]" class="form-control form-control-sm" style="width:90px"></td>
            <td><input type="number" step="0.01" name="aziziya_room_options[__INDEX__][price_usd]" class="form-control form-control-sm" style="width:90px"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="aziziya-service-row-template">
        <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
            <div class="col-md-3"><label class="form-label small">Name</label><input type="text" name="aziziya_services[__INDEX__][name]" aria-label="Service name" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label class="form-label small">Description</label><input type="text" name="aziziya_services[__INDEX__][description]" aria-label="Service description" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Price</label><input type="number" step="0.01" name="aziziya_services[__INDEX__][price]" aria-label="Service price" class="form-control form-control-sm"></div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input type="checkbox" name="aziziya_services[__INDEX__][is_included]" aria-label="Included" class="form-check-input" value="1" checked>
                    <label class="form-check-label small">Included</label>
                </div>
            </div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
        </div>
    </template>

    <template id="room-option-row-template">
        <tr class="repeater-row">
            <td><input type="text" name="room_options[__INDEX__][variant_code]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="room_options[__INDEX__][sharing_type]" class="form-control form-control-sm"></td>
            <td><input type="number" name="room_options[__INDEX__][occupancy]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="room_options[__INDEX__][display_label]" class="form-control form-control-sm"></td>
            <td><input type="text" name="room_options[__INDEX__][price_basis]" class="form-control form-control-sm" value="per_person" style="width:100px"></td>
            <td><input type="number" step="0.01" name="room_options[__INDEX__][price_pkr]" class="form-control form-control-sm" style="width:90px"></td>
            <td><input type="number" step="0.01" name="room_options[__INDEX__][price_sar]" class="form-control form-control-sm" style="width:90px"></td>
            <td><input type="number" step="0.01" name="room_options[__INDEX__][price_usd]" class="form-control form-control-sm" style="width:90px"></td>
            <td class="text-center"><input type="checkbox" name="room_options[__INDEX__][is_available]" class="form-check-input" value="1" checked></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="itinerary-row-template">
        <tr class="repeater-row">
            <td><input type="number" name="itinerary[__INDEX__][day_number]" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="date" name="itinerary[__INDEX__][date_gregorian]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][date_hijri_label]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][city]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][accommodation_a]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][accommodation_b]" class="form-control form-control-sm"></td>
            <td><input type="text" name="itinerary[__INDEX__][notes]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="transportation-row-template">
        <tr class="repeater-row">
            <td><input type="text" name="transportation[__INDEX__][from_location]" class="form-control form-control-sm"></td>
            <td><input type="text" name="transportation[__INDEX__][to_location]" class="form-control form-control-sm"></td>
            <td><input type="text" name="transportation[__INDEX__][transport_type]" class="form-control form-control-sm"></td>
            <td class="text-center"><input type="checkbox" name="transportation[__INDEX__][is_included]" class="form-check-input" value="1" checked></td>
            <td><input type="number" step="0.01" name="transportation[__INDEX__][price]" class="form-control form-control-sm" style="width:90px"></td>
            <td>
                <select name="transportation[__INDEX__][currency]" class="form-select form-select-sm">
                    <option value="USD">USD</option><option value="SAR">SAR</option><option value="PKR">PKR</option>
                </select>
            </td>
            <td><input type="text" name="transportation[__INDEX__][price_basis]" class="form-control form-control-sm" style="width:110px"></td>
            <td><input type="text" name="transportation[__INDEX__][notes]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
        </tr>
    </template>

    <template id="upgrade-row-template">
        <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
            <div class="col-md-3"><label class="form-label small">Name</label><input type="text" name="upgrades[__INDEX__][name]" aria-label="Upgrade name" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label small">Description</label><input type="text" name="upgrades[__INDEX__][description]" aria-label="Upgrade description" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Price</label><input type="number" step="0.01" name="upgrades[__INDEX__][price]" aria-label="Upgrade price" class="form-control form-control-sm"></div>
            <div class="col-md-1">
                <label class="form-label small">Cur.</label>
                <select name="upgrades[__INDEX__][currency]" aria-label="Upgrade currency" class="form-select form-select-sm">
                    <option value="USD">USD</option><option value="SAR">SAR</option><option value="PKR">PKR</option>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small">Basis</label><input type="text" name="upgrades[__INDEX__][price_basis]" aria-label="Upgrade price basis" class="form-control form-control-sm"></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
        </div>
    </template>

    <template id="note-row-template">
        <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
            <div class="col-md-2">
                <label class="form-label small">Type</label>
                <select name="notes[__INDEX__][note_type]" aria-label="Note type" class="form-select form-select-sm">
                    <option value="general">general</option><option value="pricing">pricing</option><option value="accommodation">accommodation</option><option value="booking">booking</option><option value="travel">travel</option><option value="important">important</option><option value="disclaimer">disclaimer</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small">Title</label><input type="text" name="notes[__INDEX__][title]" aria-label="Note title" class="form-control form-control-sm"></div>
            <div class="col-md-5"><label class="form-label small">Content</label><input type="text" name="notes[__INDEX__][content]" aria-label="Note content" class="form-control form-control-sm"></div>
            <div class="col-md-1"><div class="form-check mt-4"><input type="checkbox" name="notes[__INDEX__][is_important]" aria-label="Important note" class="form-check-input" value="1"></div></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
        </div>
    </template>

    <template id="media-row-template">
        <div class="row g-2 align-items-end mb-2 repeater-row border-bottom pb-2">
            <div class="col-md-2">
                <label class="form-label small">Type</label>
                <select name="media[__INDEX__][media_type]" aria-label="Media type" class="form-select form-select-sm">
                    <option value="gallery">gallery</option><option value="hotel">hotel</option><option value="accommodation">accommodation</option><option value="aziziya">aziziya</option><option value="other">other</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small">Image</label><input type="file" name="media[__INDEX__][file]" aria-label="Media image" class="form-control form-control-sm" accept="image/*"></div>
            <div class="col-md-2"><label class="form-label small">Video URL</label><input type="text" name="media[__INDEX__][video_url]" aria-label="Media video URL" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Alt Text</label><input type="text" name="media[__INDEX__][alt_text]" aria-label="Media alt text" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Caption</label><input type="text" name="media[__INDEX__][caption]" aria-label="Media caption" class="form-control form-control-sm"></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></div>
        </div>
    </template>

    @push('scripts')
    <script>
        (function () {
            const nextIndex = {};

            document.querySelectorAll('[data-repeater-add]').forEach(function (btn) {
                const containerId = btn.dataset.repeaterAdd;
                const container = document.getElementById(containerId);
                nextIndex[containerId] = container.children.length;

                btn.addEventListener('click', function () {
                    const template = document.getElementById(btn.dataset.repeaterTemplate).innerHTML.replaceAll('__INDEX__', nextIndex[containerId]++);
                    container.insertAdjacentHTML('beforeend', template);
                });
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-row')) {
                    e.target.closest('.repeater-row')?.remove();
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

            // Section-nav active-state tracking while scrolling.
            const sectionLinks = Array.from(document.querySelectorAll('.admin-section-nav .nav-link'));
            const sections = sectionLinks.map(link => document.querySelector(link.getAttribute('href')));
            if ('IntersectionObserver' in window && sectionLinks.length) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        const idx = sections.indexOf(entry.target);
                        if (idx === -1) return;
                        sectionLinks.forEach(l => l.classList.remove('active'));
                        sectionLinks[idx].classList.add('active');
                    });
                }, { rootMargin: '-20% 0px -70% 0px' });
                sections.forEach(section => section && observer.observe(section));
            }
        })();
    </script>
    @endpush
@endsection
