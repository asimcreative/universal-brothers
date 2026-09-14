<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LibraryItemRequest;
use App\Models\AdminActivity;
use App\Models\Hotel;
use App\Support\Library\LibraryRegistry;
use App\Support\Library\LibraryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Every section of the reusable package library — hotels, meal plans,
 * transport, included and not-included services, additional options, Mashaer
 * arrangements, notes and journey templates — through one controller driven
 * by LibraryRegistry, so they all list, validate, archive and protect in-use
 * records the same way.
 */
class LibraryController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $library = $this->type($type);
        $status = $request->input('status', 'active');

        $query = $library->query()
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'archived', fn ($q) => $q->where('is_active', false))
            ->when($request->filled('q'), function ($q) use ($request, $library) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(function ($inner) use ($library, $term) {
                    foreach ($library->search as $column) {
                        $inner->orWhere($column, 'like', $term);
                    }
                });
            });

        foreach ($library->filters as $column => $filter) {
            if ($request->filled($column) && array_key_exists($request->input($column), $filter['options'])) {
                $query->where($column, $request->input($column));
            }
        }

        if ($library->tracksUsage()) {
            // Distinct live packages, matching the "Where it is used" page — a
            // hotel used for both Option A and B of one package is one use,
            // and a deleted package is not a use.
            $query->withCount([$library->usageRelation.' as usage_count' => fn ($q) => $q
                ->whereHas('package')
                ->select(DB::raw('count(distinct package_id)'))]);
        }

        $records = $query->ordered()->paginate(25)->withQueryString();

        return view('admin.library.index', [
            'library' => $library,
            'records' => $records,
            'status' => $status,
            'counts' => [
                'active' => $library->query()->where('is_active', true)->count(),
                'archived' => $library->query()->where('is_active', false)->count(),
            ],
            'types' => LibraryRegistry::all(),
        ]);
    }

    public function create(string $type): View
    {
        $library = $this->type($type);

        return view('admin.library.form', ['library' => $library, 'record' => $library->newRecord(), 'types' => LibraryRegistry::all()]);
    }

    public function store(LibraryItemRequest $request, string $type): RedirectResponse|JsonResponse
    {
        $library = $this->type($type);
        $record = $library->newRecord();
        $record->fill($library->attributesFrom($request->validated()));
        $this->applyImages($library, $record, $request);
        $this->fillDerivedColumns($library, $record);
        $record->save();

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $record->getKey(),
                'title' => $record->libraryTitle(),
                'record' => $record->toArray(),
                'message' => ucfirst($library->singular)." \"{$record->libraryTitle()}\" added to the library.",
            ], 201);
        }

        return redirect()->route('admin.library.index', $type)
            ->with('status', ucfirst($library->singular)." \"{$record->libraryTitle()}\" was added.");
    }

    public function edit(string $type, int $id): View
    {
        $library = $this->type($type);
        $record = $library->query()->findOrFail($id);

        return view('admin.library.form', [
            'library' => $library,
            'record' => $record,
            'types' => LibraryRegistry::all(),
            'usageCount' => $library->tracksUsage() ? $library->packagesUsing($record)->count() : null,
        ]);
    }

    public function update(LibraryItemRequest $request, string $type, int $id): RedirectResponse
    {
        $library = $this->type($type);
        $record = $library->query()->findOrFail($id);
        $record->fill($library->attributesFrom($request->validated()));
        $this->applyImages($library, $record, $request);
        $this->fillDerivedColumns($library, $record);
        $record->save();

        $usage = $library->tracksUsage() ? $library->packagesUsing($record)->count() : 0;
        $message = ucfirst($library->singular).' saved.';

        if ($usage > 0 && $library->canPushToPackages()) {
            $message .= " {$usage} ".str('package')->plural($usage).' still show the previous details. Open "Where it is used" to update them.';
        }

        return redirect()->route('admin.library.edit', [$type, $id])->with('status', $message);
    }

    /** The packages that use a record, and the update-them action. */
    public function usage(string $type, int $id): View
    {
        $library = $this->type($type);
        abort_unless($library->tracksUsage(), 404);
        $record = $library->query()->findOrFail($id);

        return view('admin.library.usage', [
            'library' => $library,
            'record' => $record,
            'packages' => $library->packagesUsing($record)->get(),
            'types' => LibraryRegistry::all(),
        ]);
    }

    public function push(string $type, int $id): RedirectResponse
    {
        $library = $this->type($type);
        abort_unless($library->canPushToPackages(), 404);
        $record = $library->query()->findOrFail($id);

        $result = $library->pushToPackages($record);

        AdminActivity::record('library_pushed', $record, "Updated {$result['packages']} ".str('package')->plural($result['packages'])." with the latest details of the {$library->singular} \"{$record->libraryTitle()}\".");

        return back()->with('status', "Updated {$result['packages']} ".str('package')->plural($result['packages'])." with the latest details of \"{$record->libraryTitle()}\".");
    }

    public function duplicate(string $type, int $id): RedirectResponse
    {
        $library = $this->type($type);
        $record = $library->query()->findOrFail($id);

        $copy = $record->replicate(['cover_image']);
        $copy->{$library->titleColumn()} = str($record->libraryTitle())->limit(240, '').' (Copy)';
        $this->fillDerivedColumns($library, $copy);
        $copy->save();

        return redirect()->route('admin.library.edit', [$type, $copy->getKey()])
            ->with('status', 'A copy was created. Change what is different and save.');
    }

    public function archive(string $type, int $id): RedirectResponse
    {
        return $this->setActive($type, $id, false);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        return $this->setActive($type, $id, true);
    }

    /**
     * Deleting is only possible for a record no package uses. A used record is
     * archived instead: it leaves the pickers, and the packages keep their
     * content and their link.
     */
    public function destroy(string $type, int $id): RedirectResponse
    {
        $library = $this->type($type);
        $record = $library->query()->findOrFail($id);

        if ($library->tracksUsage() && ($usage = $library->packagesUsing($record)->count()) > 0) {
            return back()->withErrors(['record' => "\"{$record->libraryTitle()}\" is used in {$usage} ".str('package')->plural($usage).', so it cannot be deleted. Archive it instead — the packages keep their details.']);
        }

        foreach ($library->fields as $field) {
            if ($field['type'] === 'image' && $record->{$field['name']}) {
                Storage::disk('public')->delete($record->{$field['name']});
            }
        }

        $record->delete();
        AdminActivity::record('library_deleted', null, 'Deleted the '.$library->singular." \"{$record->libraryTitle()}\".");

        return redirect()->route('admin.library.index', $type)->with('status', "Deleted \"{$record->libraryTitle()}\".");
    }

    private function setActive(string $type, int $id, bool $active): RedirectResponse
    {
        $library = $this->type($type);
        $record = $library->query()->findOrFail($id);
        $record->forceFill(['is_active' => $active])->save();

        return back()->with('status', $active
            ? "\"{$record->libraryTitle()}\" was restored and can be picked in packages again."
            : "\"{$record->libraryTitle()}\" was archived. Packages that already use it are not changed.");
    }

    private function applyImages(LibraryType $library, Model $record, Request $request): void
    {
        foreach ($library->fields as $field) {
            if ($field['type'] !== 'image') {
                continue;
            }

            $name = $field['name'];

            if ($request->hasFile($name)) {
                if ($record->{$name}) {
                    Storage::disk('public')->delete($record->{$name});
                }
                $record->{$name} = $request->file($name)->store("library/{$library->key}", 'public');
            } elseif ($request->boolean("remove_{$name}") && $record->{$name}) {
                Storage::disk('public')->delete($record->{$name});
                $record->{$name} = null;
            }
        }
    }

    /** Columns a record needs that the admin is never asked for. */
    private function fillDerivedColumns(LibraryType $library, Model $record): void
    {
        if ($library->key === 'hotels') {
            $record->city = Hotel::LOCATIONS[$record->location] ?? 'Other';

            if (blank($record->slug) || $record->isDirty('name')) {
                $base = str($record->name)->slug()->limit(200, '')->toString() ?: 'hotel';
                $slug = $base;
                $i = 2;
                while (Hotel::where('slug', $slug)->when($record->exists, fn ($q) => $q->where('id', '!=', $record->id))->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $record->slug = $slug;
            }
        }

        if ($library->key === 'journey-templates' && $record->days === null) {
            $record->days = [];
        }
    }

    private function type(string $key): LibraryType
    {
        return LibraryRegistry::find($key) ?? abort(404);
    }
}
