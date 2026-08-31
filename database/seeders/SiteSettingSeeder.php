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
            // "10,000+ Hajis Served" and "20+ Awards & Recognitions" are the
            // project owner's confirmed public-facing figures (business
            // decision, not a re-verification of the underlying data — see
            // FINAL_AUDIT_REPORT.md §21/§22). An earlier pass's live-site
            // audit had separately recovered "50,000+" pilgrims and 7 real
            // named awards; those are historical findings, superseded here
            // by the owner's explicit confirmation for what the site should
            // publicly state. The 7 real award *records* are deliberately
            // left as provisional data (see AwardSeeder.php) — the owner
            // confirmed the aggregate count is 20+ but has not yet supplied
            // 20+ real award names, so none were invented to match.
            ['key' => 'pilgrims_served', 'value' => '10,000+', 'group' => 'general'],
            ['key' => 'industry_awards_count', 'value' => '20+', 'group' => 'general'],
            ['key' => 'hajj_registration_no', 'value' => '4143', 'group' => 'legal'],
            ['key' => 'government_license_no', 'value' => '2014', 'group' => 'legal'],
            ['key' => 'affiliations', 'value' => 'IATA, TAAP, PHGOC, ELAF, Ministry of Religious Affairs (Pakistan), FPCCI, HOAP, DTS, SECP, KCCI', 'group' => 'legal'],
            ['key' => 'leadership', 'value' => 'Furqan Abdul Qadir (Chief Executive), Junaid Abdul Qadir (Director)', 'group' => 'general'],
            // Real, brochure-confirmed fact (every Hajj package's Mina
            // itinerary rows read "Zone 1 near to Jamarat A Category" — see
            // HAJJ_BROCHURE_EXTRACTION.md) — made CMS-editable rather than
            // hardcoded in the homepage trust ticker/header/Hajj Services
            // page, since a Mina camp assignment is a real-world fact that
            // could change between Hajj seasons (release-gate QA finding).
            ['key' => 'mina_camp_location', 'value' => 'Zone 1, Category A', 'group' => 'general'],
            ['key' => 'iata_registered', 'value' => '1', 'group' => 'legal'],
            // Left blank deliberately — no real Hajj 2027 flight schedule has been
            // supplied by the client yet. The Hajj Services page shows a "to be
            // announced" placeholder until this is filled in here.
            ['key' => 'hajj_next_flight_date', 'value' => null, 'group' => 'general'],
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
