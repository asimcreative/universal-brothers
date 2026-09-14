<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Support\SiteImagery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Which photograph each record gets, and which it must never get.
 *
 * The reason this file exists: two Tourism packages shipped to production
 * carrying religious imagery — a Kashmir sightseeing tour with a photograph of
 * the Kaaba, and a Bhurban tour with Al-Masjid an-Nabawi. Both reached the live
 * site. That is not a near-miss like showing the wrong mountain; it uses the two
 * holiest sites in Islam as decoration for a holiday package, which this company
 * of all companies cannot be seen doing.
 *
 * They were not hard-coded. They fell through `forPackage()`'s catch-all, which
 * returned religious imagery for ANY unmatched non-pilgrimage package — so the
 * next tour added through the admin would have done exactly the same thing. The
 * catch-all is what these tests pin.
 */
class SiteImageryTest extends TestCase
{
    use RefreshDatabase;

    /** Photographs of places that are sacred, not scenic. */
    private const RELIGIOUS = [
        'kaaba-tawaf', 'kaaba-close', 'haram-dusk', 'haram-panorama', 'haram-courtyard',
        'makkah-skyline', 'nabawi-aerial', 'nabawi-dome', 'quba-mosque',
        'mina-tents', 'arafat', 'jamarat', 'muzdalifah',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--force' => true]);
    }

    public function test_no_tourism_package_is_given_religious_imagery(): void
    {
        $packages = Package::published()
            ->whereHas('category', fn ($q) => $q->where('slug', 'tourism'))
            ->with('category:id,slug,name')
            ->get();

        $this->assertGreaterThan(0, $packages->count(), 'No tourism packages to check.');

        foreach ($packages as $package) {
            $key = SiteImagery::forPackage($package);

            $this->assertNotContains(
                $key,
                self::RELIGIOUS,
                "'{$package->name}' is a tourism package and was given '{$key}' — a photograph of a sacred site."
            );
        }
    }

    public function test_the_unmatched_package_fallback_is_never_religious(): void
    {
        // The catch-all itself, reached directly: a published tourism package
        // whose name matches no destination in the pool. This is the trap the
        // two live defects fell into, and it has to stay shut for names nobody
        // has thought of yet.
        $package = Package::published()
            ->whereHas('category', fn ($q) => $q->where('slug', 'tourism'))
            ->with('category:id,slug,name')
            ->firstOrFail();

        $package->name = 'Somewhere Nobody Has Mapped Yet';
        $package->slug = 'somewhere-nobody-has-mapped-yet';

        $key = SiteImagery::forPackage($package);

        $this->assertNotNull($key, 'An unmatched package should still get a photograph.');
        $this->assertNotContains(
            $key,
            self::RELIGIOUS,
            "The unmatched-package fallback returned '{$key}', a photograph of a sacred site."
        );
    }

    public function test_the_two_destinations_that_shipped_wrong_now_resolve_correctly(): void
    {
        // The actual reported cases, named explicitly — a generic rule can pass
        // while the specific packages that were wrong are still wrong.
        $this->assertSame('murree-bhurban', SiteImagery::forDestination('Bhurban Tour'));
        $this->assertSame('kashmir-neelum', SiteImagery::forDestination('Kashmir Tour'));
    }

    public function test_pilgrimage_packages_still_get_pilgrimage_imagery(): void
    {
        // The fix must not have swung the other way: Hajj and Umrah packages
        // are exactly where this imagery belongs.
        $packages = Package::published()
            ->whereHas('category', fn ($q) => $q->whereIn('slug', ['hajj', 'umrah']))
            ->with('category:id,slug,name')
            ->get();

        $this->assertGreaterThan(0, $packages->count());

        foreach ($packages as $package) {
            $this->assertContains(
                SiteImagery::forPackage($package),
                self::RELIGIOUS,
                "'{$package->name}' is a pilgrimage package but was not given pilgrimage imagery."
            );
        }
    }

    public function test_a_departure_city_in_the_name_does_not_override_the_destination(): void
    {
        // "Skardu Tour Direct from Dubai" goes TO Skardu. Naming Dubai as the
        // departure point must not pull Dubai imagery onto it.
        $key = SiteImagery::forDestination('The Splendid Skardu Tour Direct from Dubai');

        $this->assertStringStartsWith('skardu-', (string) $key);
    }

    public function test_every_published_package_gets_a_photograph(): void
    {
        foreach (Package::published()->with('category:id,slug,name')->get() as $package) {
            $this->assertNotNull(
                SiteImagery::forPackage($package),
                "'{$package->name}' would render with no photograph at all."
            );
        }
    }

    public function test_every_library_photograph_has_the_provenance_its_licence_requires(): void
    {
        foreach (SiteImagery::all() as $key => $entry) {
            $source = $entry['source'] ?? null;

            $this->assertNotNull($source, "'{$key}' has no source metadata.");

            foreach (['file', 'page', 'licence', 'licenceUrl', 'artist'] as $field) {
                $this->assertNotEmpty($source[$field] ?? null, "'{$key}' is missing its {$field}.");
            }
        }
    }

