<?php

namespace Tests\Feature\Admin;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaItemManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_guest_cannot_access_media_admin(): void
    {
        $this->get('/admin/media')->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_an_image_media_item_leaving_sort_order_blank(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post('/admin/media', [
            'media_type' => 'image',
            'gallery_type' => 'gallery',
            'title' => 'Mina Camp Aerial View',
            'file_path' => UploadedFile::fake()->image('mina.jpg'),
            'sort_order' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.media.index'));
        $this->assertDatabaseHas('media_items', ['title' => 'Mina Camp Aerial View', 'sort_order' => 0]);
    }

    public function test_admin_can_create_a_video_media_item_without_a_file_upload(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/media', [
            'media_type' => 'video',
            'gallery_type' => 'promo',
            'title' => 'Hajj Orientation Video',
            'video_url' => 'https://www.youtube.com/watch?v=example',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.media.index'));
        $this->assertDatabaseHas('media_items', ['title' => 'Hajj Orientation Video', 'video_url' => 'https://www.youtube.com/watch?v=example']);
    }

    public function test_creating_an_image_item_without_a_file_fails_validation(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/media', [
            'media_type' => 'image',
            'gallery_type' => 'gallery',
        ]);

        $response->assertSessionHasErrors('file_path');
    }

    public function test_admin_can_update_and_delete_a_media_item(): void
    {
        Storage::fake('public');
        $item = MediaItem::create([
            'media_type' => 'image', 'gallery_type' => 'gallery', 'file_path' => 'media/old.jpg',
            'sort_order' => 0, 'is_active' => true,
        ]);

        $update = $this->actingAs($this->admin)->put("/admin/media/{$item->id}", [
            'media_type' => 'image', 'gallery_type' => 'event', 'title' => 'Updated Title', 'sort_order' => '', 'is_active' => '1',
        ]);
        $update->assertRedirect(route('admin.media.index'));
        $this->assertDatabaseHas('media_items', ['id' => $item->id, 'title' => 'Updated Title', 'gallery_type' => 'event']);

        $delete = $this->actingAs($this->admin)->delete("/admin/media/{$item->id}");
        $delete->assertRedirect(route('admin.media.index'));
        $this->assertDatabaseMissing('media_items', ['id' => $item->id]);
    }
}
