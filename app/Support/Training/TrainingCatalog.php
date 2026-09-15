<?php

namespace App\Support\Training;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The admin video training: chapters from resources/data/admin-video-training.php,
 * and — when the videos have been recorded — each chapter's file, length,
 * captions and thumbnail from manifest.json in config('training.video_path').
 *
 * The written companion guide works without any video; a chapter whose video
 * is missing simply says so instead of showing a broken player.
 */
class TrainingCatalog
{
    private static ?array $data = null;

    private static ?array $manifest = null;

    private static ?array $chapters = null;

    /** @return array<string, array{title: string, icon: string, summary: string}> */
    public static function categories(): array
    {
        return self::data()['categories'];
    }

    /** @return array<string, array> chapter key => chapter with key, number and video */
    public static function chapters(): array
    {
        if (self::$chapters !== null) {
            return self::$chapters;
        }

        $number = 0;

        return self::$chapters = collect(self::data()['chapters'])
            ->map(function (array $chapter, string $key) use (&$number) {
                $number++;

                return array_merge(
                    ['fields' => [], 'notes' => [], 'mistakes' => [], 'check' => [], 'learn' => [], 'steps' => [], 'keywords' => []],
                    $chapter,
                    ['key' => $key, 'number' => $number, 'video' => self::video($key)],
                );
            })
            ->all();
    }

    public static function chapter(string $key): ?array
    {
        return self::chapters()[$key] ?? null;
    }

    public static function count(): int
    {
        return count(self::data()['chapters']);
    }

    /** @return array{0: ?array, 1: ?array} */
    public static function neighbours(string $key): array
    {
        $keys = array_keys(self::data()['chapters']);
        $index = array_search($key, $keys, true);

        return [
            $index > 0 ? self::chapter($keys[$index - 1]) : null,
            $index !== false && isset($keys[$index + 1]) ? self::chapter($keys[$index + 1]) : null,
        ];
    }

    /** @return array<string, list<array>> category key => chapters, optionally filtered by search words */
    public static function grouped(?string $search = null): array
    {
        $chapters = collect(self::chapters());

        if (filled($search)) {
            $needle = Str::lower(trim($search));
            $chapters = $chapters->filter(fn (array $c) => Str::contains(Str::lower(self::searchText($c)), $needle));
        }

        return collect(self::categories())
            ->map(fn ($category, $key) => $chapters->where('category', $key)->values()->all())
            ->filter()
            ->all();
    }

    /** @return array<string, list<string>> */
    public static function checklist(): array
    {
        return self::data()['checklist'];
    }

    /** The chapter's admin page as [label, url], or null when that page no longer exists. */
    public static function related(array $chapter): ?array
    {
        [$label, $route, $params] = $chapter['related'] + [null, null, []];

        return $route && Route::has($route) ? ['label' => $label, 'url' => route($route, $params)] : null;
    }

    // ------------------------------------------------------------------
    // Recorded files
    // ------------------------------------------------------------------

    public static function path(string $file = ''): string
    {
        return rtrim(config('training.video_path'), '/\\').($file !== '' ? DIRECTORY_SEPARATOR.$file : '');
    }

    /**
     * @return array{file: string, duration: int, captions: ?string, poster: ?string, recorded_at: ?string}|null
     */
    public static function video(string $key): ?array
    {
        $entry = self::manifest()['chapters'][$key] ?? null;

        if (! $entry || ! self::safeFile($entry['file'] ?? null) || ! is_file(self::path($entry['file']))) {
            return null;
        }

        return [
            'file' => $entry['file'],
            'duration' => (int) ($entry['duration'] ?? 0),
            'captions' => self::safeFile($entry['captions'] ?? null) && is_file(self::path($entry['captions'])) ? $entry['captions'] : null,
            'poster' => self::safeFile($entry['poster'] ?? null) && is_file(self::path($entry['poster'])) ? $entry['poster'] : null,
            'recorded_at' => $entry['recorded_at'] ?? null,
        ];
    }

    /** Total recorded length in seconds. */
    public static function totalDuration(): int
    {
        return (int) collect(self::chapters())->sum(fn ($c) => $c['video']['duration'] ?? 0);
    }

    /**
     * The captions of a chapter as clickable moments: [seconds, text].
     *
     * @return list<array{0: int, 1: string}>
     */
    public static function moments(array $chapter): array
    {
        $captions = $chapter['video']['captions'] ?? null;
        if (! $captions) {
            return [];
        }

        $moments = [];
        $blocks = preg_split('/\R\R+/', trim((string) file_get_contents(self::path($captions))));
        foreach ($blocks as $block) {
            if (! preg_match('/(\d{2}):(\d{2}):(\d{2})\.\d{3}\s+-->/', $block, $time)) {
                continue;
            }
            $lines = preg_split('/\R/', $block);
            $text = '';
            foreach ($lines as $i => $line) {
                if (str_contains($line, '-->')) {
                    $text = trim(implode(' ', array_slice($lines, $i + 1)));
                    break;
                }
            }
            if ($text !== '') {
                $moments[] = [(int) $time[1] * 3600 + (int) $time[2] * 60 + (int) $time[3], $text];
            }
        }

        return $moments;
    }

    public static function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        return intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }

    /** Only plain file names from the manifest are served — never a path. */
    public static function safeFile(?string $file): bool
    {
        return is_string($file) && preg_match('/^[a-z0-9][a-z0-9._-]*\.(webm|vtt|jpg|jpeg|png)$/i', $file) === 1;
    }

    /** For tests: forget cached content and manifest. */
    public static function flush(): void
    {
        self::$data = null;
        self::$manifest = null;
        self::$chapters = null;
    }

    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $file = self::path('manifest.json');
        $decoded = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return self::$manifest = is_array($decoded) ? $decoded : ['chapters' => []];
    }

    private static function data(): array
    {
        return self::$data ??= require resource_path('data/admin-video-training.php');
    }

    private static function searchText(array $chapter): string
    {
        return implode(' ', array_merge(
            [$chapter['title'], $chapter['description'], $chapter['objective'] ?? '', $chapter['why'] ?? ''],
            $chapter['learn'], $chapter['steps'], $chapter['keywords'], $chapter['notes'],
        ));
    }
}
