<?php

namespace Database\Seeders;

use App\Models\Affiliation;
use Illuminate\Database\Seeder;

/**
 * Real industry affiliations recovered from the live site audit — the same
 * list already seeded as a flat comma-separated SiteSetting value
 * ('affiliations'). Structured here as real records so the Affiliations
 * page can render a proper logo/description grid instead of a text string.
 * No logo images exist yet (none were recovered during the audit) — the
 * public page falls back to a text badge per organization until real logo
 * files are supplied.
 */
class AffiliationSeeder extends Seeder
{
    public function run(): void
    {
        $affiliations = [
            'IATA',
            'TAAP',
            'PHGOC',
            'ELAF',
            'Ministry of Religious Affairs (Pakistan)',
            'FPCCI',
            'HOAP',
            'DTS',
            'SECP',
            'KCCI',
        ];

        foreach ($affiliations as $i => $name) {
            Affiliation::updateOrCreate(
                ['organization_name' => $name],
                ['sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
