{{-- Dialogs used by the package builder. --}}
@push('modals')
    {{-- Pick saved items (transport, services, additional options, notes) --}}
    <div class="modal fade" id="libraryPickerModal" tabindex="-1" aria-labelledby="libraryPickerTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="libraryPickerTitle" data-picker-title>Add from saved content</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="form-help mt-0" data-picker-help>Tick what this package includes. Items already in the package are marked "Added".</p>
                    <label class="visually-hidden" for="libraryPickerSearch">Search</label>
                    <input type="search" id="libraryPickerSearch" class="form-control mb-2" placeholder="Search…" data-picker-search data-no-dirty>
                    <div class="picker-list" data-picker-list></div>
                    <p class="form-help mb-0 mt-2">
                        Missing something? Add it under <strong>Reusable Content</strong> in the menu, or use "Add your own" in this step.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-picker-accept>Add selected</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Copy sections from another package --}}
    <div class="modal fade" id="copyFromModal" tabindex="-1" aria-labelledby="copyFromTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="copyFromTitle">Copy from another package</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="copyFromPackage">Package to copy from</label>
                    <select id="copyFromPackage" class="form-select mb-3" data-copy-package data-no-dirty>
                        <option value="">Choose a package…</option>
                        @foreach($library['packages'] as $item)
                            <option value="{{ $item['url'] }}">{{ $item['label'] }}</option>
                        @endforeach
                    </select>

                    <fieldset>
                        <legend class="form-label">What to copy</legend>
                        @foreach([
                            'pricing' => 'Hotel options and room prices',
                            'hotels' => 'Hotels and Aziziya',
                            'meals' => 'Meal plans on matching hotels',
                            'journey' => 'Journey plan',
                            'mashaer' => 'Mina, Arafat & Muzdalifah',
                            'transport' => 'Transport',
                            'inclusions' => 'Included services',
                            'exclusions' => 'Not included',
                            'extras' => 'Additional options',
                            'notes' => 'Notes & policies',
                        ] as $section => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $section }}" id="copy-section-{{ $section }}" data-copy-section data-no-dirty>
                                <label class="form-check-label" for="copy-section-{{ $section }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    <p class="form-help mb-0 mt-2">What you tick <strong>replaces</strong> that part of this form. Hotel options are copied along with room prices or hotels, so every price stays with its hotel. Before anything changes you will see exactly what is copied and what it replaces. The title, code, photos and internal notes are never copied. Nothing is saved until you press a save button.</p>
                    <div class="alert alert-danger py-2 mt-2 mb-0" data-copy-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-copy-accept>Copy into this form</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add a hotel to the library without leaving the builder --}}
    <div class="modal fade" id="newHotelModal" tabindex="-1" aria-labelledby="newHotelTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="newHotelTitle">Add a new hotel</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="form-help mt-0">The hotel is saved to your hotel list, so every package can pick it. You can add photos and details later under Reusable Content → Hotels.</p>
                    <div class="mb-2">
                        <label class="form-label" for="newHotelName">Hotel name <span class="required-mark">*</span></label>
                        <input type="text" id="newHotelName" class="form-control" data-new-hotel="name" data-no-dirty maxlength="255">
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label" for="newHotelLocation">City / place</label>
                            <select id="newHotelLocation" class="form-select" data-new-hotel="location" data-no-dirty>
                                @foreach(\App\Models\Hotel::LOCATIONS as $value => $label)
                                    @continue($value === 'other')
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label" for="newHotelStars">Stars</label>
                            <select id="newHotelStars" class="form-select" data-new-hotel="star_rating" data-no-dirty>
                                <option value="">—</option>
                                @foreach([5, 4, 3, 2, 1] as $star)<option value="{{ $star }}">{{ $star }} ★</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-danger py-2 mt-2 mb-0" data-new-hotel-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-new-hotel-accept>Save hotel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Search the saved hotels, see a hotel's details, and add it --}}
    <div class="modal fade" id="findHotelModal" tabindex="-1" aria-labelledby="findHotelTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="findHotelTitle">Find a hotel</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="form-help mt-0">Search your saved hotels. Adding one copies its name and stars into this package; you can still change them for this package only.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-sm-7">
                            <label class="visually-hidden" for="findHotelSearch">Hotel name</label>
                            <input type="search" id="findHotelSearch" class="form-control" placeholder="Type a hotel name…" data-find-hotel-search data-no-dirty>
                        </div>
                        <div class="col-sm-5">
                            <label class="visually-hidden" for="findHotelCity">City</label>
                            <select id="findHotelCity" class="form-select" data-find-hotel-city data-no-dirty>
                                <option value="">Every city</option>
                                @foreach(\App\Models\Hotel::LOCATIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="find-hotel">
                        <div class="picker-list find-hotel-list" data-find-hotel-list role="listbox" aria-label="Saved hotels"></div>
                        <div class="find-hotel-details" data-find-hotel-details aria-live="polite">
                            <p class="text-muted mb-0">Choose a hotel to see its details.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-wrap">
                    <label class="form-label mb-0 me-1" for="findHotelFor">Add to</label>
                    <select id="findHotelFor" class="form-select w-auto" data-find-hotel-for data-no-dirty></select>
                    <button type="button" class="btn btn-outline-secondary ms-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-find-hotel-accept disabled>Add this hotel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Save the journey plan as a reusable template --}}
    <div class="modal fade" id="saveJourneyModal" tabindex="-1" aria-labelledby="saveJourneyTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="saveJourneyTitle">Save journey plan as a template</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="saveJourneyName">Template name <span class="required-mark">*</span></label>
                    <input type="text" id="saveJourneyName" class="form-control" placeholder="e.g. 14 days — Madinah first" data-save-journey-name data-no-dirty maxlength="255">
                    <p class="form-help">Saves the days as they are now. Other packages can apply it and change the dates.</p>
                    <div class="alert alert-danger py-2 mb-0" data-save-journey-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-save-journey-accept>Save template</button>
                </div>
            </div>
        </div>
    </div>

    @if(($mode ?? 'package') === 'package' && $package->exists)
        <div class="modal fade" id="saveTemplateModal" tabindex="-1" aria-labelledby="saveTemplateTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('admin.hajj-packages.save-template', $package) }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="saveTemplateTitle">Save as a package template</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="form-help mt-0">The template stores the <strong>saved</strong> version of this package — options, prices, hotels, journey, services, transport, notes and additional options. It never stores the code, web address, photos or internal notes. Save your changes first if you want them included.</p>
                        <label class="form-label" for="templateName">Template name <span class="required-mark">*</span></label>
                        <input type="text" id="templateName" name="template_name" class="form-control" required maxlength="255" value="{{ $package->name }}">
                        <label class="form-label mt-2" for="templateDescription">Description</label>
                        <textarea id="templateDescription" name="template_description" rows="2" class="form-control" placeholder="When to use this template"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save template</button>
                    </div>
                </form>
            </div>
        </div>

        @unless($package->isPublished())
            <div class="modal fade" id="applyTemplateModal" tabindex="-1" aria-labelledby="applyTemplateTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" method="POST" action="{{ route('admin.hajj-packages.apply-template', $package) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="applyTemplateTitle">Apply a template to this draft</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="applyTemplateId">Template</label>
                            <select id="applyTemplateId" name="template_id" class="form-select" required>
                                @foreach($library['packageTemplates'] as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="alert alert-warning py-2 mt-3 mb-2 small">
                                The template <strong>replaces</strong> this draft's options, prices, hotels, journey, services, transport, notes and additional options. The title, code, photos and internal notes stay as they are. Unsaved changes in this form are lost.
                            </div>
                            <p class="form-help mb-0">Only drafts can have a template applied, so a live package is never replaced by accident.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Replace with template</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    @endif
@endpush
