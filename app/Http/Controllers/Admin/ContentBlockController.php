<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\ContentBlock;
use App\Support\PageBuilder\BlockRegistry;
use App\Support\PageBuilder\PageRenderer;
use App\Support\PageBuilder\SectionValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Saved sections: page-builder sections kept for reuse.
 *
 * Inserting one into a page makes an independent copy by default. A super
 * admin may instead insert it "linked", so the page always shows the saved
 * section's current content — which is why editing a linked saved section says
 * which pages will change, and why one that pages link to cannot be deleted.
 *
 * Changing a linked saved section changes live pages without anyone pressing
 * Publish, so it needs the same permission as linking one in the first place.
 */
class ContentBlockController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status') === 'archived' ? 'archived' : 'active';
        $category = array_key_exists((string) $request->query('category'), ContentBlock::CATEGORIES) ? $request->query('category') : null;
        $search = trim((string) $request->query('q'));

        $blocks = ContentBlock::query()
            ->with('editor:id,name')
            ->where('is_archived', $status === 'archived')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.content-blocks.index', [
            'blocks' => $blocks,
            'status' => $status,
            'category' => $category,
            'search' => $search,
            'counts' => ['active' => ContentBlock::active()->count(), 'archived' => ContentBlock::where('is_archived', true)->count()],
            'linkedCounts' => $blocks->getCollection()->mapWithKeys(fn (ContentBlock $b) => [$b->id => $b->linkedPages()->count()]),
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->query('type');

        if (! $type || ! BlockRegistry::find($type) || ! empty(BlockRegistry::find($type)['internal'])) {
            return view('admin.content-blocks.choose', ['groups' => BlockRegistry::grouped()]);
        }

        return view('admin.content-blocks.form', [
            'block' => new ContentBlock(['type' => $type, 'category' => 'general', 'data' => BlockRegistry::defaults($type)]),
            'linkedPages' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $data] = $this->validated($request, null);

        $block = ContentBlock::create($attributes + [
            'data' => $data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        AdminActivity::record('content_block_created', $block, "Created the saved section \"{$block->name}\".");

        return redirect()->route('admin.content-blocks.edit', $block)->with('status', "Saved section \"{$block->name}\" created. Insert it into any page from the page builder's Add section → Saved sections.");
    }

    public function edit(ContentBlock $block): View
    {
        return view('admin.content-blocks.form', ['block' => $block, 'linkedPages' => $block->linkedPages()]);
    }

    public function update(Request $request, ContentBlock $block): RedirectResponse
    {
        $this->ensureCanChangeLinked($request, $block, 'change');

        [$attributes, $data] = $this->validated($request, $block);

        $block->update($attributes + ['data' => $data, 'updated_by' => $request->user()->id]);
        $linked = $block->linkedPages()->count();

        AdminActivity::record('content_block_updated', $block, "Updated the saved section \"{$block->name}\".");

        return redirect()->route('admin.content-blocks.edit', $block)->with('status', $linked
            ? "Saved. The {$linked} ".str('page')->plural($linked).' linked to this section now show the new content.'
            : 'Saved section updated. Copies already inserted into pages are not changed.');
    }

    public function preview(ContentBlock $block): Response
    {
        $sections = app(PageRenderer::class)->prepare([
            ['id' => 'preview', 'type' => $block->type, 'visible' => true, 'data' => array_replace(BlockRegistry::defaults($block->type), (array) $block->data)],
        ]);

        return response(view('admin.content-blocks.preview', ['block' => $block, 'sections' => $sections]))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function duplicate(Request $request, ContentBlock $block): RedirectResponse
    {
        $copy = $block->replicate(['created_by', 'updated_by']);
        $copy->name = mb_substr('Copy of '.$block->name, 0, 120);
        $copy->is_archived = false;
        $copy->created_by = $request->user()->id;
        $copy->updated_by = $request->user()->id;
        $copy->save();

        return redirect()->route('admin.content-blocks.edit', $copy)->with('status', 'A copy was created. Rename it so it is easy to tell apart.');
    }

    public function archive(Request $request, ContentBlock $block): RedirectResponse
    {
        $this->ensureCanChangeLinked($request, $block, 'archive');

        $block->update(['is_archived' => true, 'updated_by' => $request->user()->id]);

        return redirect()->route('admin.content-blocks.index')->with('status', "\"{$block->name}\" was archived. It is no longer offered when adding sections, and linked pages stop showing it.");
    }

    public function restore(Request $request, ContentBlock $block): RedirectResponse
    {
        $this->ensureCanChangeLinked($request, $block, 'restore');

        $block->update(['is_archived' => false, 'updated_by' => $request->user()->id]);

        return redirect()->route('admin.content-blocks.index', ['status' => 'archived'])->with('status', "\"{$block->name}\" was restored.");
    }

    public function destroy(ContentBlock $block): RedirectResponse
    {
        $linked = $block->linkedPages();

        if ($linked->isNotEmpty()) {
            return back()->with('status', "\"{$block->name}\" cannot be deleted because {$linked->count()} ".str('page')->plural($linked->count()).' link to it: '.$linked->pluck('title')->implode(', ').'. Remove the linked sections from those pages first, or archive it.');
        }

        $name = $block->name;
        $block->delete();
        AdminActivity::record('content_block_deleted', null, "Deleted the saved section \"{$name}\".");

        return redirect()->route('admin.content-blocks.index')->with('status', "\"{$name}\" was deleted. Copies already inserted into pages are not affected.");
    }

    private function ensureCanChangeLinked(Request $request, ContentBlock $block, string $action): void
    {
        abort_if(
            $request->user()->cannot('link-saved-sections') && $block->linkedPages()->isNotEmpty(),
            403,
            "Only a super admin can {$action} a saved section that pages link to, because the change shows on those pages straight away. Duplicate it to make your own copy instead."
        );
    }

    /** @return array{0: array, 1: array} attributes and cleaned section data */
    private function validated(Request $request, ?ContentBlock $block): array
    {
        $type = $block?->type ?? (string) $request->input('type');

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', Rule::in(array_keys(ContentBlock::CATEGORIES))],
            'type' => [$block ? 'nullable' : 'required', Rule::in(array_keys(BlockRegistry::libraryTypes()))],
            'data' => ['nullable', 'array'],
        ], [
            'name.required' => 'Give the saved section a name so you can find it later.',
        ]);

        // Saved sections can appear on live pages the moment they are saved
        // (linked sections), so they are checked as strictly as publishing.
        $result = (new SectionValidator(true))->validateBlock($type, (array) $request->input('data', []));

        if ($result['errors'] !== []) {
            throw ValidationException::withMessages($result['errors']);
        }

        unset($attributes['data']);
        $attributes['type'] = $type;

        return [$attributes, $result['data']];
    }
}
