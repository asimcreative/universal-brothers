<?php

namespace App\Support\PageBuilder;

/**
 * Turns the link an admin copies from the browser into a player address.
 * Only YouTube (served from its privacy-enhanced domain) and Vimeo are accepted.
 */
class VideoEmbed
{
    public static function embedUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $patterns = [
            '~^https?://(?:www\.|m\.)?youtube\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{6,20})~i',
            '~^https?://(?:www\.)?youtu\.be/([A-Za-z0-9_-]{6,20})~i',
            '~^https?://(?:www\.)?youtube(?:-nocookie)?\.com/(?:embed|shorts|live)/([A-Za-z0-9_-]{6,20})~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return 'https://www.youtube-nocookie.com/embed/'.$m[1];
            }
        }

        if (preg_match('~^https?://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d{3,12})~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
