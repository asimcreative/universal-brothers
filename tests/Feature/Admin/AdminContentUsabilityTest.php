<?php

namespace Tests\Feature\Admin;

use App\Models\AiSetting;
use App\Models\Office;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Ai\AiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Screens rebuilt for non-technical administrators: the AI pages on the shared
 * admin design and permissions, the API key check, office maps without pasted
 * code, and Site Settings without stored key names.
 */
class AdminContentUsabilityTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'sk-usability-test-key-1234567890abcd';

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_ai_pages_use_the_shared_admin_layout_and_components(): void
    {
        $admin = $this->superAdmin();

        foreach ([route('admin.ai.index'), route('admin.ai.test'), route('admin.ai.conversations')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'class="admin-page-title'), "{$url} shows one page title");
            $this->assertStringContainsString('admin-stat-card', $html, "{$url} uses the shared stat cards");
            $this->assertStringNotContainsString('admin-card p-', $html, "{$url} no longer uses the unstyled card class");
            $this->assertStringNotContainsString('class="badge ', $html, "{$url} uses status pills, not raw badges");
        }

        $this->actingAs($admin)->get(route('admin.ai.index'))->assertSee('admin-panel', false)->assertSee('Replace the API key');
    }

    public function test_only_super_admins_can_open_or_change_the_ai_settings(): void
    {
        $editor = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($editor)->get(route('admin.ai.index'))->assertForbidden();
        $this->actingAs($editor)->put(route('admin.ai.update'), ['assistant_name' => 'Hacked'])->assertForbidden();
        $this->actingAs($editor)->post(route('admin.ai.key.check'))->assertForbidden();
        $this->actingAs($editor)->delete(route('admin.ai.key.clear'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.ai.test'))->assertOk();
        $this->actingAs($editor)->get(route('admin.dashboard'))->assertDontSee(route('admin.ai.index').'"', false);
    }

    public function test_checking_the_key_reports_the_result_without_ever_showing_the_key(): void
    {
        $admin = $this->superAdmin();
        config()->set('ai.api_key', null);
        $settings = AiSetting::current();
        $settings->api_key = self::KEY;
        $settings->save();
        AiConfig::flush();

        Http::fake(['*' => Http::sequence()
            ->push(['model' => 'gpt-4o-mini', 'choices' => [['message' => ['content' => 'OK']]]])
            ->push(['error' => ['message' => 'Incorrect API key provided: '.self::KEY]], 401)]);
        $this->actingAs($admin)->from(route('admin.ai.index'))->post(route('admin.ai.key.check'))
            ->assertRedirect(route('admin.ai.index'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'The API key works'));

        $response = $this->actingAs($admin)->from(route('admin.ai.index'))->post(route('admin.ai.key.check'));
        $response->assertSessionHasErrors('api_key');
        $this->assertStringNotContainsString(self::KEY, session('errors')->first('api_key'));

        $page = $this->actingAs($admin)->get(route('admin.ai.index'))->getContent();
        $this->assertStringNotContainsString(self::KEY, $page);
        $this->assertStringContainsString('sk-', $page);
    }

    public function test_the_assistant_icon_must_be_an_icon_name(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('admin.ai.update'), [
            'assistant_name' => 'Assistant', 'assistant_icon' => 'bi-stars" onmouseover="alert(1)', 'provider' => 'openai', 'model' => 'gpt-4o-mini',
            'temperature' => 0.3, 'max_tokens' => 700, 'timeout_seconds' => 30, 'rate_limit_per_minute' => 12,
            'max_message_length' => 1000, 'max_conversation_messages' => 60, 'daily_message_limit' => 50, 'log_level' => 'errors',
        ])->assertSessionHasErrors('assistant_icon');
    }

    public function test_an_office_map_accepts_the_google_maps_embed_code_and_keeps_only_its_address(): void
    {
        $admin = $this->superAdmin();
        $embed = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1abc" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" onload="alert(1)"></iframe>';

        $this->actingAs($admin)->post(route('admin.offices.store'), [
            'label' => 'Head Office', 'address' => 'Karachi', 'google_maps_embed' => $embed, 'is_active' => '1',
        ])->assertRedirect();

        $office = Office::where('label', 'Head Office')->firstOrFail();
        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18!1abc', $office->google_maps_embed);

        $html = $this->get(route('contact'))->assertOk()->getContent();
        $this->assertStringContainsString('src="https://www.google.com/maps/embed?pb=!1m18!1abc"', $html);
        $this->assertStringNotContainsString('onload', $html);
    }

    public function test_an_office_map_that_is_not_google_maps_is_refused_with_instructions(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.offices.store'), [
            'label' => 'Branch', 'address' => 'Lahore', 'google_maps_embed' => '<iframe src="https://evil.example/map"></iframe>',
        ])->assertSessionHasErrors('google_maps_embed');

        $this->assertStringContainsString('Embed a map', session('errors')->first('google_maps_embed'));
        $this->assertNull(Office::normalizeMapEmbed('<script>alert(1)</script>'));
        $this->assertNotNull(Office::normalizeMapEmbed('<iframe src="https://www.google.com/maps?q=Karachi&amp;output=embed"></iframe>'));
    }

    public function test_site_settings_show_plain_names_instead_of_stored_keys(): void
    {
        SiteSetting::create(['key' => 'government_license_no', 'value' => '2014', 'group' => 'legal']);

        $this->actingAs($this->superAdmin())->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Hajj licence number')
            ->assertDontSee('government license no');
    }
}
