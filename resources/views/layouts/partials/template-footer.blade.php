@php
    $ubHajj = $navCategories->firstWhere('slug', 'hajj');
    $ubUmrah = $navCategories->firstWhere('slug', 'umrah');
    $ubTourism = $navCategories->firstWhere('slug', 'tourism');
    $ubWhatsapp = $primaryOffice?->whatsapp;
    $ubYears = \App\Models\SiteSetting::get('years_in_operation', '20+');

    $ubQuickLinks = collect([
        ['Home', route('home')],
        ['About Us', url('/about-us')],
        ['Awards & Recognition', route('awards')],
        ['Affiliations', route('affiliations')],
        ['Media', route('media')],
        ['Testimonials', route('testimonials')],
        ['FAQs', route('faqs')],
        ['Contact Us', route('contact')],
    ]);

    $ubServiceLinks = collect([
        $ubHajj ? ['Hajj Packages', route('packages.category', 'hajj')] : null,
        $ubHajj ? ['Hajj Services', route('hajj-services')] : null,
        $ubUmrah ? ['Umrah Packages', route('packages.category', 'umrah')] : null,
        $ubUmrah ? ['Umrah Services', route('umrah-services')] : null,
        $ubTourism ? ['Tourism', route('packages.category', 'tourism')] : null,
    ])->filter()->values();

    $ubLegal = collect([
        ['Terms & Conditions', url('/terms-and-conditions')],
        ['Privacy Policy', url('/privacy-policy')],
        ['Refund Policy', url('/refund-policy')],
        ['Complaints & Feedback', url('/complaints-and-feedback')],
    ]);
@endphp

