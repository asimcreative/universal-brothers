<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaItemController extends Controller
{
    public function index(Request $request): View
    {
        $collection = array_key_exists((string) $request->query('collection'), MediaItem::COLLECTIONS) ? $request->query('collection') : 'gallery';

        return view('admin.media.index', [
            'collection' => $collection,
            'counts' => MediaItem::selectRaw('collection, count(*) as total')->groupBy('collection')->pluck('total', 'collection'),
            'items' => MediaItem::where('collection', $collection)
                ->when($collection === 'gallery', fn ($q) => $q->orderBy('gallery_type')->orderBy('sort_order'), fn ($q) => $q->latest('id'))
                ->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.media.form', ['item' => new MediaItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        if ($request->hasFile('file_path')) {
            $data += $this->fileDetails($request);
        }

        MediaItem::create($data + ['collection' => 'gallery', 'uploaded_by' => $request->user()->id]);

        return redirect()->route('admin.media.index')->with('status', 'Media item created.');
    }

    public function edit(MediaItem $item): View
    {
        return view('admin.media.form', compact('item'));
    }

    public function update(Request $request, MediaItem $item): RedirectResponse
    {
        $data = $this->validated($request, $item);

        if ($request->hasFile('file_path')) {
            if ($item->file_path) {
                Storage::disk('public')->delete($item->file_path);
            }
            $data += $this->fileDetails($request);
        }

        $item->update($data);

        return redirect()->route('admin.media.index', $item->collection === 'library' ? ['collection' => 'library'] : [])->with('status', 'Media item updated.');
    }

    public function destroy(MediaItem $item): RedirectResponse
    {
        if ($item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }
        $item->delete();

        return redirect()->route('admin.media.index', $item->collection === 'library' ? ['collection' => 'library'] : [])->with('status', 'Media item deleted.');
    }

    /** Stored path plus the file's real type, size and dimensions. */
    private function fileDetails(Request $request): array
    {
        $file = $request->file('file_path');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return [
            'file_path' => $file->store('media', 'public'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function validated(Request $request, ?MediaItem $item): array
    {
        $data = $request->validate([
            'media_type' => ['required', 'in:image,video'],
            'gallery_type' => ['required', 'in:gallery,event,promo'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'file_path' => [$item || $request->input('media_type') === 'video' ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        unset($data['file_path']);

        return $data;
    }
}
