<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class FaqPageController extends Controller
{
    public function index(): View
    {
        $faqsByCategory = Faq::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('faqs', compact('faqsByCategory'));
    }
}