{{--
    The designer's footer, carrying our real links, offices and contact details.

    The class names belong to the template's compiled stylesheet, so they are
    reproduced exactly. Everything with a value in it — the address, the phone,
    the email, the link lists — comes from Offices and Site Settings, so none of
    it can drift from what the rest of the site shows.
--}}
<footer class="bg-brand-900 text-ivory">
    <div class="mx-auto w-full max-w-[var(--container-site)] px-5 sm:px-8 lg:px-10">
        <div class="grid gap-12 pt-16 sm:pt-20 lg:grid-cols-[1.7fr_1fr_1.1fr_1.3fr] lg:gap-10">
            <div class="reveal-on-scroll">
                <a class="flex items-center gap-3" aria-label="Universal Brothers" href="{{ route('home') }}">
                    <svg viewBox="0 0 44 48" class="h-10 w-auto shrink-0" aria-hidden="true"><path d="M22 1.5 41.5 12.75v22.5L22 46.5 2.5 35.25v-22.5L22 1.5Z" class="fill-none stroke-sand-300" stroke-width="2"></path><text x="22" y="30" text-anchor="middle" class="font-display font-bold fill-ivory" style="font-size:17px;letter-spacing:-0.02em">UB</text></svg>
                    <span class="flex flex-col leading-none">
                        <span class="font-display text-[19px] font-bold uppercase tracking-[0.06em] text-ivory">Universal <span class="text-accent-500">Brothers</span></span>
                        <span class="mt-1 text-[10px] uppercase tracking-[0.22em] text-sand-300/80">Hajj &middot; Umrah &middot; Tourism</span>
                    </span>
                </a>

                <p class="mt-6 max-w-sm text-sm leading-relaxed text-ivory/65">Universal Brothers (Pvt) Ltd is a company of {{ \App\Models\SiteSetting::get('parent_group', "Maxim's Group") }}, operating as an Umrah &amp; Hajj Organizer and Travel &amp; Tours Operator under the brand &ldquo;Crown Packages&rdquo; &mdash; {{ $ubYears }} years of serving pilgrims.</p>

                {{-- Every platform an admin has saved a link for, plus
                     WhatsApp, which comes from the office record rather than
                     from Settings. --}}
                @php $ubWhatsappHref = $ubWhatsapp ? 'https://wa.me/' . preg_replace('/[^\d]/', '', $ubWhatsapp) : null; @endphp
                <ul class="-mx-2.5 mt-7 flex items-center">
                    <x-social-links
                        :size="18"
                        class="contents"
                        link-class="flex size-10 items-center justify-center rounded-full text-ivory/80 transition-all duration-300 hover:-translate-y-0.5 hover:text-accent-500 focus-visible:text-accent-500 focus-visible:outline-none" />

                    @if($ubWhatsappHref)
                        <li><a href="{{ $ubWhatsappHref }}" target="_blank" rel="noopener" aria-label="Universal Brothers on WhatsApp" class="flex size-10 items-center justify-center rounded-full text-ivory/80 transition-all duration-300 hover:-translate-y-0.5 hover:text-accent-500 focus-visible:text-accent-500 focus-visible:outline-none"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.76-1.66-2.06-.17-.3-.02-.46.13-.61.14-.14.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.38-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.87 9.87 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2"></path></svg></a></li>
                    @endif
                </ul>
            </div>

            <div class="reveal-on-scroll reveal-delay-1">
                <h3 class="font-display text-lg font-bold text-ivory">Quick Links</h3>
                <ul class="mt-6 space-y-0.5">
                    @foreach($ubQuickLinks as [$ubLabel, $ubUrl])
                        <li>
                            <a class="group flex items-center gap-3 py-2.5 text-sm text-ivory/70 transition-colors duration-300 hover:text-accent-500" href="{{ $ubUrl }}">
                                <span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-accent-500 transition-transform duration-300 group-hover:scale-150"></span>
                                <span class="transition-transform duration-300 group-hover:translate-x-1">{{ $ubLabel }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($ubServiceLinks->isNotEmpty())
                <div class="reveal-on-scroll reveal-delay-2">
                    <h3 class="font-display text-lg font-bold text-ivory">Our Services</h3>
                    <ul class="mt-6 space-y-0.5">
                        @foreach($ubServiceLinks as [$ubLabel, $ubUrl])
                            <li>
                                <a class="group flex items-center gap-3 py-2.5 text-sm text-ivory/70 transition-colors duration-300 hover:text-accent-500" href="{{ $ubUrl }}">
                                    <span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-accent-500 transition-transform duration-300 group-hover:scale-150"></span>
                                    <span class="transition-transform duration-300 group-hover:translate-x-1">{{ $ubLabel }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="reveal-on-scroll reveal-delay-3">
                <h3 class="font-display text-lg font-bold text-ivory">Contact</h3>
                <ul class="mt-6 space-y-5">
                    @if($primaryOffice?->phone_primary)
                        <li>
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $primaryOffice->phone_primary) }}" class="group flex items-start gap-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone mt-0.5 shrink-0 text-accent-500"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                <span class="text-sm leading-relaxed text-ivory/75 transition-colors duration-300 group-hover:text-ivory">{{ $primaryOffice->phone_primary }}</span>
                            </a>
                        </li>
                    @endif
                    @if($primaryOffice?->email)
                        <li>
                            <a href="mailto:{{ $primaryOffice->email }}" class="group flex items-start gap-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail mt-0.5 shrink-0 text-accent-500"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                                <span class="text-sm leading-relaxed text-ivory/75 transition-colors duration-300 group-hover:text-ivory">{{ $primaryOffice->email }}</span>
                            </a>
                        </li>
                    @endif
                    @if($primaryOffice?->address)
                        <li>
                            <div class="group flex items-start gap-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin mt-0.5 shrink-0 text-accent-500"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span class="text-sm leading-relaxed text-ivory/75 transition-colors duration-300 group-hover:text-ivory">{{ $primaryOffice->address }}</span>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div>
            <div class="mt-14 flex flex-col items-center gap-5 border-t border-ivory/10 py-7 text-sm sm:flex-row sm:justify-between">
                <p class="text-ivory/60">&copy; {{ date('Y') }} Universal Brothers (Pvt) Ltd. All rights reserved. A company of {{ \App\Models\SiteSetting::get('parent_group', "Maxim's Group") }}.</p>
                <ul class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
                    @foreach($ubLegal as [$ubLabel, $ubUrl])
                        <li><a class="text-ivory/70 transition-colors duration-300 hover:text-accent-500" href="{{ $ubUrl }}">{{ $ubLabel }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</footer>
