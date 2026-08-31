<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\Award;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        if ($page->template === 'about') {
            $awards = Award::where('is_active', true)->orderBy('sort_order')->get();
            $affiliations = Affiliation::where('is_active', true)->orderBy('sort_order')->get();
            $stats = [
                'years' => SiteSetting::get('years_in_operation', '20+'),
                'pilgrims' => SiteSetting::get('pilgrims_served', '10,000+'),
                'awards_count' => SiteSetting::get('industry_awards_count', '20+'),
            ];

            return view('page', compact('page', 'awards', 'affiliations', 'stats'));
        }

        return view('page', compact('page'));
    }
}
