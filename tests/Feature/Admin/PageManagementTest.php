<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_guest_cannot_access_page_admin(): void
    {
        $response = $this->get('/admin/pages');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_a_page(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/pages', [
            'title' => 'About Us',
            'slug' => 'about-us',
            'body' => '<p>Real company history.</p>',
            'template' => 'about',
            'is_active' => '1',
            'meta_title' => 'About Us | Universal Brothers',
            'meta_description' => 'Company history.',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('pages', ['slug' => 'about-us', 'title' => 'About Us', 'is_active' => true]);
        $this->assertNotNull(Page::where('slug', 'about-us')->first()->published_at);
    }

    public function test_admin_can_update_a_page_and_unpublish_it(): void
    {
        $page = Page::create([
            'title' => 'About Us', 'slug' => 'about-us', 'body' => 'Old', 'template' => 'about',
            'is_active' => true, 'published_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/pages/{$page->id}", [
            'title' => 'About Us Updated',
            'slug' => 'about-us',
            'body' => 'New body',
            'template' => 'about',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertSame('About Us Updated', $page->title);
        $this->assertFalse((bool) $page->is_active);
        $this->assertNull($page->published_at);
    }

    public function test_page_slug_must_be_unique(): void
    {
        Page::create(['title' => 'About Us', 'slug' => 'about-us', 'template' => 'about', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post('/admin/pages', [
            'title' => 'Duplicate', 'slug' => 'about-us', 'template' => 'default', 'is_active' => '0',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_delete_a_page(): void
    {
        $page = Page::create(['title' => 'Temp', 'slug' => 'temp-page', 'template' => 'default', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->delete("/admin/pages/{$page->id}");

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
