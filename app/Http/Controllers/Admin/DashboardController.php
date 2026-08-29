<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
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
            'inquiries_new' => Inquiry::where('status', 'new')->count(),
            'testimonials_active' => Testimonial::where('is_active', true)->count(),
        ];

        $recentInquiries = Inquiry::with('package')->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentInquiries'));
    }
}
