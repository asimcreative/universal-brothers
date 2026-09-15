<?php

namespace Tests\Feature\Admin;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The image picker's upload, library listing and image descriptions. */
class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);
    }

    public function test_an_admin_can_upload_an_image_with_its_description(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'file' => UploadedFile::fake()->image('mina camp.jpg', 1600, 900),
            'alt_text' => 'Rows of white tents in Mina',
            'caption' => 'Mina, Hajj 2025',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('item.alt', 'Rows of white tents in Mina')
            ->assertJsonPath('item.width', 1600)
            ->assertJsonPath('item.collection', 'library');

        $item = MediaItem::firstOrFail();
        Storage::disk('public')->assertExists($item->file_path);
        $this->assertStringStartsWith('media/library/', $item->file_path);
        $this->assertStringNotContainsString('mina camp', $item->file_path, 'Stored under a random name, not the uploaded one.');
        $this->assertSame($this->admin->id, $item->uploaded_by);
        $this->assertStringStartsWith('/storage/media/library/', $response->json('item.url'));
    }

    public function test_unsafe_or_unsuitable_files_are_refused_in_plain_words(): void
    {
        $cases = [
            [UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'), 'Choose a JPG, PNG, WebP or GIF'],
            [UploadedFile::fake()->create('shell.php', 5, 'application/x-php'), 'not an image'],
            [UploadedFile::fake()->image('huge.jpg', 1200, 800)->size(6000), 'smaller than 5 MB'],
            [UploadedFile::fake()->image('tiny.png', 10, 10), 'too small or too large'],
        ];

        foreach ($cases as [$file, $message]) {
            $response = $this->actingAs($this->admin)->postJson(route('admin.media-library.store'), ['file' => $file]);
            $response->assertUnprocessable();
            $this->assertStringContainsString($message, implode(' ', $response->json('errors.file')));
        }

        $this->assertSame(0, MediaItem::count());
    }

    public function test_the_library_lists_and_searches_images_and_descriptions_can_be_updated(): void
    {
        $kaaba = MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'library', 'title' => 'Kaaba at night', 'file_path' => 'media/library/a.jpg', 'is_active' => true]);
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'gallery', 'title' => 'Madinah', 'file_path' => 'media/b.jpg', 'is_active' => true]);
        MediaItem::create(['media_type' => 'video', 'gallery_type' => 'gallery', 'collection' => 'gallery', 'title' => 'Video', 'video_url' => 'https://www.youtube.com/embed/x', 'is_active' => true]);

        $this->actingAs($this->admin)->getJson(route('admin.media-library.index'))->assertOk()->assertJsonCount(2, 'items');
        $this->actingAs($this->admin)->getJson(route('admin.media-library.index', ['q' => 'kaaba']))->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.title', 'Kaaba at night');

        $this->actingAs($this->admin)->patchJson(route('admin.media-library.update', $kaaba), ['alt_text' => 'The Kaaba lit at night'])
            ->assertOk()->assertJsonPath('item.alt', 'The Kaaba lit at night');
    }

    public function test_images_uploaded_for_pages_never_appear_in_the_public_gallery(): void
    {
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'gallery', 'title' => 'Gallery photo', 'file_path' => 'media/g.jpg', 'is_active' => true]);
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'library', 'title' => 'Page photo', 'file_path' => 'media/library/p.jpg', 'is_active' => true]);

        $gallery = $this->get(route('media'))->assertOk()->viewData('gallery');

        $this->assertSame(['Gallery photo'], $gallery->pluck('title')->all());
    }

    public function test_guests_cannot_list_or_upload(): void
    {
        $this->getJson(route('admin.media-library.index'))->assertUnauthorized();
        $this->postJson(route('admin.media-library.store'), ['file' => UploadedFile::fake()->image('a.jpg')])->assertUnauthorized();
        $this->assertSame(0, MediaItem::count());
    }

    public function test_the_gallery_admin_shows_descriptions_and_the_uploaded_tab(): void
    {
        MediaItem::create(['media_type' => 'image', 'gallery_type' => 'gallery', 'collection' => 'library', 'title' => 'Page photo', 'file_path' => 'media/library/p.jpg', 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.media.index', ['collection' => 'library']))
            ->assertOk()
            ->assertSee('Uploaded for pages and text')
            ->assertSee('Page photo')
            ->assertSee('No description');
    }
}
