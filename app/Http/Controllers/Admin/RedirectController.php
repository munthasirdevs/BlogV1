<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_seo');
    }

    public function index(): View
    {
        $redirects = Redirect::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.seo.redirects', compact('redirects'));
    }

    public function create(): View
    {
        return view('admin.seo.redirects-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'old_url' => ['required', 'string', 'max:500', 'unique:redirects,old_url'],
            'new_url' => ['required', 'string', 'max:500'],
            'redirect_type' => ['required', 'in:301,302'],
        ]);

        Redirect::create($validated);

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect created successfully.');
    }

    public function edit(Redirect $redirect): View
    {
        return view('admin.seo.redirects-edit', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        $validated = $request->validate([
            'old_url' => ['required', 'string', 'max:500', 'unique:redirects,old_url,' . $redirect->id],
            'new_url' => ['required', 'string', 'max:500'],
            'redirect_type' => ['required', 'in:301,302'],
        ]);

        $redirect->update($validated);

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect updated successfully.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();

        return redirect()->route('admin.redirects.index')
            ->with('success', 'Redirect deleted successfully.');
    }
}
