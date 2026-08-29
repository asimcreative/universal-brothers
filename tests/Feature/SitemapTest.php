<?php

namespace Tests\Feature;

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
}
