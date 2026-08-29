<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Real company facts/settings, sourced from the Hajj 2027 brochure and the
 * live Hajj site audit (see PROJECT_DISCOVERY.md, EXISTING_WEBSITE_AUDIT.md).
 * Nothing invented — integration keys (GA4, reCAPTCHA, etc.) are left blank
 * for the client to supply.
 */
class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Universal Brothers (Pvt) Ltd', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'The Leader & Trend Setter', 'group' => 'general'],
            ['key' => 'brand_name', 'value' => 'Crown Packages', 'group' => 'general'],
            ['key' => 'parent_group', 'value' => "Maxim's Group", 'group' => 'general'],
            ['key' => 'years_in_operation', 'value' => '20+', 'group' => 'general'],
            ['key' => 'pilgrims_served', 'value' => '50,000+', 'group' => 'general'],
            ['key' => 'hajj_registration_no', 'value' => '4143', 'group' => 'legal'],
            ['key' => 'government_license_no', 'value' => '2014', 'group' => 'legal'],
            ['key' => 'affiliations', 'value' => 'IATA, TAAP, PHGOC, ELAF, Ministry of Religious Affairs (Pakistan), FPCCI, HOAP, DTS, SECP, KCCI', 'group' => 'legal'],
            ['key' => 'leadership', 'value' => 'Furqan Abdul Qadir (Chief Executive), Junaid Abdul Qadir (Director)', 'group' => 'general'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/Universalbrotherstravel', 'group' => 'social'],
            ['key' => 'ga4_measurement_id', 'value' => null, 'group' => 'integrations'],
            ['key' => 'google_search_console_verification', 'value' => null, 'group' => 'integrations'],
            ['key' => 'recaptcha_site_key', 'value' => null, 'group' => 'integrations'],
            ['key' => 'recaptcha_secret_key', 'value' => null, 'group' => 'integrations'],
            ['key' => 'payment_gateway_provider', 'value' => null, 'group' => 'integrations'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
