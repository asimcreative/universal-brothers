<?php

namespace App\Support\PageBuilder;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\NewsArticle;
use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Prepares a page's visible sections for the public template: each section
 * gets its Blade view and whatever live data it shows (packages, testimonials,
 * awards …), loaded here so the views stay free of queries.
 *
 * Sections that link to a saved section are replaced by that saved section's
 * current content. A section that would show nothing (for example "Latest
 * news" when no news is published) is left out rather than rendered empty.
 */
class PageRenderer
{
    /** @return list<array{id: string, type: string, view: string, data: array, extra: array}> */
    public function prepare(array $sections): array
    {
        $prepared = [];
        $blocks = $this->linkedBlocks($sections);

        foreach ($sections as $index => $section) {
            if (empty($section['visible'])) {
                continue;
            }

            $type = $section['type'] ?? null;
            $data = $section['data'] ?? [];

            if ($type === 'saved_block') {
                $block = $blocks->get((int) ($data['block_id'] ?? 0));

                // A linked section never nests another link.
                if (! $block || $block->is_archived || $block->type === 'saved_block') {
                    continue;
                }

                $type = $block->type;
                $data = array_replace(BlockRegistry::defaults($type), (array) $block->data);
            }

            if (! BlockRegistry::find($type)) {
                continue;
            }

            $extra = $this->extra($type, $data);

            if ($extra === null) {
                continue;
            }

            $prepared[] = [
                'id' => $section['id'] ?? 'section-'.$index,
                'type' => $type,
                'view' => 'pages.blocks.'.str_replace('_', '-', $type),
                'data' => $data,
                'extra' => $extra,
            ];
        }

        return $prepared;
    }

    /** Does the page open with its own banner (so the standard page title banner is not needed)? */
    public function opensWithBanner(array $prepared): bool
    {
        return in_array($prepared[0]['type'] ?? null, ['hero', 'hero_slider'], true);
    }

    private function linkedBlocks(array $sections): Collection
    {
        $ids = collect($sections)
            ->where('type', 'saved_block')
            ->pluck('data.block_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $ids->isEmpty() ? collect() : ContentBlock::whereIn('id', $ids)->get()->keyBy('id');
    }

    /** Live data for a section; null means "nothing to show, leave the section out". */
    private function extra(string $type, array $data): ?array
    {
        return match ($type) {
            'hero_slider' => $this->slider($data),
            'stats' => $this->stats($data),
            'packages_hajj', 'packages_umrah', 'packages_tourism' => $this->packages($type, $data),
            'testimonials' => $this->testimonials($data),
            'faqs' => $this->faqs($data),
            'awards' => $this->awards($data),
            'affiliations' => $this->affiliations(),
            'gallery' => $this->gallery($data),
            'news' => $this->news($data),
            'offices', 'contact_form' => ['offices' => Office::where('is_active', true)->orderBy('sort_order')->get()],
            'video' => ($embed = VideoEmbed::embedUrl($data['video_url'] ?? null)) ? ['embed' => $embed] : null,
            'company_story' => ['facts' => $this->companyFacts()],
            default => [],
        };
    }

    private function slider(array $data): ?array
    {
        if (($data['source'] ?? 'slides') === 'homepage') {
            $slides = Slider::where('page_context', 'home')->where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (Slider $s) => [
                    'image' => $s->image,
                    'heading' => $s->title,
                    'text' => $s->subtitle,
                    'button_text' => $s->cta_label,
                    'button_link' => $s->cta_url,
                ])->all();
        } else {
            $slides = collect($data['slides'] ?? [])
                ->filter(fn ($s) => is_array($s) && (filled($s['image']['path'] ?? null) || filled($s['heading'] ?? null)))
                ->map(fn ($s) => [
                    'image' => $s['image']['path'] ?? null,
                    'heading' => $s['heading'] ?? '',
                    'text' => $s['text'] ?? '',
                    'button_text' => $s['button_text'] ?? '',
                    'button_link' => $s['button_link'] ?? '',
                ])
                ->values()->all();
        }

        return $slides === [] ? null : ['slides' => $slides];
    }

    private function stats(array $data): ?array
    {
        if (($data['source'] ?? 'company') === 'company') {
            $items = [
                ['value' => SiteSetting::get('years_in_operation', '20+'), 'label' => 'Years of Experience'],
                ['value' => SiteSetting::get('pilgrims_served', '10,000+'), 'label' => 'Pilgrims Served'],
                ['value' => (string) SiteSetting::get('industry_awards_count', '20+'), 'label' => 'Awards & Recognitions'],
            ];
        } else {
            $items = array_values(array_filter($data['items'] ?? [], fn ($i) => filled($i['value'] ?? null)));
        }

        return $items === [] ? null : ['items' => $items];
    }

    private function packages(string $type, array $data): ?array
    {
        $slug = BlockRegistry::find($type)['category'];
        $category = PackageCategory::where('slug', $slug)->where('is_active', true)->first();

        if (! $category) {
            return null;
        }

        $query = $category->packages()->published()->with('series');
        match ($data['order'] ?? 'featured') {
            'price' => $query->orderByRaw('starting_price is null')->orderBy('starting_price'),
            'newest' => $query->latest('id'),
            default => $query->orderBy('is_featured', 'desc')->orderBy('sort_order'),
        };

        $packages = $query->limit((int) ($data['count'] ?? 3))->get();
        $packages->each(fn (Package $p) => $p->setRelation('category', $category));

        return $packages->isEmpty() ? null : ['packages' => $packages, 'category' => $category];
    }

    private function testimonials(array $data): ?array
    {
        $count = (int) ($data['count'] ?? 3);
        $videos = ! empty($data['include_video'])
            ? Testimonial::where('is_active', true)->whereNotNull('video_url')->orderBy('sort_order')->limit(min(3, $count))->get()
            : collect();
        $texts = Testimonial::where('is_active', true)->whereNull('video_url')->orderBy('sort_order')->limit($count - $videos->count())->get();

        return $videos->isEmpty() && $texts->isEmpty() ? null : ['videos' => $videos, 'texts' => $texts];
    }

    private function faqs(array $data): ?array
    {
        $query = Faq::where('is_active', true)->orderBy('sort_order');

        if (($data['category'] ?? 'all') !== 'all') {
            $query->where('category', $data['category']);
        }

        if (($data['count'] ?? '5') !== 'all') {
            $query->limit((int) $data['count']);
        }

        $faqs = $query->get();

        return $faqs->isEmpty() ? null : ['faqs' => $faqs];
    }

    private function awards(array $data): ?array
    {
        $query = Award::where('is_active', true)->orderBy('sort_order');

        if (($data['count'] ?? '6') !== 'all') {
            $query->limit((int) $data['count']);
        }

        $awards = $query->get();

        return $awards->isEmpty() ? null : ['awards' => $awards];
    }

    private function affiliations(): ?array
    {
        $affiliations = Affiliation::where('is_active', true)->orderBy('sort_order')->get();

        return $affiliations->isEmpty() ? null : ['affiliations' => $affiliations];
    }

    private function gallery(array $data): ?array
    {
        if (($data['source'] ?? 'chosen') === 'website') {
            $images = MediaItem::gallery()->images()->where('is_active', true)->orderBy('sort_order')
                ->limit((int) ($data['count'] ?? 9))->get()
                ->map(fn (MediaItem $m) => ['path' => $m->file_path, 'alt' => $m->alt_text ?: ($m->title ?: 'Universal Brothers gallery photo'), 'caption' => $m->caption ?: $m->title])
                ->all();
        } else {
            $images = collect($data['images'] ?? [])
                ->filter(fn ($i) => filled($i['image']['path'] ?? null))
                ->map(fn ($i) => ['path' => $i['image']['path'], 'alt' => $i['image']['alt'] ?? '', 'caption' => $i['caption'] ?? ''])
                ->values()->all();
        }

        return $images === [] ? null : ['images' => $images];
    }

    private function news(array $data): ?array
    {
        $news = NewsArticle::where('is_active', true)->latest('published_at')->limit((int) ($data['count'] ?? 3))->get();

        return $news->isEmpty() ? null : ['news' => $news];
    }

    private function companyFacts(): array
    {
        return array_filter([
            'Parent Group' => SiteSetting::get('parent_group', "Maxim's Group"),
            'Operating Brand' => SiteSetting::get('brand_name', 'Crown Packages'),
            'Hajj Licence No.' => SiteSetting::get('government_license_no', '2014'),
            'Hajj Registration No.' => SiteSetting::get('hajj_registration_no', '4143'),
            'Mina Camp' => SiteSetting::get('mina_camp_location'),
        ], fn ($value) => filled($value));
    }
}
