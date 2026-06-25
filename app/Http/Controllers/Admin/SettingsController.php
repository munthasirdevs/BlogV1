<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_settings');
    }

    public function index(): View
    {
        $settings = Setting::orderBy('group_name')
            ->orderBy('key')
            ->get()
            ->groupBy('group_name');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        foreach ($request->except('_token', '_method') as $key => $value) {
            $group = $request->input('group_' . $key, 'general');

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group_name' => $group]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
