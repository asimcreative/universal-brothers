<?php

namespace Database\Seeders;

use App\Models\Affiliation;
use App\Models\Award;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Attach Universal Brothers' OWN award photographs and affiliation logos.
 *
 * Every image referenced here was cut out of the client's own 2027 brochure
 * deck (`docs/source-documents/HAJJ 2027 Packages *.pdf`) — the "Merits and
 * Credits" and "Affiliation With" pages. They are the real award ceremonies,
 * the real medals and the real member organisations' marks, which is why they
 * replace the generated seals that stood in for them until now.
 *
 * Idempotent by design: it matches on the record's existing name and only
 * writes when the stored value differs, so running it twice changes nothing
 * and running it after the client uploads their own file through the admin
 * does NOT overwrite that — see `$onlyWhenEmpty`.
 */
class CompanyImageSeeder extends Seeder
{
    /**
     * Award title fragment => shipped asset path.
     *
     * Matched loosely on a distinctive fragment rather than the full title,
     * because the titles carry parentheses and qualifiers that are easy to
     * drift ("FPCCI Achievement Award (Gold Medal)").
     */
    private const AWARDS = [
        'fpcci' => 'images/company/award-fpcci-achievement.webp',
        'who' => 'images/company/award-whos-who-pakistan.webp',
        'quality standard' => 'images/company/award-quality-standard.webp',
        'best hajj operator' => 'images/company/award-best-hajj-operator.webp',
        'consumers choice' => 'images/company/award-consumers-choice.webp',
        'brand icon' => 'images/company/award-brand-icon-pakistan.webp',
        'brands of the year' => 'images/company/award-brands-of-the-year.webp',
    ];

    /**
     * Affiliation name fragment => shipped logo path.
     *
     * DTS and SECP are deliberately absent: the client's brochure does not
     * carry their marks, and inventing or scraping one would be exactly the
     * unverified-source problem the image policy exists to prevent. Those two
     * keep the generated seal until the client supplies a logo.
     */
    private const AFFILIATIONS = [
        'iata' => 'images/company/logo-iata.webp',
        'taap' => 'images/company/affil-taap.webp',
        'phgoc' => 'images/company/affil-phgoc.webp',
        'elaf' => 'images/company/affil-elaf.webp',
        'ministry of religious' => 'images/company/affil-ministry-religious-affairs.webp',
        'fpcci' => 'images/company/affil-fpcci.webp',
        'hoap' => 'images/company/affil-hoap.webp',
        'kcci' => 'images/company/affil-kcci.webp',
    ];

    /**
     * When true, a record that already has an image is left alone — so a real
     * upload made through the admin is never clobbered by a redeploy.
     */
    public bool $onlyWhenEmpty = false;

    public function run(): void
    {
        $base = base_path('public/');
        $set = 0;
        $skipped = 0;
        $missingFiles = [];

        foreach (Award::all() as $award) {
            $path = $this->match(self::AWARDS, $award->title ?? $award->name ?? '');
            if (! $path) {
                continue;
            }
            if (! is_file($base.$path)) {
                $missingFiles[$path] = true;
                continue;
            }
            if ($this->onlyWhenEmpty && filled($award->image)) {
                $skipped++;
                continue;
            }
            if ($award->image !== $path) {
                $award->forceFill(['image' => $path])->save();
                $set++;
            }
        }

        foreach (Affiliation::all() as $affiliation) {
            $path = $this->match(self::AFFILIATIONS, $affiliation->organization_name ?? '');
            if (! $path) {
                continue;
            }
            if (! is_file($base.$path)) {
                $missingFiles[$path] = true;
                continue;
            }
            if ($this->onlyWhenEmpty && filled($affiliation->logo)) {
                $skipped++;
                continue;
            }
            if ($affiliation->logo !== $path) {
                $affiliation->forceFill(['logo' => $path])->save();
                $set++;
            }
        }

        $this->command?->info("CompanyImageSeeder: {$set} updated, {$skipped} left as uploaded.");

        foreach (array_keys($missingFiles) as $missing) {
            $this->command?->warn("  missing asset: {$missing}");
        }
    }

    /** @param array<string, string> $map */
    private function match(array $map, string $name): ?string
    {
        $needleSpace = Str::lower($name);
        foreach ($map as $fragment => $path) {
            if (str_contains($needleSpace, $fragment)) {
                return $path;
            }
        }

        return null;
    }
}
