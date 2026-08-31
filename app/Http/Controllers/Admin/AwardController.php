<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AwardController extends Controller
{
    public function index(): View
    {
        return view('admin.awards.index', ['awards' => Award::orderBy('sort_order')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.awards.form', ['award' => new Award]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('awards', 'public');
        }

        Award::create($data);

        return redirect()->route('admin.awards.index')->with('status', 'Award created.');
    }

    public function edit(Award $award): View
    {
        return view('admin.awards.form', compact('award'));
    }

    public function update(Request $request, Award $award): RedirectResponse
    {
        $data = $this->validated($request, $award);

        if ($request->hasFile('image')) {
            if ($award->image) {
                Storage::disk('public')->delete($award->image);
            }
            $data['image'] = $request->file('image')->store('awards', 'public');
        }

        $award->update($data);

        return redirect()->route('admin.awards.index')->with('status', 'Award updated.');
    }

    public function destroy(Award $award): RedirectResponse
    {
        if ($award->image) {
            Storage::disk('public')->delete($award->image);
        }
        $award->delete();

        return redirect()->route('admin.awards.index')->with('status', 'Award deleted.');
    }

    private function validated(Request $request, ?Award $award): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('awards', 'name')->ignore($award?->id)],
            'awarding_organization' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'string', 'max:9'],
            'image' => ['nullable', 'image', 'max:4096'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        unset($data['image']);

        return $data;
    }
}
