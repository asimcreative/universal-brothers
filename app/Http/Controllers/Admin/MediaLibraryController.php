<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The image picker's server side: list the library, upload a new image, and
 * save an image's description (alt text), title and caption.
 *
 * Uploads are images only (JPG, PNG, WebP, GIF). SVG is refused because it can
 * carry script; every file is re-checked as a real image, stored under a random
 * name the uploader cannot influence, and recorded with its true type and size.
 */
class MediaLibraryController extends Controller
{
    public const MAX_KB = 5120;

    public const MAX_PIXELS = 8000;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'collection' => ['nullable', Rule::in(array_keys(MediaItem::COLLECTIONS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = MediaItem::query()->images()->latest('id');

        if (filled($validated['q'] ?? null)) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $validated['q']).'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('alt_text', 'like', $term)
                ->orWhere('original_name', 'like', $term)->orWhere('caption', 'like', $term));
        }

        if (filled($validated['collection'] ?? null)) {
            $query->where('collection', $validated['collection']);
        }

        $page = $query->paginate(24);

        return response()->json([
            'items' => collect($page->items())->map->toPickerArray(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
            'total' => $page->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:'.self::MAX_KB,
                'dimensions:min_width=40,min_height=40,max_width='.self::MAX_PIXELS.',max_height='.self::MAX_PIXELS],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
        ], [
            'file.required' => 'Choose an image to upload.',
            'file.uploaded' => 'The image could not be uploaded. It may be larger than the server allows — try a smaller file (under 5 MB).',
            'file.image' => 'This file is not an image. Choose a JPG, PNG, WebP or GIF photo.',
            'file.mimes' => 'This type of file cannot be used. Choose a JPG, PNG, WebP or GIF image.',
            'file.max' => 'This image is too large. Choose one smaller than 5 MB — photos from a phone can usually be shared at a smaller size.',
            'file.dimensions' => 'This image is too small or too large. It must be between 40 and '.number_format(self::MAX_PIXELS).' pixels wide and tall.',
            'alt_text.max' => 'The description is too long. One clear sentence (under 255 characters) is enough.',
        ]);

        $file = $request->file('file');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        if (! $width || ! $height) {
            return response()->json(['message' => 'This file could not be read as an image. Try saving it again as a JPG or PNG.', 'errors' => ['file' => ['This file could not be read as an image.']]], 422);
        }

        // hashName() picks the extension from the detected content type, not from
        // the name the uploader gave the file.
        $path = $file->store('media/library', 'public');
        $original = Str::limit(preg_replace('/[^\w .()-]/u', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image', 120, '');

        $item = MediaItem::create([
            'media_type' => 'image',
            'gallery_type' => 'gallery',
            'collection' => 'library',
            'title' => $validated['title'] ?? $original,
            'alt_text' => $validated['alt_text'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'file_path' => $path,
            'original_name' => $original.'.'.$file->extension(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'sort_order' => 0,
            'is_active' => true,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['item' => $item->toPickerArray(), 'message' => 'Image uploaded.'], 201);
    }

    public function update(Request $request, MediaItem $item): JsonResponse
    {
        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
        ], [
            'alt_text.max' => 'The description is too long. One clear sentence (under 255 characters) is enough.',
        ]);

        $item->update($validated);

        return response()->json(['item' => $item->fresh()->toPickerArray(), 'message' => 'Image details saved.']);
    }
}
