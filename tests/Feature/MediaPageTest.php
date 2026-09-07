<?php

namespace Tests\Feature;

use App\Models\MediaItem;
use App\Models\NewsArticle;
use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage gap closed 2026-09-05: `MediaPageController` had no PHPUnit
 * coverage — only Playwright checks that the page renders its honest empty
 * state. Nothing proved the three tabs actually separate news/images/videos
 * correctly, or that inactive rows stay hidden, because no environment this
 * project has ever run in had a single real MediaItem or NewsArticle row.
 */
class MediaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_media_page_separates_images_and_videos_and_hides_inactive_items(): void
    {
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'title' => 'Active Gallery Photo', 'file_path' => 'media/photo.jpg', 'sort_order' => 1, 'is_active' => true]);
        MediaItem::create(['media_type' => 'video', 'gallery_type' => 'gallery', 'title' => 'Active Video Clip', 'video_url' => 'https://example.com/embed/abc', 'sort_order' => 1, 'is_active' => true]);
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'title' => 'Hidden Photo', 'file_path' => 'media/hidden.jpg', 'sort_order' => 2, 'is_active' => false]);
        MediaItem::create(['media_type' => 'video', 'gallery_type' => 'gallery', 'title' => 'Hidden Video', 'video_url' => 'https://example.com/embed/hidden', 'sort_order' => 2, 'is_active' => false]);

        $response = $this->get('/media');

        $response->assertOk();

        $gallery = $response->viewData('gallery');
        $videos = $response->viewData('videos');

        $this->assertCount(1, $gallery);
        $this->assertCount(1, $videos);
        $this->assertSame('Active Gallery Photo', $gallery->first()->title);
        $this->assertSame('Active Video Clip', $videos->first()->title);
    }

    public function test_media_page_shows_published_news_newest_first_and_hides_drafts(): void
    {
        NewsArticle::create(['title' => 'Older Published Article', 'slug' => 'older-published', 'body' => 'Body.', 'is_active' => true, 'published_at' => now()->subDays(5)]);
        NewsArticle::create(['title' => 'Newest Published Article', 'slug' => 'newest-published', 'body' => 'Body.', 'is_active' => true, 'published_at' => now()]);
        NewsArticle::create(['title' => 'Unpublished Draft Article', 'slug' => 'draft-article', 'body' => 'Body.', 'is_active' => false, 'published_at' => now()]);

        $response = $this->get('/media');

        $response->assertOk();

        $news = $response->viewData('news');
        $this->assertCount(2, $news);
        $this->assertSame('Newest Published Article', $news->first()->title);
        $response->assertDontSee('Unpublished Draft Article');
    }

    public function test_media_page_caps_news_at_six(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            NewsArticle::create(['title' => "Article $i", 'slug' => "article-$i", 'body' => 'Body.', 'is_active' => true, 'published_at' => now()->subDays($i)]);
        }

        $response = $this->get('/media');

        $response->assertOk();
        $this->assertCount(6, $response->viewData('news'));
    }

    public function test_media_page_renders_honest_empty_states_when_nothing_is_published(): void
    {
        $response = $this->get('/media');

        $response->assertOk();
        $response->assertSee('No news articles have been published yet.');
        $this->assertCount(0, $response->viewData('gallery'));
        $this->assertCount(0, $response->viewData('videos'));
    }
}
