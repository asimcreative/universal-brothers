@php
    $facebook = \App\Models\SiteSetting::get('social_facebook');
    $hajjReg = \App\Models\SiteSetting::get('hajj_registration_no');
    $govLicense = \App\Models\SiteSetting::get('government_license_no');
    $yearsInOperation = \App\Models\SiteSetting::get('years_in_operation', '20+');
    $hajjCategory = $navCategories->firstWhere('slug', 'hajj');
    $umrahCategory = $navCategories->firstWhere('slug', 'umrah');
    $tourismCategory = $navCategories->firstWhere('slug', 'tourism');
@endphp

<footer class="bg-dark text-light pt-5 pb-3 mt-5 site-footer">
    <div class="container">
        <div class="mb-4">
            <h4 class="text-white mb-1">Universal Brothers (Private) Limited</h4>
            <p class="text-secondary fst-italic mb-0">Serving Journeys. Building Trust. For {{ $yearsInOperation }} Years.</p>
        </div>
        <div class="row gy-4">
            @if($hajjCategory)
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">Hajj</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('hajj-services') }}" class="text-secondary text-decoration-none">Hajj 2027</a></li>
                        <li class="mb-2"><a href="{{ route('packages.category', 'hajj') }}" class="text-secondary text-decoration-none">Hajj Packages</a></li>
                        <li class="mb-2"><a href="{{ route('hajj-services') }}#how-to-apply" class="text-secondary text-decoration-none">How to Apply</a></li>
                        <li class="mb-2"><a href="{{ route('hajj-services') }}#hajj-process" class="text-secondary text-decoration-none">Hajj Process</a></li>
                        <li class="mb-2"><a href="{{ route('hajj-services') }}#faqs" class="text-secondary text-decoration-none">Hajj FAQs</a></li>
                    </ul>
                </div>
            @endif
            @if($umrahCategory)
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">Umrah</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('packages.category', 'umrah') }}" class="text-secondary text-decoration-none">Umrah Packages</a></li>
                        <li class="mb-2"><a href="{{ route('umrah-services') }}#how-to-apply" class="text-secondary text-decoration-none">How to Apply</a></li>
                        <li class="mb-2"><a href="{{ route('umrah-services') }}#umrah-process" class="text-secondary text-decoration-none">Umrah Process</a></li>
                        <li class="mb-2"><a href="{{ route('faqs') }}" class="text-secondary text-decoration-none">Umrah FAQs</a></li>
                    </ul>
                </div>
            @endif
            @if($tourismCategory)
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">Tourism</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('packages.category', ['tourism', 'series' => 'domestic']) }}" class="text-secondary text-decoration-none">Pakistan Tours</a></li>
                        <li class="mb-2"><a href="{{ route('packages.category', ['tourism', 'series' => 'international']) }}" class="text-secondary text-decoration-none">International Tours</a></li>
                    </ul>
                </div>
            @endif
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Company</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ url('/about-us') }}" class="text-secondary text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="{{ route('awards') }}" class="text-secondary text-decoration-none">Awards</a></li>
                    <li class="mb-2"><a href="{{ route('affiliations') }}" class="text-secondary text-decoration-none">Affiliations</a></li>
                    <li class="mb-2"><a href="{{ route('testimonials') }}" class="text-secondary text-decoration-none">Testimonials</a></li>
                    <li class="mb-2"><a href="{{ route('media') }}" class="text-secondary text-decoration-none">Media</a></li>
                    <li class="mb-2"><a href="{{ route('contact') }}" class="text-secondary text-decoration-none">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="row gy-4 mt-0">
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Support</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('faqs') }}" class="text-secondary text-decoration-none">FAQs</a></li>
                    <li class="mb-2"><a href="{{ url('terms-and-conditions') }}" class="text-secondary text-decoration-none">Terms &amp; Conditions</a></li>
                    <li class="mb-2"><a href="{{ url('privacy-policy') }}" class="text-secondary text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="{{ route('contact') }}" class="text-secondary text-decoration-none">Complaints &amp; Feedback</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Contact</h6>
                @if($primaryOffice)
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i>{{ $primaryOffice->address }}</li>
                        @if($primaryOffice->phone_primary)
                            <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i>{{ $primaryOffice->phone_primary }}</li>
                        @endif
                        @if($primaryOffice->email)
                            <li class="mb-2"><i class="bi bi-envelope-fill me-2"></i>{{ $primaryOffice->email }}</li>
                        @endif
                    </ul>
                @endif
                @if($facebook)
                    <a href="{{ $facebook }}" class="text-light me-2" target="_blank" rel="noopener" aria-label="Universal Brothers on Facebook"><i class="bi bi-facebook fs-5"></i></a>
                @endif
            </div>
        </div>
        <hr class="border-secondary mt-4">
        <div class="d-flex flex-column flex-md-row justify-content-between small text-secondary">
            <p class="mb-2 mb-md-0">&copy; {{ now()->year }} Universal Brothers (Pvt) Ltd. All rights reserved.
                @if($hajjReg) Hajj Registration No. {{ $hajjReg }}. @endif
                @if($govLicense) Government License No. {{ $govLicense }}. @endif
            </p>
            <p class="mb-0">
                <a href="{{ url('refund-policy') }}" class="text-secondary text-decoration-none">Refund Policy</a>
            </p>
        </div>
    </div>
</footer>
