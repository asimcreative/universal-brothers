<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Support\Content\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsArticleController extends Controller
{
    public function index(): View
    {
        return view('admin.news.index', ['articles' => NewsArticle::latest('published_at')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.news.form', ['article' => new NewsArticle]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('news', 'public');
        }

        NewsArticle::create($data);

        return redirect()->route('admin.news.index')->with('status', 'Article created.');
    }

    public function edit(NewsArticle $article): View
    {
        return view('admin.news.form', compact('article'));
    }

    public function update(Request $request, NewsArticle $article): RedirectResponse
    {
        $data = $this->validated($request, $article);

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('news', 'public');
        }

        $article->update($data);

        return redirect()->route('admin.news.index')->with('status', 'Article updated.');
    }

    public function destroy(NewsArticle $article): RedirectResponse
    {
        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }
        $article->delete();

        return redirect()->route('admin.news.index')->with('status', 'Article deleted.');
    }

    private function validated(Request $request, ?NewsArticle $article): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:200000'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        // Formatted text from the editor is cleaned before it is stored.
        $data['body'] = RichText::clean($data['body'] ?? null, 'full');

        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = $article?->slug ?? Str::slug($data['title']).'-'.Str::random(6);
        $data['published_at'] = $data['published_at'] ?? now();
        unset($data['cover_image']);

        return $data;
    }
}
