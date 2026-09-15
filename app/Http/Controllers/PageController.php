<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\Content\RichText;
use App\Support\PageBuilder\PageRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::live()->where('slug', $slug)->firstOrFail();

        return $this->render($page);
    }

    /**
     * The public template for a page. Also used by the admin's signed preview,
     * which passes a page filled from its unpublished draft.
     */
    public function render(Page $page, array $extra = []): View
    {
        $seo = [
            'title' => $page->meta_title ?: $page->title.' | Universal Brothers',
            'description' => $page->meta_description ?: Str::limit(RichText::toPlainText($page->body), 160),
            'og_title' => $page->og_title ?: ($page->meta_title ?: $page->title),
            'og_description' => $page->og_description ?: ($page->meta_description ?: Str::limit(RichText::toPlainText($page->body), 200)),
            'og_image' => $page->og_image ?: $page->featured_image,
            'canonical' => $page->canonical_url,
            'noindex' => $page->noindex || ($extra['preview'] ?? false),
        ];

        if ($page->usesSections()) {
            $renderer = app(PageRenderer::class);
            $sections = $renderer->prepare($page->sections);

            return view('page-sections', array_merge(compact('page', 'seo', 'sections'), [
                'opensWithBanner' => $renderer->opensWithBanner($sections),
            ], $extra));
        }

        if ($page->template === 'about') {
            $awards = Award::where('is_active', true)->orderBy('sort_order')->get();
            $affiliations = Affiliation::where('is_active', true)->orderBy('sort_order')->get();
            $stats = [
                'years' => SiteSetting::get('years_in_operation', '20+'),
                'pilgrims' => SiteSetting::get('pilgrims_served', '10,000+'),
                'awards_count' => SiteSetting::get('industry_awards_count', '20+'),
            ];

            return view('page', array_merge(compact('page', 'seo', 'awards', 'affiliations', 'stats'), $extra));
        }

        return view('page', array_merge(compact('page', 'seo'), $extra));
    }
}
