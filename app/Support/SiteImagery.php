<?php

namespace App\Support;

use App\Models\Package;
use Illuminate\Support\Str;

/**
 * The site's photograph library, and the rules for choosing one.
 *
 * Until now every image slot on the public site rendered a generated geometric
 * composition — the database holds zero images across all nine image columns
 * (packages, hotels, awards, affiliations, testimonials, sliders, media, news,
 * pages), so there was never a real photograph anywhere. This class is what
 * puts real photography in front of the visitor while keeping two rules:
 *
 *   1. A CMS image always wins. The moment the client uploads a real cover for
 *      a package, or a hotel supplies its own photography, `resolve()` uses it
 *      and this library steps aside. Nothing here has to be unpicked later.
 *
 *   2. Nothing claims to be something it is not. A photograph of the Abraj Al
 *      Bait towers is captioned as the Makkah skyline, never as "your hotel".
 *      We do not own photography of the individual properties, so the imagery
 *      describes the CITY the stay is in — which is true for every package —
 *      and the hotel's own name, stars and distance stay as text beside it.
 *
 * Selection is data-driven. There is no `if ($package->code === 'UB001')`
 * anywhere: a package's photograph comes from its own fields (which city it
 * starts in, which locations its accommodation rows carry, what its name says
 * about the destination), so a package added through the admin tomorrow gets
 * appropriate imagery with no code change.
 */
class SiteImagery
{
    /** @var array<string, array>|null */
    private static ?array $registry = null;

    public const BASE = 'images/photos';

    /** @return array<string, array> */
    public static function all(): array
    {
        return self::$registry ??= require resource_path('data/site-imagery.php');
    }

    public static function has(?string $key): bool
    {
        return $key !== null && isset(self::all()[$key]);
    }

