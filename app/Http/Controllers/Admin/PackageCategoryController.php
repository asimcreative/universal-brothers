<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PackageCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => PackageCategory::withCount('series', 'packages')->orderBy('sort_order')->get(),
        ]);
    }

    public function edit(PackageCategory $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category->load('series'),
        ]);
    }

    public function update(Request $request, PackageCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $category->update($data);

        return back()->with('status', 'Category updated.');
    }

    public function storeSeries(Request $request, PackageCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category->series()->create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'sort_order' => $category->series()->count() + 1,
            'is_active' => true,
        ]);

        return back()->with('status', 'Series added.');
    }

    public function destroySeries(PackageCategory $category, PackageSeries $series): RedirectResponse
    {
        abort_unless($series->package_category_id === $category->id, 404);
        $series->delete();

        return back()->with('status', 'Series removed.');
    }
}
