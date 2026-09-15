<?php

namespace App\Support\Content;

use Illuminate\Support\Str;

/**
 * Plain-language names and help for Site Settings, so the settings screen
 * never shows a stored key such as "government_license_no".
 */
class SettingLabels
{
    public const LABELS = [
        'site_name' => ['Company name', 'The full registered name, shown in the footer and search results.'],
        'site_tagline' => ['Tagline', 'A short line that goes with the company name.'],
        'brand_name' => ['Brand name', 'The name the packages are sold under, for example Crown Packages.'],
        'parent_group' => ['Parent group', 'Shown on the About page.'],
        'years_in_operation' => ['Years of experience', 'Shown as a figure across the website. Include "+" if you like, for example 20+.'],
        'pilgrims_served' => ['Pilgrims served', 'Shown as a figure across the website, for example 10,000+.'],
        'industry_awards_count' => ['Number of awards', 'Shown as a figure across the website, for example 20+.'],
        'hajj_registration_no' => ['Hajj registration number', 'Shown in the footer and on the About page.'],
        'government_license_no' => ['Hajj licence number', 'Shown in the footer, the homepage and the About page.'],
        'affiliations' => ['Affiliations (text list)', 'Organisation names separated by commas. The Affiliations page itself uses the Affiliations section of the admin.'],
        'leadership' => ['Leadership', 'Names and roles, separated by commas.'],
        'mina_camp_location' => ['Mina camp location', 'For example "Zone 1, Category A". Leave empty to hide it.'],
        'iata_registered' => ['IATA registered', 'Type 1 to show the IATA badge, or 0 to hide it.'],
        'hajj_next_flight_date' => ['Next Hajj flight date', 'Optional.'],
        'social_facebook' => ['Facebook page', 'The full address of the Facebook page, starting with https://.'],
        'ga4_measurement_id' => ['Google Analytics ID', 'Starts with G-. Leave empty if analytics is not used.'],
        'google_search_console_verification' => ['Google Search Console code', 'The verification code Google gives you. Leave empty if not used.'],
        'recaptcha_site_key' => ['reCAPTCHA site key', 'From the Google reCAPTCHA admin console. Leave empty if not used.'],
        'recaptcha_secret_key' => ['reCAPTCHA secret key', 'Kept hidden after saving. Leave empty to keep the current key.'],
        'payment_gateway_provider' => ['Payment provider', 'Not used by the website yet.'],
    ];

    public static function label(string $key): string
    {
        return self::LABELS[$key][0] ?? Str::ucfirst(str_replace('_', ' ', $key));
    }

    public static function help(string $key): ?string
    {
        return self::LABELS[$key][1] ?? null;
    }
}
