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

    /**
     * Keys emitted into the current response, in the order they first appeared.
     *
     * 38 of the 45 photographs are CC BY or CC BY-SA, which oblige us to credit
     * the photographer wherever the work is used. Collecting the keys as they
     * are rendered is what lets the footer print credits for exactly the photos
     * on THIS page — rather than every photo in the library, which would be
     * noise, or none at all, which is what shipped until now and is a licence
     * breach however small it looks.
     *
     * @var array<string, true>
     */
    private static array $used = [];

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

        if (! $largest) {
            return null;
        }

        // Recorded here rather than in the Blade components because this is the
        // one place every emitted photograph passes through — an `<img>` with no
        // `src` is not an image. A component added next month is credited
        // without anyone remembering to wire it up.
        self::$used[$key] = true;

        return asset(self::BASE.'/'.$largest['file']);
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

        // The catch-all for everything that is NOT Hajj or Umrah and whose
        // destination we could not identify.
        //
        // This used to return `pick(['kaaba-tawaf', 'nabawi-aerial'])`, which
        // put the Kaaba on a Kashmir sightseeing tour and Al-Masjid an-Nabawi
        // on a Bhurban one. Both shipped. That is not a near-miss like showing
        // the wrong mountain — it takes the two holiest sites in Islam and uses
        // them as decoration for a holiday package, which this company of all
        // companies cannot be seen doing.
        //
        // The names are now mapped (see destinationPool), but the fallback
        // itself had to change: the next unmatched tour would have hit exactly
        // the same trap. Religious imagery is reserved for religious travel,
        // and an unidentified destination gets something that makes no claim
        // about WHERE it is — an aircraft and an airport are honest for any
        // tour, because every tour involves the journey.
        return self::pick(['saudia-aircraft', 'jeddah-airport'], $seed);
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
            // Added after both of these fell through to the catch-all and were
            // served religious imagery — a Kashmir tour card carrying the
            // Kaaba, a Bhurban tour carrying Al-Masjid an-Nabawi.
            'bhurban' => ['murree-bhurban'],
            'murree' => ['murree-bhurban'],
            'patriata' => ['murree-bhurban'],
            'nathia' => ['murree-bhurban'],
            'kashmir' => ['kashmir-neelum'],
            'neelum' => ['kashmir-neelum'],
            'neelam' => ['kashmir-neelum'],
            'muzaffarabad' => ['kashmir-neelum'],

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
    /**
     * Attribution for the photographs rendered so far in this response.
     *
     * Returns only those whose licence actually obliges it — the seven CC0
     * images are public domain and crediting them would pad the block with
     * noise that makes the real obligations harder to read.
     *
     * Each entry carries the full TASL set the Creative Commons licences ask
     * for — Title, Author, Source, Licence — plus a note that the file was
     * modified. Every image in the library was resized and re-encoded to WebP
     * at three widths, which is a derivative; CC BY-SA in particular expects
     * that to be indicated, and saying so costs nothing.
     *
     * @return array<int, array{key: string, title: string, artist: string, page: string, licence: string, licenceUrl: string}>
     */
    public static function credits(): array
    {
        $credits = [];

        foreach (array_keys(self::$used) as $key) {
            $source = self::get($key)['source'] ?? null;

            if (! $source || empty($source['attributionRequired'])) {
                continue;
            }

            $credits[] = [
                'key' => $key,
                // The register stores the Commons filename ("File:Foo bar.jpg").
                // Stripping the prefix and the extension gives the work's title
                // as a reader would recognise it.
                'title' => self::workTitle($source['file'] ?? $key),
                'artist' => trim((string) ($source['artist'] ?? '')) ?: 'Unknown',
                'page' => (string) ($source['page'] ?? ''),
                'licence' => (string) ($source['licence'] ?? ''),
                'licenceUrl' => (string) ($source['licenceUrl'] ?? ''),
            ];
        }

        usort($credits, fn (array $a, array $b) => strcasecmp($a['title'], $b['title']));

        return $credits;
    }

    private static function workTitle(string $file): string
    {
        $title = preg_replace('/^File:/i', '', $file);
        $title = preg_replace('/\.(jpe?g|png|webp|gif|tiff?)$/i', '', (string) $title);

        return trim(str_replace('_', ' ', (string) $title));
    }

    /**
     * Forget what has been rendered. Only needed in tests, where several
     * responses share one process.
     */
    public static function forgetUsed(): void
    {
        self::$used = [];
    }

    /**
     * Photographs that can stand in for one another.
     *
     * Same subject, same register — so swapping within a family changes the
     * picture without changing what it says. A Makkah photo never substitutes
     * for a Madinah one, and neither ever substitutes for a mountain valley.
     *
     * @var array<int, list<string>>
     */
    private const FAMILIES = [
        ['kaaba-tawaf', 'kaaba-close', 'haram-dusk', 'haram-panorama', 'haram-courtyard', 'makkah-skyline'],
        ['nabawi-aerial', 'nabawi-dome', 'quba-mosque'],
        ['mina-tents', 'arafat', 'muzdalifah', 'jamarat'],
        ['jeddah-airport', 'saudia-aircraft', 'haramain-train'],
    ];

    /**
     * A photograph like `$key` that is not already on this page.
     *
     * The same picture appearing twice on one short page — once as the hero,
     * once behind the empty state directly beneath it — reads as a mistake
     * rather than a motif. It shipped that way on /umrah and /media because
     * each of those templates names its hero photo and its empty-state photo
     * separately, and the two happened to match.
     *
     * Fixing it here rather than in the templates means it also holds for the
     * next page someone adds, and for the day somebody changes a hero and
     * forgets what sits under it.
     */
    public static function unusedSibling(?string $key): ?string
    {
        if ($key === null || ! isset(self::$used[$key])) {
            return $key;
        }

        foreach (self::FAMILIES as $family) {
            if (! in_array($key, $family, true)) {
                continue;
            }

            foreach ($family as $sibling) {
                if ($sibling !== $key && ! isset(self::$used[$sibling]) && self::has($sibling)) {
                    return $sibling;
                }
            }
        }

        // Every sibling is already on the page, or the key belongs to no
        // family. Repeating it beats rendering nothing.
        return $key;
    }

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
