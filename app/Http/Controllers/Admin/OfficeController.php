<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(): View
    {
        return view('admin.offices.index', ['offices' => Office::orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.offices.form', ['office' => new Office]);
    }

    public function store(Request $request): RedirectResponse
    {
        Office::create($this->validated($request));

        return redirect()->route('admin.offices.index')->with('status', 'Office created.');
    }

    public function edit(Office $office): View
    {
        return view('admin.offices.form', compact('office'));
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $office->update($this->validated($request));

        return redirect()->route('admin.offices.index')->with('status', 'Office updated.');
    }

    public function destroy(Office $office): RedirectResponse
    {
        $office->delete();

        return redirect()->route('admin.offices.index')->with('status', 'Office deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone_primary' => ['nullable', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'google_maps_embed' => ['nullable', 'string'],
            'is_domestic' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_domestic'] = $request->boolean('is_domestic');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
