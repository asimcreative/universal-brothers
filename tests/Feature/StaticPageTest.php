<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Office::factory()->create();
    }

    public function test_published_page_is_publicly_visible(): void
    {
        Page::create([
            'title' => 'About Us', 'slug' => 'about-us', 'body' => '<p>20+ years of trusted service.</p>',
            'template' => 'about', 'is_active' => true, 'published_at' => now(),
        ]);

        $response = $this->get('/about-us');

        $response->assertOk();
        $response->assertSee('About Us');
        $response->assertSee('20+ years of trusted service', false);
    }

    public function test_unpublished_page_returns_404(): void
    {
        Page::create(['title' => 'Draft Page', 'slug' => 'draft-page', 'template' => 'default', 'is_active' => false]);

        $response = $this->get('/draft-page');

        $response->assertNotFound();
    }

    public function test_unknown_slug_returns_404_not_a_server_error(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
    }
}
