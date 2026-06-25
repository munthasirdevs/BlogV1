<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.static.about');
    }

    public function privacy(): View
    {
        return view('pages.static.privacy');
    }

    public function terms(): View
    {
        return view('pages.static.terms');
    }

    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('pages.static.show', compact('page'));
    }
}
