<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Inquiry;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $offices = Office::where('is_active', true)->orderBy('sort_order')->get();

        return view('contact', compact('offices'));
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        Inquiry::create([
            ...$request->validated(),
            'source_page' => url()->previous(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Thank you for contacting us — we will get back to you shortly.');
    }
}
