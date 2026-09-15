<?php

namespace Tests\Feature\Admin;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The Saved Sections screens: create, edit, preview, duplicate, archive and delete. */
class ContentBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function block(array $data = []): ContentBlock
    {
        return ContentBlock::create([
            'name' => 'Hajj call to action', 'category' => 'call-to-action', 'type' => 'cta',
            'data' => array_merge(['heading' => 'Book your Hajj 2027', 'button_text' => 'Contact us', 'button_link' => '/contact', 'style' => 'cream'], $data),
        ]);
    }

    private function linkedPage(ContentBlock $block): Page
    {
        return Page::create([
            'title' => 'Hajj Guide', 'slug' => 'hajj-guide', 'template' => 'default', 'status' => 'published', 'is_active' => true, 'published_at' => now()->subDay(),
            'sections' => [['id' => 's_linked0001', 'type' => 'saved_block', 'visible' => true, 'data' => ['block_id' => $block->id]]],
        ]);
    }

    public function test_guests_cannot_reach_saved_sections(): void
    {
        $this->get(route('admin.content-blocks.index'))->assertRedirect('/admin/login');
        $this->post(route('admin.content-blocks.store'), [])->assertRedirect('/admin/login');
    }

    public function test_choosing_a_kind_then_creating_a_saved_section(): void
    {
        $this->actingAs($this->admin)->get(route('admin.content-blocks.create'))->assertOk()->assertSee('Call-to-action banner');
        $this->actingAs($this->admin)->get(route('admin.content-blocks.create', ['type' => 'cta']))->assertOk()->assertSee('Button link');

        $this->actingAs($this->admin)->post(route('admin.content-blocks.store'), [
            'name' => 'Umrah call to action', 'category' => 'umrah', 'type' => 'cta',
            'data' => ['heading' => 'Plan your Umrah', 'button_text' => 'Umrah services', 'button_link' => '/umrah-services', 'style' => 'navy'],
        ])->assertRedirect();

        $block = ContentBlock::where('name', 'Umrah call to action')->firstOrFail();
        $this->assertSame('Plan your Umrah', $block->data['heading']);
    }

    public function test_a_saved_section_is_checked_as_strictly_as_publishing(): void
    {
        $this->actingAs($this->admin)->post(route('admin.content-blocks.store'), [
            'name' => 'Broken', 'category' => 'general', 'type' => 'cta',
            'data' => ['heading' => '', 'button_text' => 'Go', 'button_link' => 'not a link'],
        ])->assertSessionHasErrors(['data.heading', 'data.button_link']);

        $this->assertSame(0, ContentBlock::count());
    }

    public function test_editing_a_linked_saved_section_warns_and_updates_the_live_page(): void
    {
        $block = $this->block();
        $this->linkedPage($block);

        $this->actingAs($this->admin)->get(route('admin.content-blocks.edit', $block))
            ->assertOk()
            ->assertSee('Linked on 1 page')
            ->assertSee('Hajj Guide');

        $this->actingAs($this->admin)->put(route('admin.content-blocks.update', $block), [
            'name' => $block->name, 'category' => $block->category,
            'data' => array_merge($block->data, ['heading' => 'Hajj 2027 bookings now open']),
        ])->assertSessionHas('status', fn ($s) => str_contains($s, '1 page linked'));

        $this->get('/hajj-guide')->assertSee('Hajj 2027 bookings now open');
    }

    public function test_a_linked_saved_section_cannot_be_deleted_but_can_be_archived(): void
    {
        $block = $this->block();
        $this->linkedPage($block);

        $this->actingAs($this->admin)->delete(route('admin.content-blocks.destroy', $block))->assertSessionHas('status', fn ($s) => str_contains($s, 'cannot be deleted'));
        $this->assertModelExists($block);

        $this->actingAs($this->admin)->patch(route('admin.content-blocks.archive', $block))->assertRedirect(route('admin.content-blocks.index'));
        $this->assertTrue($block->fresh()->is_archived);
        $this->get('/hajj-guide')->assertDontSee('Book your Hajj 2027');
    }

    public function test_only_a_super_admin_can_change_a_saved_section_that_pages_link_to(): void
    {
        $block = $this->block();
        $this->linkedPage($block);
        $admin = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.content-blocks.edit', $block))
            ->assertOk()
            ->assertSee('only a super admin can change it');

        $this->actingAs($admin)->put(route('admin.content-blocks.update', $block), [
            'name' => $block->name, 'category' => $block->category,
            'data' => array_merge($block->data, ['heading' => 'Changed without publishing']),
        ])->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.content-blocks.archive', $block))->assertForbidden();

        $this->assertSame('Book your Hajj 2027', $block->fresh()->data['heading']);
        $this->assertFalse($block->fresh()->is_archived);
        $this->get('/hajj-guide')->assertSee('Book your Hajj 2027');

        // An unlinked saved section is theirs to change, and duplicating a linked one is allowed.
        $own = $this->block(['heading' => 'Umrah offer']);
        $this->actingAs($admin)->put(route('admin.content-blocks.update', $own), [
            'name' => 'Umrah offer', 'category' => 'umrah',
            'data' => array_merge($own->data, ['heading' => 'Umrah 2027 offer']),
        ])->assertRedirect();
        $this->assertSame('Umrah 2027 offer', $own->fresh()->data['heading']);
        $this->actingAs($admin)->post(route('admin.content-blocks.duplicate', $block))->assertRedirect();
    }

    public function test_an_unused_saved_section_can_be_deleted_and_duplicated(): void
    {
        $block = $this->block();

        $this->actingAs($this->admin)->post(route('admin.content-blocks.duplicate', $block));
        $this->assertDatabaseHas('content_blocks', ['name' => 'Copy of Hajj call to action']);

        $this->actingAs($this->admin)->delete(route('admin.content-blocks.destroy', $block))->assertRedirect(route('admin.content-blocks.index'));
        $this->assertModelMissing($block);
    }

    public function test_the_preview_shows_the_section_in_the_site_design(): void
    {
        $block = $this->block();

        $this->actingAs($this->admin)->get(route('admin.content-blocks.preview', $block))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Book your Hajj 2027')
            ->assertSee('pb-cta', false);
    }
}
