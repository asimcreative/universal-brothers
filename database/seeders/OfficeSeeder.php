<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Real office/contact data — consistent across the Hajj 2027 brochure and
 * both live sites (see PROJECT_DISCOVERY.md and EXISTING_WEBSITE_AUDIT.md).
 */
class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $address = 'A-9, 1st Floor, Hassan Homes, FL-3/8, Opposite Nehr-e-Khayyam, KDA Scheme Block-5, Clifton, Karachi, Pakistan';

        Office::updateOrCreate(
            ['label' => 'Head Office — Karachi'],
            [
                'address' => $address,
                'phone_primary' => '(92-21) 111-102-786',
                'phone_secondary' => '(92-21) 111-106-786',
                'whatsapp' => '+92 322 2102786',
                'email' => 'info@maximsgroup.org',
                // Google's keyless embed (a plain map query, not the paid
                // JS Maps SDK) resolving the real office address above —
                // frontend visual redesign, 2026-08-30: the Contact page
                // directive asked for a map, and this address is already
                // real, confirmed data used throughout the site, so
                // embedding it is not inventing anything new.
                'google_maps_embed' => '<iframe src="https://www.google.com/maps?q='.rawurlencode($address).'&output=embed" title="Universal Brothers head office location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>',
                'is_domestic' => true,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
