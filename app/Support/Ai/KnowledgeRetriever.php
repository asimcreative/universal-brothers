<?php

namespace App\Support\Ai;

use App\Models\AiKnowledgeEntry;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns a visitor's question into grounded context plus the links that back it.
 *
 * Three passes, in order of how much they can be trusted:
 *
 * 1. EXACT — a package code in the question ("UB010") resolves straight to that
 *    package. No scoring, no guessing.
 * 2. STRUCTURED — packages matched by keyword, then rendered live from the
 *    database by PackageContext so the prices are current by construction.
 * 3. TEXT — the knowledge index, for everything that is not a package: FAQs,
 *    company pages, awards, offices.
 *
 * Scoring is LIKE-based rather than a full-text index because this project runs
 * SQLite locally and in the test suite and MySQL in production, and MATCH
 * AGAINST does not exist in SQLite. Keeping one code path means the behaviour
 * under test is the behaviour in production.
 */
class KnowledgeRetriever
{
    /**
     * Words too common in this domain to discriminate between records — every
     * package mentions Hajj, Makkah and hotels — plus ordinary English and
     * Roman Urdu filler. Scoring on these ranks noise to the top.
     */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'are', 'you', 'your', 'what', 'which', 'with', 'from', 'that', 'this',
        'have', 'has', 'can', 'could', 'would', 'should', 'about', 'please', 'tell', 'give', 'need',
        'want', 'how', 'much', 'many', 'does', 'did', 'was', 'were', 'will', 'any', 'all', 'get',
        'kya', 'kia', 'mujhe', 'hai', 'hain', 'kitna', 'kitni', 'kitne', 'bhai', 'karo', 'chahiye',
        'batao', 'aur', 'nahi', 'nhi', 'mein', 'par', 'hajj', 'umrah', 'package', 'packages',
    ];

    private const CURRENCY_TERMS = [
        'pkr' => ['pkr', 'rupee', 'rupees', 'rs', 'pakistani', 'روپے'],
        'sar' => ['sar', 'riyal', 'riyals', 'sr', 'saudi', 'ريال'],
        'usd' => ['usd', 'dollar', 'dollars', 'us$', '$', 'american'],
    ];

    /**
     * @return array{context: string, sources: array<int, array<string, string>>, packages: array<int, string>, currencies: array<int, string>}
     */
    public function retrieve(string $question): array
    {
        $currencies = $this->currencies($question);
        $terms = $this->terms($question);

        $packages = $this->packages($question, $terms);
        $entries = $this->entries($terms);

        $blocks = [];
        $sources = [];

        foreach ($packages as $package) {
            $blocks[] = PackageContext::render($package, $currencies);

            if ($url = PackageContext::url($package)) {
                $sources[] = [
                    'title' => trim(($package->code ? $package->code.' — ' : '').$package->name),
                    'url' => $url,
                    'reason' => 'Full package details, itinerary and prices',
                ];
            }
        }

        foreach ($entries as $entry) {
            $blocks[] = '### '.$entry->title
                .($entry->url ? "\nPage: {$entry->url}" : '')
                ."\n".Str::limit($entry->body, config('ai.retrieval.entry_excerpt_chars'));

            if ($entry->url && ! $this->alreadyLinked($sources, $entry->url)) {
                $sources[] = [
                    'title' => $entry->title,
                    'url' => $entry->url,
                    'reason' => $this->reasonFor($entry->source_type),
                ];
            }
        }

        if (! $blocks) {
            $blocks[] = 'No matching Universal Brothers records were found for this question.';
        }

        $context = Str::limit(implode("\n\n---\n\n", $blocks), config('ai.retrieval.max_context_chars'), '');

        return [
            'context' => $context,
            'sources' => array_slice($sources, 0, 5),
            'packages' => $packages->pluck('code')->filter()->values()->all(),
            'currencies' => $currencies,
        ];
    }

    /**
     * Currencies the visitor named. Empty means "no preference", which makes
     * PackageContext render all three — better than guessing one and leaving
     * the visitor to wonder about the others.
     *
     * @return array<int, string>
     */
    public function currencies(string $question): array
    {
        $haystack = ' '.mb_strtolower($question).' ';
        $found = [];

        foreach (self::CURRENCY_TERMS as $currency => $terms) {
            foreach ($terms as $term) {
                // Word-boundary match so "sr" inside "sharing" or "us" inside
                // "used" does not select a currency.
                if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/u', $haystack)) {
                    $found[] = $currency;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Package codes written anywhere in the question, however spaced or cased:
     * "UB001", "ub 010", "UB-023".
     *
     * @return array<int, string>
     */
    public function codes(string $question): array
    {
        preg_match_all('/\bub[\s\-_]?(\d{1,3})\b/i', $question, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $digits) => 'UB'.str_pad($digits, 3, '0', STR_PAD_LEFT))
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function terms(string $question): array
    {
        $normalised = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $question) ?? '');

        return collect(preg_split('/\s+/u', $normalised, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->reject(fn (string $word) => mb_strlen($word) < 3 || in_array($word, self::STOP_WORDS, true))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Package>
     */
    private function packages(string $question, array $terms): Collection
    {
        $limit = (int) config('ai.retrieval.max_packages');
        $codes = $this->codes($question);

        // An explicit code is an exact instruction, so it bypasses scoring
        // entirely. Asking about UB010 must never return UB011.
        if ($codes) {
            return Package::query()
                ->published()
                ->whereIn('code', $codes)
                // Loaded across the whole collection, not per package: see
                // PackageContext::RELATIONS.
                ->with(PackageContext::RELATIONS)
                ->limit($limit)
                ->get();
        }

        if (! $terms) {
            return collect();
        }

        $ids = AiKnowledgeEntry::query()
            ->where('source_type', 'package')
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', '%'.$term.'%')
                        ->orWhere('keywords', 'like', '%'.$term.'%')
                        ->orWhere('body', 'like', '%'.$term.'%');
                }
            })
            ->limit(40)
            ->get(['source_id', 'title', 'keywords', 'body'])
            ->map(fn (AiKnowledgeEntry $entry) => [
                'id' => $entry->source_id,
                'score' => $this->score($entry, $terms),
            ])
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('id')
            ->all();

        if (! $ids) {
            return collect();
        }

        return Package::query()
            ->published()
            ->whereIn('id', $ids)
            ->with(PackageContext::RELATIONS)
            ->get();
    }

    /**
     * Everything that is NOT a package: FAQs, pages, offices, awards.
     *
     * The `source_type != 'package'` filter is the guarantee that a package's
     * index body can never become answer context — package facts are rendered
     * live by PackageContext instead. See KnowledgeIndexer's docblock.
     *
     * @param  array<int, string>  $terms
     * @return Collection<int, AiKnowledgeEntry>
     */
    private function entries(array $terms): Collection
    {
        if (! $terms) {
            return collect();
        }

        return AiKnowledgeEntry::query()
            ->where('source_type', '!=', 'package')
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', '%'.$term.'%')
                        ->orWhere('keywords', 'like', '%'.$term.'%')
                        ->orWhere('body', 'like', '%'.$term.'%');
                }
            })
            ->limit(60)
            ->get()
            ->sortByDesc(fn (AiKnowledgeEntry $entry) => $this->score($entry, $terms))
            ->take((int) config('ai.retrieval.max_entries'))
            ->values();
    }

    /** @param array<int, string> $terms */
    private function score(AiKnowledgeEntry $entry, array $terms): int
    {
        $title = mb_strtolower((string) $entry->title);
        $keywords = mb_strtolower((string) $entry->keywords);
        $body = mb_strtolower((string) $entry->body);

        $score = (int) ($entry->weight ?? 10);

        foreach ($terms as $term) {
            if (str_contains($title, $term)) {
                $score += 25;
            }

            if (str_contains($keywords, $term)) {
                $score += 15;
            }

            if (str_contains($body, $term)) {
                // Repeated mentions count, but with a ceiling so one long page
                // cannot outrank a precise short one on sheer length.
                $score += min(substr_count($body, $term), 4) * 4;
            }
        }

        return $score;
    }

    /** @param array<int, array<string, string>> $sources */
    private function alreadyLinked(array $sources, string $url): bool
    {
        foreach ($sources as $source) {
            if ($source['url'] === $url) {
                return true;
            }
        }

        return false;
    }

    private function reasonFor(string $sourceType): string
    {
        return match ($sourceType) {
            'category' => 'Browse all packages in this category',
            'series' => 'Packages in this series',
            'faq' => 'Frequently asked questions',
            'office', 'contact' => 'Contact details and enquiry form',
            'award' => "Universal Brothers' awards",
            'affiliation' => 'Accreditations and memberships',
            'testimonial' => 'What other pilgrims have said',
            'news' => 'Latest news and announcements',
            'hotel' => 'Hotel information',
            'media' => 'Photo and video gallery',
            default => 'Related page on the website',
        };
    }
}
