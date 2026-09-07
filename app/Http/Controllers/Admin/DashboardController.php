<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\MediaItem;
use App\Models\NewsArticle;
use App\Models\Package;
use App\Models\Testimonial;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'packages_published' => Package::published()->count(),
            'packages_draft' => Package::where('status', 'draft')->count(),
            'hajj_packages' => Package::whereHas('category', fn ($q) => $q->where('slug', 'hajj'))->count(),
            'umrah_packages' => Package::whereHas('category', fn ($q) => $q->where('slug', 'umrah'))->count(),
            'tourism_packages' => Package::whereHas('category', fn ($q) => $q->where('slug', 'tourism'))->count(),
            'inquiries_new' => Inquiry::where('status', 'new')->count(),
            'inquiries_total' => Inquiry::count(),
            'testimonials_active' => Testimonial::where('is_active', true)->count(),
            'media_items' => MediaItem::where('is_active', true)->count(),
            'news_published' => NewsArticle::where('is_active', true)->count(),
        ];

        $recentInquiries = Inquiry::with('package')->latest()->limit(6)->get();

        $recentlyUpdatedPackages = Package::with('category')->latest('updated_at')->limit(6)->get();

        $recentNews = NewsArticle::latest('updated_at')->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentInquiries', 'recentlyUpdatedPackages', 'recentNews'));
    }
}