    /** @return array|null */
    public static function get(?string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * The widest rendition's URL — the `src` a browser falls back to when it
     * does not support `srcset`.
     *
     * Deliberately takes no width argument. An earlier version defaulted to
     * `1600`, which silently stopped matching anything the moment the build
     * was retuned to 1440/1000/560 and fell through to "whatever happens to be
     * first in the array". Asking for the largest is what every caller wants,
     * and it cannot go stale when the rendition widths change.
     */
    public static function url(?string $key): ?string
    {
        $largest = self::largestRendition($key);

        return $largest ? asset(self::BASE.'/'.$largest['file']) : null;
    }

    /** @return array{file: string, w: int, h: int, bytes: int}|null */
    private static function largestRendition(?string $key): ?array
    {
        $entry = self::get($key);
        if (! $entry) {
            return null;
        }

        $largest = null;
        foreach ($entry['renditions'] as $r) {
            if (! $largest || $r['w'] > $largest['w']) {
                $largest = $r;
            }
        }

        return $largest;
    }

    /**
     * A `srcset` across every rendition, so a phone downloads the small file
     * and a desktop the large one. Without this the site would ship its full
     * photographic weight to a phone that can only show a fraction of those
     * pixels.
     */
    public static function srcset(?string $key): ?string
    {
        $entry = self::get($key);
        if (! $entry) {
            return null;
        }

        $parts = [];
        foreach ($entry['renditions'] as $r) {
            $parts[] = asset(self::BASE.'/'.$r['file']).' '.$r['w'].'w';
        }

        return implode(', ', $parts);
    }

    /**
     * Intrinsic dimensions of the largest rendition, for the `width`/`height`
     * attributes that stop the page reflowing as each photograph arrives.
     */
    public static function dimensions(?string $key): ?array
    {
        $largest = self::largestRendition($key);

        return $largest ? ['w' => $largest['w'], 'h' => $largest['h']] : null;
    }

    public static function alt(?string $key, string $fallback = ''): string
    {
        return self::get($key)['alt'] ?? $fallback;
    }

    // ---------------------------------------------------------------------
    // Choosing a photograph from real data
    // ---------------------------------------------------------------------

    /**
     * Deterministic pick from a pool, seeded by a stable string.
     *
     * The brief is explicit that the same Kaaba photograph must not appear on
     * every package. Hashing the package's own code spreads twelve packages
     * across the pool while keeping each package's image stable across page
     * loads, pagination and caching — the same approach the generated-visual
     * system already uses for its variants.
     */
    public static function pick(array $pool, string $seed): ?string
    {
        $pool = array_values(array_filter($pool, fn ($k) => self::has($k)));

        return $pool ? $pool[crc32($seed) % count($pool)] : null;
    }

    /** Photograph for a city an accommodation row sits in. */
    public static function forCity(?string $location, string $seed = ''): ?string
    {
        return match (Str::lower((string) $location)) {
            'makkah', 'mecca' => self::pick(['haram-courtyard', 'haram-dusk', 'makkah-skyline'], $seed.'makkah'),
            'medinah', 'madinah', 'medina' => self::pick(['nabawi-aerial', 'nabawi-dome', 'quba-mosque'], $seed.'madinah'),
            // Aziziya is a residential district of Makkah, so the city skyline is
            // the honest illustration; the card's own text names the actual
            // accommodation.
            'aziziya' => 'makkah-skyline',
            default => null,
        };
    }

    /** Photograph for a Mashaer location (Mina / Arafat / Muzdalifah). */
    public static function forMashaer(?string $location): ?string
    {
        $l = Str::lower((string) $location);

        return match (true) {
            str_contains($l, 'mina') => 'mina-tents',
            str_contains($l, 'arafat'), str_contains($l, 'arafah') => 'arafat',
            str_contains($l, 'muzdalifah'), str_contains($l, 'muzdalifa') => 'muzdalifah',
            str_contains($l, 'jamarat') => 'jamarat',
            default => null,
        };
    }

    /** Photograph for a transport leg, chosen from its stored type. */
    public static function forTransport(?string $type): ?string
    {
        $t = Str::lower((string) $type);

        return match (true) {
            str_contains($t, 'train'), str_contains($t, 'haramain'), str_contains($t, 'rail') => 'haramain-train',
            str_contains($t, 'flight'), str_contains($t, 'air') => 'saudia-aircraft',
            default => 'jeddah-airport',
        };
    }

    /**
     * Hero/card photograph for a package, derived from the package itself.
     *
     * Hajj and Umrah packages lead with the city the pilgrim arrives in first,
     * which `medinah_first` records; the rest of the pool keeps twelve cards
     * from looking like twelve copies. Tourism packages are matched on the
     * destination named in the package itself.
     */
    public static function forPackage(Package $package): ?string
    {
        $seed = $package->code ?: $package->slug ?: (string) $package->id;

        if ($destination = self::forDestination($package->name.' '.$package->slug)) {
            return $destination;
        }

        $category = Str::lower((string) ($package->category->slug ?? ''));

        if ($category === 'hajj' || $category === 'umrah') {
            $madinahFirst = (bool) $package->medinah_first;

            $pool = $madinahFirst
                ? ['nabawi-aerial', 'nabawi-dome', 'quba-mosque', 'haram-courtyard']
                : ['kaaba-tawaf', 'haram-dusk', 'kaaba-close', 'makkah-skyline'];

            return self::pick($pool, $seed);
        }

        return self::pick(['kaaba-tawaf', 'nabawi-aerial'], $seed);
    }

    /**
     * Match a destination named in free text to its photograph.
     *
     * The four real tourism packages are Hunza, Skardu, Malam Jabba and a
     * Skardu tour out of Dubai, and the destination is stated in the package
     * name. Reading the name rather than hard-coding a slug means a new tour
     * added through the admin picks up the right photograph by itself.
     */
    public static function forDestination(?string $text): ?string
    {
        $pool = self::destinationPool($text);

        return $pool ? self::pick($pool, Str::lower((string) $text)) : null;
    }

    /**
     * A SECOND photograph of the same destination, different from the first.
     *
     * Used by the detail page, which shows the destination once in the hero and
     * once again in the body. Most destinations are represented by a single
     * photograph, and in that case this returns null and the second block simply
     * does not render — showing the same picture twice, or padding with a
     * generic filler, would both be worse than showing it once.
     */
    public static function secondaryForDestination(?string $text): ?string
    {
        $pool = self::destinationPool($text);
        if (count($pool) < 2) {
            return null;
        }

        $first = self::forDestination($text);
        $rest = array_values(array_filter($pool, fn ($k) => $k !== $first && self::has($k)));

        return $rest ? $rest[crc32('alt'.Str::lower((string) $text)) % count($rest)] : null;
    }

    /**
     * Which photographs cover the destination named in a piece of free text.
     *
     * @return list<string>
     */
    private static function destinationPool(?string $text): array
    {
        $t = Str::lower((string) $text);
        if ($t === '') {
            return [];
        }

        foreach ([
            'hunza' => ['hunza-attabad', 'hunza-baltit', 'hunza-valley'],
            'attabad' => ['hunza-attabad'],
            'karimabad' => ['hunza-baltit'],
            'skardu' => ['skardu-skyline', 'skardu-shangrila', 'skardu-deosai'],
            'deosai' => ['skardu-deosai'],
            'shangrila' => ['skardu-shangrila'],
            'malam jabba' => ['malam-jabba'],
            'malam-jabba' => ['malam-jabba'],
            'swat' => ['malam-jabba'],
            'fairy meadows' => ['fairy-meadows'],
            'nanga parbat' => ['karakoram-highway'],
            'gilgit' => ['karakoram-highway'],
            'baltistan' => ['skardu-skyline'],
            'naran' => ['kaghan-naran'],
            'kaghan' => ['kaghan-naran'],
            'shogran' => ['kaghan-naran'],
            'islamabad' => ['islamabad-faisal'],
            'karachi' => ['karachi'],
            'jeddah' => ['jeddah-airport'],

            // International tours. Same principle: the destination is stated in
            // the package's own name, so a tour added through the admin picks up
            // the right photograph without a code change.
            'dubai' => ['dubai'],
            'turkey' => ['turkey-istanbul', 'turkey-cappadocia'],
            'istanbul' => ['turkey-istanbul'],
            'cappadocia' => ['turkey-cappadocia'],
            'maldives' => ['maldives'],
            'jordan' => ['jordan-petra'],
            'petra' => ['jordan-petra'],
            'egypt' => ['egypt-pyramids'],
            'thailand' => ['thailand'],
            'bangkok' => ['thailand'],
            'singapore' => ['singapore'],
            'malaysia' => ['malaysia'],
            'kuala lumpur' => ['malaysia'],
            'bintan' => ['indonesia-bali'],
            'indonesia' => ['indonesia-bali'],
            'bali' => ['indonesia-bali'],
            'sri lanka' => ['sri-lanka'],
            'south africa' => ['south-africa'],
            'cape town' => ['south-africa'],
            'hong kong' => ['hong-kong'],
            'china' => ['china-wall'],
            'baku' => ['azerbaijan-baku'],
            'azerbaijan' => ['azerbaijan-baku'],
            'europe' => ['europe', 'europe-alps'],
        ] as $needle => $pool) {
            if (str_contains($t, $needle)) {
                return $pool;
            }
        }

        return [];
    }

    /**
     * Turn a stored CMS image path into a URL, whichever storage it lives in.
     *
     * Uploaded content lives on the `public` disk and is served through the
     * storage symlink; the shipped photograph library lives under
     * `public/images/` and is served directly. Handling both here means a
     * component never has to care, and the client replacing a photograph
     * through the admin needs no template change.
     */
    public static function resolve(?string $stored): ?string
    {
        if (blank($stored)) {
            return null;
        }
        if (Str::startsWith($stored, ['http://', 'https://', '//'])) {
            return $stored;
        }
        if (Str::startsWith($stored, ['images/', '/images/'])) {
            return asset(ltrim($stored, '/'));
        }

        return \Storage::url($stored);
    }
}
