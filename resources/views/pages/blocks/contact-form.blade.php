{{-- Enquiry form. Submits to the site's existing contact or inquiry endpoint. $data, $extra['offices'], $sectionId --}}
@php
    $ubOffice = $extra['offices']->first();
    $ubDetails = ! empty($data['show_details']) && $ubOffice;
    $ubUid = 'form-'.$sectionId;
@endphp
<section id="{{ $sectionId }}" class="section pb-section {{ \App\Support\PageBuilder\Block::background($data['background'] ?? 'cream') }}">
    <div class="container">
        @include('pages.blocks.partials.heading', ['data' => $data])
        <div class="row g-4 justify-content-center">
            @if($ubDetails)
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="h6">{{ $ubOffice->label }}</h3>
                            <p class="mb-2"><i class="bi bi-geo-alt-fill me-2" aria-hidden="true"></i>{{ $ubOffice->address }}</p>
                            @if($ubOffice->phone_primary)<p class="mb-2"><i class="bi bi-telephone-fill me-2" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $ubOffice->phone_primary) }}">{{ $ubOffice->phone_primary }}</a></p>@endif
                            @if($ubOffice->whatsapp)<p class="mb-2"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>{{ $ubOffice->whatsapp }}</p>@endif
                            @if($ubOffice->email)<p class="mb-0"><i class="bi bi-envelope-fill me-2" aria-hidden="true"></i><a href="mailto:{{ $ubOffice->email }}">{{ $ubOffice->email }}</a></p>@endif
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-lg-7">
                @if(($data['form'] ?? 'contact') === 'inquiry')
                    <x-inquiry-form :title="$data['heading'] ? 'Your details' : 'Send an Inquiry'" />
                @else
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('contact.store') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="{{ $ubUid }}-name" class="form-label">Full Name</label>
                                        <input type="text" name="name" id="{{ $ubUid }}-name" class="form-control" value="{{ old('name') }}" autocomplete="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="{{ $ubUid }}-phone" class="form-label">Phone</label>
                                        <input type="text" name="phone" id="{{ $ubUid }}-phone" class="form-control" value="{{ old('phone') }}" autocomplete="tel" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="{{ $ubUid }}-email" class="form-label">Email</label>
                                        <input type="email" name="email" id="{{ $ubUid }}-email" class="form-control" value="{{ old('email') }}" autocomplete="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="{{ $ubUid }}-message" class="form-label">Message</label>
                                        <textarea name="message" id="{{ $ubUid }}-message" rows="4" class="form-control" required>{{ old('message') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
