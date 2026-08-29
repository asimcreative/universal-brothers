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
        Office::updateOrCreate(
            ['label' => 'Head Office — Karachi'],
            [
                'address' => 'A-9, 1st Floor, Hassan Homes, FL-3/8, Opposite Nehr-e-Khayyam, KDA Scheme Block-5, Clifton, Karachi, Pakistan',
                'phone_primary' => '(92-21) 111-102-786',
                'phone_secondary' => '(92-21) 111-106-786',
                'whatsapp' => '+92 322 2102786',
                'email' => 'info@maximsgroup.org',
                'is_domestic' => true,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