    public function test_a_page_does_not_show_the_same_photograph_twice_in_a_row(): void
    {
        // /umrah and /media each shipped with one picture used as both the hero
        // and the backdrop of the empty state directly beneath it. Two copies of
        // the same image stacked on a short page reads as a bug, not a motif.
        foreach (['/umrah', '/media'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            preg_match_all('~images/photos/([a-z0-9-]+)-\d+\.webp~', $html, $matches);

            $distinct = array_unique($matches[1] ?? []);

            $this->assertGreaterThan(
                1,
                count($distinct),
                "{$path} renders only one distinct photograph (".implode(', ', $distinct).'), so the hero and the empty state are showing the same picture.'
            );
        }
    }

    public function test_a_tourism_page_does_not_close_with_a_pilgrimage_call_to_action(): void
    {
        $package = Package::published()
            ->whereHas('category', fn ($q) => $q->where('slug', 'tourism'))
            ->firstOrFail();

        $response = $this->get(route('packages.show', [
            'category' => 'tourism',
            'package' => $package->slug,
        ]))->assertOk();

        $html = $response->getContent();

        // The closing band used to read "Your Sacred Journey Begins With a
        // Conversation" over a photograph of Masjid al-Haram, with a button to
        // the Hajj catalogue — on a sightseeing page. The picture was the
        // visible half; the link was the half a visitor would have clicked.
        $this->assertStringNotContainsString('Your Sacred Journey Begins', $html);
        $this->assertStringContainsString('Where Would You Like to Go?', $html);
        $this->assertStringContainsString('Browse All Tours', $html);

        // The CTA band must not reach for the package's own photograph either —
        // the hero already shows it a few hundred pixels higher.
        $own = SiteImagery::forPackage($package->load('category:id,slug,name'));
        preg_match_all('~images/photos/([a-z0-9-]+)-\d+\.webp~', $html, $matches);
        $distinct = array_values(array_unique($matches[1] ?? []));

        $this->assertContains($own, $distinct, 'The hero photograph is missing.');
        $this->assertGreaterThan(1, count($distinct), 'The CTA is repeating the hero photograph.');
    }

    public function test_a_pilgrimage_page_keeps_its_pilgrimage_call_to_action(): void
    {
        $package = Package::published()
            ->whereHas('category', fn ($q) => $q->where('slug', 'hajj'))
            ->firstOrFail();

        // The fix must not have swung the other way.
        $this->get(route('packages.show', ['category' => 'hajj', 'package' => $package->slug]))
            ->assertOk()
            ->assertSee('Your Sacred Journey Begins', false);
    }

    public function test_a_substitute_photograph_stays_in_the_same_family(): void
    {
        SiteImagery::forgetUsed();

        // Makkah must never be substituted by Madinah, or by a mountain valley.
        // A different picture is only an improvement if it still says the same
        // thing.
        SiteImagery::url('kaaba-tawaf');

        $substitute = SiteImagery::unusedSibling('kaaba-tawaf');

        $this->assertNotSame('kaaba-tawaf', $substitute);
        $this->assertContains(
            $substitute,
            ['kaaba-close', 'haram-dusk', 'haram-panorama', 'haram-courtyard', 'makkah-skyline'],
            "'kaaba-tawaf' was substituted by '{$substitute}', which is not a photograph of the same place."
        );
    }

    public function test_an_unused_photograph_is_returned_unchanged(): void
    {
        SiteImagery::forgetUsed();

        // Nothing has been rendered, so there is nothing to avoid.
        $this->assertSame('kaaba-tawaf', SiteImagery::unusedSibling('kaaba-tawaf'));
        $this->assertNull(SiteImagery::unusedSibling(null));
    }

    public function test_credits_are_produced_only_for_photographs_actually_rendered(): void
    {
        SiteImagery::forgetUsed();

        $this->assertSame([], SiteImagery::credits(), 'Credits appeared before any photograph was rendered.');

        SiteImagery::url('arafat');

        $credits = SiteImagery::credits();

        $this->assertCount(1, $credits);
        $this->assertSame('arafat', $credits[0]['key']);
        $this->assertNotEmpty($credits[0]['artist']);
        $this->assertNotEmpty($credits[0]['licence']);
        $this->assertStringStartsWith('https://', $credits[0]['page']);
    }

    public function test_public_domain_photographs_are_not_padded_into_the_credits(): void
    {
        SiteImagery::forgetUsed();

        $cc0 = collect(SiteImagery::all())
            ->filter(fn (array $e) => empty($e['source']['attributionRequired'] ?? null))
            ->keys()
            ->first();

        $this->assertNotNull($cc0, 'Expected at least one public-domain photograph in the library.');

        SiteImagery::url($cc0);

        $this->assertSame(
            [],
            SiteImagery::credits(),
            "'{$cc0}' requires no attribution, so listing it would bury the credits that are actually obligations."
        );
    }
}
