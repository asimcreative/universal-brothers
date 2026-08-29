@php
    $facebook = \App\Models\SiteSetting::get('social_facebook');
    $hajjReg = \App\Models\SiteSetting::get('hajj_registration_no');
    $govLicense = \App\Models\SiteSetting::get('government_license_no');
@endphp

<footer class="bg-dark text-light pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3">Universal Brothers</h5>
                <p class="small text-secondary">A company of Maxim's Group. Umrah &amp; Hajj Organizer, Travel &amp; Tours Operator — The Leader &amp; Trend Setter, serving pilgrims and travelers for over 20 years.</p>
                @if($facebook)
                    <a href="{{ $facebook }}" class="text-light me-2" target="_blank" rel="noopener"><i class="bi bi-facebook fs-5"></i></a>
                @endif
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('home') }}" class="text-secondary text-decoration-none">Home</a></li>
                    @foreach($navCategories as $navCategory)
                        <li class="mb-2"><a href="{{ route('packages.category', $navCategory->slug) }}" class="text-secondary text-decoration-none">{{ $navCategory->name }}</a></li>
                    @endforeach
                    <li class="mb-2"><a href="{{ route('contact') }}" class="text-secondary text-decoration-none">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Services</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2 text-secondary">Hajj Packages</li>
                    <li class="mb-2 text-secondary">Umrah Packages</li>
                    <li class="mb-2 text-secondary">Domestic Tourism</li>
                    <li class="mb-2 text-secondary">International Tourism</li>
                    <li class="mb-2 text-secondary">Visa Consultancy</li>
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
            </div>
        </div>
        <hr class="border-secondary">
        <div class="d-flex flex-column flex-md-row justify-content-between small text-secondary">
            <p class="mb-2 mb-md-0">&copy; {{ now()->year }} Universal Brothers (Pvt) Ltd. All rights reserved.
                @if($hajjReg) Hajj Registration No. {{ $hajjReg }}. @endif
                @if($govLicense) Government License No. {{ $govLicense }}. @endif
            </p>
            <p class="mb-0">
                <a href="#" class="text-secondary text-decoration-none me-3">Privacy Policy</a>
                <a href="#" class="text-secondary text-decoration-none me-3">Terms &amp; Conditions</a>
                <a href="#" class="text-secondary text-decoration-none">Refund Policy</a>
            </p>
        </div>
    </div>
</footer>
