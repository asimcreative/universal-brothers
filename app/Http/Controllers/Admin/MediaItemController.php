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
    public function index(): View
    {
        return view('admin.media.index', ['items' => MediaItem::orderBy('gallery_type')->orderBy('sort_order')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.media.form', ['item' => new MediaItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        if ($request->hasFile('file_path')) {
            $data['file_path'] = $request->file('file_path')->store('media', 'public');
        }

        MediaItem::create($data);

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
            $data['file_path'] = $request->file('file_path')->store('media', 'public');
        }

        $item->update($data);

        return redirect()->route('admin.media.index')->with('status', 'Media item updated.');
    }

    public function destroy(MediaItem $item): RedirectResponse
    {
        if ($item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }
        $item->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media item deleted.');
    }

    private function validated(Request $request, ?MediaItem $item): array
    {
        $data = $request->validate([
            'media_type' => ['required', 'in:image,video'],
            'gallery_type' => ['required', 'in:gallery,event,promo'],
            'title' => ['nullable', 'string', 'max:255'],
            'file_path' => [$item || $request->input('media_type') === 'video' ? 'nullable' : 'required', 'image', 'max:8192'],
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
