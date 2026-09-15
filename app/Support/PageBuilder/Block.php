<?php

namespace App\Support\PageBuilder;

use App\Support\SiteImagery;
use Illuminate\Support\Facades\Storage;

/**
 * Small helpers for the public section templates (resources/views/pages/blocks).
 *
 * Section data is validated before a page can be published, but a preview
 * shows an unfinished draft, so everything that reaches an attribute goes
 * through here again rather than trusting the stored value.
 */
class Block
{
    /** A link that is safe to put in href; anything unusable becomes "#". */
    public static function href(?string $link): string
    {
        $link = trim((string) $link);

        return $link !== '' && SectionValidator::isValidLink($link) ? $link : '#';
    }

    public static function isExternal(?string $link): bool
    {
        return (bool) preg_match('~^https?://~i', (string) $link)
            && parse_url((string) $link, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST);
    }

    /** Public address of a stored image, or null when there is none (or it is not a known image). */
    public static function imageUrl(?string $path): ?string
    {
        if (blank($path) || ! preg_match('~^(media|pages)/[A-Za-z0-9/_.-]+\.(jpe?g|png|webp|gif)$~i', $path) || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /** CSS classes for a section's background colour. */
    public static function background(?string $background): string
    {
        return match ($background) {
            'cream' => 'bg-light',
            'navy' => 'bg-primary text-white pb-section--dark',
            default => '',
        };
    }

    public static function isDark(?string $background): bool
    {
        return $background === 'navy';
    }

    /** A photograph from the shipped library, used when a banner has no uploaded photo. */
    public static function libraryPhoto(string $key): array
    {
        return SiteImagery::has($key)
            ? ['src' => SiteImagery::url($key), 'srcset' => SiteImagery::srcset($key)]
            : ['src' => null, 'srcset' => null];
    }
}
