<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Models\NewsArticle;
use Illuminate\View\View;

class MediaPageController extends Controller
{
    public function index(): View
    {
        $news = NewsArticle::where('is_active', true)->latest('published_at')->limit(6)->get();
        $gallery = MediaItem::where('is_active', true)->where('media_type', 'image')->orderBy('sort_order')->get();
        $videos = MediaItem::where('is_active', true)->where('media_type', 'video')->orderBy('sort_order')->get();

        return view('media', compact('news', 'gallery', 'videos'));
    }
}
