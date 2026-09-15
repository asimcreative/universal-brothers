<?php

namespace App\Support\Guide;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Reads the admin guide's words from resources/data — the help centre
 * (admin-guide.php) and the builder's "Need help?" panels
 * (package-builder-help.php). The content lives in version-controlled files
 * so it can be corrected without touching code, and reviewed like code.
 */
class GuideContent
{
    private static ?array $guide = null;

    private static ?array $builderHelp = null;

    /** @return array<string, string> group key => title */
    public static function groups(): array
    {
        return self::guide()['groups'];
    }

    /** @return array<string, array> section key => section, each with its own `key` */
    public static function sections(): array
    {
        return collect(self::guide()['sections'])
            ->map(fn (array $section, string $key) => array_merge(['key' => $key, 'tips' => [], 'faqs' => [], 'links' => [], 'steps' => []], $section))
            ->all();
    }

    public static function section(string $key): ?array
    {
        return self::sections()[$key] ?? null;
    }

    /** @return array<string, list<array>> group key => its sections, in guide order */
    public static function grouped(?string $search = null): array
    {
        $sections = collect(self::sections());

        if (filled($search)) {
            $needle = Str::lower(trim($search));
            $sections = $sections->filter(fn (array $s) => Str::contains(Str::lower(self::searchText($s)), $needle));
        }

        return collect(self::groups())
            ->map(fn ($title, $group) => $sections->where('group', $group)->values()->all())
            ->filter()
            ->all();
    }

    /** @return array{0: ?array, 1: ?array} the sections before and after, in guide order */
    public static function neighbours(string $key): array
    {
        $keys = array_keys(self::sections());
        $index = array_search($key, $keys, true);

        return [
            $index > 0 ? self::section($keys[$index - 1]) : null,
            $index !== false && isset($keys[$index + 1]) ? self::section($keys[$index + 1]) : null,
        ];
    }

    /**
     * A section's links as [label, url], leaving out any whose page does not
     * exist — a renamed page makes a link disappear rather than break.
     *
     * @return list<array{label: string, url: string}>
     */
    public static function links(array $section): array
    {
        return collect($section['links'] ?? [])
            ->filter(fn ($link) => Route::has($link[1]))
            ->map(fn ($link) => ['label' => $link[0], 'url' => route($link[1], $link[2] ?? [])])
            ->values()
            ->all();
    }

    /** @return list<array{target: string, title: string, text: string, menu?: bool}> */
    public static function tour(): array
    {
        return self::guide()['tour'];
    }

    /** The "Need help?" panel for one builder step, or null. */
    public static function builderHelp(string $step): ?array
    {
        self::$builderHelp ??= require resource_path('data/package-builder-help.php');

        return self::$builderHelp[$step] ?? null;
    }

    private static function guide(): array
    {
        return self::$guide ??= require resource_path('data/admin-guide.php');
    }

    private static function searchText(array $section): string
    {
        return implode(' ', array_merge(
            [$section['title'], $section['summary'], $section['why'] ?? '', $section['example'] ?? ''],
            $section['steps'],
            $section['tips'],
            collect($section['faqs'])->flatten()->all(),
        ));
    }
}
