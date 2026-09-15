<?php

namespace App\Support\PageBuilder;

use App\Models\Page;
use App\Support\Content\RichText;

/**
 * Everything an admin edits about a page, as one value.
 *
 * The page's own columns always hold what visitors see (the published version).
 * While an admin works, their changes live in `pages.draft` as a document of
 * this shape; publishing writes the document into the columns. A preview
 * renders the draft document without touching the columns at all.
 *
 *   title, slug, featured_image
 *   sections          list of sections (see SectionValidator for the shape)
 *   meta_title, meta_description, focus_keyword, canonical_url
 *   og_title, og_description, og_image, noindex
 */
final class PageDocument
{
    public const KEYS = [
        'title', 'slug', 'featured_image', 'sections',
        'meta_title', 'meta_description', 'focus_keyword', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'noindex',
    ];

    private function __construct(private array $data) {}

    public static function make(array $data): self
    {
        $document = [];

        foreach (self::KEYS as $key) {
            $document[$key] = $data[$key] ?? match ($key) {
                'sections' => [],
                'noindex' => false,
                default => null,
            };
        }

        $document['noindex'] = (bool) $document['noindex'];
        $document['sections'] = array_values((array) $document['sections']);

        return new self($document);
    }

    /** What the admin should be editing: their saved draft, or else the published page. */
    public static function forEditing(Page $page): self
    {
        if (is_array($page->draft)) {
            return self::make($page->draft);
        }

        return self::fromPublished($page, seedLegacy: true);
    }

    /**
     * The published page as a document.
     *
     * A page that has never been published from the builder has no sections
     * yet. With $seedLegacy its current content is turned into equivalent
     * sections, so the admin starts from what is live instead of a blank page;
     * the live page itself is untouched until they publish.
     */
    public static function fromPublished(Page $page, bool $seedLegacy = false): self
    {
        $sections = $page->sections;

        if (! is_array($sections)) {
            $sections = $seedLegacy ? self::sectionsFromLegacy($page) : [];
        }

        return self::make([
            'title' => $page->title,
            'slug' => $page->slug,
            'featured_image' => $page->featured_image,
            'sections' => $sections,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'focus_keyword' => $page->focus_keyword,
            'canonical_url' => $page->canonical_url,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image' => $page->og_image,
            'noindex' => $page->noindex,
        ]);
    }

    /** Sections that reproduce a page built before the section builder existed. */
    public static function sectionsFromLegacy(Page $page): array
    {
        $section = fn (string $type, array $data) => [
            'id' => SectionValidator::newId(),
            'type' => $type,
            'visible' => true,
            'data' => array_replace(BlockRegistry::defaults($type), $data),
        ];

        $body = RichText::forEditor($page->body, 'full');

        if ($page->template === 'about') {
            return [
                $section('company_story', ['eyebrow' => 'The Beginning', 'heading' => '', 'content' => $body, 'image' => ['path' => $page->featured_image, 'alt' => $page->featured_image ? $page->title : '']]),
                $section('stats', ['eyebrow' => 'By the Numbers', 'heading' => 'Our Experience', 'source' => 'company', 'background' => 'navy']),
                $section('awards', ['count' => 'all']),
                $section('affiliations', []),
            ];
        }

        return [$section('text', ['content' => $body, 'heading' => '', 'eyebrow' => ''])];
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function sections(): array
    {
        return $this->data['sections'];
    }

    public function visibleSections(): array
    {
        return array_values(array_filter($this->data['sections'], fn (array $s) => ! empty($s['visible'])));
    }

    public function with(array $changes): self
    {
        return self::make(array_replace($this->data, $changes));
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /** Write the document into the page's columns — this is what publishing does. */
    public function applyTo(Page $page): void
    {
        $page->fill([
            'title' => $this->data['title'],
            'slug' => $this->data['slug'],
            'featured_image' => $this->data['featured_image'],
            'sections' => $this->data['sections'],
            'body' => $this->bodyHtml(),
            'meta_title' => $this->data['meta_title'],
            'meta_description' => $this->data['meta_description'],
            'focus_keyword' => $this->data['focus_keyword'],
            'canonical_url' => $this->data['canonical_url'],
            'og_title' => $this->data['og_title'],
            'og_description' => $this->data['og_description'],
            'og_image' => $this->data['og_image'],
            'noindex' => $this->data['noindex'],
        ]);
    }

    /**
     * The written content of the visible sections as one piece of HTML, kept in
     * `pages.body` so everything that already reads it — the AI assistant's
     * knowledge, the search description fallback — sees the published words.
     */
    public function bodyHtml(): ?string
    {
        $parts = [];

        foreach ($this->visibleSections() as $section) {
            $data = $section['data'] ?? [];

            foreach (['heading', 'left_heading'] as $key) {
                if (filled($data[$key] ?? null) && is_string($data[$key])) {
                    $parts[] = '<h2>'.e($data[$key]).'</h2>';
                }
            }

            foreach (['content', 'left_content', 'right_content'] as $key) {
                if (filled($data[$key] ?? null) && is_string($data[$key])) {
                    $parts[] = $data[$key];
                }
            }

            foreach (['subheading', 'intro', 'text'] as $key) {
                if (filled($data[$key] ?? null) && is_string($data[$key])) {
                    $parts[] = '<p>'.e($data[$key]).'</p>';
                }
            }

            foreach ($data['cards'] ?? $data['items'] ?? [] as $item) {
                if (is_array($item) && filled($item['title'] ?? null)) {
                    $parts[] = '<p>'.e($item['title']).(filled($item['text'] ?? null) ? ': '.e($item['text']) : '').'</p>';
                }
            }
        }

        return $parts === [] ? null : RichText::clean(implode("\n", $parts), 'full');
    }
}
