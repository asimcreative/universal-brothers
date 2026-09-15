<?php

namespace Tests\Feature\Admin;

use App\Models\AdminTrainingProgress;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\User;
use App\Support\Guide\GuideContent;
use App\Support\Training\TrainingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Admin Guide → Video Training (issue #12): the chapter index, the player and
 * written companion pages, admin-only video files, per-admin progress, the
 * printable checklist, contextual "Watch Guide" links, and the safety of the
 * training data. Videos are replaced by small stand-in files in a temporary
 * folder, so the tests never depend on (or touch) the recorded files.
 */
class VideoTrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $videos;

    /** The chapter titles the brief requires, in order. */
    private const REQUIRED_TITLES = [
        'Welcome to the Universal Brothers Admin Panel', 'Understanding the Dashboard', 'Finding Hajj Package Management',
        'Creating a New Hajj Package', 'Adding Basic Package Information', 'Configuring Hajj Package Settings',
        'Creating Package A and Package B', 'Adding Hotels and Accommodation', 'Adding Room Types and Prices',
        'Entering PKR, SAR and USD Prices', 'Building the Complete Itinerary', 'Adding Mina, Arafat and Muzdalifah Details',
        'Configuring Transport and Meals', 'Managing Included Services', 'Managing Excluded Services', 'Adding Optional Upgrades',
        'Adding Reusable Notes and Policies', 'Uploading Package Images', 'Adding SEO Information', 'Reviewing the Complete Package',
        'Saving the Package as a Draft', 'Previewing the Package', 'Fixing Validation Warnings', 'Publishing the Package',
        'Viewing the Public Package Page', 'Editing an Existing Package', 'Duplicating a Package Safely', 'Final Checklist and Best Practices',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PackageCategory::firstOrCreate(['slug' => 'hajj'], ['name' => 'Hajj', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'tour_status' => 'completed']);

        $this->videos = storage_path('framework/testing/training-videos-'.uniqid());
        File::ensureDirectoryExists($this->videos);
        config(['training.video_path' => $this->videos]);
        TrainingCatalog::flush();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->videos);
        TrainingCatalog::flush();
        parent::tearDown();
    }

    /** Stand-in recorded files for the given chapters. */
    private function recordVideos(array $keys, int $duration = 65): void
    {
        $chapters = [];
        foreach ($keys as $key) {
            $number = str_pad((string) TrainingCatalog::chapter($key)['number'], 2, '0', STR_PAD_LEFT);
            File::put("{$this->videos}/{$number}-{$key}.webm", str_repeat("\x1A\x45\xDF\xA3", 512));
            File::put("{$this->videos}/{$number}-{$key}.vtt", "WEBVTT\n\n1\n00:00:05.000 --> 00:00:09.000\nPress \"Add Hajj Package\".\n\n2\n00:01:02.500 --> 00:01:05.000\nThe builder opens.\n");
            File::put("{$this->videos}/{$number}-{$key}.jpg", "\xFF\xD8\xFF\xE0fake-jpeg");
            $chapters[$key] = ['file' => "{$number}-{$key}.webm", 'duration' => $duration, 'captions' => "{$number}-{$key}.vtt", 'poster' => "{$number}-{$key}.jpg"];
        }
        File::put("{$this->videos}/manifest.json", json_encode(['chapters' => $chapters]));
        TrainingCatalog::flush();
    }

    // ------------------------------------------------------------------
    // Content
    // ------------------------------------------------------------------

    public function test_the_training_has_every_required_chapter_in_order_with_a_complete_written_guide(): void
    {
        $chapters = TrainingCatalog::chapters();
        $titles = array_column($chapters, 'title');

        $this->assertSame(self::REQUIRED_TITLES, array_slice($titles, 0, 28));
        $this->assertContains('Using Reusable Information', $titles);
        $this->assertContains('Using Package Templates', $titles);

        $this->assertSame(['Getting Started', 'Hajj Package Creation', 'Review and Publishing', 'Advanced Management'], array_column(TrainingCatalog::categories(), 'title'));

        foreach ($chapters as $key => $c) {
            $this->assertArrayHasKey($c['category'], TrainingCatalog::categories(), "{$key}: unknown category");
            foreach (['title', 'description', 'objective', 'why', 'expected'] as $field) {
                $this->assertNotEmpty($c[$field] ?? null, "{$key} is missing {$field}");
            }
            $this->assertContains($c['difficulty'], ['Beginner', 'Intermediate'], $key);
            $this->assertGreaterThan(0, $c['task_minutes'], $key);
            $this->assertNotEmpty($c['learn'], "{$key} has nothing under What you will learn");
            $this->assertNotEmpty($c['steps'], "{$key} has no steps");
            $this->assertNotEmpty($c['mistakes'], "{$key} has no common mistakes");
            $this->assertTrue(Route::has($c['related'][1]), "{$key} links to a missing admin page");
            $this->assertNotNull(GuideContent::section($c['guide']), "{$key} links to a missing guide section");
        }
    }

    public function test_the_training_is_written_without_technical_words(): void
    {
        // The explanations only — "fields" are the literal values typed on screen.
        $text = json_encode(collect(TrainingCatalog::chapters())->map(fn ($c) => [$c['title'], $c['description'], $c['objective'], $c['why'], $c['learn'], $c['steps'], $c['notes'], $c['mistakes'], $c['check'], $c['expected']]));

        foreach (['database', 'json', 'slug', 'boolean', 'schema', 'controller', 'eloquent', 'migration', 'backend', 'frontend', 'html', 'seeder'] as $word) {
            $this->assertDoesNotMatchRegularExpression('/\b'.$word.'\b/i', $text, "The training uses the technical word \"{$word}\"");
        }
    }

    public function test_the_checklist_has_every_required_item(): void
    {
        $items = collect(TrainingCatalog::checklist())->flatten()->all();

        foreach (['Package title and code', 'Hajj year', 'Duration', 'Arrival city', 'Package configuration (shifting, Aziziya)', 'Package A / B / C', 'Hotels', 'Room types', 'PKR prices', 'SAR prices', 'USD prices', 'Itinerary', 'Islamic dates', 'Mina details', 'Arafat details', 'Muzdalifah details', 'Transport', 'Meals', 'Inclusions', 'Exclusions', 'Optional upgrades', 'Reusable notes', 'Package-specific notes', 'Images', 'SEO', 'Final review', 'Draft saved', 'Preview checked', 'Validation warnings resolved', 'Package published'] as $item) {
            $this->assertContains($item, $items);
        }
    }

    public function test_the_script_document_matches_the_chapters(): void
    {
        $script = File::get(base_path('docs/training/ADMIN_PACKAGE_CREATION_VIDEO_SCRIPT.md'));

        foreach (TrainingCatalog::chapters() as $c) {
            $this->assertStringContainsString("## {$c['number']}. {$c['title']}", $script);
            foreach ($c['steps'] as $step) {
                $this->assertStringContainsString($step, $script, "The script is out of date for chapter {$c['number']} — run php artisan training:write-script");
            }
        }
    }

    // ------------------------------------------------------------------
    // Index, search and pages
    // ------------------------------------------------------------------

    public function test_the_index_shows_every_chapter_card_grouped_with_its_details(): void
    {
        $this->recordVideos(['welcome', 'room-types']);

        $response = $this->actingAs($this->admin)->get(route('admin.training.index'))->assertOk();

        foreach (TrainingCatalog::categories() as $category) {
            $response->assertSee($category['title'], false);
        }
        foreach (TrainingCatalog::chapters() as $c) {
            $response->assertSee('Chapter '.$c['number']);
            $response->assertSee(e($c['title']), false);
            $response->assertSee(e($c['description']), false);
            $response->assertSee('aria-label="Watch chapter '.$c['number'].': '.e($c['title']).'"', false);
        }
        $response->assertSee('1:05');
        $response->assertSee('Beginner');
        $response->assertSee('About 3 min to do');
        $response->assertSee('Not completed');
        $response->assertSee(route('admin.training.poster', 'welcome'), false);
        $response->assertSee('28 chapters have no video file on this server yet');
        $response->assertSee('Start Training');
        $response->assertSee('Restart Training');
    }

    public function test_the_training_can_be_searched_by_the_words_admins_use(): void
    {
        foreach (['Package', 'Room pricing', 'Hotel', 'Itinerary', 'Images', 'SEO', 'Draft', 'Publish', 'Duplicate', 'Reusable information', 'Notes', 'Transport', 'Meals'] as $term) {
            $this->assertNotEmpty(collect(TrainingCatalog::grouped($term))->flatten(1)->all(), "Searching \"{$term}\" finds nothing");
        }

        $this->actingAs($this->admin)->get(route('admin.training.index', ['q' => 'room pricing']))
            ->assertOk()
            ->assertSee('Adding Room Types and Prices')
            ->assertDontSee('Understanding the Dashboard');

        $this->actingAs($this->admin)->get(route('admin.training.index', ['q' => 'zzqx-nothing']))->assertOk()->assertSee('No chapter matches');
    }

    public function test_a_chapter_page_has_the_player_its_moments_and_the_written_companion_guide(): void
    {
        $this->recordVideos(['room-types']);
        $chapter = TrainingCatalog::chapter('room-types');

        $response = $this->actingAs($this->admin)->get(route('admin.training.show', 'room-types'))->assertOk();

        $response->assertSee('Chapter 9: Adding Room Types and Prices', false);
        $response->assertSee('<video controls preload="metadata" playsinline', false);
        $response->assertDontSee('autoplay', false);
        $response->assertSee(route('admin.training.video', 'room-types'), false);
        $response->assertSee('<track kind="captions" srclang="en"', false);
        $response->assertSee('data-training-seek="-10"', false);
        $response->assertSee('Playback speed');
        $response->assertSee('<option value="1" selected>Normal speed</option>', false);
        $response->assertSee('Replay');
        $response->assertSee('Mark as Completed');
        $response->assertSee('In this video');
        $response->assertSee('data-training-jump="62"', false);
        $response->assertSee('The builder opens.');
        foreach (['What you will learn', 'Step-by-step instructions', 'Important notes', 'Common mistakes', 'Related admin section', 'Previous chapter', 'Next chapter'] as $heading) {
            $response->assertSee($heading);
        }
        $response->assertSee(e($chapter['steps'][0]), false);
        $response->assertSee('Entering PKR, SAR and USD Prices');
        $response->assertSee('Creating Package A and Package B');
    }

    public function test_a_chapter_without_a_video_still_shows_the_written_guide(): void
    {
        $this->actingAs($this->admin)->get(route('admin.training.show', 'itinerary'))
            ->assertOk()
            ->assertSee('The video for this chapter is not on this server yet.')
            ->assertDontSee('<video', false)
            ->assertSee('Step-by-step instructions');

        $this->actingAs($this->admin)->get('/admin/guide/videos/not-a-chapter')->assertNotFound();
    }

    public function test_the_printable_checklist_can_be_printed_and_downloaded(): void
    {
        $this->actingAs($this->admin)->get(route('admin.training.checklist'))
            ->assertOk()
            ->assertSee('Print checklist')
            ->assertSee('Download checklist')
            ->assertSee('Validation warnings resolved');

        $download = $this->actingAs($this->admin)->get(route('admin.training.checklist.download'))->assertOk();
        $download->assertHeader('Content-Disposition', 'attachment; filename="hajj-package-checklist.txt"');
        $this->assertStringContainsString('[ ] Package title and code', $download->getContent());
        $this->assertStringContainsString('[ ] Package published', $download->getContent());
    }

    // ------------------------------------------------------------------
    // Files: admins only, seekable, never outside the folder
    // ------------------------------------------------------------------

    public function test_videos_captions_and_thumbnails_are_served_only_to_signed_in_admins(): void
    {
        $this->recordVideos(['welcome']);

        $video = $this->actingAs($this->admin)->get(route('admin.training.video', 'welcome'));
        $video->assertOk();
        $this->assertStringStartsWith('video/webm', $video->headers->get('Content-Type'));
        $this->assertSame('bytes', $video->headers->get('Accept-Ranges'));

        $partial = $this->actingAs($this->admin)->get(route('admin.training.video', 'welcome'), ['Range' => 'bytes=0-99']);
        $partial->assertStatus(206);
        $this->assertStringStartsWith('bytes 0-99/', $partial->headers->get('Content-Range'));

        $this->assertStringStartsWith('text/vtt', $this->actingAs($this->admin)->get(route('admin.training.captions', 'welcome'))->assertOk()->headers->get('Content-Type'));
        $this->assertSame('image/jpeg', $this->actingAs($this->admin)->get(route('admin.training.poster', 'welcome'))->assertOk()->headers->get('Content-Type'));

        $this->actingAs($this->admin)->get(route('admin.training.video', 'dashboard'))->assertNotFound();

        auth()->logout();
        $this->get(route('admin.training.index'))->assertRedirect('/admin/login');
        $this->get(route('admin.training.video', 'welcome'))->assertRedirect('/admin/login');
        $this->get(route('admin.training.captions', 'welcome'))->assertRedirect('/admin/login');
    }

    public function test_a_manifest_can_never_point_outside_the_video_folder(): void
    {
        File::put(storage_path('framework/testing/secret.webm'), 'not for admins');
        File::put("{$this->videos}/manifest.json", json_encode(['chapters' => ['welcome' => ['file' => '../secret.webm', 'duration' => 10]]]));
        TrainingCatalog::flush();

        $this->assertNull(TrainingCatalog::chapter('welcome')['video']);
        $this->actingAs($this->admin)->get(route('admin.training.video', 'welcome'))->assertNotFound();

        File::delete(storage_path('framework/testing/secret.webm'));
    }

    public function test_every_admin_role_can_use_the_training(): void
    {
        $editor = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($editor)->get(route('admin.training.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.training.show', 'welcome'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Progress
    // ------------------------------------------------------------------

    public function test_playback_position_is_saved_and_the_chapter_resumes_there(): void
    {
        $this->recordVideos(['prices'], 200);

        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'prices'), ['position' => 73.6, 'duration' => 200])
            ->assertOk()->assertJson(['position' => 73, 'completed' => false]);

        $record = AdminTrainingProgress::where('user_id', $this->admin->id)->where('chapter_key', 'prices')->firstOrFail();
        $this->assertSame(73, $record->position_seconds);
        $this->assertSame(73, $record->furthest_seconds);
        $this->assertSame(200, $record->duration_seconds);
        $this->assertNotNull($record->last_watched_at);

        // Seeking back keeps the furthest point watched.
        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'prices'), ['position' => 20, 'duration' => 200])->assertOk();
        $this->assertSame(73, $record->fresh()->furthest_seconds);

        $this->actingAs($this->admin)->get(route('admin.training.show', 'prices'))->assertSee('data-resume="20"', false);
        $this->actingAs($this->admin)->get(route('admin.training.show', ['chapter' => 'prices', 'start' => 0]))->assertSee('data-resume="0"', false);
        $this->actingAs($this->admin)->get(route('admin.training.index'))->assertSee('Stopped at 0:20')->assertSee('Continue Training');
    }

    public function test_watching_nine_tenths_of_a_chapter_completes_it(): void
    {
        $this->recordVideos(['hotels'], 100);

        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'hotels'), ['position' => 89, 'duration' => 100])->assertJson(['completed' => false]);
        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'hotels'), ['position' => 90, 'duration' => 100])
            ->assertJson(['completed' => true, 'summary' => ['completed' => 1, 'total' => TrainingCatalog::count()]]);

        // A position past the end is capped.
        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'hotels'), ['position' => 5000, 'duration' => 100])->assertJson(['position' => 100]);

        $this->actingAs($this->admin)->get(route('admin.training.index'))->assertSee('1 of '.TrainingCatalog::count().' chapters completed');
    }

    public function test_chapters_can_be_marked_completed_and_not_completed_by_hand(): void
    {
        $this->actingAs($this->admin)->post(route('admin.training.complete', 'seo'))->assertRedirect();
        $this->assertNotNull(AdminTrainingProgress::where('chapter_key', 'seo')->value('completed_at'));
        $this->actingAs($this->admin)->get(route('admin.training.show', 'seo'))->assertSee('Mark as not completed');

        $this->actingAs($this->admin)->postJson(route('admin.training.complete', 'review'))->assertOk()->assertJson(['completed' => true]);

        $this->actingAs($this->admin)->delete(route('admin.training.uncomplete', 'seo'))->assertRedirect();
        $this->assertNull(AdminTrainingProgress::where('chapter_key', 'seo')->value('completed_at'));

        $this->actingAs($this->admin)->post('/admin/guide/videos/not-a-chapter/complete')->assertNotFound();
    }

    public function test_progress_is_private_to_each_admin_and_can_be_reset(): void
    {
        $other = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.training.complete', 'welcome'));
        $this->actingAs($other)->post(route('admin.training.complete', 'dashboard'));

        $this->actingAs($other)->get(route('admin.training.index'))->assertSee('1 of '.TrainingCatalog::count().' chapters completed');

        $this->actingAs($this->admin)->post(route('admin.training.reset'))->assertRedirect(route('admin.training.index'));

        $this->assertSame(0, AdminTrainingProgress::where('user_id', $this->admin->id)->count());
        $this->assertSame(1, AdminTrainingProgress::where('user_id', $other->id)->count());
    }

    public function test_continue_and_restart_training_go_to_the_right_chapter(): void
    {
        $this->actingAs($this->admin)->get(route('admin.training.continue'))->assertRedirect(route('admin.training.show', 'welcome'));

        $this->actingAs($this->admin)->post(route('admin.training.complete', 'welcome'));
        $this->actingAs($this->admin)->get(route('admin.training.continue'))->assertRedirect(route('admin.training.show', 'dashboard'));

        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'itinerary'), ['position' => 30, 'duration' => 300]);
        $this->actingAs($this->admin)->get(route('admin.training.continue'))->assertRedirect(route('admin.training.show', 'itinerary'));

        $this->actingAs($this->admin)->get(route('admin.training.restart'))->assertRedirect(route('admin.training.show', ['chapter' => 'welcome', 'start' => 0]));
    }

    public function test_the_progress_endpoint_rejects_nonsense(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'welcome'), ['position' => -5])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson(route('admin.training.progress', 'welcome'), [])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson('/admin/guide/videos/not-a-chapter/progress', ['position' => 1])->assertNotFound();

        auth()->logout();
        $this->postJson(route('admin.training.progress', 'welcome'), ['position' => 1])->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Contextual help
    // ------------------------------------------------------------------

    public function test_admin_pages_link_straight_to_the_matching_video(): void
    {
        $builder = $this->actingAs($this->admin)->get(route('admin.hajj-packages.create'))->assertOk();
        foreach (['pricing' => 'room-types', 'journey' => 'itinerary', 'review' => 'review', 'publish' => 'publish'] as $step => $video) {
            $builder->assertSee('data-watch-guide="'.$video.'"', false);
            $builder->assertSee(route('admin.training.show', $video), false);
        }
        $builder->assertSee('Watch Guide: Adding Room Types and Prices');
        $builder->assertSee('Watch Guide: Building the Complete Itinerary');

        $this->actingAs($this->admin)->get(route('admin.library.index', 'hotels'))->assertOk()->assertSee('Watch Guide: Using Reusable Information');
        $this->actingAs($this->admin)->get(route('admin.guide.show', 'room-pricing'))->assertOk()->assertSee('Watch Guide: Adding Room Types and Prices');
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.training.index'), false)->assertSee('Video Training');
    }

    // ------------------------------------------------------------------
    // Training data safety
    // ------------------------------------------------------------------

    public function test_the_demonstration_package_is_marked_as_training_and_copies_approved_brochure_data(): void
    {
        $demo = json_decode(File::get(resource_path('data/training-demo-package.json')), true);

        $this->assertStringStartsWith('TRN', $demo['basics']['code']);
        $this->assertStringContainsString('Admin Training Example', $demo['internal_note']);

        Artisan::call('db:seed');
        $ub015 = Package::where('code', $demo['source_package'])->with(['roomOptions.variant', 'variants'])->firstOrFail();

        $this->assertSame($ub015->name, $demo['basics']['name']);
        $this->assertSame($ub015->duration_days, $demo['basics']['duration_days']);
        foreach (['A', 'B'] as $code) {
            foreach ($demo['rooms'][$code] as $room) {
                $row = $ub015->roomOptions->first(fn ($r) => $r->variant?->code === $code && $r->display_label === $room['type']);
                $this->assertNotNull($row, "UB015 has no {$room['type']} for option {$code}");
                $this->assertEquals((float) $room['usd'], (float) $row->price_usd);
                $this->assertEquals((float) $room['sar'], (float) $row->price_sar);
                $this->assertEquals((float) $room['pkr'], (float) $row->price_pkr);
            }
        }

        // Seeding never creates a training package.
        $this->assertSame(0, Package::where('code', 'like', 'TRN%')->count());
    }

    public function test_the_recording_command_refuses_any_database_but_the_training_one(): void
    {
        $users = User::count();

        $this->artisan('training:recording', ['action' => 'prepare'])
            ->expectsOutputToContain('Refused')
            ->assertExitCode(1);
        $this->artisan('training:recording', ['action' => 'cleanup'])->assertExitCode(1);

        $this->assertSame($users, User::count());
        $this->assertTrue($this->admin->fresh()->is_active);
    }
}
