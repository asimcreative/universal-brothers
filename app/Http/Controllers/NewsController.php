<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function show(string $slug): View
    {
        $article = NewsArticle::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('news.show', compact('article'));
    }
}
