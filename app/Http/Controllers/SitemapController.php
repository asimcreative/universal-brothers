<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $categories = PackageCategory::where('is_active', true)->get();

        $packages = Package::published()
            ->with('category')
            ->orderBy('updated_at', 'desc')
            ->get();

        $pages = Page::where('is_active', true)->orderBy('updated_at', 'desc')->get();

        $news = NewsArticle::where('is_active', true)->orderBy('updated_at', 'desc')->get();

        $xml = view('sitemap', compact('categories', 'packages', 'pages', 'news'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
