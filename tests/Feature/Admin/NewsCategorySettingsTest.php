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

    /**
     * Regression for FINAL_CODE_REVIEW.md H-1: the controller previously did a
     * raw `SiteSetting::where(...)->update(...)` query-builder mass update,
     * which bypasses Eloquent model events, so the `saved` hook that forgets
     * the `Cache::rememberForever` entry never fired — an admin's edit hit the
     * DB but `SiteSetting::get()` kept serving the stale cached value forever.
     * This test reads through the cache (as HomeController does), not just
     * the DB row, so it would have caught the original bug.
     */
    public function test_updating_a_site_setting_actually_invalidates_its_cached_value(): void
    {
        SiteSetting::set('pilgrims_served', '50,000+');
        $this->assertSame('50,000+', SiteSetting::get('pilgrims_served'));

        $this->actingAs($this->admin)->put('/admin/settings', [
            'settings' => ['pilgrims_served' => '60,000+'],
        ])->assertRedirect();

        $this->assertSame('60,000+', SiteSetting::get('pilgrims_served'));
    }

    /**
     * Regression for FINAL_CODE_REVIEW.md M-5: secret-type settings must not
     * be overwritten with a blank value just because the form intentionally
     * never echoes the live secret back into the page source.
     */
    public function test_submitting_a_blank_secret_setting_keeps_the_existing_value(): void
    {
        SiteSetting::set('recaptcha_secret_key', 'existing-secret-value');

        $this->actingAs($this->admin)->put('/admin/settings', [
            'settings' => ['recaptcha_secret_key' => ''],
        ])->assertRedirect();

        $this->assertSame('existing-secret-value', SiteSetting::get('recaptcha_secret_key'));
    }

    public function test_guest_cannot_manage_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }

    /**
     * Regression for a MEDIUM finding from the release-gate security audit:
     * `SiteSettingController::update()` looped over `$request->input('settings', [])`
     * with no validation at all, and its fallback branch would silently
     * `SiteSetting::create()` a brand-new row for any key a tampered request
     * submitted — an unvalidated write path with no key allow-list. Only
     * keys that already exist as real SiteSetting rows may now be touched.
     */
    public function test_submitting_an_unknown_setting_key_does_not_create_a_new_row(): void
    {
        $this->actingAs($this->admin)->put('/admin/settings', [
            'settings' => ['a_key_that_does_not_exist' => 'injected value'],
        ])->assertRedirect();

        $this->assertDatabaseMissing('site_settings', ['key' => 'a_key_that_does_not_exist']);
    }
}
