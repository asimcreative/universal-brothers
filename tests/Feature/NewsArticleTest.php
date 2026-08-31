<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for FINAL_CODE_REVIEW.md H-2: news_articles.body was writable
 * from the admin but had no public route/view at all — the homepage card
 * linked nowhere, so a visitor could never read a full article.
 */
class NewsArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_published_news_article_is_publicly_readable(): void
    {
        NewsArticle::create([
            'title' => 'Hajj 2027 Registration Opens',
            'slug' => 'hajj-2027-registration-opens-abc123',
            'excerpt' => 'Registration is now open.',
            'body' => '<p>Full article body content about registration.</p>',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get('/news/hajj-2027-registration-opens-abc123');

        $response->assertOk();
        $response->assertSee('Hajj 2027 Registration Opens');
        $response->assertSee('Full article body content about registration', false);
        // Regression: this used to link to `route('home').'#news'`, a dead
        // anchor since the homepage's old "Travel News" section (with
        // id="news") was replaced by the News Ticker during the frontend
        // redesign — found during release-gate responsive QA. The article
        // page's own "Back to Travel News" link must resolve to a real
        // destination that actually lists news (now /media's News tab).
        $response->assertSee(route('media'), false);
    }

    public function test_inactive_news_article_returns_404(): void
    {
        NewsArticle::create([
            'title' => 'Draft Article', 'slug' => 'draft-article-xyz', 'body' => 'Body.', 'is_active' => false,
        ]);

        $response = $this->get('/news/draft-article-xyz');

        $response->assertNotFound();
    }

    public function test_homepage_news_card_links_to_the_full_article(): void
    {
        NewsArticle::create([
            'title' => 'Linked Article', 'slug' => 'linked-article-abc', 'excerpt' => 'Excerpt.',
            'body' => 'Body.', 'is_active' => true, 'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('news.show', 'linked-article-abc'), false);
    }
}
