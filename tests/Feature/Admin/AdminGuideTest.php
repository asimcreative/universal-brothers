<?php

namespace Tests\Feature\Admin;

use App\Models\AdminGuideCompletion;
use App\Models\PackageCategory;
use App\Models\User;
use App\Support\Guide\GuideContent;
use App\Support\Packages\PackageCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin guide (help centre), the first-visit welcome panel and the guided
 * tour's saved state (issue #11), through their real routes.
 */
class AdminGuideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        PackageCategory::firstOrCreate(['slug' => 'hajj'], ['name' => 'Hajj', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    // ------------------------------------------------------------------
    // Content
    // ------------------------------------------------------------------

    public function test_the_guide_has_every_required_section_with_real_help_in_it(): void
    {
        $required = [
            'dashboard', 'website-content', 'hajj-packages', 'package-options', 'room-pricing', 'hotels', 'itinerary',
            'mashaer', 'transport', 'meals', 'inclusions', 'exclusions', 'upgrades', 'notes', 'templates', 'media',
            'pages-builder', 'text-editor', 'images-alt-text', 'reusable-sections', 'page-search-sharing', 'fixing-problems',
            'faqs', 'awards', 'affiliations', 'testimonials', 'news', 'enquiries', 'settings', 'ai-assistant',
            'preview-publishing', 'safe-editing',
        ];

        $sections = GuideContent::sections();
        $this->assertSame($required, array_keys($sections));

        foreach ($sections as $key => $section) {
            $this->assertArrayHasKey($section['group'], GuideContent::groups(), "{$key} has an unknown group");
            foreach (['title', 'icon', 'summary', 'why', 'example'] as $field) {
                $this->assertNotEmpty($section[$field] ?? null, "{$key} is missing {$field}");
            }
            $this->assertNotEmpty($section['steps'], "{$key} has no steps");
            $this->assertNotEmpty($section['faqs'], "{$key} has no common questions");
        }
    }

    public function test_the_guide_is_written_without_technical_words(): void
    {
        $words = ['database', 'json', 'slug', 'boolean', 'null', 'schema', 'controller', 'eloquent', 'migration', 'backend', 'frontend', 'foreign key', 'html'];

        $text = collect(GuideContent::sections())->map(fn ($s) => json_encode([$s['title'], $s['summary'], $s['why'], $s['steps'], $s['example'], $s['tips'], $s['faqs']]))
            ->merge(collect(PackageCompleteness::STEPS)->keys()->map(fn ($step) => json_encode(GuideContent::builderHelp($step))))
            ->merge([json_encode(GuideContent::tour())])
            ->implode(' ');

        foreach ($words as $word) {
            $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($word, '/').'\b/i', $text, "The guide uses the technical word \"{$word}\"");
        }
    }

    public function test_every_builder_step_has_a_complete_need_help_panel(): void
    {
        foreach (array_keys(PackageCompleteness::STEPS) as $step) {
            $help = GuideContent::builderHelp($step);
            $this->assertNotNull($help, "Step {$step} has no help panel");
            foreach (['purpose', 'enter', 'required', 'example', 'after'] as $answer) {
                $this->assertNotEmpty($help[$answer] ?? null, "Step {$step} help is missing \"{$answer}\"");
            }
            $this->assertNotNull(GuideContent::section($help['guide']), "Step {$step} links to a guide section that does not exist");
        }
    }

    // ------------------------------------------------------------------
    // Pages
    // ------------------------------------------------------------------

    public function test_the_guide_index_lists_every_section_by_group_with_progress(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.guide.index'))->assertOk();

        $response->assertSee('0 of 32 sections read');
        foreach (GuideContent::groups() as $title) {
            $response->assertSee($title, false);
        }
        foreach (GuideContent::sections() as $key => $section) {
            $response->assertSee(route('admin.guide.show', $key), false);
        }
        $response->assertSee('How to create a Hajj package');
    }

    public function test_every_section_page_renders_its_steps_example_questions_and_working_links(): void
    {
        foreach (GuideContent::sections() as $key => $section) {
            $response = $this->actingAs($this->admin)->get(route('admin.guide.show', $key))->assertOk();

            $response->assertSee($section['title'], false);
            $response->assertSee('Step by step');
            $response->assertSee(e($section['steps'][0]), false);
            $response->assertSee(e($section['example']), false);
            $response->assertSee(e($section['faqs'][0][0]), false);
            $response->assertSee('Mark as read');

            foreach (GuideContent::links($section) as $link) {
                $this->actingAs($this->admin)->get($link['url'])->assertOk();
            }
        }
    }

    public function test_the_hajj_package_section_walks_through_all_fourteen_builder_steps(): void
    {
        $steps = GuideContent::section('hajj-packages')['steps'];
        $text = implode(' ', $steps);

        foreach (['Step 1', 'Step 2', 'Step 3', 'Step 4', 'Step 5', 'Step 6', 'Step 12', 'Step 13', 'Step 14'] as $mention) {
            $this->assertStringContainsString($mention, $text);
        }
        $this->assertStringContainsString('Review', $text);
    }

    public function test_searching_the_guide_finds_sections_by_their_words(): void
    {
        $this->actingAs($this->admin)->get(route('admin.guide.index', ['q' => 'Kaaba View']))
            ->assertOk()
            ->assertSee(route('admin.guide.show', 'upgrades'), false)
            ->assertDontSee(route('admin.guide.show', 'awards'), false);

        $this->actingAs($this->admin)->get(route('admin.guide.index', ['q' => 'zzqx-nothing']))
            ->assertOk()
            ->assertSee('Nothing in the guide matches');
    }

    public function test_an_unknown_section_is_not_found_and_the_old_help_address_redirects(): void
    {
        $this->actingAs($this->admin)->get('/admin/guide/not-a-section')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/help')->assertRedirect(route('admin.guide.index'));
    }

    public function test_sections_are_marked_read_per_admin_and_only_once(): void
    {
        $other = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.guide.complete', 'room-pricing'))->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.guide.complete', 'room-pricing'))->assertRedirect();

        $this->assertSame(1, AdminGuideCompletion::where('user_id', $this->admin->id)->where('section_key', 'room-pricing')->count());
        $this->actingAs($this->admin)->get(route('admin.guide.index'))->assertSee('1 of 32 sections read');
        $this->actingAs($this->admin)->get(route('admin.guide.show', 'room-pricing'))->assertSee('You have read this section.');

        $this->actingAs($other)->get(route('admin.guide.index'))->assertSee('0 of 32 sections read');

        $this->actingAs($this->admin)->delete(route('admin.guide.uncomplete', 'room-pricing'))->assertRedirect();
        $this->assertSame(0, AdminGuideCompletion::count());

        $this->actingAs($this->admin)->post('/admin/guide/not-a-section/complete')->assertNotFound();
    }

    public function test_guests_cannot_open_the_guide_or_change_onboarding(): void
    {
        $this->get(route('admin.guide.index'))->assertRedirect('/admin/login');
        $this->post(route('admin.guide.complete', 'dashboard'))->assertRedirect('/admin/login');
        $this->post(route('admin.onboarding.tour'), ['status' => 'completed', 'step' => 0])->assertRedirect('/admin/login');
        $this->post(route('admin.onboarding.dismiss'))->assertRedirect('/admin/login');
    }

    public function test_admin_pages_offer_a_need_help_panel_from_the_guide(): void
    {
        foreach ([
            route('admin.faqs.index') => 'faqs',
            route('admin.inquiries.index') => 'enquiries',
            route('admin.library.index', 'hotels') => 'hotels',
            route('admin.package-templates.index') => 'templates',
            route('admin.hajj-packages.index') => 'hajj-packages',
        ] as $url => $key) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertSee('Need help with this page?')
                ->assertSee(route('admin.guide.show', $key), false);
        }
    }

    // ------------------------------------------------------------------
    // Welcome panel and tour
    // ------------------------------------------------------------------

    public function test_a_new_admin_is_welcomed_with_the_tour_and_every_tour_stop_exists_on_the_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        $response->assertSee('Welcome to your website admin');
        $response->assertSee('Start guided tour');
        $response->assertSee('Skip for now');
        $response->assertSee('Open the guide');
        $response->assertSee('id="admin-tour-data"', false);

        $this->assertCount(9, GuideContent::tour());
        foreach (GuideContent::tour() as $stop) {
            $response->assertSee('data-tour="'.$stop['target'].'"', false);
        }
    }

    public function test_the_tour_saves_progress_and_offers_to_resume_where_it_stopped(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'in_progress', 'step' => 2])->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'paused', 'step' => 3])->assertOk();

        $this->admin->refresh();
        $this->assertSame('paused', $this->admin->tour_status);
        $this->assertSame(3, $this->admin->tour_step);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee('Resume tour (step 4 of 9)')
            ->assertSee('data-tour-start="3"', false);
    }

    public function test_a_finished_tour_does_not_come_back_until_restarted(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'completed', 'step' => 8])->assertOk();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee('Welcome to your website admin');

        $this->actingAs($this->admin)->post(route('admin.onboarding.restart'))
            ->assertRedirect(route('admin.dashboard', ['tour' => 'start']));

        $this->admin->refresh();
        $this->assertSame('not_started', $this->admin->tour_status);
        $this->assertNull($this->admin->onboarding_dismissed_at);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('Welcome to your website admin');
    }

    public function test_skipping_the_welcome_hides_it_for_that_admin_only(): void
    {
        $other = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.onboarding.dismiss'))->assertRedirect();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee('Welcome to your website admin');
        $this->actingAs($other)->get(route('admin.dashboard'))->assertSee('Welcome to your website admin');
    }

    public function test_the_tour_state_endpoint_rejects_nonsense(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'finished', 'step' => 1])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'paused', 'step' => 9])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson(route('admin.onboarding.tour'), ['status' => 'paused', 'step' => -1])->assertUnprocessable();

        $this->assertSame('not_started', $this->admin->fresh()->tour_status);
    }
}
