<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Seeder;

/**
 * The 7 real, named awards recovered from the live site audit — the same
 * list already seeded as a flat HTML list inside AboutPageSeeder's page
 * body. Structured here as real records so the Awards page can query
 * actual data instead of a hardcoded count.
 *
 * These are PROVISIONAL, explicitly per the project owner's confirmation
 * (see FINAL_AUDIT_REPORT.md §21/§22): the owner has confirmed the
 * public-facing aggregate statistic is "20+ Awards & Recognitions"
 * (`industry_awards_count` — deliberately decoupled from this table's row
 * count, see HomeController/PageController), but has NOT yet supplied
 * 20+ real award names/details. Per explicit instruction, do NOT invent
 * award names to make this list match 20 — this list stays exactly these
 * 7 real, already-verified records until the owner supplies the rest.
 * Years are left blank where not actually known — not guessed.
 */
class AwardSeeder extends Seeder
{
    public function run(): void
    {
        $awards = [
            ['name' => 'FPCCI Achievement Award (Gold Medal)', 'awarding_organization' => 'Federation of Pakistan Chambers of Commerce & Industry (FPCCI)'],
            ['name' => "Who's Who Pakistan Award", 'awarding_organization' => null],
            ['name' => 'Quality Standard Award', 'awarding_organization' => null],
            ['name' => 'Best Hajj Operator in Pakistan', 'awarding_organization' => 'World Hajj & Umrah Convention (WHUC), London Olympia'],
            ['name' => 'Consumers Choice Award', 'awarding_organization' => null],
            ['name' => 'Brand Icon of Pakistan Award', 'awarding_organization' => null, 'year' => '2010–2011'],
            ['name' => 'Brands of the Year Award', 'awarding_organization' => null, 'description' => '25 years of excellence'],
        ];

        foreach ($awards as $i => $award) {
            Award::updateOrCreate(
                ['name' => $award['name']],
                array_merge([
                    'awarding_organization' => null,
                    'year' => null,
                    'description' => null,
                ], $award, ['sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
