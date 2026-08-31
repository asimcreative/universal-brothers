<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use Illuminate\View\View;

class AffiliationController extends Controller
{
    public function index(): View
    {
        $affiliations = Affiliation::where('is_active', true)->orderBy('sort_order')->get();

        return view('affiliations', compact('affiliations'));
    }
}
