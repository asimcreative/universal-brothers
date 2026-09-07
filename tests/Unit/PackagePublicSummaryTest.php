<?php

namespace Tests\Unit;

use App\Models\Package;
use PHPUnit\Framework\TestCase;

/**
 * The project's first unit tests (2026-09-05) — until now every test needed a
 * booted app and a database, even for logic that is pure string handling.
 *
 * `Package::publicSummary()` decides whether a stored summary is safe to show
 * a visitor. It exists because all 35 real Tourism packages store an internal
 * data-recovery note in `summary` ("…this content could not be recovered and
 * is not invented here"), which was rendering verbatim on package cards, the
 * detail hero and the SEO meta description. These cases pin the substitution
 * rule itself, with no database in the way.
 */
class PackagePublicSummaryTest extends TestCase
{
    private const INTERNAL_NOTE = 'Recovered from the live tourism.universalbrothers.com listing pages. Full itinerary, inclusions, and hotel details pending — every individual product detail page on the live site currently returns a server error, so this content could not be recovered and is not invented here.';

    public function test_the_internal_recovery_note_is_replaced_with_a_visitor_safe_line(): void
    {
        $package = new Package(['summary' => self::INTERNAL_NOTE]);

        $result = $package->publicSummary();

        $this->assertStringNotContainsString('is not invented here', $result);
        $this->assertStringNotContainsString('server error', $result);
        $this->assertStringNotContainsString('universalbrothers.com', $result);
        $this->assertSame(
            'Full package details are being finalized — please contact us for the latest itinerary and pricing.',
            $result
        );
    }

    public function test_a_real_marketing_summary_is_returned_untouched(): void
    {
        $real = 'Hajj 2027 / 1448 AH — 13 Days Package, non-shifting itinerary, optional Aziziya upgrade available.';
        $package = new Package(['summary' => $real]);

        $this->assertSame($real, $package->publicSummary());
    }

    public function test_a_null_summary_stays_null(): void
    {
        $package = new Package(['summary' => null]);

        $this->assertNull($package->publicSummary());
    }

    public function test_an_empty_summary_is_not_swapped_for_the_fallback(): void
    {
        // '' is falsy, so the guard must not treat it as the internal note —
        // an empty summary means "nothing written yet", and the card simply
        // renders no summary line at all rather than an unexpected sentence.
        $package = new Package(['summary' => '']);

        $this->assertSame('', $package->publicSummary());
    }

    public function test_the_match_is_on_the_marker_phrase_not_the_whole_string(): void
    {
        // The note is stored identically on all 35 rows today, but the guard
        // keys on the distinctive closing phrase so a trivially reworded or
        // whitespace-differing copy is still caught rather than leaking.
        $package = new Package(['summary' => 'Some other lead-in text — this content could not be recovered and is not invented here.']);

        $this->assertStringNotContainsString('is not invented here', $package->publicSummary());
    }
}
