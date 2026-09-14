<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Library\LibraryRegistry;
use Illuminate\View\View;

/** Plain-language guide to building and maintaining packages. */
class HelpController extends Controller
{
    public function index(): View
    {
        return view('admin.help', ['types' => LibraryRegistry::all()]);
    }
}
