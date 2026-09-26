<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correct what the site tells a rupee customer the Qurbani costs.
 *
 * Every package page said "Assistance in doing Qurbani (approx. US$200
 * charge applies)" and listed "Qurbani actual cost (approx. US$200)" among
 * the exclusions. That is what the US$ brochure says. The PKR brochure lists
 * Qurbani among the INCLUSIONS and prints "Ticket not Included" where the
 * other two print "Ticket & Qurbani not Included"; the Riyal brochure charges
 * approximately SAR 750.
 *
 * So a visitor reading the rupee price list was being told they owed two
 * hundred dollars for something their own list includes. That is the site
 * misquoting the price, not a formatting slip, which is why it is corrected
 * on the packages that are already live rather than only in the seeder.
 *
 * The seeder change alone does not reach production: the deploy runs
 * `migrate --force` and never `db:seed`, and the migration that carried the
 * fourteen new packages runs in only-missing mode, so it never revisits a
 * package that already exists. Text already written into `package_features`
 * and `package_notes` therefore stays exactly as it was until something
 * rewrites it. This is that something.
 *
 * Matched on the old wording rather than on the package, so it corrects only
 * rows that still carry it and leaves anything an admin has since edited
 * alone. Running it twice changes nothing the second time.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        [
            'table' => 'package_features',
            'column' => 'description',
            'from' => 'Assistance in doing Qurbani (approx. US$200 charge applies — see exclusions)',
            'to' => 'Assistance in doing Qurbani',
        ],
        [
            'table' => 'package_features',
            'column' => 'description',
            'from' => 'Qurbani actual cost (approx. US$200) — assistance in arranging it is included, the cost itself is not',
            'to' => 'Qurbani cost, on the riyal and US dollar price lists only (approx. SAR 750 / US$200) — the rupee list includes it. Assistance in arranging it is included either way',
        ],
        [
            'table' => 'package_notes',
            'column' => 'content',
            'from' => 'Ticket & Qurbani not included.',
            'to' => 'Airline ticket not included. Qurbani is included on the rupee price list and charged separately on the riyal and US dollar lists.',
        ],
        // The same note, stored as rich text by the page builder.
        [
            'table' => 'package_notes',
            'column' => 'content',
            'from' => '<p>Ticket & Qurbani not included.</p>',
            'to' => '<p>Airline ticket not included. Qurbani is included on the rupee price list and charged separately on the riyal and US dollar lists.</p>',
        ],
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $change) {
            DB::table($change['table'])
                ->where($change['column'], $change['from'])
                ->update([$change['column'] => $change['to']]);
        }
    }

    public function down(): void
    {
        foreach (self::REPLACEMENTS as $change) {
            DB::table($change['table'])
                ->where($change['column'], $change['to'])
                ->update([$change['column'] => $change['from']]);
        }
    }
};
