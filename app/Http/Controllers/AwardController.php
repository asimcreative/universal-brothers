<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Illuminate\View\View;

class AwardController extends Controller
{
    public function index(): View
    {
        $awards = Award::where('is_active', true)->orderBy('sort_order')->get();

        return view('awards', compact('awards'));
    }
}
