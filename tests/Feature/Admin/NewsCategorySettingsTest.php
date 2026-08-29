<?php

namespace Tests\Feature\Admin;

use App\Models\NewsArticle;
use App\Models\PackageCategory;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the News, Categories/Series, and Site Settings admin controllers —
 * identified as having zero automated test coverage during the
 * REQUIREMENTS_TRACEABILITY.md pass, despite being real, working features.
 */
class NewsCategorySettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_admin_can_create_update_and_delete_a_news_article(): void
    {
        $create = $this->actingAs($this->admin)->post('/admin/news', [
            'title' => 'Hajj 2027 Registration Opens',
            'excerpt' => 'Registration for Hajj 2027 packages is now open.',
            'body' => 'Full article body.',
            'is_active' => '1',
        ]);
        $create->assertRedirect(route('admin.news.index'));
        $article = NewsArticle::where('title', 'Hajj 2027 Registration Opens')->firstOrFail();
        $this->assertNotNull($article->slug);

        $update = $this->actingAs($this->admin)->put("/admin/news/{$article->id}", [
            'title' => 'Hajj 2027 Registration Opens — Updated',
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.news.index'));
        $this->assertDatabaseHas('news_articles', ['id' => $article->id, 'title' => 'Hajj 2027 Registration Opens — Updated']);

        $delete = $this->actingAs($this->admin)->delete("/admin/news/{$article->id}");
        $delete->assertRedirect(route('admin.news.index'));
        $this->assertDatabaseMissing('news_articles', ['id' => $article->id]);
    }

    public function test_guest_cannot_manage_news(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
    }

    public function test_admin_can_update_category_details_and_add_remove_series(): void
    {
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);

        $update = $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'icon' => 'bi-moon-stars',
            'description' => 'Real Hajj 2027 packages.',
            'is_active' => '1',
        ]);
        $update->assertRedirect();
        $this->assertDatabaseHas('package_categories', ['id' => $category->id, 'icon' => 'bi-moon-stars']);

        $addSeries = $this->actingAs($this->admin)->post("/admin/categories/{$category->id}/series", [
            'name' => 'Platinum Non-Aziziya',
        ]);
        $addSeries->assertRedirect();
        $series = $category->series()->where('name', 'Platinum Non-Aziziya')->firstOrFail();

        $removeSeries = $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}/series/{$series->id}");
        $removeSeries->assertRedirect();
        $this->assertDatabaseMissing('package_series', ['id' => $series->id]);
    }

    public function test_guest_cannot_manage_categories(): void
    {
        $this->get('/admin/categories')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_and_update_site_settings(): void
    {
        SiteSetting::create(['key' => 'years_in_operation', 'value' => '20+', 'group' => 'general']);

        $index = $this->actingAs($this->admin)->get('/admin/settings');
        $index->assertOk();
        $index->assertSee('years_in_operation');

        $update = $this->actingAs($this->admin)->put('/admin/settings', [
            'settings' => ['years_in_operation' => '25+'],
        ]);
        $update->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'years_in_operation', 'value' => '25+']);
    }

    public function test_guest_cannot_manage_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }
}
