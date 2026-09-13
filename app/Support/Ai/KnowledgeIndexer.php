<?php

namespace App\Support\Ai;

use App\Models\Affiliation;
use App\Models\AiKnowledgeEntry;
use App\Models\AiSetting;
use App\Models\Award;
use App\Models\Faq;
use App\Models\Hotel;
use App\Models\MediaItem;
use App\Models\NewsArticle;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the searchable index from the project's own database.
 *
 * Two rules shape everything here:
 *
 * 1. A PACKAGE ENTRY IS FOR MATCHING, NEVER FOR ANSWERING. An index is a
 *    cache, and a cached price is a price that can go stale — this project has
 *    already shipped superseded figures once (issue #5) and that must not
 *    become possible a second way. So KnowledgeRetriever excludes
 *    source_type = 'package' when it collects text excerpts: a package entry
 *    only ever decides WHICH package is relevant, and every fact the model
 *    then sees is rendered live from the database by PackageContext.
 *
 *    A package body can therefore contain a figure — the brochure's own
 *    airfare estimate lives in the exclusions text, for instance — without
 *    that figure being able to reach an answer. AiAssistantTest proves the
 *    guarantee directly by changing a price after indexing and asserting the
 *    new one appears in the context.
 *
 * 2. ONLY PUBLIC CONTENT. Unpublished packages, inactive pages, hidden
 *    testimonials and draft news are skipped, because the assistant must never
 *    become a way to read something the website itself will not show.
 */
class KnowledgeIndexer
{
    public function rebuild(): int
    {
        $entries = [];

        foreach ($this->packages() as $entry) {
            $entries[] = $entry;
        }

        $entries = array_merge(
            $entries,
            $this->categories(),
            $this->series(),
            $this->faqs(),
            $this->pages(),
            $this->news(),
            $this->awards(),
            $this->affiliations(),
            $this->testimonials(),
            $this->offices(),
            $this->hotels(),
            $this->media(),
        );

        DB::transaction(function () use ($entries) {
            AiKnowledgeEntry::query()->delete();

            foreach (array_chunk($entries, 100) as $chunk) {
                AiKnowledgeEntry::insert(array_map(static fn (array $row) => $row + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $chunk));
            }
        });

        $count = count($entries);

        $settings = AiSetting::current();
        $settings->forceFill(['indexed_at' => now(), 'indexed_records' => $count])->save();

        AiConfig::flush();

        return $count;
    }

    /**
     * Packages carry the heaviest weight because they are what visitors
     * actually ask about, and they are indexed with every term someone might
     * search them by — code, hotel names, cities, series, Aziziya status.
     *
     * @return array<int, array<string, mixed>>
     */
    private function packages(): array
    {
        $packages = Package::query()
            ->published()
            ->with([
                'category:id,name,slug',
                'series:id,name',
                'accommodations:id,package_id,location,hotel_name,star_rating,nights',
                'itineraryDays:id,package_id,day_number,city',
                'inclusions:id,package_id,type,description',
                'exclusions:id,package_id,type,description',
                'variants:id,package_id,code,label',
                'aziziya',
            ])
            ->get();

        $entries = [];

        foreach ($packages as $package) {
            $lines = [];

            $lines[] = $package->name;

            if ($package->code) {
                $lines[] = "Package code: {$package->code}";
            }

            if ($package->duration_label || $package->duration_days) {
                $lines[] = 'Duration: '.($package->duration_label ?: $package->duration_days.' days');
            }

            if ($package->category) {
                $lines[] = "Category: {$package->category->name}";
            }

            if ($package->series) {
                $lines[] = "Series: {$package->series->name}";
            }

            if ($package->isHajj()) {
                $lines[] = 'Arrival: '.($package->medinah_first ? 'Medinah first' : 'Makkah first');
                $lines[] = 'Hotel movement: '.($package->is_shifting ? 'Shifting' : 'Non-shifting');
                $lines[] = 'Aziziya: '.($package->has_aziziya ? 'With Aziziya accommodation' : 'Non-Aziziya');
            }

            if ($summary = $package->publicSummary()) {
                $lines[] = $summary;
            }

            if ($package->description) {
                $lines[] = Str::limit(strip_tags($package->description), 600);
            }

            if ($package->variants->isNotEmpty()) {
                $lines[] = 'Variants: '.$package->variants
                    ->map(fn ($v) => trim("Package {$v->code}".($v->label ? " ({$v->label})" : '')))
                    ->implode(', ');
            }

            foreach ($package->accommodations as $accommodation) {
                $lines[] = trim(sprintf(
                    '%s hotel: %s%s%s',
                    Str::title($accommodation->location ?? ''),
                    $accommodation->hotel_name,
                    $accommodation->star_rating ? " ({$accommodation->star_rating} star)" : '',
                    $accommodation->nights ? ", {$accommodation->nights} nights" : ''
                ));
            }

            if ($package->aziziya && $package->aziziya->accommodation_name) {
                $lines[] = "Aziziya accommodation: {$package->aziziya->accommodation_name}";
            }

            $cities = $package->itineraryDays->pluck('city')->filter()->unique()->values();

            if ($cities->isNotEmpty()) {
                $lines[] = 'Itinerary covers: '.$cities->implode(', ');
            }

            if ($package->inclusions->isNotEmpty()) {
                $lines[] = 'Includes: '.$package->inclusions->pluck('description')->filter()->take(20)->implode('; ');
            }

            if ($package->exclusions->isNotEmpty()) {
                $lines[] = 'Not included: '.$package->exclusions->pluck('description')->filter()->take(20)->implode('; ');
            }

            $entries[] = [
                'source_type' => 'package',
                'source_id' => $package->id,
                'reference' => $package->code ?: $package->slug,
                'title' => $package->name,
                'url' => $this->packageUrl($package),
                'category' => $package->category?->slug,
                'body' => implode("\n", array_filter($lines)),
                'keywords' => implode(' ', array_filter([
                    $package->code,
                    $package->slug,
                    $package->category?->name,
                    $package->series?->name,
                    $package->package_type,
                    $package->has_aziziya ? 'aziziya' : 'non-aziziya',
                    $package->is_shifting ? 'shifting' : 'non-shifting',
                    $package->medinah_first ? 'medinah first madinah first' : 'makkah first mecca first',
                ])),
                'weight' => 40,
            ];
        }

        return $entries;
    }

    private function packageUrl(Package $package): ?string
    {
        $categorySlug = $package->category?->slug;

        if (! $categorySlug) {
            return null;
        }

        return route('packages.show', ['category' => $categorySlug, 'package' => $package->slug]);
    }

    /** @return array<int, array<string, mixed>> */
    private function categories(): array
    {
        return PackageCategory::where('is_active', true)->get()->map(fn (PackageCategory $category) => [
            'source_type' => 'category',
            'source_id' => $category->id,
            'reference' => $category->slug,
            'title' => "{$category->name} packages",
            'url' => route('packages.category', ['category' => $category->slug]),
            'category' => $category->slug,
            'body' => trim("{$category->name} packages from Universal Brothers.\n".strip_tags((string) $category->description)),
            'keywords' => "{$category->name} {$category->slug} packages listing",
            'weight' => 30,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function series(): array
    {
        return PackageSeries::where('is_active', true)->with('category:id,slug,name')->get()
            ->map(fn (PackageSeries $series) => [
                'source_type' => 'series',
                'source_id' => $series->id,
                'reference' => $series->slug,
                'title' => $series->name,
                'url' => $series->category
                    ? route('packages.category', ['category' => $series->category->slug]).'?series='.$series->slug
                    : null,
                'category' => $series->category?->slug,
                'body' => trim($series->name."\n".strip_tags((string) $series->description)),
                'keywords' => "{$series->name} {$series->slug} series",
                'weight' => 20,
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function faqs(): array
    {
        return Faq::where('is_active', true)->get()->map(fn (Faq $faq) => [
            'source_type' => 'faq',
            'source_id' => $faq->id,
            'reference' => null,
            'title' => $faq->question,
            'url' => route('faqs'),
            'category' => $faq->category,
            'body' => "Q: {$faq->question}\nA: ".strip_tags((string) $faq->answer),
            'keywords' => (string) $faq->category,
            'weight' => 25,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(): array
    {
        return Page::where('is_active', true)->get()->map(fn (Page $page) => [
            'source_type' => 'page',
            'source_id' => $page->id,
            'reference' => $page->slug,
            'title' => $page->title,
            'url' => route('pages.show', ['slug' => $page->slug]),
            'category' => 'page',
            'body' => Str::limit(trim($page->title."\n".strip_tags((string) $page->body)), 4000),
            'keywords' => $page->slug,
            'weight' => 15,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function news(): array
    {
        return NewsArticle::where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(50)
            ->get()
            ->map(fn (NewsArticle $article) => [
                'source_type' => 'news',
                'source_id' => $article->id,
                'reference' => $article->slug,
                'title' => $article->title,
                'url' => route('news.show', ['slug' => $article->slug]),
                'category' => 'news',
                'body' => Str::limit(trim($article->title."\n".($article->excerpt ?: '')."\n".strip_tags((string) $article->body)), 2500),
                'keywords' => 'news update announcement',
                'weight' => 12,
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function awards(): array
    {
        return Award::where('is_active', true)->get()->map(fn (Award $award) => [
            'source_type' => 'award',
            'source_id' => $award->id,
            'reference' => null,
            'title' => $award->name,
            'url' => route('awards'),
            'category' => 'company',
            'body' => trim(implode("\n", array_filter([
                $award->name,
                $award->awarding_organization ? "Awarded by: {$award->awarding_organization}" : null,
                $award->year ? "Year: {$award->year}" : null,
                strip_tags((string) $award->description),
            ]))),
            'keywords' => 'award recognition achievement certificate',
            'weight' => 18,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function affiliations(): array
    {
        return Affiliation::where('is_active', true)->get()->map(fn (Affiliation $affiliation) => [
            'source_type' => 'affiliation',
            'source_id' => $affiliation->id,
            'reference' => null,
            'title' => $affiliation->organization_name,
            'url' => route('affiliations'),
            'category' => 'company',
            'body' => trim(implode("\n", array_filter([
                $affiliation->organization_name,
                $affiliation->year ? "Member since: {$affiliation->year}" : null,
                strip_tags((string) $affiliation->description),
            ]))),
            'keywords' => 'affiliation membership accreditation licensed registered IATA',
            'weight' => 18,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function testimonials(): array
    {
        return Testimonial::where('is_active', true)->limit(30)->get()->map(fn (Testimonial $testimonial) => [
            'source_type' => 'testimonial',
            'source_id' => $testimonial->id,
            'reference' => null,
            'title' => 'Testimonial from '.$testimonial->name,
            'url' => route('testimonials'),
            'category' => 'company',
            'body' => trim(($testimonial->package_label ? "About: {$testimonial->package_label}\n" : '').'"'.strip_tags((string) $testimonial->quote).'" — '.$testimonial->name),
            'keywords' => 'testimonial review feedback experience pilgrim',
            'weight' => 10,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function offices(): array
    {
        return Office::where('is_active', true)->get()->map(fn (Office $office) => [
            'source_type' => 'office',
            'source_id' => $office->id,
            'reference' => null,
            'title' => $office->label ?: 'Universal Brothers office',
            'url' => route('contact'),
            'category' => 'contact',
            'body' => trim(implode("\n", array_filter([
                $office->label,
                $office->address ? "Address: {$office->address}" : null,
                $office->phone_primary ? "Phone: {$office->phone_primary}" : null,
                $office->phone_secondary ? "Phone: {$office->phone_secondary}" : null,
                $office->whatsapp ? "WhatsApp: {$office->whatsapp}" : null,
                $office->email ? "Email: {$office->email}" : null,
            ]))),
            'keywords' => 'contact office address phone whatsapp email location visit call',
            'weight' => 28,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function hotels(): array
    {
        return Hotel::where('is_active', true)->get()->map(fn (Hotel $hotel) => [
            'source_type' => 'hotel',
            'source_id' => $hotel->id,
            'reference' => $hotel->slug,
            'title' => $hotel->name,
            'url' => null,
            'category' => 'hotel',
            'body' => trim(implode("\n", array_filter([
                $hotel->name,
                $hotel->city ? "City: {$hotel->city}" : null,
                $hotel->star_rating ? "Rating: {$hotel->star_rating} star" : null,
                strip_tags((string) $hotel->description),
            ]))),
            'keywords' => trim("hotel accommodation {$hotel->city}"),
            'weight' => 16,
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function media(): array
    {
        return MediaItem::where('is_active', true)->limit(40)->get()->map(fn (MediaItem $item) => [
            'source_type' => 'media',
            'source_id' => $item->id,
            'reference' => null,
            'title' => $item->title ?: 'Media',
            'url' => route('media'),
            'category' => 'media',
            'body' => trim(($item->title ?: '').' ('.($item->media_type ?: 'media').')'),
            'keywords' => 'photo video gallery media',
            'weight' => 6,
        ])->all();
    }
}
