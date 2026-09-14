<?php

namespace Tests\Feature\Admin;

use App\Models\AiConversation;
use App\Models\AiKnowledgeEntry;
use App\Models\AiMessage;
use App\Models\AiSetting;
use App\Models\User;
use App\Support\Ai\AiClient;
use App\Support\Ai\AiConfig;
use App\Support\Ai\KnowledgeRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin side of the AI assistant.
 *
 * The load-bearing assertions are the ones about the API key: that it is
 * ciphertext in the database, that the screen shows only a mask, that saving
 * the form with the field blank does not wipe it, and that an unauthenticated
 * visitor cannot reach any of it.
 */
class AiAssistantAdminTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'sk-admin-test-key-1234567890abcdef';

    private function admin(): User
    {
        Artisan::call('db:seed', ['--force' => true]);

        return User::firstOrFail();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'assistant_name' => 'Universal Brothers Assistant',
            'assistant_icon' => 'bi-stars',
            'provider' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
            'max_tokens' => 700,
            'timeout_seconds' => 30,
            'rate_limit_per_minute' => 12,
            'max_message_length' => 1000,
            'max_conversation_messages' => 60,
            'daily_message_limit' => 0,
            'log_level' => 'errors',
        ], $overrides);
    }

    // -------------------------------------------------------- authorization

    public function test_a_guest_cannot_reach_any_ai_admin_screen(): void
    {
        foreach ([
            route('admin.ai.index'),
            route('admin.ai.test'),
            route('admin.ai.conversations'),
        ] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_a_guest_cannot_change_settings_or_rebuild_the_index(): void
    {
        $this->put(route('admin.ai.update'), $this->validPayload())->assertRedirect('/admin/login');
        $this->post(route('admin.ai.reindex'))->assertRedirect('/admin/login');
        $this->delete(route('admin.ai.key.clear'))->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_open_the_settings_screen(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('AI Assistant');
    }

    // ------------------------------------------------------------ the key

    public function test_a_saved_key_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ai.update'), $this->validPayload(['api_key' => self::KEY]))
            ->assertRedirect();

        $raw = DB::table('ai_settings')->value('api_key');

        $this->assertNotSame(self::KEY, $raw, 'The API key was stored in plaintext.');
        $this->assertStringNotContainsString('sk-admin-test', (string) $raw);
        // And it still decrypts back to the original through the model cast.
        $this->assertSame(self::KEY, AiSetting::current()->api_key);
    }

    public function test_the_settings_screen_shows_only_a_masked_key(): void
    {
        AiSetting::current()->forceFill(['api_key' => self::KEY])->save();
        config()->set('ai.api_key', null);
        AiConfig::flush();

        $response = $this->actingAs($this->admin())->get(route('admin.ai.index'))->assertOk();

        $this->assertStringNotContainsString(self::KEY, $response->getContent());
        $response->assertSee('sk-', false);
        $response->assertSee('••••', false);
    }

    public function test_saving_with_a_blank_key_field_keeps_the_existing_key(): void
    {
        $admin = $this->admin();

        AiSetting::current()->forceFill(['api_key' => self::KEY])->save();

        // The form cannot render the real value, so an empty field must mean
        // "unchanged" — otherwise every unrelated settings save silently
        // destroys the credential.
        $this->actingAs($admin)
            ->put(route('admin.ai.update'), $this->validPayload(['api_key' => '']))
            ->assertRedirect();

        $this->assertSame(self::KEY, AiSetting::current()->api_key);
    }

    public function test_the_key_can_be_cleared_explicitly(): void
    {
        AiSetting::current()->forceFill(['api_key' => self::KEY])->save();

        $this->actingAs($this->admin())->delete(route('admin.ai.key.clear'))->assertRedirect();

        $this->assertNull(AiSetting::current()->api_key);
    }

    public function test_the_environment_key_takes_priority_over_the_stored_one(): void
    {
        AiSetting::current()->forceFill(['api_key' => 'sk-database-key-value'])->save();
        config()->set('ai.api_key', 'sk-environment-key-value');
        AiConfig::flush();

        $this->assertSame('sk-environment-key-value', AiConfig::apiKey());
        $this->assertSame('environment', AiConfig::apiKeySource());
    }

    public function test_the_key_is_hidden_from_model_serialisation(): void
    {
        AiSetting::current()->forceFill(['api_key' => self::KEY])->save();

        // Guards against a stray toJson()/log/queue payload carrying it.
        $this->assertStringNotContainsString(self::KEY, AiSetting::current()->toJson());
        $this->assertArrayNotHasKey('api_key', AiSetting::current()->toArray());
    }

    // -------------------------------------------------------- configuration

    public function test_settings_are_validated(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ai.update'), $this->validPayload([
                'temperature' => 9,
                'max_tokens' => 1,
                'model' => '',
            ]))
            ->assertSessionHasErrors(['temperature', 'max_tokens', 'model']);
    }

    public function test_a_plaintext_base_url_is_rejected(): void
    {
        // The API key travels on this request, so an http:// endpoint would
        // put the credential on the wire in clear.
        $this->actingAs($this->admin())
            ->put(route('admin.ai.update'), $this->validPayload(['base_url' => 'http://evil.example.com/v1']))
            ->assertSessionHasErrors('base_url');
    }

    public function test_a_localhost_base_url_is_allowed_for_self_hosted_models(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ai.update'), $this->validPayload(['base_url' => 'http://localhost:11434/v1']))
            ->assertSessionHasNoErrors();
    }

    public function test_an_admin_can_toggle_the_assistant(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.ai.update'), $this->validPayload(['is_enabled' => 1, 'public_enabled' => 1]))
            ->assertRedirect();

        $this->assertTrue(AiSetting::current()->is_enabled);
        $this->assertTrue(AiSetting::current()->public_enabled);

        // Unchecked switches are absent from a real form post, so the
        // controller must read them as false rather than leaving them on.
        $this->actingAs($admin)->put(route('admin.ai.update'), $this->validPayload())->assertRedirect();

        $this->assertFalse(AiSetting::current()->is_enabled);
        $this->assertFalse(AiSetting::current()->public_enabled);
    }

    public function test_an_admin_can_raise_or_lower_the_daily_limit_per_visitor(): void
    {
        $admin = $this->admin();

        foreach ([120, 10, 0] as $limit) {
            $this->actingAs($admin)
                ->put(route('admin.ai.update'), $this->validPayload(['daily_message_limit' => $limit]))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            AiConfig::flush();

            $this->assertSame($limit, AiConfig::dailyMessageLimit(), "The portal could not set the daily limit to {$limit}.");
        }
    }

    public function test_the_settings_screen_explains_the_daily_limit_in_plain_terms(): void
    {
        AiSetting::current()->forceFill(['daily_message_limit' => 50])->save();
        AiConfig::flush();

        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('Daily limit per visitor')
            ->assertSee('0 = no limit.', false)
            ->assertSee('Each visitor gets', false)
            ->assertSee('500', false)
            ->assertDontSee('Visitor IP addresses are not reaching the site');
    }

    public function test_the_settings_screen_warns_when_visitor_addresses_do_not_reach_the_site(): void
    {
        AiSetting::current()->forceFill(['daily_message_limit' => 50])->save();
        AiConfig::flush();

        $this->actingAs($this->admin())
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('Visitor IP addresses are not reaching the site');
    }

    public function test_an_admin_can_change_the_model(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ai.update'), $this->validPayload(['model' => 'gpt-4o']))
            ->assertRedirect();

        AiConfig::flush();

        $this->assertSame('gpt-4o', AiConfig::model());
    }

    // ------------------------------------------------------------ the index

    public function test_an_admin_can_rebuild_the_knowledge_index(): void
    {
        $admin = $this->admin();

        $this->assertSame(0, AiKnowledgeEntry::count());

        $this->actingAs($admin)->post(route('admin.ai.reindex'))->assertRedirect();

        $this->assertGreaterThan(0, AiKnowledgeEntry::count());
        $this->assertNotNull(AiSetting::current()->indexed_at);
        $this->assertSame(AiKnowledgeEntry::count(), AiSetting::current()->indexed_records);
    }

    public function test_a_package_index_entry_is_used_for_matching_but_never_for_answering(): void
    {
        $this->actingAs($this->admin())->post(route('admin.ai.reindex'));

        // A package entry's body can legitimately contain a figure — the
        // brochure's own airfare estimate sits in the exclusions text — so the
        // guarantee that matters is not "no digits in the index" but "the
        // index body never becomes answer context". Every package fact the
        // model sees is rendered live by PackageContext instead.
        $indexBody = AiKnowledgeEntry::where('reference', 'UB010')->value('body');

        $this->assertStringContainsString('Package code: UB010', $indexBody, 'Index entry should exist for matching.');

        $context = app(KnowledgeRetriever::class)->retrieve('What is UB010?')['context'];

        // "ROOM PRICES (per person)" is only ever emitted by PackageContext;
        // "Package code:" is only ever in the index body. Their presence and
        // absence prove which one produced the context.
        $this->assertStringContainsString('ROOM PRICES (per person)', $context);
        $this->assertStringNotContainsString('Package code: UB010', $context);
    }

    // -------------------------------------------------------- the test panel

    public function test_the_test_panel_reports_a_working_connection(): void
    {
        config()->set('ai.api_key', self::KEY);
        AiConfig::flush();

        Http::fake(['*' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'UB010 costs PKR 3,485,000 for a quad room.']]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20],
        ])]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.ai.test'), ['question' => 'What is UB010 in PKR?'])
            ->assertOk();

        $response->assertSee('Connection OK')
            ->assertSee('UB010 costs PKR 3,485,000', false);

        $this->assertStringNotContainsString(self::KEY, $response->getContent());
    }

    public function test_the_test_panel_reports_a_failure_safely(): void
    {
        config()->set('ai.api_key', self::KEY);
        AiConfig::flush();

        // A provider error body that echoes the key straight back, which is
        // exactly what OpenAI does on a bad credential.
        Http::fake(['*' => Http::response([
            'error' => ['message' => 'Incorrect API key provided: '.self::KEY],
        ], 401)]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.ai.test'), ['question' => 'Hello'])
            ->assertOk();

        $response->assertSee('invalid_api_key');

        // The key must not appear, and neither must the provider's raw body:
        // AiClient maps the status to its own message and never passes the
        // response text outwards, so there is nothing for the view to scrub.
        // (AiClient::scrub still redacts it on the way to the log, which is
        // the other place it could have settled — covered below.)
        $this->assertStringNotContainsString(self::KEY, $response->getContent());
        $this->assertStringNotContainsString('Incorrect API key provided', $response->getContent());
        $response->assertSee('The AI provider rejected the API key.');
    }

    public function test_a_key_echoed_in_a_provider_error_is_scrubbed_before_logging(): void
    {
        $this->assertStringNotContainsString(
            self::KEY,
            AiClient::scrub('Incorrect API key provided: '.self::KEY)
        );

        $this->assertStringContainsString(
            '[redacted]',
            AiClient::scrub('Incorrect API key provided: '.self::KEY)
        );

        $this->assertStringContainsString(
            'Bearer [redacted]',
            AiClient::scrub('Authorization: Bearer abcdef1234567890')
        );
    }

    public function test_the_test_panel_shows_the_retrieved_context(): void
    {
        config()->set('ai.api_key', self::KEY);
        AiConfig::flush();

        $this->actingAs($this->admin())->post(route('admin.ai.reindex'));

        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => 'Answer.']]],
        ])]);

        $this->actingAs($this->admin())
            ->post(route('admin.ai.test'), ['question' => 'What is UB010 in PKR?'])
            ->assertOk()
            ->assertSee('Knowledge sent to the model')
            ->assertSee('UB010');
    }

    // ------------------------------------------------------- conversations

    public function test_an_admin_can_review_conversations(): void
    {
        $conversation = AiConversation::create([
            'uuid' => (string) Str::uuid(),
            'message_count' => 2,
            'last_activity_at' => now(),
        ]);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'What is the price of UB010?',
        ]);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'UB010 quad sharing is PKR 3,485,000.',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.ai.conversations'))->assertOk()->assertSee('Conversations');

        $this->actingAs($admin)
            ->get(route('admin.ai.conversation', $conversation))
            ->assertOk()
            ->assertSee('What is the price of UB010?')
            ->assertSee('PKR 3,485,000');
    }

    public function test_the_settings_screen_reports_the_most_requested_packages(): void
    {
        $conversation = AiConversation::create([
            'uuid' => (string) Str::uuid(),
            'last_activity_at' => now(),
        ]);

        foreach (['What is UB010 in PKR?', 'and ub 010 in USD?', 'tell me about UB004'] as $content) {
            AiMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $content,
            ]);
        }

        // Only the visitor's own messages count, and "ub 010" normalises to
        // the same code as "UB010" — so this measures demand, not spelling.
        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'UB023 UB023 UB023 is also available.',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.ai.index'))->assertOk();

        $response->assertSee('Most requested packages');
        $response->assertSee('UB010');
        $response->assertDontSee('UB023');
    }

    public function test_stored_message_content_is_escaped_when_rendered(): void
    {
        $conversation = AiConversation::create([
            'uuid' => (string) Str::uuid(),
            'last_activity_at' => now(),
        ]);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.ai.conversation', $conversation))
            ->assertOk();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $response->getContent());
        $response->assertSee('&lt;script&gt;', false);
    }
}
