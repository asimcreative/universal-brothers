<?php

namespace Tests\Feature\Admin;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use App\Support\PageBuilder\PageDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Pages built from sections: create, edit, draft, preview, publish, unpublish,
 * reorder, duplicate, hide, delete, saved sections, validation and access —
 * each through the real admin routes, and checked on the public page.
 */
class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function section(string $id, string $type, array $data, bool $visible = true): array
    {
        return [$id => ['id' => $id, 'type' => $type, 'visible' => $visible ? '1' : '0', 'data' => $data]];
    }

    private function payload(array $sections, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Hajj Preparation Guide',
            'slug' => 'hajj-preparation-guide',
            'sections' => $sections,
            'intent' => 'save',
        ], $overrides);
    }

    private function draftPage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Hajj Preparation Guide', 'slug' => 'hajj-preparation-guide', 'template' => 'default',
            'status' => 'draft', 'is_active' => false,
        ], $attributes));
    }

    // ------------------------------------------------------------ access

    public function test_guests_cannot_reach_any_page_builder_screen(): void
    {
        $page = $this->draftPage();

        $this->get('/admin/pages')->assertRedirect('/admin/login');
        $this->get('/admin/pages/create')->assertRedirect('/admin/login');
        $this->get("/admin/pages/{$page->id}/edit")->assertRedirect('/admin/login');
        $this->put("/admin/pages/{$page->id}", $this->payload([]))->assertRedirect('/admin/login');
        $this->get('/admin/pages/section-form?type=text')->assertRedirect('/admin/login');
        $this->post("/admin/pages/{$page->id}/publish")->assertRedirect('/admin/login');
        $this->assertFalse($page->fresh()->is_active);
    }

    public function test_an_inactive_admin_is_sent_back_to_the_login(): void
    {
        $inactive = User::factory()->create(['role' => 'content_editor', 'is_active' => false]);

        $this->actingAs($inactive)->get('/admin/pages')->assertRedirect(route('admin.login'));
    }

    public function test_the_preview_needs_a_signature_as_well_as_an_admin_session(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->get("/admin/pages/{$page->id}/preview")->assertForbidden();

        auth()->logout();
        $this->get(URL::temporarySignedRoute('admin.pages.preview', now()->addHour(), ['page' => $page]))->assertRedirect('/admin/login');
    }

    // ------------------------------------------------------------ create and edit

    public function test_creating_a_page_makes_a_draft_from_the_chosen_layout_that_visitors_cannot_see(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/pages', [
            'title' => 'Hajj Preparation Guide',
            'slug' => 'hajj-preparation-guide',
            'starter' => 'information',
        ]);

        $page = Page::where('slug', 'hajj-preparation-guide')->firstOrFail();
        $response->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('draft', $page->status);
        $this->assertFalse($page->is_active);
        $this->assertSame(['hero', 'text', 'text_image', 'cta'], array_column($page->draft['sections'], 'type'));
        $this->assertSame('Hajj Preparation Guide', $page->draft['sections'][0]['data']['heading']);

        $this->get('/hajj-preparation-guide')->assertNotFound();
    }

    public function test_the_builder_opens_with_the_sections_and_their_plain_labels(): void
    {
        $this->actingAs($this->admin)->post('/admin/pages', ['title' => 'Our Services', 'slug' => 'our-services', 'starter' => 'service']);
        $page = Page::where('slug', 'our-services')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Page banner')
            ->assertSee('Services')
            ->assertSee('Questions and answers')
            ->assertSee('Enquiry form')
            ->assertSee('Add a section')
            ->assertSee('Save draft')
            ->assertSee('Publish');
    }

    public function test_a_page_built_before_sections_opens_with_its_content_as_sections_and_stays_unchanged_until_published(): void
    {
        $page = Page::create([
            'title' => 'About Us', 'slug' => 'about-us', 'template' => 'about',
            'body' => '<p>Real company history.</p>', 'is_active' => true, 'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('This page now uses sections')
            ->assertSee('Story with company details')
            ->assertSee('Real company history.', false);

        $this->assertNull($page->fresh()->sections);
        $this->get('/about-us')->assertOk()->assertSee('Real company history.');
    }

    // ------------------------------------------------------------ draft, preview, publish

    public function test_saving_a_draft_keeps_the_live_page_unchanged(): void
    {
        $page = $this->draftPage();
        $sections = $this->section('s_intro0001', 'text', ['heading' => 'What to pack', 'content' => '<p>Live words.</p>']);
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($sections, ['intent' => 'publish']));
        $this->assertTrue($page->fresh()->isLive());

        $changed = $this->section('s_intro0001', 'text', ['heading' => 'What to pack', 'content' => '<p>Draft words.</p>']);
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($changed))
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHas('status');

        $page->refresh();
        $this->assertSame('<p>Draft words.</p>', $page->draft['sections'][0]['data']['content']);
        $this->assertTrue($page->hasUnpublishedChanges());
        $this->get('/hajj-preparation-guide')->assertOk()->assertSee('Live words.')->assertDontSee('Draft words.');
    }

    public function test_the_preview_shows_the_unpublished_draft_and_is_never_indexed(): void
    {
        $page = $this->draftPage();
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_prev00001', 'text', ['content' => '<p>Only in the draft.</p>'])
        ));

        $this->actingAs($this->admin)
            ->get(URL::temporarySignedRoute('admin.pages.preview', now()->addHour(), ['page' => $page]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Preview')
            ->assertSee('Only in the draft.');

        $this->get('/hajj-preparation-guide')->assertNotFound();
    }

    public function test_save_and_preview_saves_then_returns_to_open_the_preview(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), $this->payload($this->section('s_prev00002', 'text', ['content' => '<p>x</p>']), ['intent' => 'preview']))
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHas('open_preview', true);
    }

    public function test_publishing_puts_the_sections_on_the_website_and_keeps_a_version(): void
    {
        $page = $this->draftPage();

        $sections = $this->section('s_hero00001', 'hero', ['heading' => 'Prepare for Hajj', 'subheading' => 'Everything in one place.'])
            + $this->section('s_text00001', 'text', ['heading' => 'Documents to bring', 'content' => '<ul><li>Original passport</li><li>Two photographs</li></ul>'])
            + $this->section('s_cta000001', 'cta', ['heading' => 'Ready to book?', 'button_text' => 'Contact us', 'button_link' => '/contact', 'style' => 'navy']);

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($sections, ['intent' => 'publish']))
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('published', $page->status);
        $this->assertTrue($page->is_active);
        $this->assertNull($page->draft);
        $this->assertCount(3, $page->sections);
        $this->assertSame(1, PageRevision::where('page_id', $page->id)->where('action', 'published')->count());
        $this->assertStringContainsString('Original passport', $page->body);

        $this->get('/hajj-preparation-guide')->assertOk()
            ->assertSee('<h1 class="page-hero-title">Prepare for Hajj</h1>', false)
            ->assertSee('<li>Original passport</li>', false)
            ->assertSee('Ready to book?')
            ->assertSee('href="/contact"', false);
    }

    public function test_publishing_with_problems_saves_the_draft_and_explains_each_problem_by_section(): void
    {
        $page = $this->draftPage();

        $sections = $this->section('s_text00002', 'text', ['heading' => 'Intro', 'content' => ''])
            + $this->section('s_cta000002', 'cta', ['heading' => 'Book now', 'button_text' => 'Book', 'button_link' => 'www.example.com'])
            + $this->section('s_img000001', 'text_image', ['content' => '<p>Mina</p>', 'image' => ['path' => 'media/library/missing.jpg', 'alt' => '']]);

        $response = $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($sections, ['intent' => 'publish']));

        $response->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors([
                'sections.s_text00002.data.content',
                'sections.s_cta000002.data.button_link',
                'sections.s_img000001.data.image.path',
            ]);

        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString('Section 1 (Text)', $errors->first('sections.s_text00002.data.content'));
        $this->assertStringContainsString('Add https:// at the start', $errors->first('sections.s_cta000002.data.button_link'));
        $this->assertStringContainsString('Choose the image again', $errors->first('sections.s_img000001.data.image.path'));

        $page->refresh();
        $this->assertFalse($page->is_active);
        $this->assertSame('www.example.com', $page->draft['sections'][1]['data']['button_link'], 'The typed link is kept, not discarded.');
    }

    public function test_a_page_with_only_hidden_sections_cannot_be_published(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_hidden001', 'text', ['content' => '<p>Hidden.</p>'], visible: false),
            ['intent' => 'publish']
        ))->assertSessionHasErrors('sections');

        $this->assertFalse($page->fresh()->is_active);
    }

    public function test_missing_image_descriptions_block_publishing_but_decorative_banners_do_not(): void
    {
        $image = MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'library', 'file_path' => 'media/library/mina.jpg', 'is_active' => true]);
        $page = $this->draftPage();

        $withoutAlt = $this->section('s_img000002', 'text_image', ['content' => '<p>Mina</p>', 'image' => ['path' => $image->file_path, 'alt' => '']])
            + $this->section('s_hero00002', 'hero', ['heading' => 'Mina', 'image' => ['path' => $image->file_path, 'alt' => '']]);

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($withoutAlt, ['intent' => 'publish']))
            ->assertSessionHasErrors('sections.s_img000002.data.image.alt')
            ->assertSessionDoesntHaveErrors('sections.s_hero00002.data.image.alt');
    }

    public function test_scheduling_publishes_later_and_the_page_stays_hidden_until_then(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_sched0001', 'text', ['content' => '<p>Coming soon.</p>']),
            ['intent' => 'schedule', 'publish_at' => now()->addDay()->toIso8601String()]
        ))->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('scheduled', $page->status);
        $this->get('/hajj-preparation-guide')->assertNotFound();

        $this->travel(2)->days();
        $this->get('/hajj-preparation-guide')->assertOk()->assertSee('Coming soon.');
    }

    public function test_a_scheduled_time_in_the_past_is_refused(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_sched0002', 'text', ['content' => '<p>x</p>']),
            ['intent' => 'schedule', 'publish_at' => now()->subHour()->toIso8601String()]
        ))->assertSessionHasErrors('publish_at');
    }

    public function test_unpublishing_takes_the_page_off_the_website_and_keeps_its_content(): void
    {
        $page = $this->draftPage();
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_unpub0001', 'text', ['content' => '<p>Kept.</p>']), ['intent' => 'publish']
        ));

        $this->actingAs($this->admin)->patch(route('admin.pages.unpublish', $page))->assertRedirect();

        $page->refresh();
        $this->assertSame('draft', $page->status);
        $this->assertFalse($page->is_active);
        $this->assertCount(1, $page->sections);
        $this->get('/hajj-preparation-guide')->assertNotFound();
    }

    public function test_archiving_and_restoring_a_page(): void
    {
        $page = $this->draftPage(['is_active' => true, 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->actingAs($this->admin)->patch(route('admin.pages.archive', $page))->assertRedirect(route('admin.pages.index'));
        $this->assertSame('archived', $page->fresh()->status);
        $this->get('/hajj-preparation-guide')->assertNotFound();

        $this->actingAs($this->admin)->patch(route('admin.pages.restore', $page))->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame('draft', $page->fresh()->status);
    }

    public function test_an_earlier_version_can_be_restored_as_the_draft(): void
    {
        $page = $this->draftPage();
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($this->section('s_rev000001', 'text', ['content' => '<p>First version.</p>']), ['intent' => 'publish']));
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($this->section('s_rev000001', 'text', ['content' => '<p>Second version.</p>']), ['intent' => 'publish']));

        $first = PageRevision::where('page_id', $page->id)->oldest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.pages.revisions.restore', [$page, $first]))->assertRedirect(route('admin.pages.edit', $page));

        $page->refresh();
        $this->assertSame('<p>First version.</p>', $page->draft['sections'][0]['data']['content']);
        $this->get('/hajj-preparation-guide')->assertSee('Second version.');
    }

    // ------------------------------------------------------------ arranging sections

    public function test_the_submitted_order_becomes_the_page_order(): void
    {
        $page = $this->draftPage();

        $sections = $this->section('s_second001', 'text', ['heading' => 'Second on the old page', 'content' => '<p>B</p>'])
            + $this->section('s_first0001', 'text', ['heading' => 'First on the old page', 'content' => '<p>A</p>']);

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($sections, ['intent' => 'publish']));

        $this->assertSame(['s_second001', 's_first0001'], array_column($page->fresh()->sections, 'id'));
        $this->get('/hajj-preparation-guide')->assertSeeInOrder(['Second on the old page', 'First on the old page']);
    }

    public function test_hidden_sections_are_kept_but_not_shown(): void
    {
        $page = $this->draftPage();

        $sections = $this->section('s_shown0001', 'text', ['content' => '<p>Shown text.</p>'])
            + $this->section('s_hidden002', 'text', ['content' => '<p>Hidden text.</p>'], visible: false);

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload($sections, ['intent' => 'publish']));

        $this->assertCount(2, $page->fresh()->sections);
        $this->assertFalse($page->fresh()->sections[1]['visible']);
        $this->get('/hajj-preparation-guide')->assertSee('Shown text.')->assertDontSee('Hidden text.');
    }

    public function test_a_duplicated_section_is_saved_as_its_own_section(): void
    {
        $page = $this->draftPage();
        $data = ['heading' => 'Repeated', 'content' => '<p>Same words.</p>'];

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_orig00001', 'text', $data) + $this->section('s_copy00001', 'text', $data), ['intent' => 'publish']
        ));

        $this->assertSame(['s_orig00001', 's_copy00001'], array_column($page->fresh()->sections, 'id'));
        $this->assertSame(2, substr_count($this->get('/hajj-preparation-guide')->getContent(), '<p>Same words.</p>'));
    }

    public function test_a_deleted_section_is_removed_from_the_page(): void
    {
        $page = $this->draftPage();
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_keep00001', 'text', ['content' => '<p>Keep me.</p>']) + $this->section('s_drop00001', 'text', ['content' => '<p>Delete me.</p>']),
            ['intent' => 'publish']
        ));

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_keep00001', 'text', ['content' => '<p>Keep me.</p>']), ['intent' => 'publish']
        ));

        $this->assertSame(['s_keep00001'], array_column($page->fresh()->sections, 'id'));
        $this->get('/hajj-preparation-guide')->assertSee('Keep me.')->assertDontSee('Delete me.');
    }

    public function test_the_section_library_returns_a_ready_to_edit_section_card(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.pages.section-form', ['type' => 'faqs']))->assertOk();

        $this->assertMatchesRegularExpression('/^s_[a-z0-9]{10}$/', $response->json('id'));
        $this->assertStringContainsString('Questions and answers', $response->json('html'));
        $this->assertStringContainsString('name="sections['.$response->json('id').'][type]" value="faqs"', $response->json('html'));

        $this->actingAs($this->admin)->getJson(route('admin.pages.section-form', ['type' => 'not-a-section']))->assertUnprocessable();
    }

    public function test_unsafe_formatted_text_is_cleaned_before_it_is_stored_and_shown(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_xss000001', 'text', ['content' => '<p onclick="steal()">Hello <script>alert(1)</script><a href="javascript:alert(1)">link</a></p><img src="x" onerror="alert(1)">']),
            ['intent' => 'publish']
        ));

        $stored = $page->fresh()->sections[0]['data']['content'];
        $this->assertStringNotContainsString('script', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);

        $html = $this->get('/hajj-preparation-guide')->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    // ------------------------------------------------------------ page details

    public function test_the_page_address_must_be_valid_unique_and_not_used_by_the_website(): void
    {
        Page::create(['title' => 'Taken', 'slug' => 'taken', 'template' => 'default', 'is_active' => true]);
        $page = $this->draftPage();

        foreach (['taken' => 'Another page already uses', 'contact' => 'already used by another part', 'Bad Address!' => 'small letters, numbers and single dashes'] as $slug => $message) {
            $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload([], ['slug' => $slug]))
                ->assertSessionHasErrors('slug');
            $this->assertStringContainsString($message, session('errors')->first('slug'));
        }
    }

    public function test_a_failed_save_keeps_everything_that_was_typed(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)
            ->from(route('admin.pages.edit', $page))
            ->put(route('admin.pages.update', $page), $this->payload(
                $this->section('s_typed0001', 'text', ['heading' => 'Typed heading', 'content' => '<p>Typed words.</p>']),
                ['title' => '']
            ))
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('title');

        $this->actingAs($this->admin)->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Typed heading')
            ->assertSee('Typed words.', false)
            ->assertSee('Give the page a title');
    }

    public function test_search_and_sharing_fields_reach_the_public_page(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_seo000001', 'text', ['content' => '<p>Guide.</p>']),
            ['intent' => 'publish', 'meta_title' => 'Hajj Guide 2027 | Universal Brothers', 'meta_description' => 'Everything to prepare.', 'og_title' => 'Share title', 'noindex' => '1']
        ))->assertSessionHasNoErrors();

        $this->get('/hajj-preparation-guide')->assertOk()
            ->assertSee('<title>Hajj Guide 2027 | Universal Brothers</title>', false)
            ->assertSee('<meta property="og:title" content="Share title">', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->get('/sitemap.xml')->assertDontSee('hajj-preparation-guide');
    }

    public function test_duplicating_a_page_creates_a_private_draft_copy(): void
    {
        $page = $this->draftPage(['is_active' => true, 'status' => 'published', 'published_at' => now()->subDay(),
            'sections' => [['id' => 's_dup000001', 'type' => 'text', 'visible' => true, 'data' => ['content' => '<p>x</p>']]]]);

        $this->actingAs($this->admin)->post(route('admin.pages.duplicate', $page));

        $copy = Page::where('slug', 'hajj-preparation-guide-copy')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertFalse($copy->is_active);
        $this->assertSame('Copy of Hajj Preparation Guide', $copy->draft['title']);
        $this->assertNotSame('s_dup000001', $copy->draft['sections'][0]['id']);
    }

    public function test_admin_can_delete_a_page(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->delete(route('admin.pages.destroy', $page))->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    // ------------------------------------------------------------ saved sections

    public function test_a_section_can_be_saved_for_reuse_from_the_builder(): void
    {
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_reuse0001', 'cta', ['heading' => 'Book your Hajj 2027', 'button_text' => 'Contact us', 'button_link' => '/contact']),
            ['intent' => 'save_block:s_reuse0001', 'block_name' => 'Hajj call to action', 'block_category' => 'call-to-action']
        ))->assertRedirect(route('admin.pages.edit', $page));

        $block = ContentBlock::where('name', 'Hajj call to action')->firstOrFail();
        $this->assertSame('cta', $block->type);
        $this->assertSame('Book your Hajj 2027', $block->data['heading']);
        $this->assertNotNull($page->fresh()->draft, 'The page draft is saved at the same time.');
    }

    public function test_a_saved_section_is_inserted_as_an_independent_copy_by_default(): void
    {
        $block = ContentBlock::create(['name' => 'Trust', 'category' => 'trust', 'type' => 'cta', 'data' => ['heading' => 'Trusted since 2004', 'button_text' => 'About us', 'button_link' => '/about-us']]);

        $html = $this->actingAs($this->admin)->getJson(route('admin.pages.section-form', ['block' => $block->id]))->assertOk()->json('html');

        $this->assertStringContainsString('value="cta"', $html);
        $this->assertStringContainsString('Trusted since 2004', $html);
        $this->assertStringNotContainsString('saved_block', $html);
    }

    public function test_only_a_super_admin_can_insert_a_linked_saved_section(): void
    {
        $block = ContentBlock::create(['name' => 'Trust', 'category' => 'trust', 'type' => 'cta', 'data' => ['heading' => 'Trusted', 'button_text' => 'Go', 'button_link' => '/']]);
        $editor = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($editor)->getJson(route('admin.pages.section-form', ['block' => $block->id, 'mode' => 'linked']))->assertForbidden();
        $this->actingAs($this->admin)->getJson(route('admin.pages.section-form', ['block' => $block->id, 'mode' => 'linked']))
            ->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'value="saved_block"'));
    }

    public function test_a_linked_saved_section_shows_its_latest_content_on_the_page(): void
    {
        $block = ContentBlock::create(['name' => 'Trust', 'category' => 'trust', 'type' => 'cta', 'data' => ['heading' => 'Old wording', 'button_text' => 'Go', 'button_link' => '/contact', 'style' => 'cream']]);
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_link00001', 'saved_block', ['block_id' => $block->id]), ['intent' => 'publish']
        ))->assertSessionHasNoErrors();

        $this->get('/hajj-preparation-guide')->assertSee('Old wording');

        $block->update(['data' => array_merge($block->data, ['heading' => 'New wording'])]);
        $this->get('/hajj-preparation-guide')->assertSee('New wording')->assertDontSee('Old wording');

        $block->update(['is_archived' => true]);
        $this->get('/hajj-preparation-guide')->assertDontSee('New wording');
    }

    // ------------------------------------------------------------ public rendering

    public function test_live_data_sections_render_from_the_admin_managed_records(): void
    {
        Faq::create(['category' => 'hajj', 'question' => 'Is Mina included?', 'answer' => '<p>Yes, <strong>always</strong>.</p>', 'sort_order' => 1, 'is_active' => true]);
        $page = $this->draftPage();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_faq000001', 'faqs', ['heading' => 'Questions', 'category' => 'all', 'count' => '5'])
            + $this->section('s_stat00001', 'stats', ['source' => 'company', 'heading' => 'Our Experience'])
            + $this->section('s_vid000001', 'video', ['heading' => 'Watch', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']),
            ['intent' => 'publish']
        ))->assertSessionHasNoErrors();

        $this->get('/hajj-preparation-guide')->assertOk()
            ->assertSee('Is Mina included?')
            ->assertSee('<strong>always</strong>', false)
            ->assertSee('Years of Experience')
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_the_published_document_matches_what_the_builder_edits(): void
    {
        $page = $this->draftPage();
        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), $this->payload(
            $this->section('s_doc000001', 'text', ['content' => '<p>Doc.</p>']), ['intent' => 'publish']
        ));

        $page->refresh();
        $this->assertSame(PageDocument::fromPublished($page)->toArray()['sections'], PageDocument::forEditing($page)->sections());
        $this->assertFalse($page->hasUnpublishedChanges());
    }
}
