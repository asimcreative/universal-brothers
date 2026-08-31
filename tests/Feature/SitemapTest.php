<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_categories_packages_and_static_pages(): void
    {
        $category = PackageCategory::factory()->create(['slug' => 'hajj']);
        $package = Package::factory()->create(['package_category_id' => $category->id, 'slug' => 'test-package']);
        Page::create(['title' => 'About Us', 'slug' => 'about-us', 'template' => 'about', 'is_active' => true]);
        Page::create(['title' => 'Draft Page', 'slug' => 'draft-page', 'template' => 'default', 'is_active' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('packages.category', 'hajj'), false);
        $response->assertSee(route('packages.show', ['hajj', 'test-package']), false);
        $response->assertSee(url('about-us'), false);
        $response->assertDontSee(url('draft-page'), false);
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md H-2's follow-on gap: once news
     * articles got a real public page, the sitemap needed to list them too —
     * same class of omission the Pages module had before an earlier pass.
     */
    public function test_sitemap_includes_published_news_articles_and_excludes_drafts(): void
    {
        NewsArticle::create(['title' => 'Published Article', 'slug' => 'published-article', 'body' => 'Body.', 'is_active' => true]);
        NewsArticle::create(['title' => 'Draft Article', 'slug' => 'draft-article', 'body' => 'Body.', 'is_active' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('news.show', 'published-article'), false);
        $response->assertDontSee(route('news.show', 'draft-article'), false);
    }
}
