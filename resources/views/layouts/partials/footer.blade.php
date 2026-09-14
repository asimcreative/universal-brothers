@php
    $facebook = \App\Models\SiteSetting::get('social_facebook');
    $hajjReg = \App\Models\SiteSetting::get('hajj_registration_no');
    $govLicense = \App\Models\SiteSetting::get('government_license_no');
    $yearsInOperation = \App\Models\SiteSetting::get('years_in_operation', '20+');
    $parentGroup = \App\Models\SiteSetting::get('parent_group');
    $brandName = \App\Models\SiteSetting::get('brand_name');
    $hajjCategory = $navCategories->firstWhere('slug', 'hajj');
    $umrahCategory = $navCategories->firstWhere('slug', 'umrah');
    $tourismCategory = $navCategories->firstWhere('slug', 'tourism');
@endphp

{{--
    Restructured from a 4-column row followed by a second, half-empty 2-column
    row — which left a visible gap under the Tourism/Company columns and made
    the footer read as two unrelated blocks. It is now one balanced grid: an
    identity/contact column beside four link columns, with the legal links
    moved into the bottom bar where they belong.
--}}
<footer class="site-footer">
    <div class="container">
        <div class="row g-4 g-lg-5 footer-main">
            <div class="col-lg-4">
                <div class="footer-brand">
                    <span class="site-brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 44 44" width="38" height="38" role="presentation" focusable="false">
                            <rect x="9" y="9" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4"/>
                            <rect x="9" y="9" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4" transform="rotate(45 22 22)"/>
                            <circle cx="22" cy="22" r="7.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                        <span class="site-brand-initials">UB</span>
                    </span>
                    <div>
                        <p class="footer-brand-name">Universal Brothers (Private) Limited</p>
                        <p class="footer-brand-tagline">Serving Journeys. Building Trust. For {{ $yearsInOperation }} Years.</p>
                    </div>
                </div>

                @if($primaryOffice)
                    <ul class="footer-contact">
                        @if($primaryOffice->address)
                            <li><i class="bi bi-geo-alt-fill"></i><span>{{ $primaryOffice->address }}</span></li>
                        @endif
                        @if($primaryOffice->phone_primary)
                            <li><i class="bi bi-telephone-fill"></i><a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}">{{ $primaryOffice->phone_primary }}</a></li>
                        @endif
                        @if($primaryOffice->email)
                            <li><i class="bi bi-envelope-fill"></i><a href="mailto:{{ $primaryOffice->email }}">{{ $primaryOffice->email }}</a></li>
                        @endif
                    </ul>
                @endif

                <div class="footer-credentials">
                    @if(\App\Models\SiteSetting::get('iata_registered', '1'))
                        <span><i class="bi bi-patch-check-fill"></i>IATA Registered</span>
                    @endif
                    @if($govLicense)<span><i class="bi bi-shield-check"></i>Licence {{ $govLicense }}</span>@endif
                    @if($hajjReg)<span><i class="bi bi-file-earmark-text"></i>Reg. {{ $hajjReg }}</span>@endif
                </div>

                @if($facebook)
                    <div class="footer-social">
                        <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Universal Brothers on Facebook"><i class="bi bi-facebook"></i></a>
                    </div>
                @endif
            </div>

            @if($hajjCategory)
                <div class="col-6 col-lg-2">
                    <h2 class="footer-heading">Hajj</h2>
                    <ul class="footer-links">
                        <li><a href="{{ route('hajj-services') }}">Hajj 2027</a></li>
                        <li><a href="{{ route('packages.category', 'hajj') }}">Hajj Packages</a></li>
                        <li><a href="{{ route('hajj-services') }}#how-to-apply">How to Apply</a></li>
                        <li><a href="{{ route('hajj-services') }}#hajj-process">Hajj Process</a></li>
                        <li><a href="{{ route('hajj-services') }}#faqs">Hajj FAQs</a></li>
                    </ul>
                </div>
            @endif

            @if($umrahCategory)
                <div class="col-6 col-lg-2">
                    <h2 class="footer-heading">Umrah</h2>
                    <ul class="footer-links">
                        <li><a href="{{ route('packages.category', 'umrah') }}">Umrah Packages</a></li>
                        <li><a href="{{ route('umrah-services') }}#how-to-apply">How to Apply</a></li>
                        <li><a href="{{ route('umrah-services') }}#umrah-process">Umrah Process</a></li>
                        <li><a href="{{ route('faqs') }}">Umrah FAQs</a></li>
                    </ul>
                </div>
            @endif

            @if($tourismCategory)
                <div class="col-6 col-lg-2">
                    <h2 class="footer-heading">Tourism</h2>
                    <ul class="footer-links">
                        <li><a href="{{ route('packages.category', 'tourism') }}">All Tour Packages</a></li>
                        <li><a href="{{ route('packages.category', ['tourism', 'series' => 'domestic']) }}">Pakistan Tours</a></li>
                        <li><a href="{{ route('packages.category', ['tourism', 'series' => 'international']) }}">International Tours</a></li>
                    </ul>
                </div>
            @endif

            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Company</h2>
                <ul class="footer-links">
                    <li><a href="{{ url('/about-us') }}">About Us</a></li>
                    <li><a href="{{ route('awards') }}">Awards</a></li>
                    <li><a href="{{ route('affiliations') }}">Affiliations</a></li>
                    <li><a href="{{ route('testimonials') }}">Testimonials</a></li>
                    <li><a href="{{ route('media') }}">Media</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; {{ now()->year }} Universal Brothers (Pvt) Ltd. All rights reserved.
                @if($parentGroup) A company of {{ $parentGroup }}.@endif
                @if($brandName) Operating as &ldquo;{{ $brandName }}&rdquo;.@endif
            </p>
            <ul class="footer-legal">
                <li><a href="{{ route('faqs') }}">FAQs</a></li>
                <li><a href="{{ url('terms-and-conditions') }}">Terms &amp; Conditions</a></li>
                <li><a href="{{ url('privacy-policy') }}">Privacy Policy</a></li>
                <li><a href="{{ url('refund-policy') }}">Refund Policy</a></li>
                <li><a href="{{ route('contact') }}">Complaints &amp; Feedback</a></li>
            </ul>
        </div>

        {{-- Last thing in the footer, which is the last thing on the page: the
             credits can only list photographs that have already been rendered. --}}
        <x-photo-credits />
    </div>
</footer>
